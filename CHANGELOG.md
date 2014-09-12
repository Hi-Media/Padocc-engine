Change log
==========

## Version 2.1.0 (2014-09-12)

Features:

  - [#2](https://github.com/Hi-Media/Padocc-engine/issues/2): Add task for the [Bower package manager](http://bower.io/).
  - [#1](https://github.com/Hi-Media/Padocc-engine/issues/1): Add task for the
    [Symfony2 console component](http://symfony.com/doc/current/components/console/introduction.html).

## Version 2.0.2 (2014-08-26)

Fixes:

  - Fixes the `git checkout --quiet -fb` issue when checking out a tag reference in the bash script `gitexport.sh`.
  - Removing the non-standard use of `\s` in sed expressions for compatibility purposes on MacOs X and FreeBSD systems.
  - Changes the way of checking `composer` installation in the Composer task because of a non-expected behavior on MacOs X.

## Version 2.0.1 (2014-07-16)

Doc:

  - Add both versioneye (dependency status) and license badges in documentation.
  - Add authors section in `composer.json`.

## Version 2.0.0 (2014-07-15)

First release on Github.
