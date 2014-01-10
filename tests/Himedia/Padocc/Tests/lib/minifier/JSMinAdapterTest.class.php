<?php

/**
 * @category TwengaDeploy
 * @package Tests
 * @author Geoffroy AUBRY <geoffroy.aubry@twenga.com>
 */
class JSminAdapterTest extends PHPUnit_Framework_TestCase
{

    /**
     * Collection de services.
     * @var ServiceContainer
     */
    private $oServiceContainer;

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
   public function shellExecCallback ($sCmd)
    {
        $this->aShellExecCmds[] = $sCmd;
        return array();
    }

    /**
     * Sets up the fixture, for example, open a network connection.
     * This method is called before a test is executed.
     */
    public function setUp ()
    {
        $oBaseLogger = new Logger_Adapter(Logger_Interface::WARNING);
        $oLogger = new Logger_IndentedDecorator($oBaseLogger, '   ');

        $oMockShell = $this->getMock('Shell_Adapter', array('exec'), array($oLogger));
        $oMockShell->expects($this->any())->method('exec')->will(
            $this->returnCallback(array($this, 'shellExecCallback'))
        );
        $this->aShellExecCmds = array();

        //$oShell = new Shell_Adapter($oLogger);
        $oClass = new ReflectionClass('Shell_Adapter');
        $oProperty = $oClass->getProperty('_aFileStatus');
        $oProperty->setAccessible(true);
        $oProperty->setValue($oMockShell, array(
            '/path/to/srcdir' => 2,
            '/path/to/srcfile' => 1
        ));

        //$oShell = new Shell_Adapter($oLogger);
        $oProperties = new Properties_Adapter($oMockShell);
        $oNumbering = new Numbering_Adapter();

        $this->oServiceContainer = new ServiceContainer();
        $this->oServiceContainer
            ->setLogAdapter($oLogger)
            ->setPropertiesAdapter($oProperties)
            ->setShellAdapter($oMockShell)
            ->setNumberingAdapter($oNumbering);
    }

    /**
     * Tears down the fixture, for example, close a network connection.
     * This method is called after a test is executed.
     */
    public function tearDown()
    {
        $this->oServiceContainer = NULL;
    }

    /**
     * @covers Minifier_JSMinAdapter::_getContent
     */
    public function testGetContent_WithoutFiles ()
    {
        $oJSminAdapter = new Minifier_JSMinAdapter(
            DEPLOYMENT_JSMIN_BIN_PATH,
            $this->oServiceContainer->getShellAdapter()
        );

        $class = new ReflectionClass($oJSminAdapter);
        $method = $class->getMethod('_getContent');
        $method->setAccessible(true);

        $sResult = $method->invokeArgs($oJSminAdapter, array(array()));
        $this->assertEquals('', $sResult);
    }

    /**
     * @covers Minifier_JSMinAdapter::_getContent
     */
    public function testGetContent_throwExceptionIfNotFound ()
    {
        $oJSminAdapter = new Minifier_JSMinAdapter(
            DEPLOYMENT_JSMIN_BIN_PATH,
            $this->oServiceContainer->getShellAdapter()
        );

        $class = new ReflectionClass($oJSminAdapter);
        $method = $class->getMethod('_getContent');
        $method->setAccessible(true);

        $this->setExpectedException('RuntimeException', "File not found: '/unknow/file'!");
        $sResult = $method->invokeArgs($oJSminAdapter, array(array('/unknow/file')));
    }

    /**
     * @covers Minifier_JSMinAdapter::_getContent
     */
    public function testGetContent_With1File ()
    {
        $oJSminAdapter = new Minifier_JSMinAdapter(
            DEPLOYMENT_JSMIN_BIN_PATH,
            $this->oServiceContainer->getShellAdapter()
        );

        $class = new ReflectionClass($oJSminAdapter);
        $method = $class->getMethod('_getContent');
        $method->setAccessible(true);

        $sResult = $method->invokeArgs($oJSminAdapter, array(array(__DIR__ . '/resources/a.txt')));
        $this->assertEquals("a1\na2", $sResult);
    }

    /**
     * @covers Minifier_JSMinAdapter::_getContent
     */
    public function testGetContent_WithFiles ()
    {
        $oJSminAdapter = new Minifier_JSMinAdapter(
            DEPLOYMENT_JSMIN_BIN_PATH,
            $this->oServiceContainer->getShellAdapter()
        );

        $class = new ReflectionClass($oJSminAdapter);
        $method = $class->getMethod('_getContent');
        $method->setAccessible(true);

        $sResult = $method->invokeArgs($oJSminAdapter, array(array(
            __DIR__ . '/resources/a.txt',
            __DIR__ . '/resources/b.txt'
        )));
        $this->assertEquals("a1\na2b1\nb2", $sResult);
    }

