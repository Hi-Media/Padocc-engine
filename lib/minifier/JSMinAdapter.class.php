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
     * @see _minifyJS()
     */
    private $_oShell;

    /**
     * Chemin du binaire JSMin
     * @var string
     * @see _minifyJS()
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

    /**
     * Minifie la liste de fichiers JS ou CSS spécifiée et enregistre le résultat dans $sDestPath.
     *
     * @param array $aSrcPaths liste de fichiers se finissant tous par '.js', ou tous par '.css'
     * @param string $sDestPath chemin/fichier dans lequel enregistrer le résultat du minify
     * @return Minifier_Interface $this
     * @throws BadMethodCallException si $aSrcPaths vide
     * @throws UnexpectedValueException si les sources n'ont pas toutes la même extension de fichier
     * @throws UnexpectedValueException si la destination est un CSS quand les sources sont des JS ou inversement
     * @throws DomainException si des fichiers ne se terminent ni par '.js', ni par '.css'
     */
    public function minify (array $aSrcPaths, $sDestPath)
    {
        if (count($aSrcPaths) === 0) {
            throw new BadMethodCallException('Source files missing!');
        }

        // Est-ce que les fichiers en entrée sont tous des JS ou tous des CSS ?
        $sFirstExtension = strrchr(reset($aSrcPaths), '.');
        foreach ($aSrcPaths as $sSrcPath) {
            $sExtension = strrchr($sSrcPath, '.');
            if ($sExtension !== $sFirstExtension) {
                throw new UnexpectedValueException('All files must be either JS or CSS: ' . print_r($aSrcPaths, true));
            }
        }

        // La destination est-elle en accord avec les entrées ?
        if (strrchr($sDestPath, '.') !== $sFirstExtension) {
            throw new UnexpectedValueException("Destination file must be same type of input files: '$sDestPath'");
        }

        // On redirige vers le service idoine :
        switch ($sFirstExtension) {
            case '.js':
                $this->_minifyJS($aSrcPaths, $sDestPath);
                break;

            case '.css':
                $this->_minifyCSS($aSrcPaths, $sDestPath);
                break;

            default:
                $sMsg = "All specified paths must finish either by '.js' or '.css': '$sFirstExtension'!";
                throw new DomainException($sMsg);
                break;
        }

        return $this;
    }

    /**
     * Minifie la liste des fichiers JS spécifiée et enregistre le résultat dans $sDestPath.
     *
     * @param array $aSrcPaths liste de fichiers se finissant tous par '.js'
     * @param string $sDestPath chemin/fichier dans lequel enregistrer le résultat du minify
     * @throws RuntimeException en cas d'erreur shell
     */
    protected function _minifyJS (array $aSrcPaths, $sDestPath)
    {
        $sCmd = 'cat';
        foreach ($aSrcPaths as $sSrcPath) {
            $sCmd .= ' ' . $this->_oShell->escapePath($sSrcPath);
        }
        $sCmd .= " | $this->_sBinPath >'" . $sDestPath . "'";
        $this->_oShell->exec($sCmd);
    }

    /**
     * Minifie la liste des fichiers CSS spécifiée et enregistre le résultat dans $sDestPath.
     *
     * @param array $aSrcPaths liste de fichiers se finissant tous par '.css'
     * @param string $sDestPath chemin/fichier dans lequel enregistrer le résultat du minify
     */
    protected function _minifyCSS (array $aSrcPaths, $sDestPath)
    {
        $sContent = $this->_getContent($aSrcPaths);

        // remove comments
        $sContent = preg_replace('#/\*[^*]*\*+([^/][^*]*\*+)*/#', '', $sContent);

        // remove tabs, spaces, newlines, etc.
        $sContent = str_replace(array("\r" , "\n" , "\t"), '', $sContent);
        $sContent = str_replace(array('    ' , '   ' , '  '), ' ', $sContent);

        file_put_contents($sDestPath, $sContent);
    }

    /*public function _minifyCSS (array $aSrcPaths, $sDestPath)
    {
        $sContent = $this->getContent($aSrcPaths);

        // Separate s0.c4tw.net >> s0.c4tw.net/s1.c4tw.net
        $sContent = preg_replace_callback(
            '!s0.c4tw.net/(.*)/(.*).(png|gif|jpg)!',
            array($this, 'getNewImgPath'),
            $sContent
        );

        // remove comments
        $sContent = preg_replace('!/\*[^*]*\*+([^/][^*]*\*+)*XXX/!', '', $sContent);

        // remove tabs, spaces, newlines, etc.
        $sContent = str_replace(array("\r\n" , "\r" , "\n" , "\t"), '', $sContent);
        $sContent = str_replace(array('  ' , '   ' , '    '), ' ', $sContent);
    }

    private function _getNewImgPath (array $aMatches)
    {
        list(, $sDir, $sFilename, $sExtension) = $aMatches;

        // sDomain = "[|c|cn].c4tw.net"
        // s0cn.c4tw.net/20110906174722P/web/css/images/events/dayoffer/bg-beige.jpg
        $sNewImgPath = 's' . (crc32($sFilename) % 2) . $this->sDomain . '/'
                  . $this->sTwBuild . $this->sOutPath . '/css/' . $sDir . '/' . $sFilename . '.' . $sExtension;

        return $sNewImgPath;
    }

    private function _getHash ($sString)
    {
        $sHash = abs(crc32($sString));

        // This function returns the same int value on a 64 bit mc. like the crc32() function on a 32 bit mc.
        if ($sHash & 0x80000000) {
            $sHash ^= 0xffffffff;
            $sHash += 1;
        }

        return $sHash;
    }*/

    /**
     * Retourne la concaténation du contenu des fichiers spécifiés.
     *
     * @param array $aSrcPaths liste de chemins dont on veut concaténer le contenu
     * @return string la concaténation du contenu des fichiers spécifiés.
     * @see _minifyCSS()
     */
    private function _getContent (array $aSrcPaths)
    {
        $aExpandedPaths = array();
        foreach ($aSrcPaths as $sPath) {
            if (strpos($sPath, '*') !== false || strpos($sPath, '?') !== false) {
                $aExpandedPaths = array_merge($aExpandedPaths, glob($sPath));
            } else {
                $aExpandedPaths[] = $sPath;
            }
        }

        $sContent = '';
        foreach ($aExpandedPaths as $sPath) {
            $sContent .= file_get_contents($sPath);
        }
        return $sContent;
    }
}
