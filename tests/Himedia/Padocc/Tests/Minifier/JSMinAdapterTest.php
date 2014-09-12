<?php

namespace Himedia\Padocc\Tests\Minifier;

use GAubry\Shell\ShellAdapter;
use Himedia\Padocc\Minifier\JSMinAdapter;
use Himedia\Padocc\Tests\PadoccTestCase;
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
 * @author Geoffroy Letournel <gletournel@hi-media.com>
 * @license Apache License, Version 2.0
 */
class JSminAdapterTest extends PadoccTestCase
{

    /**
     * @var ShellAdapter
     */
    private $oShell;

    /**
     * Tableau indexé contenant les commandes Shell de tous les appels effectués à Shell_Adapter::exec().
     * @var array
     * @see shellExecCallback()
     */
    private $aShellExecCmds;

    /**
     * Callback déclenchée sur appel de Shell_Adapter::exec().
     * Log tous les appels dans le tableau indexé $this->aShellExecCmds.
     *
     * @param string $sCmd commande Shell qui aurait dûe être exécutée.
     * @see $aShellExecCmds
     */
    public function shellExecCallback($sCmd)
    {
        $this->aShellExecCmds[] = $sCmd;
    }

    /**
     * Sets up the fixture, for example, open a network connection.
     * This method is called before a test is executed.
     */
    public function setUp()
    {
        $this->aShellExecCmds = array();

        $this->oShell = $this->getMock('\GAubry\Shell\ShellAdapter', array('exec'), array(new NullLogger()));
        $this->oShell->expects($this->any())->method('exec')->will(
            $this->returnCallback(array($this, 'shellExecCallback'))
        );

        $oClass = new \ReflectionClass('\GAubry\Shell\ShellAdapter');
        $oProperty = $oClass->getProperty('_aFileStatus');
        $oProperty->setAccessible(true);
        $oProperty->setValue($this->oShell, array(
            '/path/to/srcdir'  => 2,
            '/path/to/srcfile' => 1
        ));
    }

    /**
     * Tears down the fixture, for example, close a network connection.
     * This method is called after a test is executed.
     */
    public function tearDown()
    {
        $this->oShell = null;
    }

    /**
     * @covers \Himedia\Padocc\Minifier\JSMinAdapter::getContent
     */
    public function testGetContentWithoutFiles()
    {
        $oJSminAdapter = new JSMinAdapter(
            $this->aConfig['jsmin_path'],
            $this->oShell
        );

        $class = new \ReflectionClass($oJSminAdapter);
        $method = $class->getMethod('getContent');
        $method->setAccessible(true);

        $sResult = $method->invokeArgs($oJSminAdapter, array(array()));
        $this->assertEquals('', $sResult);
    }

    /**
     * @covers \Himedia\Padocc\Minifier\JSMinAdapter::getContent
     */
    public function testGetContentThrowExceptionIfNotFound()
    {
        $oJSminAdapter = new JSMinAdapter(
            $this->aConfig['jsmin_path'],
            $this->oShell
        );

        $class = new \ReflectionClass($oJSminAdapter);
        $method = $class->getMethod('getContent');
        $method->setAccessible(true);

        $this->setExpectedException('RuntimeException', "File not found: '/unknow/file'!");
        $method->invokeArgs($oJSminAdapter, array(array('/unknow/file')));
    }

    /**
     * @covers \Himedia\Padocc\Minifier\JSMinAdapter::getContent
     */
    public function testGetContentWith1File()
    {
        $oJSminAdapter = new JSMinAdapter(
            $this->aConfig['jsmin_path'],
            $this->oShell
        );

        $class = new \ReflectionClass($oJSminAdapter);
        $method = $class->getMethod('getContent');
        $method->setAccessible(true);

        $sResult = $method->invokeArgs(
            $oJSminAdapter,
            array(array($this->getTestsDir() . '/resources/minifier/a.txt'))
        );
        $this->assertEquals("a1\na2", $sResult);
    }

