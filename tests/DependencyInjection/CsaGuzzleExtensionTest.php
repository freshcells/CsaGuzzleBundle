<?php

/*
 * This file is part of the CsaGuzzleBundle package
 *
 * (c) Charles Sarrazin <charles@sarraz.in>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code
 */

namespace Csa\Bundle\GuzzleBundle\Tests\DependencyInjection;

use Csa\Bundle\GuzzleBundle\DependencyInjection\CompilerPass\MiddlewarePass;
use Csa\Bundle\GuzzleBundle\DependencyInjection\CsaGuzzleExtension;
use Csa\Bundle\GuzzleBundle\Tests\AutoconfiguredClient;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\MessageFormatter;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Exception\InvalidArgumentException;
use Symfony\Component\Yaml\Parser;

class CsaGuzzleExtensionTest extends TestCase
{
    public function testClientCreated()
    {
        $yaml = <<<'YAML'
profiler:
    enabled: false
clients:
    foo:
        config: { base_url: example.com }
YAML;

        $container = $this->createContainer($yaml);

        $this->assertTrue($container->hasDefinition('csa_guzzle.client.foo'), 'Client must be created.');

        $client = $container->getDefinition('csa_guzzle.client.foo');

        $this->assertEquals(
            [MiddlewarePass::CLIENT_TAG => [[]]],
            $client->getTags(),
            'Clients must be tagged.'
        );

        $this->assertEquals(
            ['base_url' => 'example.com'],
            $client->getArgument(0),
            'Config must be passed to client constructor.'
        );

        $defaultClient = $container->getAlias(ClientInterface::class);
        $this->assertEquals('csa_guzzle.client.foo', $defaultClient);

        $this->assertFalse($client->isLazy());
    }

    public function testDefaultClientNotLazy()
    {
        $yaml = <<<'YAML'
profiler:
    enabled: false
clients:
    foo:
        config: { base_url: example.com }
YAML;

        $container = $this->createContainer($yaml);
        $client    = $container->getDefinition('csa_guzzle.client.foo');

        $this->assertFalse($client->isLazy());
    }

    public function testLazyClient()
    {
        $yaml = <<<'YAML'
profiler:
    enabled: false
clients:
    foo:
        lazy: true
        config: { base_url: example.com }
YAML;

        $container = $this->createContainer($yaml);
        $client    = $container->getDefinition('csa_guzzle.client.foo');

        $this->assertTrue($client->isLazy());
    }

    public function testClientAliasing()
    {
        $yaml = <<<'YAML'
profiler:
    enabled: false
clients:
    foo:
        alias: bar
YAML;

        $container = $this->createContainer($yaml);

        $this->assertTrue($container->hasDefinition('csa_guzzle.client.foo'), 'Client must be created.');
        $this->assertSame($container->findDefinition('bar'), $container->getDefinition('csa_guzzle.client.foo'));
    }

    public function testClientClassOverride()
    {
        $yaml = <<<YAML
clients:
    foo:
        class: AppBundle\Client
YAML;

        $container = $this->createContainer($yaml);

        $client = $container->getDefinition('csa_guzzle.client.foo');

        $this->assertEquals('AppBundle\Client', $client->getClass());
    }

    public function testClientConfigInstanceOverride()
    {
        $yaml = <<<'YAML'
clients:
    foo:
        config:
            handler: my.handler.id
YAML;

        $container = $this->createContainer($yaml);
        $config    = $container->getDefinition('csa_guzzle.client.foo')->getArgument(0);
        $this->assertInstanceOf(
            'Symfony\Component\DependencyInjection\Reference',
            $config['handler']
        );
        $this->assertSame(
            'my.handler.id',
            (string)$config['handler']
        );
    }

    public function testInvalidClientConfig()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Config for "csa_guzzle.client.bar" should be an array, but got string');

        $yaml = <<<'YAML'
clients:
    foo:
        config: ~       # legacy mode
    bar:
        config: invalid # exception
YAML;

