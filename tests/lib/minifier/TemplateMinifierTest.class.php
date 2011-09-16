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

    private $aShellExecCmds;

    private $oJSminAdapter;

    public function shellExecCallback ($sCmd)
    {
        $this->aShellExecCmds[] = $sCmd;
        return array();
    }

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
        $oTplMinifier = new Minifier_TemplateMinifier($this->oJSminAdapter, $this->oServiceContainer->getLogAdapter());
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
            "Copy from '$sTmp/tmp_448037154.js' to '$sTmp/448037154.js' failed!"
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
        $oTplMinifier = new Minifier_TemplateMinifier($this->oJSminAdapter, $this->oServiceContainer->getLogAdapter());
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

        copy($sFirstPath, $sTmp . '/tmp_448037154.js');
        @unlink($sTmp . '/448037154.js');
        @unlink($sTmp . '/c448037154.js');
        @unlink($sTmp . '/cn448037154.js');

        $iFilesSize = $method->invokeArgs($oTplMinifier, array($aPaths, $sJSParentDir, $sDestDir, $sImgOutPath));
        $this->assertEquals(
            array("cat \"$sFirstPath\" | $sJSMinPath >'$sTmp/tmp_448037154.js' && sed --in-place '1i/* Contains: $sFirstPath */\n' '$sTmp/tmp_448037154.js'"),
            $this->aShellExecCmds
        );
        $this->assertFileEquals($sFirstPath, $sTmp . '/448037154.js');
        $this->assertFileEquals($sFirstPath, $sTmp . '/c448037154.js');
        $this->assertFileEquals($sFirstPath, $sTmp . '/cn448037154.js');
        $this->assertEquals(343*3, $iFilesSize);
        $this->assertFileNotExists($sTmp . '/tmp_448037154.js');

        @unlink($sTmp . '/448037154.js');
        @unlink($sTmp . '/c448037154.js');
        @unlink($sTmp . '/cn448037154.js');
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
        $oTplMinifier = new Minifier_TemplateMinifier($this->oJSminAdapter, $this->oServiceContainer->getLogAdapter());
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

        copy($sFirstPath, $sTmp . '/tmp_448037154.js');
        touch($sTmp . '/448037154.js');
        @unlink($sTmp . '/c448037154.js');
        @unlink($sTmp . '/cn448037154.js');

        $iFilesSize = $method->invokeArgs($oTplMinifier, array($aPaths, $sJSParentDir, $sDestDir, $sImgOutPath));
        $this->assertEquals(
            array("cat \"$sFirstPath\" | $sJSMinPath >'$sTmp/tmp_448037154.js' && sed --in-place '1i/* Contains: $sFirstPath */\n' '$sTmp/tmp_448037154.js'"),
            $this->aShellExecCmds
        );
        $this->assertFileExists($sTmp . '/448037154.js');
        $this->assertEquals('', file_get_contents($sTmp . '/448037154.js'));
        $this->assertFileEquals($sFirstPath, $sTmp . '/c448037154.js');
        $this->assertFileEquals($sFirstPath, $sTmp . '/cn448037154.js');
        $this->assertEquals(343*2, $iFilesSize);
        $this->assertFileNotExists($sTmp . '/tmp_448037154.js');

        @unlink($sTmp . '/448037154.js');
        @unlink($sTmp . '/c448037154.js');
        @unlink($sTmp . '/cn448037154.js');
    }

    /**
     * @covers Minifier_TemplateMinifier::_minifyCSS
     */
    public function testMinifyCSS_with1SimpleFile ()
    {
        $oTplMinifier = new Minifier_TemplateMinifier($this->oJSminAdapter, $this->oServiceContainer->getLogAdapter());
        $class = new ReflectionClass($oTplMinifier);
        $method = $class->getMethod('_minifyCSS');
        $method->setAccessible(true);

        $sTmp = DEPLOYMENT_TMP_DIR;
        $sFirstPath = __DIR__ . '/resources/a.css';	// size=196 octets une fois minifié
        $aPaths = array($sFirstPath);
        $sCSSParentDir = '';
        $sDestDir = $sTmp;
        $sImgOutPath = '';
        $sSrcTemplateFile = 'tplX';

        //copy($sFirstPath, $sTmp . '/tmp_448037154.css');
        @unlink($sTmp . '/1046150687.css');
        @unlink($sTmp . '/c1046150687.css');
        @unlink($sTmp . '/cn1046150687.css');

        $iFilesSize = $method->invokeArgs(
            $oTplMinifier,
            array($aPaths, $sCSSParentDir, $sDestDir, $sImgOutPath, $sSrcTemplateFile)
        );
        $this->assertFileEquals($sTmp . '/1046150687.css', $sTmp . '/c1046150687.css');
        $this->assertFileEquals($sTmp . '/1046150687.css', $sTmp . '/cn1046150687.css');
        $this->assertEquals(196*3, $iFilesSize);
        $this->assertFileNotExists($sTmp . '/tmp_1046150687.css');

        @unlink($sTmp . '/1046150687.css');
        @unlink($sTmp . '/c1046150687.css');
        @unlink($sTmp . '/cn1046150687.css');
    }

    /**
     * @covers Minifier_TemplateMinifier
     */
    public function testMinifyCSS_with1FileWithURLs ()
    {
        $oTplMinifier = new Minifier_TemplateMinifier($this->oJSminAdapter, $this->oServiceContainer->getLogAdapter());
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

        $sPattern = "/* Contains: /home/gaubry/deployment/tests/lib/minifier/resources/urls.css */\n"
        . ".s1{background:url(http://s1%1\$s.c4tw.net/ID/css/images/sprites/search.png) no-repeat;}#search .hproduct .highlight{background:url(http://s1%1\$s.c4tw.net/ID/css/images/search/pct_best_partner.png) no-repeat;display:block;height:61px;left:0;position:absolute;top:0;width:61px;}.lang-jp .shareBtn{top:253px;}.shareBtn{display:none;position:absolute;right:-6px;top:213px;}";
        @unlink($sTmp . '/1141071088.css');
        @unlink($sTmp . '/c1141071088.css');
        @unlink($sTmp . '/cn1141071088.css');

        $method->invokeArgs(
            $oTplMinifier,
            array($aPaths, $sCSSParentDir, $sDestDir, $sImgOutPath, $sSrcTemplateFile)
        );
        $this->assertEquals(sprintf($sPattern, ''), file_get_contents($sTmp . '/1141071088.css'));
        $this->assertEquals(sprintf($sPattern, 'c'), file_get_contents($sTmp . '/c1141071088.css'));
        $this->assertEquals(sprintf($sPattern, 'cn'), file_get_contents($sTmp . '/cn1141071088.css'));
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
