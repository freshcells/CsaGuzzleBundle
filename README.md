CsaGuzzleBundle
===============

Forked from https://github.com/csarrazi/CsaGuzzleBundle

Description
-----------

This bundle integrates Guzzle >=4.0 in Symfony.
Versions `2.x` and `3.x` support Guzzle 6 and versions `1.x` support Guzzle 4 and 5.

Currently, the bundle supports the following features:

* Integration with Symfony's debug tools (web debug toolbar, profiler, logger, timeline, ...)
* Configuring a Guzzle client simply using configuration
* Multiple middleware / subscribers (logger, profiler, timeline, cache, mock, and more to come)
* Service descriptions to describe your services in json format (only in the 1.3 branch, though)

![Web debug Toolbar](https://cloud.githubusercontent.com/assets/4208880/12815019/c49faeec-cb43-11e5-9de9-dc3423ea6c35.jpg)
![Profiler panel integration](https://cloud.githubusercontent.com/assets/4208880/12815021/c4a16746-cb43-11e5-9061-f1ea15b04e62.jpg)
![Profiler timeline integration](https://cloud.githubusercontent.com/assets/4208880/12815020/c49fc7ec-cb43-11e5-89c3-93ee82711dc2.jpg)

Installation
------------

All the installation instructions are located in the documentation

Upgrade
-------

Although I try to guarantee forward-compatibility of the bundle with previous versions.
Here are the upgrade notes between each version.

See [Upgrade.md](UPGRADE.md).

Support
-------

As Guzzle 4 and Guzzle 5 are no longer supported by its creator, you should aim to migrate to Guzzle 6.x as soon as
possible. Versions `1.x` of this bundle are no longer supported, and version `2.x` of will be supported until Symfony
2.8 EOL (November 2018).

Documentation
-------------

### Documentation for stable (3.x)

* [Installation](../master/src/Resources/doc/install.md)
* [Creating clients](../master/src/Resources/doc/clients.md)
* [Registering new middleware](../master/src/Resources/doc/middleware.md)
* [Available middleware](../master/src/Resources/doc/available_middleware.md)
* [Streaming a guzzle response](../master/src/Resources/doc/response_streaming.md)
* [Configuration reference](../master/src/Resources/doc/configuration_reference.md)

### Documentation for legacy (2.x)

* [Installation](../2.x/src/Resources/doc/install.md)
* [Creating clients](../2.x/src/Resources/doc/clients.md)
* [Registering new middleware](../2.x/src/Resources/doc/middleware.md)
* [Available middleware](../2.x/src/Resources/doc/available_middleware.md)
* [Streaming a guzzle response](../2.x/src/Resources/doc/response_streaming.md)
* [Configuration reference](../2.x/src/Resources/doc/configuration_reference.md)

Contributing
------------

CsaGuzzleBundle is an open source project. If you'd like to contribute, please read
the [Contributing Guidelines](CONTRIBUTING.md).

License
-------

This library is under Apache License 2.0. For the full copyright and license
information, please view the [LICENSE](src/Resources/meta/LICENSE) file that was
distributed with this source code.
