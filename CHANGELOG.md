Change log
==========

## Version 1.6.0 (2014-04-15)

Features:

  - [#10](https://github.com/geoffroy-aubry/Supervisor/issues/10): Add compatibility with FreeBSD/OS X.

## Version 1.5.0 (2014-04-11)

Features:

  - [#9](https://github.com/geoffroy-aubry/Supervisor/issues/9): Remove dependency to readlink because not portable to OS-X.

## Version 1.4.0 (2014-04-07)

Features:

  - [#8](https://github.com/geoffroy-aubry/Supervisor/issues/8): Allow to force supervisor execution_id.
  - [#7](https://github.com/geoffroy-aubry/Supervisor/issues/7): Add proper signal handling and interrupts.

## Version 1.3.1 (2013-11-11)

Features:

  - Add protection against whitespaces in customized named parameters.
  - Escape backslashes on stdout and into both error and warning mails.

## Version 1.3.0 (2013-10-28)

Features:

  - [#5](https://github.com/geoffroy-aubry/Supervisor/issues/5): Allow to add recipients through CLI,
    and add them to those defined in config file.

Doc:

  - [#6](https://github.com/geoffroy-aubry/Supervisor/issues/6): Document the configuration file

## Version 1.2.0 (2013-10-25)

Features:

  - [#4](https://github.com/geoffroy-aubry/Supervisor/issues/4): Handle CSV output of supervised scripts

Unit tests:

  - estimated code coverage: 88% (530 of 601 lines).

## Version 1.1.0 (2013-10-18)

UI:

  - [#2](https://github.com/geoffroy-aubry/Supervisor/issues/2): Add attractive HTML emails

Doc:

  - [#3](https://github.com/geoffroy-aubry/Supervisor/issues/3): Document warning tags in README.md

Unit tests:

  - estimated code coverage: 88% (503 of 572 lines).

## Version 1.0.1 (2013-10-10)

Fixes:

  - [#1](https://github.com/geoffroy-aubry/Supervisor/issues/1) Warning tags not recognized with an utf-8 message

## Version 1.0.0 (2013-10-08)

First release on Github.

Unit tests:

  - estimated code coverage: 90% (453 of 504 lines).