    /**
     * @covers \Himedia\Padocc\Minifier\JSMinAdapter::getContent
     */
    public function testGetContentWithFiles()
    {
        $oJSminAdapter = new JSMinAdapter(
            $this->aConfig['jsmin_path'],
            $this->oShell
        );

        $class = new \ReflectionClass($oJSminAdapter);
        $method = $class->getMethod('getContent');
        $method->setAccessible(true);

        $sResult = $method->invokeArgs($oJSminAdapter, array(array(
            $this->getTestsDir() . '/resources/minifier/a.txt',
            $this->getTestsDir() . '/resources/minifier/b.txt'
        )));
        $this->assertEquals("a1\na2b1\nb2", $sResult);
    }

    /**
     * @covers \Himedia\Padocc\Minifier\JSMinAdapter::getContent
     */
    public function testGetContentWithFileWithJoker()
    {
        $oJSminAdapter = new JSMinAdapter(
            '/path/to/jsmin',
            $this->oShell
        );

        $class = new \ReflectionClass($oJSminAdapter);
        $method = $class->getMethod('getContent');
        $method->setAccessible(true);

        $sResult = $method->invokeArgs(
            $oJSminAdapter,
            array(array($this->getTestsDir() . '/resources/minifier/*.txt'))
        );
        $this->assertEquals("a1\na2b1\nb2", $sResult);
    }

    /**
     * @covers \Himedia\Padocc\Minifier\JSMinAdapter::__construct
     * @covers \Himedia\Padocc\Minifier\JSMinAdapter::minifyJS
     * @covers \Himedia\Padocc\Minifier\JSMinAdapter::getHeader
     * @covers \Himedia\Padocc\Minifier\JSMinAdapter::getLargestCommonPrefix
     */
    public function testMinifyJSWith1File()
    {
        $oJSminAdapter = new JSMinAdapter(
            '/path/to/jsmin',
            $this->oShell
        );

        $class = new \ReflectionClass($oJSminAdapter);
        $method = $class->getMethod('minifyJS');
        $method->setAccessible(true);

        $method->invokeArgs($oJSminAdapter, array(array('/path/to/resources/a.txt'), '/dest/path'));
        $this->assertEquals(array(
            'cat "/path/to/resources/a.txt" '
                . "| /path/to/jsmin >'/dest/path'"
                . " && sed --in-place '1i/* Contains: /path/to/resources/a.txt */\n' '/dest/path'"
        ), $this->aShellExecCmds);
    }

    /**
     * @covers \Himedia\Padocc\Minifier\JSMinAdapter::__construct
     * @covers \Himedia\Padocc\Minifier\JSMinAdapter::minifyJS
     * @covers \Himedia\Padocc\Minifier\JSMinAdapter::getHeader
     * @covers \Himedia\Padocc\Minifier\JSMinAdapter::getLargestCommonPrefix
     */
    public function testMinifyJSWithFiles()
    {
        $oJSminAdapter = new JSMinAdapter(
            '/path/to/jsmin',
            $this->oShell
        );

        $class = new \ReflectionClass($oJSminAdapter);
        $method = $class->getMethod('minifyJS');
        $method->setAccessible(true);

        $method->invokeArgs($oJSminAdapter, array(
            array('/path/to/resources/a.txt', '/path/to/resources/b.txt'),
            '/dest/path'
        ));
        $this->assertEquals(array(
            'cat "/path/to/resources/a.txt" "/path/to/resources/b.txt" '
                . "| /path/to/jsmin >'/dest/path'"
                . " && sed --in-place '1i/* Contains (basedir='/path/to/resources/'): a.txt, b.txt */\n' '/dest/path'"
        ), $this->aShellExecCmds);
    }

