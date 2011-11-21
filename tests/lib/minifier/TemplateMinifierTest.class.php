<?php

/**
 * @category TwengaDeploy
 * @package Tests
 * @author Geoffroy AUBRY <geoffroy.aubry@twenga.com>
 */
class TemplateMinifierTest extends PHPUnit_Framework_TestCase
{

    /**
     * Collection de services.
     * @var ServiceContainer
     */
    private $oServiceContainer;

    /**
     * Instance Minifier
     * @var Minifier_Interface
     */
    private $oJSminAdapter;

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
        /*$oClass = new ReflectionClass('Shell_Adapter');
        $oProperty = $oClass->getProperty('_aFileStatus');
        $oProperty->setAccessible(true);
        $oProperty->setValue($oMockShell, array(
            '/path/to/srcdir' => 2,
            '/path/to/srcfile' => 1
        ));*/

        //$oShell = new Shell_Adapter($oLogger);
        $oProperties = new Properties_Adapter($oMockShell);
        $oNumbering = new Numbering_Adapter();

        $this->oServiceContainer = new ServiceContainer();
        $this->oServiceContainer
            ->setLogAdapter($oLogger)
            ->setPropertiesAdapter($oProperties)
            ->setShellAdapter($oMockShell)
            ->setNumberingAdapter($oNumbering);

        $this->oJSminAdapter = new Minifier_JSMinAdapter(
            DEPLOYMENT_JSMIN_BIN_PATH,
            $this->oServiceContainer->getShellAdapter()
        );
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
     * @covers Minifier_TemplateMinifier::__construct
     * @covers Minifier_TemplateMinifier::_minifyJS
     * @covers Minifier_TemplateMinifier::_generateSubdomainsFiles
     * @covers Minifier_TemplateMinifier::_getHash
     */
    public function testMinifyJS_throwExceptionIfSubdomainCopyFailed ()
    {
        $sHash = '012345678';
        $oTplMinifier = $this->getMock(
            'Minifier_TemplateMinifier',
            array('_getHash'),
            array($this->oJSminAdapter, $this->oServiceContainer->getLogAdapter())
        );
        $oTplMinifier->expects($this->any())->method('_getHash')->will($this->returnValue($sHash));
        $oTplMinifier->expects($this->exactly(1))->method('_getHash');

        //$oTplMinifier = new Minifier_TemplateMinifier($this->oJSminAdapter, $this->oServiceContainer->getLogAdapter());
        $class = new ReflectionClass($oTplMinifier);
        $method = $class->getMethod('_minifyJS');
        $method->setAccessible(true);

        $sTmp = DEPLOYMENT_TMP_DIR;
        $sJSMinPath = DEPLOYMENT_JSMIN_BIN_PATH;
        $sFirstPath = __DIR__ . '/resources/a.js';	// size=343 octets
        $aPaths = array($sFirstPath);
        $sJSParentDir = '';
        $sDestDir = $sTmp;
        $sImgOutPath = '';

        $this->setExpectedException(
            'RuntimeException',
            "Copy from '$sTmp/tmp_$sHash.js' to '$sTmp/$sHash.js' failed!"
        );
        $method->invokeArgs($oTplMinifier, array($aPaths, $sJSParentDir, $sDestDir, $sImgOutPath));
    }

