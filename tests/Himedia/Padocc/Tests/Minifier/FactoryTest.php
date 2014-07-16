<?php

namespace Himedia\Padocc\Tests\Minifier;

use GAubry\Shell\ShellAdapter;
use Himedia\Padocc\Minifier\Factory;
use Psr\Log\NullLogger;

/**
 * Copyright (c) 2014 HiMedia Group
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 *
 * @copyright 2014 HiMedia Group
 * @author Geoffroy Aubry <gaubry@hi-media.com>
 * @license Apache License, Version 2.0
 */
class FactoryTest extends \PHPUnit_Framework_TestCase
{

    /**
     * @covers \Himedia\Padocc\Minifier\Factory::getInstance
     */
    public function testGetInstanceThrowExceptionWhenUnknownType ()
    {
        $oShell = new ShellAdapter(new NullLogger());
        $this->setExpectedException('BadMethodCallException', "Unknown type: '-1'!");
        Factory::getInstance(-1, $oShell);
    }

    /**
     * @covers \Himedia\Padocc\Minifier\Factory::getInstance
     */
    public function testGetInstanceJSMin ()
    {
        $oShell = new ShellAdapter(new NullLogger());
        $oMinifier = Factory::getInstance(Factory::TYPE_JSMIN, $oShell);
        $this->assertInstanceOf('Himedia\Padocc\Minifier\MinifierInterface', $oMinifier);
    }
}
