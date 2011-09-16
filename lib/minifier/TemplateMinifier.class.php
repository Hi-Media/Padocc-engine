<?php

/**
 * Concatène et compresse les fichiers JS et CSS selon les blocs '{combine}' présents dans les templates.
 * Un seul template peut donc générer plusieurs compilations de JS et de CSS.
 * Ces paquets sont ensuite dupliqués sur les différents sous-domaines ('', 'c', 'cn').
 * Enfin redistribue les URLs des images au format '#s0.c4tw.net/([^\'")]*)/([^\'")]*)\.(png|gif|jpg)#i'
 * sur les serveurs de statique s0 et s1, en respectant les sous-domaines.
 *
 * Exemple de bloc '{combine}' de fichier .tpl :
 * {combine compress=true}
 *     <script type="text/javascript" src="/js/google/analytics_controllerv4.js"></script>
 *     ...
 * {/combine}
 *
 * Ou encore :
 * {combine compress=true}
 *     <link media="all" href="/css/search/noscript.css" rel="stylesheet" type="text/css" />
 *     ...
 * {/combine}
 *
 * Exemple d'URL modifiée, ici dans du CSS :
 * Avant : background:url(http://s0.c4tw.net/images/sprites/search.png) no-repeat;
 * Après : background:url(http://s1cn.c4tw.net/20110914184627_12723/webv4/css/images/sprites/search.png) no-repeat;
 *
 * @category TwengaDeploy
 * @package Lib
 * @author Geoffroy AUBRY <geoffroy.aubry@twenga.com>
 */
class Minifier_TemplateMinifier
{

    /**
     * Liste des sous-domaines de c4tw.net, sur lesquels seront accessibles les JS et CSS compressés.
     * @var array
     */
    private $_aSubDomains;

    /**
     * Minifier sous-jacent, effectuant les compressions de premier niveau.
     * @var Minifier_Interface
     * @see _minifyJS()
     * @see _minifyCSS()
     */
    private $_oMinifier;

    /**
     * Logger pour retourner les statistiques sur le travail accompli et avertir des fichiers manquants (warnings).
     * @var Logger_Interface
     */
    private $_oLogger;

    /**
     * Constructeur.
     *
     * @param Minifier_Interface Minifier sous-jacent, effectuant les compressions de premier niveau.
     * @param Logger_Interface Logger pour retourner les statistiques sur le travail accompli et
     * 		avertir des fichiers manquants (warnings).
     */
    public function __construct (Minifier_Interface $oMinifier, Logger_Interface $oLogger)
    {
        $this->_oMinifier = $oMinifier;
        $this->_oLogger = $oLogger;
        $this->_aSubDomains = array('', 'c', 'cn');
    }

    public function process ($sTplDir, $sCSSParentDir, $sJSParentDir, $sDestDir, $sImgOutPath)
    {
        $aTemplateFiles = $this->_getTemplates($sTplDir);
        $iNbCSSFiles = 0;
        $iNbJSFiles = 0;
        $iCSSFilesSize = 0;
        $iJSFilesSize = 0;

        foreach ($aTemplateFiles as $sTemplateFile) {
            list($aStaticCSSPaths, $aStaticJSPaths) = $this->_extractStaticPaths($sTemplateFile);

            foreach ($aStaticCSSPaths as $aPaths) {
                $iFilesSize = $this->_minifyCSS($aPaths, $sCSSParentDir, $sDestDir, $sImgOutPath, $sTemplateFile);
                if ($iFilesSize > 0) {
                    $iNbCSSFiles += count($this->_aSubDomains);
                    $iCSSFilesSize += $iFilesSize;
                }
            }

            foreach ($aStaticJSPaths as $aPaths) {
                $iFilesSize = $this->_minifyJS($aPaths, $sJSParentDir, $sDestDir, $sImgOutPath);
                if ($iFilesSize > 0) {
                    $iNbJSFiles += count($this->_aSubDomains);
                    $iJSFilesSize += $iFilesSize;
                }
            }
        }

        list($sCSSTotal, $sCSSUnit) = Tools::convertFileSize2String($iCSSFilesSize);
        list($sJSTotal, $sJSUnit) = Tools::convertFileSize2String($iJSFilesSize);
        $sMsg = 'Number of templates: ' . count($aTemplateFiles) . "\n"
              . "Generated CSS files: $sCSSTotal $sCSSUnit in $iNbCSSFiles files\n"
              . "Generated JS files: $sJSTotal $sJSUnit in $iNbJSFiles files";
        $this->_oLogger->log($sMsg);
    }

    private function _minifyJS (array $aPaths, $sJSParentDir, $sDestDir, $sImgOutPath)
    {
        foreach ($aPaths as $i => $sValue) {
            $aPaths[$i] = $sJSParentDir . '/' . preg_replace('#^/#', '', $sValue);
        }

        $sHash = $this->_getHash(implode('', $aPaths));
        $sPattern = $sDestDir . '/%s' . $sHash . '.js';

        $sTmpDestPath = sprintf($sPattern, 'tmp_');
        $this->_oMinifier->minify($aPaths, $sTmpDestPath);

        $iFilesSize = $this->_generateSubdomainsFiles($sPattern, $sTmpDestPath, $sImgOutPath);
        unlink($sTmpDestPath);
        return $iFilesSize;
    }

