<?php

namespace Himedia\Padocc\Tests;

/**
 * Test base class.
 */
abstract class PadoccTestCase extends \PHPUnit_Framework_TestCase
{
    /**
     * Configuration array.
     * @var array
     */
    protected $aConfig;

    /**
     * All configurations array.
     * @var array
     */
    protected $aAllConfigs;

    /**
     * This method is called before the first test of this test class is run.
     *
     * @since Method available since Release 3.4.0
     */
    public static function setUpBeforeClass()
    {
    }

    /**
     * Constructs a test case with the given name.
     *
     * @param  string $sName
     * @param  array  $aData
     * @param  string $sDataName
     */
    public function __construct($sName = null, array $aData = array(), $sDataName = '')
    {
        parent::__construct($sName, $aData, $sDataName);
        $this->aConfig = $GLOBALS['aConfig']['Himedia\Padocc'];
        $this->aAllConfigs = $GLOBALS['aConfig'];
        if (! file_exists($this->aConfig['dir']['tmp'])) {
            mkdir($this->aConfig['dir']['tmp'], 0777, true);
        }
    }

    /**
     * Returns the path to tests.
     *
     * @return string
     */
    public function getTestsDir()
    {
        return $this->aConfig['dir']['root'] . DIRECTORY_SEPARATOR . 'tests';
    }

    /**
     * Sets up the fixture, for example, open a network connection.
     * This method is called before a test is executed.
     */
    public function setUp ()
    {
    }

    /**
     * Tears down the fixture, for example, close a network connection.
     * This method is called after a test is executed.
     */
    public function tearDown()
    {
    }

    /**
     * This method is called after the last test of this test class is run.
     *
     * @since Method available since Release 3.4.0
     */
    public static function tearDownAfterClass()
    {
    }
}
