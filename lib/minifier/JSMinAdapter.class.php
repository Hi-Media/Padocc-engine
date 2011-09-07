<?php

/**
 * Compresser les fichiers JS et CSS.
 *
 * @category TwengaDeploy
 * @package Lib
 * @author Geoffroy AUBRY <geoffroy.aubry@twenga.com>
 */
class Minifier_JSMinAdapter implements Minifier_Interface
{

    /**
     * Shell adapter.
     * @var Shell_Interface
     * @see loadConfigShellFile()
     */
    private $_oShell;

    /**
     * Chemin du binaire JSMin
     * @var string
     */
    private $_sBinPath;

    /**
     * Constructeur.
     *
     * @param string chemin du binaire JSMin
     * @param Shell_Interface $oShell instance utilisée pour exécuter le binaire jsmin
     */
    public function __construct ($sJSMinBinPath, Shell_Interface $oShell)
    {
        $this->_sBinPath = $sJSMinBinPath;
        $this->_oShell = $oShell;
    }

    public function minifyJS (array $aSrcPaths, $sDestPath)
    {
        $sCmd = "cat '" . implode("' '", $aSrcPaths) . "' | $this->_sBinPath >'" . $sDestPath . "'";
        return $this->_oShell->exec($sCmd);
    }

    public function minifyCSS (array $aSrcPaths, $sDestPath)
    {
        // Separate s0.c4tw.net >> s0.c4tw.net/s1.c4tw.net
        $sContents = preg_replace_callback(
            '!s0.c4tw.net/(.*)/(.*).(png|gif|jpg)!',
            array($this,"ImgToCrc"),
            $sContents
        );

        // remove comments
        $sContents = preg_replace('!/\*[^*]*\*+([^/][^*]*\*+)*/!', '', $sContents);

        // remove tabs, spaces, newlines, etc.
        $sContents = str_replace(array("\r\n" , "\r" , "\n" , "\t"), '', $sContents);
        $sContents = str_replace(array('  ' , '    ' , '    '), ' ', $sContents);
    }
}