    /**
     * @covers Minifier_TemplateMinifier::__construct
     * @covers Minifier_TemplateMinifier::_minifyJS
     * @covers Minifier_TemplateMinifier::_generateSubdomainsFiles
     * @covers Minifier_TemplateMinifier::_getHash
     */
    public function testMinifyJS_with1SimpleFile ()
    {
        $sHash = '012345678';
        $oTplMinifier = $this->getMock(
            'Minifier_TemplateMinifier',
            array('_getHash'),
            array($this->oJSminAdapter, $this->oServiceContainer->getLogAdapter())
        );
        $oTplMinifier->expects($this->any())->method('_getHash')->will($this->returnValue($sHash));
        $oTplMinifier->expects($this->exactly(1))->method('_getHash');

        //$oTplMinifier = new Minifier_TemplateMinifier($this->oJSminAdapter, $this->oServiceContainer->getLogAdapter());
        $class = new ReflectionClass($oTplMinifier);
        $method = $class->getMethod('_minifyJS');
        $method->setAccessible(true);

        $sTmp = DEPLOYMENT_TMP_DIR;
        $sJSMinPath = DEPLOYMENT_JSMIN_BIN_PATH;
        $sFirstPath = __DIR__ . '/resources/a.js';	// size=343 octets
        $aPaths = array($sFirstPath);
        $sJSParentDir = '';
        $sDestDir = $sTmp;
        $sImgOutPath = '';

        copy($sFirstPath, $sTmp . "/tmp_$sHash.js");
        @unlink("$sTmp/$sHash.js");
        @unlink("$sTmp/c$sHash.js");
        @unlink("$sTmp/cn$sHash.js");
        @unlink("$sTmp/cs$sHash.js");

        $iFilesSize = $method->invokeArgs($oTplMinifier, array($aPaths, $sJSParentDir, $sDestDir, $sImgOutPath));
        $this->assertEquals(
            array("cat \"$sFirstPath\" | $sJSMinPath >'$sTmp/tmp_$sHash.js' && sed --in-place '1i/* Contains: $sFirstPath */\n' '$sTmp/tmp_$sHash.js'"),
            $this->aShellExecCmds
        );
        $this->assertFileEquals($sFirstPath, "$sTmp/$sHash.js");
        $this->assertFileEquals($sFirstPath, "$sTmp/c$sHash.js");
        $this->assertFileEquals($sFirstPath, "$sTmp/cn$sHash.js");
        $this->assertEquals(343*4, $iFilesSize);
        $this->assertFileNotExists("$sTmp/tmp_$sHash.js");

        @unlink("$sTmp/$sHash.js");
        @unlink("$sTmp/c$sHash.js");
        @unlink("$sTmp/cn$sHash.js");
        @unlink("$sTmp/cs$sHash.js");
    }

    /**
     * @covers Minifier_TemplateMinifier::_minifyJS
     */
    public function testMinifyJS_throwExceptionWhenBadFileExtension ()
    {
        $oTplMinifier = new Minifier_TemplateMinifier($this->oJSminAdapter, $this->oServiceContainer->getLogAdapter());
        $class = new ReflectionClass($oTplMinifier);
        $method = $class->getMethod('_minifyJS');
        $method->setAccessible(true);

        $sTmp = DEPLOYMENT_TMP_DIR;
        $sJSMinPath = DEPLOYMENT_JSMIN_BIN_PATH;
        $sFirstPath = '/not/found.xyz';
        $aPaths = array($sFirstPath);
        $sJSParentDir = '';
        $sDestDir = $sTmp;
        $sImgOutPath = '';

        $this->setExpectedException('UnexpectedValueException', 'Destination file must be same type of input files');
        $method->invokeArgs($oTplMinifier, array($aPaths, $sJSParentDir, $sDestDir, $sImgOutPath));
    }

    /**
     * @covers Minifier_TemplateMinifier::__construct
     * @covers Minifier_TemplateMinifier::_minifyJS
     * @covers Minifier_TemplateMinifier::_generateSubdomainsFiles
     * @covers Minifier_TemplateMinifier::_getHash
     */
    public function testMinifyJS_with1SimpleFileAnd1AlreadyExists ()
    {
        $sHash = '012345678';
        $oTplMinifier = $this->getMock(
            'Minifier_TemplateMinifier',
            array('_getHash'),
            array($this->oJSminAdapter, $this->oServiceContainer->getLogAdapter())
        );
        $oTplMinifier->expects($this->any())->method('_getHash')->will($this->returnValue($sHash));
        $oTplMinifier->expects($this->exactly(1))->method('_getHash');

        //$oTplMinifier = new Minifier_TemplateMinifier($this->oJSminAdapter, $this->oServiceContainer->getLogAdapter());
        $class = new ReflectionClass($oTplMinifier);
        $method = $class->getMethod('_minifyJS');
        $method->setAccessible(true);

        $sTmp = DEPLOYMENT_TMP_DIR;
        $sJSMinPath = DEPLOYMENT_JSMIN_BIN_PATH;
        $sFirstPath = __DIR__ . '/resources/a.js';	// size=343 octets
        $aPaths = array($sFirstPath);
        $sJSParentDir = '';
        $sDestDir = $sTmp;
        $sImgOutPath = '';

        copy($sFirstPath, "$sTmp/tmp_$sHash.js");
        touch("$sTmp/$sHash.js");
        @unlink("$sTmp/c$sHash.js");
        @unlink("$sTmp/cn$sHash.js");
        @unlink("$sTmp/cs$sHash.js");

        $iFilesSize = $method->invokeArgs($oTplMinifier, array($aPaths, $sJSParentDir, $sDestDir, $sImgOutPath));
        $this->assertEquals(
            array("cat \"$sFirstPath\" | $sJSMinPath >'$sTmp/tmp_$sHash.js' && sed --in-place '1i/* Contains: $sFirstPath */\n' '$sTmp/tmp_$sHash.js'"),
            $this->aShellExecCmds
        );
        $this->assertFileExists("$sTmp/$sHash.js");
        $this->assertEquals('', file_get_contents("$sTmp/$sHash.js"));
        $this->assertFileEquals($sFirstPath, "$sTmp/c$sHash.js");
        $this->assertFileEquals($sFirstPath, "$sTmp/cn$sHash.js");
        $this->assertEquals(343*3, $iFilesSize);
        $this->assertFileNotExists("$sTmp/tmp_$sHash.js");

        @unlink("$sTmp/$sHash.js");
        @unlink("$sTmp/c$sHash.js");
        @unlink("$sTmp/cn$sHash.js");
        @unlink("$sTmp/cs$sHash.js");
    }

