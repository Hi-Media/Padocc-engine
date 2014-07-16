#!/usr/bin/env bash

DIR=$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )
$DIR/../vendor/bin/phpunit -c conf/phpunit.xml