        $this->createContainer($yaml);
    }

    public function testMiddlewareAddedToClient()
    {
        $yaml = <<<'YAML'
logger: true
profiler: true
clients:
    foo:
        middleware: [stopwatch, debug]
YAML;

        $container = $this->createContainer($yaml);

        $this->assertTrue($container->hasDefinition('csa_guzzle.client.foo'), 'Client must be created.');

        $client = $container->getDefinition('csa_guzzle.client.foo');

        $this->assertEquals(
            [MiddlewarePass::CLIENT_TAG => [['middleware' => 'stopwatch debug history logger']]],
            $client->getTags(),
            'Only explicitly disabled middleware shouldn\'t be added.'
        );
    }

    public function testCustomMiddlewareAddedToClient()
    {
        $yaml = <<<'YAML'
logger: true
profiler: true
clients:
    foo:
        middleware: [stopwatch, debug, foo]
YAML;

        $container = $this->createContainer($yaml);

        $definition = new Definition();
        $definition->addTag('csa_guzzle.subscriber', ['alias' => 'foo']);
        $container->setDefinition('my.service.foo', $definition);

        $this->assertTrue($container->hasDefinition('csa_guzzle.client.foo'), 'Client must be created.');

        $client = $container->getDefinition('csa_guzzle.client.foo');

        $this->assertEquals(
            [MiddlewarePass::CLIENT_TAG => [['middleware' => 'stopwatch debug foo history logger']]],
            $client->getTags(),
            'Only explicitly disabled middleware shouldn\'t be added.'
        );
    }

    public function testDisableMiddleware()
    {
        $yaml = <<<'YAML'
logger: true
profiler: true
clients:
    foo:
        middleware: ['!stopwatch', '!debug', '!foo']
YAML;

        $container = $this->createContainer($yaml);

        $definition = new Definition();
        $definition->addTag('csa_guzzle.subscriber', ['alias' => 'foo']);
        $container->setDefinition('my.service.foo', $definition);

        $this->assertTrue($container->hasDefinition('csa_guzzle.client.foo'), 'Client must be created.');

        $client = $container->getDefinition('csa_guzzle.client.foo');

        $this->assertEquals(
            [MiddlewarePass::CLIENT_TAG => [['middleware' => '!stopwatch !debug !foo']]],
            $client->getTags(),
            'Only explicitly disabled middleware shouldn\'t be added.'
        );
    }

    public function testLoggerConfiguration()
    {
        $yaml    = <<<'YAML'
logger:
    enabled: true
    service: monolog.logger
    format: %s
YAML;
        $formats = [
            'clf'   => MessageFormatter::CLF,
            'debug' => MessageFormatter::DEBUG,
            'short' => MessageFormatter::SHORT
        ];

        foreach ($formats as $alias => $format) {
            $container = $this->createContainer(sprintf($yaml, $alias));

            $this->assertSame(
                $format,
                $container->getDefinition('csa_guzzle.logger.message_formatter')->getArgument(0)
            );
            $this->assertSame(
                'monolog.logger',
                (string)$container->getDefinition('csa_guzzle.middleware.logger')->getArgument(0)
            );
        }

        $yaml = <<<'YAML'
logger: false
YAML;

        $container = $this->createContainer($yaml);
        $this->assertFalse($container->hasDefinition('csa_guzzle.middleware.logger'));
    }

    public function testCacheConfiguration()
    {
        $yaml = <<<'YAML'
cache: false
YAML;

        $container = $this->createContainer($yaml);
        $this->assertFalse($container->hasDefinition('csa_guzzle.middleware.cache'));

        $yaml = <<<'YAML'
cache:
    enabled: true
    adapter: my.adapter.id
YAML;

        $container = $this->createContainer($yaml);
        $container->setDefinition('my.adapter.id', new Definition());
        $alias = $container->getAlias('csa_guzzle.cache_adapter');
        $this->assertSame('my.adapter.id', (string)$alias);
    }

    public function testMockConfiguration()
    {
        $yaml = <<<'YAML'
mock:
    enabled:      false
    storage_path: ~ # Required
    mode:         replay
YAML;

        $container = $this->createContainer($yaml);
        $this->assertFalse($container->hasDefinition('csa_guzzle.middleware.mock'));

        $yaml = <<<'YAML'
mock:
    storage_path: 'test'
    mode:          replay
    request_headers_blacklist: ['X-Guzzle-Cache']
    response_headers_blacklist: ['X-Guzzle-Cache']
YAML;

        $container = $this->createContainer($yaml);
        $this->assertTrue($container->hasDefinition('csa_guzzle.middleware.mock'));
        $storage = $container->getDefinition('csa_guzzle.mock.storage');

        $this->assertContains('X-Guzzle-Cache', $storage->getArgument(1));
    }

    public function testAutoconfigurationDoesNotRegistersServiceWhenDisabledByDefault()
    {
        $services = [
            AutoconfiguredClient::class => AutoconfiguredClient::class,
        ];

        $container = $this->createContainer('', $services);

        if (!method_exists($container, 'registerForAutoconfiguration')) {
            $this->markTestSkipped('Not supported for this symfony version');
        }

        $container->compile();

        $this->assertTrue($container->hasDefinition(AutoconfiguredClient::class));
        $actualDefinition = $container->getDefinition(AutoconfiguredClient::class);
        $this->assertFalse($actualDefinition->hasTag('csa_guzzle.client'));
    }

    public function testAutoconfigurationRegistersServiceWhenEnabled()
    {
        $services = [
            AutoconfiguredClient::class => AutoconfiguredClient::class,
        ];

        $yaml = <<<'YAML'
autoconfigure: true
YAML;

        $container = $this->createContainer($yaml, $services);

        if (!method_exists($container, 'registerForAutoconfiguration')) {
            $this->markTestSkipped('Not supported for this symfony version');
        }

        $container->compile();

        $this->assertTrue($container->hasDefinition(AutoconfiguredClient::class));
        $actualDefinition = $container->getDefinition(AutoconfiguredClient::class);
        $this->assertTrue($actualDefinition->hasTag('csa_guzzle.client'));
    }

    private function createContainer($yaml, array $services = [])
    {
        $parser    = new Parser();
        $container = new ContainerBuilder();

        foreach ($services as $serviceId => $serviceClass) {
            $definition = new Definition($serviceClass);
            if (method_exists($definition, 'setAutoconfigured')) {
                $definition->setAutoconfigured(true);
            }
            $definition->setPublic(true);

            $container->setDefinition($serviceId, $definition);
        }

        $loader = new CsaGuzzleExtension();
        $loader->load([$parser->parse($yaml)], $container);

        return $container;
    }
}