    /**
     * @covers Minifier_TemplateMinifier::_minifyCSS
     */
    public function testMinifyCSS_with1SimpleFile ()
    {
        $sHash = '012345678';
        $oTplMinifier = $this->getMock(
            'Minifier_TemplateMinifier',
            array('_getHash'),
            array($this->oJSminAdapter, $this->oServiceContainer->getLogAdapter())
        );
        $oTplMinifier->expects($this->any())->method('_getHash')->will($this->returnValue($sHash));
        $oTplMinifier->expects($this->exactly(1))->method('_getHash');

        //$oTplMinifier = new Minifier_TemplateMinifier($this->oJSminAdapter, $this->oServiceContainer->getLogAdapter());
        $class = new ReflectionClass($oTplMinifier);
        $method = $class->getMethod('_minifyCSS');
        $method->setAccessible(true);

        $sTmp = DEPLOYMENT_TMP_DIR;
        $sFirstPath = __DIR__ . '/resources/a.css';	// size=138+strlen($sFirstPath) octets une fois minifié
        $aPaths = array($sFirstPath);
        $sCSSParentDir = '';
        $sDestDir = $sTmp;
        $sImgOutPath = '';
        $sSrcTemplateFile = 'tplX';

        //copy($sFirstPath, $sTmp . '/tmp_448037154.css');
        @unlink("$sTmp/$sHash.css");
        @unlink("$sTmp/c$sHash.css");
        @unlink("$sTmp/cn$sHash.css");
        @unlink("$sTmp/cs$sHash.css");

        $iFilesSize = $method->invokeArgs(
            $oTplMinifier,
            array($aPaths, $sCSSParentDir, $sDestDir, $sImgOutPath, $sSrcTemplateFile)
        );
        $this->assertFileEquals("$sTmp/$sHash.css", "$sTmp/c$sHash.css");
        $this->assertFileEquals("$sTmp/$sHash.css", "$sTmp/cn$sHash.css");
        $this->assertEquals((138+strlen($sFirstPath))*4, $iFilesSize);
        $this->assertFileNotExists("$sTmp/tmp_$sHash.css");

        @unlink("$sTmp/$sHash.css");
        @unlink("$sTmp/c$sHash.css");
        @unlink("$sTmp/cn$sHash.css");
        @unlink("$sTmp/cs$sHash.css");
    }

