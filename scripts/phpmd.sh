#!/usr/bin/env bash

DIR=$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )
$DIR/../vendor/bin/phpmd src text codesize,design,unusedcode,naming,controversial