    /**
     * @covers \Himedia\Padocc\Minifier\JSMinAdapter::__construct
     * @covers \Himedia\Padocc\Minifier\JSMinAdapter::minifyJS
     * @covers \Himedia\Padocc\Minifier\JSMinAdapter::getHeader
     * @covers \Himedia\Padocc\Minifier\JSMinAdapter::getLargestCommonPrefix
     */
    public function testMinifyJSWithJoker()
    {
        $oJSminAdapter = new JSMinAdapter(
            '/path/to/jsmin',
            $this->oShell
        );

        $class = new \ReflectionClass($oJSminAdapter);
        $method = $class->getMethod('minifyJS');
        $method->setAccessible(true);

        $method->invokeArgs($oJSminAdapter, array(array('/path/to/resources/*.txt'), '/dest/path'));
        $this->assertEquals(array(
            'cat "/path/to/resources/"*".txt" '
                . "| /path/to/jsmin >'/dest/path'"
                . " && sed --in-place '1i/* Contains: /path/to/resources/*.txt */\n' '/dest/path'"
        ), $this->aShellExecCmds);
    }

    /**
     * @covers \Himedia\Padocc\Minifier\JSMinAdapter::__construct
     * @covers \Himedia\Padocc\Minifier\JSMinAdapter::minifyCSS
     * @covers \Himedia\Padocc\Minifier\JSMinAdapter::getHeader
     * @covers \Himedia\Padocc\Minifier\JSMinAdapter::getLargestCommonPrefix
     */
    public function testMinifyCSSWith1File()
    {
        $oJSminAdapter = new JSMinAdapter(
            '/path/to/jsmin',
            $this->oShell
        );

        $method = new \ReflectionMethod('\Himedia\Padocc\Minifier\JSMinAdapter', 'minifyCSS');
        $method->setAccessible(true);

        $sTmpPath = tempnam($this->aConfig['dir']['tmp'], 'deploy_unittest_');
        $method->invokeArgs(
            $oJSminAdapter,
            array(array($this->getTestsDir() . '/resources/minifier/a.css'), $sTmpPath)
        );
        $sContent = file_get_contents($sTmpPath);
        unlink($sTmpPath);
        $this->assertContains("/* Contains: /", $sContent);
        $this->assertContains("/tests/resources/minifier/a.css */", $sContent);
        $this->assertContains(
            "\nbody { padding: 0; background-color:#ffffff;"
            . " background:url('http://s0.twenga.com/background.gif') repeat-y 0% 0 fixed;} ",
            $sContent
        );
    }

    /**
     * @covers \Himedia\Padocc\Minifier\JSMinAdapter::__construct
     * @covers \Himedia\Padocc\Minifier\JSMinAdapter::minify
     */
    public function testMinifyThrowExceptionWhenNoSrc()
    {
        $oJSminAdapter = new JSMinAdapter(
            '/path/to/jsmin',
            $this->oShell
        );

        $this->setExpectedException('BadMethodCallException', 'Source files missing!');
        $oJSminAdapter->minify(array(), '/dest/path');
    }

    /**
     * @covers \Himedia\Padocc\Minifier\JSMinAdapter::__construct
     * @covers \Himedia\Padocc\Minifier\JSMinAdapter::minify
     */
    public function testMinifyThrowExceptionWhenDifferentSrcExtensions()
    {
        $oJSminAdapter = new JSMinAdapter(
            '/path/to/jsmin',
            $this->oShell
        );

        $this->setExpectedException('UnexpectedValueException', 'All files must be either JS or CSS: ');
        $oJSminAdapter->minify(array('/path/a.a', '/path/b'), '/dest/path');
    }

    /**
     * @covers \Himedia\Padocc\Minifier\JSMinAdapter::__construct
     * @covers \Himedia\Padocc\Minifier\JSMinAdapter::minify
     */
    public function testMinifyThrowExceptionWhenNoCompatibleDest()
    {
        $oJSminAdapter = new JSMinAdapter(
            '/path/to/jsmin',
            $this->oShell
        );

        $this->setExpectedException('UnexpectedValueException', 'Destination file must be same type of input files: ');
        $oJSminAdapter->minify(array('/path/a.a'), '/dest/path/b');
    }