    /**
     * @covers Minifier_TemplateMinifier
     */
    public function testMinifyCSS_with1FileWithURLs ()
    {
        $sHash = '012345678';
        $oTplMinifier = $this->getMock(
            'Minifier_TemplateMinifier',
            array('_getHash'),
            array($this->oJSminAdapter, $this->oServiceContainer->getLogAdapter())
        );
        $oTplMinifier->expects($this->any())->method('_getHash')->will($this->returnValue($sHash));
        $oTplMinifier->expects($this->exactly(1))->method('_getHash');

        //$oTplMinifier = new Minifier_TemplateMinifier($this->oJSminAdapter, $this->oServiceContainer->getLogAdapter());
        $class = new ReflectionClass($oTplMinifier);
        $method = $class->getMethod('_minifyCSS');
        $method->setAccessible(true);

        $sTmp = DEPLOYMENT_TMP_DIR;
        $sFirstPath = __DIR__ . '/resources/urls.css';	// size=??? octets une fois minifié
        $aPaths = array($sFirstPath);
        $sCSSParentDir = '';
        $sDestDir = $sTmp;
        $sImgOutPath = '/ID';
        $sSrcTemplateFile = 'tplX';

        $sPattern = "/* Contains: $sFirstPath */\n"
                  . ".s1{background:url(http://%1\$s/ID/css/images/sprites/search.png) no-repeat;}#search .hproduct "
                  . ".highlight{background:url(http://%1\$s/ID/css/images/search/pct_best_partner.png) no-repeat;"
                  . "display:block;height:61px;left:0;position:absolute;top:0;width:61px;}.lang-jp "
                  . ".shareBtn{top:253px;}.shareBtn{display:none;position:absolute;right:-6px;top:213px;}";
        $method->invokeArgs(
            $oTplMinifier,
            array($aPaths, $sCSSParentDir, $sDestDir, $sImgOutPath, $sSrcTemplateFile)
        );
        $this->assertEquals(sprintf($sPattern, 's1.c4tw.net'), file_get_contents("$sTmp/$sHash.css"));
        $this->assertEquals(sprintf($sPattern, 's1c.c4tw.net'), file_get_contents("$sTmp/c$sHash.css"));
        $this->assertEquals(sprintf($sPattern, 's1cn.c4tw.net'), file_get_contents("$sTmp/cn$sHash.css"));
        $this->assertEquals(sprintf($sPattern, 'static.cycling-shopping.co.uk'), file_get_contents("$sTmp/cs$sHash.css"));
        @unlink("$sTmp/$sHash.css");
        @unlink("$sTmp/c$sHash.css");
        @unlink("$sTmp/cn$sHash.css");
        @unlink("$sTmp/cs$sHash.css");
    }

    /**
     * @covers Minifier_TemplateMinifier::_minifyCSS
     */
    public function testMinifyCSS_throwWarningWith1FileNotFound ()
    {
        $oBaseLogger = new Logger_Adapter(Logger_Interface::WARNING);
        $oMockLogger = $this->getMock('Logger_IndentedDecorator', array('log'), array($oBaseLogger, '   '));
        $sMsg = "[WARNING] In template 'tplX'. File not found: '/not/found.css'! Files '/tmp/%s368657543.css' not generated.";
        $oMockLogger->expects($this->at(0))->method('log')->with($this->equalTo($sMsg), $this->equalTo(30));
        $oMockLogger->expects($this->exactly(1))->method('log');

        $oTplMinifier = new Minifier_TemplateMinifier($this->oJSminAdapter, $oMockLogger);
        $class = new ReflectionClass($oTplMinifier);
        $method = $class->getMethod('_minifyCSS');
        $method->setAccessible(true);

        $sTmp = DEPLOYMENT_TMP_DIR;
        $sFirstPath = '/not/found.css';
        $aPaths = array($sFirstPath);
        $sCSSParentDir = '';
        $sDestDir = $sTmp;
        $sImgOutPath = '';
        $sSrcTemplateFile = 'tplX';

        $iFilesSize = $method->invokeArgs(
            $oTplMinifier,
            array($aPaths, $sCSSParentDir, $sDestDir, $sImgOutPath, $sSrcTemplateFile)
        );
        $this->assertEquals(0, $iFilesSize);
    }

    /**
     * @covers Minifier_TemplateMinifier::_minifyCSS
     */
    public function testMinifyCSS_throwExceptionWhenBadFileExtension ()
    {
        $oTplMinifier = new Minifier_TemplateMinifier($this->oJSminAdapter, $this->oServiceContainer->getLogAdapter());
        $class = new ReflectionClass($oTplMinifier);
        $method = $class->getMethod('_minifyCSS');
        $method->setAccessible(true);

        $sTmp = DEPLOYMENT_TMP_DIR;
        $sFirstPath = '/not/found.xyz';
        $aPaths = array($sFirstPath);
        $sCSSParentDir = '';
        $sDestDir = $sTmp;
        $sImgOutPath = '';
        $sSrcTemplateFile = 'tplX';

        $this->setExpectedException('UnexpectedValueException', 'Destination file must be same type of input files');
        $method->invokeArgs(
            $oTplMinifier,
            array($aPaths, $sCSSParentDir, $sDestDir, $sImgOutPath, $sSrcTemplateFile)
        );
    }