    private function _minifyCSS (array $aPaths, $sCSSParentDir, $sDestDir, $sImgOutPath, $sSrcTemplateFile)
    {
        foreach ($aPaths as $i => $sValue) {
            $aPaths[$i] = $sCSSParentDir . '/' . preg_replace('#^/#', '', $sValue);
        }

        $sHash = $this->_getHash(implode('', $aPaths));
        $sPattern = $sDestDir . '/%s' . $sHash . '.css';

        $sTmpDestPath = sprintf($sPattern, 'tmp_');
        try {
            $this->_oMinifier->minify($aPaths, $sTmpDestPath);
        } catch (RuntimeException $oException) {
            $sErrorMsg = $oException->getMessage();
            if (strpos($sErrorMsg, 'File not found: ') !== 0) {
                throw $oException;
            } else {
                $this->_oLogger->log(
                    "[WARNING] In template '$sSrcTemplateFile'. " . $sErrorMsg
                        . " Files '$sPattern' not generated.",
                    Logger_Interface::WARNING
                );
                return 0;
            }
        }

        $iFilesSize = $this->_generateSubdomainsFiles($sPattern, $sTmpDestPath, $sImgOutPath);
        unlink($sTmpDestPath);
        return $iFilesSize;
    }

    private function _generateSubdomainsFiles ($sPattern, $sTmpDestPath, $sImgOutPath)
    {
        $iFilesSize = 0;
        foreach ($this->_aSubDomains as $sSubDomain) {
            $sDestPath = sprintf($sPattern, $sSubDomain);
            if (file_exists($sDestPath)) {
                continue;
            }
            try {
                copy($sTmpDestPath, $sDestPath);
            } catch (Exception $oException) {
                throw new RuntimeException(
                    "Copy from '$sTmpDestPath' to '$sDestPath' failed!",
                    1,
                    $oException
                );
            }

            $sContents = file_get_contents($sDestPath);
            $sContents = preg_replace_callback(
                '#s0.c4tw.net/([^\'")]*)/([^\'")]*)\.(png|gif|jpg)#i',
                function (array $aMatches) use ($sSubDomain, $sImgOutPath)
                {
                    list(, $sDir, $sFilename, $sExtension) = $aMatches;
                    return Minifier_TemplateMinifier::getNewImgURL(
                        $sDir, $sFilename, $sExtension, $sSubDomain, $sImgOutPath
                    );
                },
                $sContents
            );
            $iFileSize = file_put_contents($sDestPath, $sContents);
            $iFilesSize += $iFileSize;
        }
        return $iFilesSize;
    }

    private function _extractStaticPaths ($sTemplateFile)
    {
        $aCSSPaths = array();
        $aJSPaths = array();
        $sContent = file_get_contents($sTemplateFile);

        $sRegex = '/\{combine\b[^}]*\bcompress=true\b[^}]*\}([^{]+)\{/i';
        preg_match_all($sRegex, $sContent, $aMatches);
        foreach ($aMatches[1] as $sBloc) {
            preg_match_all('/\bsrc\b\s*=\s*["\'](.+?)["\']/i', $sBloc, $aMatchesJS);
            if ( ! empty($aMatchesJS[1])) {
                $aJSPaths[] = $aMatchesJS[1];
            }

            preg_match_all('/\bhref\b\s*=\s*["\'](.+?)["\']/i', $sBloc, $aMatchesCSS);
            if ( ! empty($aMatchesCSS[1])) {
                $aCSSPaths[] = $aMatchesCSS[1];
            }
        }

        return array($aCSSPaths, $aJSPaths);
    }

    /**
     * Retourne la liste de tous les fichiers template ('*.tpl') inclus directement ou non dans le
     * répertoire spécifié.
     *
     * @param string $sTplDir répertoire à scanner, récursivement
     * @return array la liste de tous les fichiers template ('*.tpl') inclus directement ou non dans le
     * répertoire spécifié.
     * @throws UnexpectedValueException si répertoire introuvable
     */
    private function _getTemplates ($sTplDir)
    {
        $aTemplateFiles = array();
        $oDirIterator = new RecursiveDirectoryIterator($sTplDir);
        $oIterator = new RecursiveIteratorIterator($oDirIterator, RecursiveIteratorIterator::SELF_FIRST);
        foreach ($oIterator as $oFile) {
            if ($oFile->isFile() && strrchr($oFile->getPathname(), '.') === '.tpl') {
                $aTemplateFiles[] = $oFile->getPathname();
            }
        }
        sort($aTemplateFiles);
        return $aTemplateFiles;
    }

    /**
     * Réécrit les URL des images au format '#s0.c4tw.net/([^\'")]*)/([^\'")]*)\.(png|gif|jpg)#i'
     * des fichiers JS et CSS compressés, pour les reditribuer sur les serveurs
     * de statique s0 et s1, en respectant les sous-domaines.
     *
     * @param string $sDir sous-répertoire de l'image
     * @param string $sFilename nom de l'image
     * @param string $sExtension son extension
     * @param string $sDomain domaine sur lequel rediriger le nouveau lien
     * @param string $sImgOutPath sous-répertoire additionnel
     * @return string nouvelle URL de l'image
     * @see _generateSubdomainsFiles()
     */
    public static function getNewImgURL ($sDir, $sFilename, $sExtension, $sDomain, $sImgOutPath)
    {
        $sNewImgPath = 's' . (crc32($sFilename) % 2) . $sDomain . '.c4tw.net'
                     . $sImgOutPath . '/css/' . $sDir . '/' . $sFilename . '.' . $sExtension;
        return $sNewImgPath;
    }

    /**
     * Retourne un hash de la chaîne spécifiée.
     * Utilisé pour générer un nom de fichier JS ou CSS à partir des fichiers le constituant.
     *
     * @param string $sString
     * @return string un hash de la chaîne spécifiée, compatible processeurs 64bits.
     */
    private function _getHash ($sString)
    {
        $sHash = abs(crc32($sString));

        // Returns the same int value on a 64 bit mc. like the crc32() function on a 32 bit mc:
        if ($sHash & 0x80000000) {
            $sHash ^= 0xffffffff;
            $sHash += 1;
        }

        return $sHash;
    }
}