    /**
     * @covers Minifier_JSMinAdapter::_getContent
     */
    public function testGetContent_WithFileWithJoker ()
    {
        $oJSminAdapter = new Minifier_JSMinAdapter(
            '/path/to/jsmin',
            $this->oServiceContainer->getShellAdapter()
        );

        $class = new ReflectionClass($oJSminAdapter);
        $method = $class->getMethod('_getContent');
        $method->setAccessible(true);

        $sResult = $method->invokeArgs($oJSminAdapter, array(array(__DIR__ . '/resources/*.txt')));
        $this->assertEquals("a1\na2b1\nb2", $sResult);
    }

    /**
     * @covers Minifier_JSMinAdapter::__construct
     * @covers Minifier_JSMinAdapter::_minifyJS
     * @covers Minifier_JSMinAdapter::_getHeader
     * @covers Minifier_JSMinAdapter::_getLargestCommonPrefix
     */
    public function testMinifyJS_With1File ()
    {
        $oJSminAdapter = new Minifier_JSMinAdapter(
            '/path/to/jsmin',
            $this->oServiceContainer->getShellAdapter()
        );

        $class = new ReflectionClass($oJSminAdapter);
        $method = $class->getMethod('_minifyJS');
        $method->setAccessible(true);

        $method->invokeArgs($oJSminAdapter, array(array('/path/to/resources/a.txt'), '/dest/path'));
        $this->assertEquals(array(
            'cat "/path/to/resources/a.txt" '
                . "| /path/to/jsmin >'/dest/path'"
                . " && sed --in-place '1i/* Contains: /path/to/resources/a.txt */\n' '/dest/path'"
        ), $this->aShellExecCmds);
    }