    /**
     * @covers Minifier_TemplateMinifier::getNewImgURL
     */
    public function testGetNewImgURL ()
    {
        $sDir = 'dir';
        $sFilename = 'filename';
        $sExt = 'ext';
        $sDomain = 'subdomain';
        $sImgOutPath = '/subdir';
        $sExpected = 's' . (crc32('filename') % 2) . 'subdomain.c4tw.net/subdir/css/dir/filename.ext';

        $sResult = Minifier_TemplateMinifier::getNewImgURL($sDir, $sFilename, $sExt, $sDomain, $sImgOutPath);
        $this->assertEquals($sExpected, $sResult);
    }

    /**
     * @covers Minifier_TemplateMinifier::_getTemplates
     */
    public function testGetTemplates ()
    {
        $oTplMinifier = new Minifier_TemplateMinifier($this->oJSminAdapter, $this->oServiceContainer->getLogAdapter());
        $class = new ReflectionClass($oTplMinifier);
        $method = $class->getMethod('_getTemplates');
        $method->setAccessible(true);

        $sTplDir = __DIR__ . '/resources/templates';
        $aExpected = array(
            $sTplDir . '/a/b/head.tpl',
            $sTplDir . '/common/statics_js.tpl',
            $sTplDir . '/common/toolbar.tpl',
            $sTplDir . '/index.tpl',
            $sTplDir . '/r.tpl',
        );

        $aTemplateFiles = $method->invokeArgs($oTplMinifier, array($sTplDir));
        $this->assertEquals($aExpected, $aTemplateFiles);
        $aTemplateFiles = $method->invokeArgs($oTplMinifier, array($sTplDir . '/'));
        $this->assertEquals($aExpected, $aTemplateFiles);
    }

    /**
     * @covers Minifier_TemplateMinifier::_extractStaticPaths
     */
    public function testExtractStaticPaths_throwExceptionIfFileNotFound ()
    {
        $oTplMinifier = new Minifier_TemplateMinifier($this->oJSminAdapter, $this->oServiceContainer->getLogAdapter());
        $class = new ReflectionClass($oTplMinifier);
        $method = $class->getMethod('_extractStaticPaths');
        $method->setAccessible(true);

        $this->setExpectedException(
            'Exception',
            'file_get_contents(/not/found): failed to open stream: No such file or directory'
        );
        $aStatics = $method->invokeArgs($oTplMinifier, array('/not/found'));
    }

    /**
     * @covers Minifier_TemplateMinifier::_extractStaticPaths
     * @dataProvider dataProvider_testExtractStaticPaths
     * @param string $sTemplateFile chemin de template (.tpl) à analyser
     * @param array $aExpected couple de 2 tableaux, le premier listant les URLs extraites de code CSS, groupées par bloc
     * 		combine, le second listant les URLs extraites de code JS, groupées également par bloc combine.
     */
    public function testExtractStaticPaths ($sTemplateFile, $aExpected)
    {
        $oTplMinifier = new Minifier_TemplateMinifier($this->oJSminAdapter, $this->oServiceContainer->getLogAdapter());
        $class = new ReflectionClass($oTplMinifier);
        $method = $class->getMethod('_extractStaticPaths');
        $method->setAccessible(true);

        $aStatics = $method->invokeArgs($oTplMinifier, array($sTemplateFile));
        $this->assertEquals($aExpected, $aStatics);
    }

    /**
     * Data provider pour testExtractStaticPaths()
     */
    public static function dataProvider_testExtractStaticPaths ()
    {
        return array(
            array(__DIR__ . '/resources/templates/common/statics_js.tpl', array(array(), array(array(
                '/js/lib/jquery.cookie.js',
                '/js/lib/jquery.carousel.min.js',
                '/js/lib/jquery.outside-events.min.js',
                '/js/lib/jquery.tw.autocomplete.js'
            ), array(
                '/js/lib/jquery.outside-events.min.js',
                '/js/lib/jquery.tw.autocomplete.js'
            )))),
            array(__DIR__ . '/resources/templates/common/toolbar.tpl', array(array(), array())),
            array(__DIR__ . '/resources/templates/index.tpl', array(array(array('/css/index_nojs.css')), array())),
            array(__DIR__ . '/resources/templates/r.tpl', array(array(
                array('/css/r.css')),
                array(array('/js/google/analytics_controllerv4.js'))
            )),
        );
    }
}