    /**
     * @covers \Himedia\Padocc\Minifier\JSMinAdapter::__construct
     * @covers \Himedia\Padocc\Minifier\JSMinAdapter::minify
     */
    public function testMinifyThrowExceptionWhenNeitherJSNorCSS()
    {
        $oJSminAdapter = new JSMinAdapter(
            '/path/to/jsmin',
            $this->oShell
        );

        $this->setExpectedException('DomainException', "All specified paths must finish either by '.js' or '.css'");
        $oJSminAdapter->minify(array('/path/a.a'), '/dest/path/b.a');
    }

    /**
     * @covers \Himedia\Padocc\Minifier\JSMinAdapter::__construct
     * @covers \Himedia\Padocc\Minifier\JSMinAdapter::minify
     */
    public function testMinifyWithJS()
    {
        $oMockJSminAdapter = $this->getMock(
            '\Himedia\Padocc\Minifier\JSMinAdapter',
            array('minifyJS'),
            array(
                '/path/to/jsmin',
                $this->oShell
            )
        );

        $oMockJSminAdapter->expects($this->any())->method('minifyJS')
            ->with($this->equalTo(array('/path/a.js')), $this->equalTo('/dest/path/b.js'));
        $oMockJSminAdapter->expects($this->exactly(1))->method('minifyJS');

        $oResult = $oMockJSminAdapter->minify(array('/path/a.js'), '/dest/path/b.js');
        $this->assertEquals($oResult, $oMockJSminAdapter);
    }

    /**
     * @covers \Himedia\Padocc\Minifier\JSMinAdapter::__construct
     * @covers \Himedia\Padocc\Minifier\JSMinAdapter::minify
     */
    public function testMinifyWithCSS()
    {
        $oMockJSminAdapter = $this->getMock(
            '\Himedia\Padocc\Minifier\JSMinAdapter',
            array('minifyCSS'),
            array(
                '/path/to/jsmin',
                $this->oShell
            )
        );

        $oMockJSminAdapter->expects($this->any())->method('minifyCSS')
            ->with($this->equalTo(array('/path/a.css')), $this->equalTo('/dest/path/b.css'));
        $oMockJSminAdapter->expects($this->exactly(1))->method('minifyCSS');

        $oResult = $oMockJSminAdapter->minify(array('/path/a.css'), '/dest/path/b.css');
        $this->assertEquals($oResult, $oMockJSminAdapter);
    }

    /**
     * @covers \Himedia\Padocc\Minifier\JSMinAdapter::getLargestCommonPrefix
     * @dataProvider dataProviderTestGetLargestCommonPrefix
     * @param array $aPaths liste de chaînes à comparer
     * @param string $sExpected le plus long préfixe commun aux chaînes fournies.
     */
    public function testGetLargestCommonPrefix(array $aPaths, $sExpected)
    {
        $oJSminAdapter = new JSMinAdapter(
            '/path/to/jsmin',
            $this->oShell
        );

        $class = new \ReflectionClass($oJSminAdapter);
        $method = $class->getMethod('getLargestCommonPrefix');
        $method->setAccessible(true);

        $sResult = $method->invokeArgs($oJSminAdapter, array($aPaths));
        $this->assertEquals($sExpected, $sResult);
    }

    /**
     * Data provider pour testGetLargestCommonPrefix()
     */
    public static function dataProviderTestGetLargestCommonPrefix()
    {
        return array(
            array(array(''), ''),
            array(array('/path/to/my file'), '/path/to/my file'),
            array(array('/path/to/a', '/path/to/b'), '/path/to/'),
            array(array('/path/to/a', '/other/path/to/b'), '/'),
            array(array('/path/to/a', 'xyz'), ''),
        );
    }
}