    /**
     * @covers Minifier_JSMinAdapter::__construct
     * @covers Minifier_JSMinAdapter::_minifyJS
     * @covers Minifier_JSMinAdapter::_getHeader
     * @covers Minifier_JSMinAdapter::_getLargestCommonPrefix
     */
    public function testMinifyJS_WithFiles ()
    {
        $oJSminAdapter = new Minifier_JSMinAdapter(
            '/path/to/jsmin',
            $this->oServiceContainer->getShellAdapter()
        );

        $class = new ReflectionClass($oJSminAdapter);
        $method = $class->getMethod('_minifyJS');
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
     * @covers Minifier_JSMinAdapter::__construct
     * @covers Minifier_JSMinAdapter::_minifyJS
     * @covers Minifier_JSMinAdapter::_getHeader
     * @covers Minifier_JSMinAdapter::_getLargestCommonPrefix
     */
    public function testMinifyJS_WithJoker ()
    {
        $oJSminAdapter = new Minifier_JSMinAdapter(
            '/path/to/jsmin',
            $this->oServiceContainer->getShellAdapter()
        );

        $class = new ReflectionClass($oJSminAdapter);
        $method = $class->getMethod('_minifyJS');
        $method->setAccessible(true);

        $method->invokeArgs($oJSminAdapter, array(array('/path/to/resources/*.txt'), '/dest/path'));
        $this->assertEquals(array(
            'cat "/path/to/resources/"*".txt" '
                . "| /path/to/jsmin >'/dest/path'"
                . " && sed --in-place '1i/* Contains: /path/to/resources/*.txt */\n' '/dest/path'"
        ), $this->aShellExecCmds);
    }

    /**
     * @covers Minifier_JSMinAdapter::__construct
     * @covers Minifier_JSMinAdapter::_minifyCSS
     * @covers Minifier_JSMinAdapter::_getHeader
     * @covers Minifier_JSMinAdapter::_getLargestCommonPrefix
     */
    public function testMinifyCSS_With1File ()
    {
        $oJSminAdapter = new Minifier_JSMinAdapter(
            '/path/to/jsmin',
            $this->oServiceContainer->getShellAdapter()
        );

        $method = new ReflectionMethod('Minifier_JSMinAdapter', '_minifyCSS');
        $method->setAccessible(true);

        $sTmpPath = tempnam(DEPLOYMENT_TMP_DIR, 'deploy_unittest_');
        $method->invokeArgs($oJSminAdapter, array(array(__DIR__ . '/resources/a.css'), $sTmpPath));
        $sContent = file_get_contents($sTmpPath);
        unlink($sTmpPath);
        $this->assertContains("/* Contains: /", $sContent);
        $this->assertContains("/tests/lib/minifier/resources/a.css */", $sContent);
        $this->assertContains(
            "\nbody { padding: 0; background-color:#ffffff;"
                . " background:url('http://s0.twenga.com/background.gif') repeat-y 0% 0 fixed;} ",
            $sContent
        );
    }

    /**
     * @covers Minifier_JSMinAdapter::__construct
     * @covers Minifier_JSMinAdapter::minify
     */
    public function testMinify_throwExceptionWhenNoSrc ()
    {
        $oJSminAdapter = new Minifier_JSMinAdapter(
            '/path/to/jsmin',
            $this->oServiceContainer->getShellAdapter()
        );

        $this->setExpectedException('BadMethodCallException', 'Source files missing!');
        $oJSminAdapter->minify(array(), '/dest/path');
    }

    /**
     * @covers Minifier_JSMinAdapter::__construct
     * @covers Minifier_JSMinAdapter::minify
     */
    public function testMinify_throwExceptionWhenDifferentSrcExtensions ()
    {
        $oJSminAdapter = new Minifier_JSMinAdapter(
            '/path/to/jsmin',
            $this->oServiceContainer->getShellAdapter()
        );

        $this->setExpectedException('UnexpectedValueException', 'All files must be either JS or CSS: ');
        $oJSminAdapter->minify(array('/path/a.a', '/path/b'), '/dest/path');
    }

    /**
     * @covers Minifier_JSMinAdapter::__construct
     * @covers Minifier_JSMinAdapter::minify
     */
    public function testMinify_throwExceptionWhenNoCompatibleDest ()
    {
        $oJSminAdapter = new Minifier_JSMinAdapter(
            '/path/to/jsmin',
            $this->oServiceContainer->getShellAdapter()
        );

        $this->setExpectedException('UnexpectedValueException', 'Destination file must be same type of input files: ');
        $oJSminAdapter->minify(array('/path/a.a'), '/dest/path/b');
    }

    /**
     * @covers Minifier_JSMinAdapter::__construct
     * @covers Minifier_JSMinAdapter::minify
     */
    public function testMinify_throwExceptionWhenNeitherJSNorCSS ()
    {
        $oJSminAdapter = new Minifier_JSMinAdapter(
            '/path/to/jsmin',
            $this->oServiceContainer->getShellAdapter()
        );

        $this->setExpectedException('DomainException', "All specified paths must finish either by '.js' or '.css'");
        $oJSminAdapter->minify(array('/path/a.a'), '/dest/path/b.a');
    }

    /**
     * @covers Minifier_JSMinAdapter::__construct
     * @covers Minifier_JSMinAdapter::minify
     */
    public function testMinify_WithJS ()
    {
        $oMockJSminAdapter = $this->getMock(
            'Minifier_JSMinAdapter',
            array('_minifyJS'),
            array(
                '/path/to/jsmin',
                $this->oServiceContainer->getShellAdapter()
            )
        );

        $oMockJSminAdapter->expects($this->any())->method('_minifyJS')
            ->with($this->equalTo(array('/path/a.js')), $this->equalTo('/dest/path/b.js'));
        $oMockJSminAdapter->expects($this->exactly(1))->method('_minifyJS');

        $oResult = $oMockJSminAdapter->minify(array('/path/a.js'), '/dest/path/b.js');
        $this->assertEquals($oResult, $oMockJSminAdapter);
    }

    /**
     * @covers Minifier_JSMinAdapter::__construct
     * @covers Minifier_JSMinAdapter::minify
     */
    public function testMinify_WithCSS ()
    {
        $oMockJSminAdapter = $this->getMock(
            'Minifier_JSMinAdapter',
            array('_minifyCSS'),
            array(
                '/path/to/jsmin',
                $this->oServiceContainer->getShellAdapter()
            )
        );

        $oMockJSminAdapter->expects($this->any())->method('_minifyCSS')
            ->with($this->equalTo(array('/path/a.css')), $this->equalTo('/dest/path/b.css'));
        $oMockJSminAdapter->expects($this->exactly(1))->method('_minifyCSS');

        $oResult = $oMockJSminAdapter->minify(array('/path/a.css'), '/dest/path/b.css');
        $this->assertEquals($oResult, $oMockJSminAdapter);
    }

    /**
     * @covers Minifier_JSMinAdapter::_getLargestCommonPrefix
     * @dataProvider dataProvider_testGetLargestCommonPrefix
     * @param array $aPaths liste de chaînes à comparer
     * @param string $sExpected le plus long préfixe commun aux chaînes fournies.
     */
    public function testGetLargestCommonPrefix (array $aPaths, $sExpected)
    {
        $oJSminAdapter = new Minifier_JSMinAdapter(
            '/path/to/jsmin',
            $this->oServiceContainer->getShellAdapter()
        );

        $class = new ReflectionClass($oJSminAdapter);
        $method = $class->getMethod('_getLargestCommonPrefix');
        $method->setAccessible(true);

        $sResult = $method->invokeArgs($oJSminAdapter, array($aPaths));
        $this->assertEquals($sExpected, $sResult);
    }

    /**
     * Data provider pour testGetLargestCommonPrefix()
     */
    public static function dataProvider_testGetLargestCommonPrefix ()
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
