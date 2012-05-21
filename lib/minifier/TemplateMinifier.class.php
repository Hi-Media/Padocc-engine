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
 * @author Geoffroy AUBRY <geoffroy.aubry@twenga.com> Tony CARON <caron.tony@twenga.com>
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
        $this->_aSubDomains = array('', 'c', 'cn', 'cs');
    }

    /**
     * Génère tous les packages de CSS ou JS minifiés décrits dans les fichiers template (.tpl)
     * trouvables dans le répertoire $sTplDir ou ses sous-répertoires.
     *
     * @param string $sTplDir répertoire de templates
     * @param string $sCSSParentDir répertoire source des CSS
     * @param string $sJSParentDir répertoire source des JS
     * @param string $sDestDir répertoire de destination
     * @param string $sImgOutPath sous-répertoire devant être inséré dans les URLs des images
     */
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

    /**
     * Retourne un couple de 2 listes de fichiers,
     * la 1re étant la liste de fichiers fournie en entrée adaptée pour un appel à Minifier_Interface::minify(),
     * la 2nde étant cette même liste de fichiers adaptée pour un appel à $this->_getHash().
     *
     * @param array $aPaths liste de fichiers JS ou CSS à minifier
     * @param string $sParentDir répertoire source des JS ou CSS
     * @return array couple de 2 listes de fichiers,
     * la 1re étant la liste de fichiers fournie en entrée adaptée pour un appel à Minifier_Interface::minify(),
     * la 2nde étant cette même liste de fichiers adaptée pour un appel à $this->_getHash().
     */
    private function _buildPaths (array $aPaths, $sParentDir)
    {
        $aPathsToMinify = array();
        $aPathsToHash = array();
        foreach ($aPaths as $sValue) {
            $sValue = preg_replace('#^/#', '', $sValue);
            $aPathsToMinify[] = $sParentDir . '/' . $sValue;
            $aPathsToHash[] = '/' . $sValue;
        }
        return array($aPathsToMinify, $aPathsToHash);
    }

    /**
     * Minifie l'ensemble de fichiers JS $aPaths en un seul dans $sDestDir.
     *
     * @param array $aPaths liste de fichiers JS à minifier
     * @param string $sJSParentDir répertoire source des CSS
     * @param string $sDestDir répertoire de destination
     * @param string $sImgOutPath sous-répertoire devant être inséré dans les URLs des images
     * @return int la taille cumulée en octets des fichiers générés
     */
    private function _minifyJS (array $aPaths, $sJSParentDir, $sDestDir, $sImgOutPath)
    {
        list($aPathsToMinify, $aPathsToHash) = $this->_buildPaths($aPaths, $sJSParentDir);

        $sHash = $this->_getHash(implode('', $aPathsToHash));
        $sPattern = $sDestDir . '/%s' . $sHash . '.js';

        $sTmpDestPath = sprintf($sPattern, 'tmp_');
        $this->_oMinifier->minify($aPathsToMinify, $sTmpDestPath);

        $iFilesSize = $this->_generateSubdomainsFiles($sPattern, $sTmpDestPath, $sImgOutPath);
        unlink($sTmpDestPath);
        return $iFilesSize;
    }

    /**
     * Minifie l'ensemble de fichiers CSS $aPaths en un seul dans $sDestDir.
     *
     * @param array $aPaths liste de fichiers CSS à minifier
     * @param string $sCSSParentDir répertoire source des CSS
     * @param string $sDestDir répertoire de destination
     * @param string $sImgOutPath sous-répertoire devant être inséré dans les URLs des images
     * @param string $sSrcTemplateFile fichier template (.tpl) cause de cet appel
     * @throws RuntimeException si l'un des CSS source est manquant
     * @return int la taille cumulée en octets des fichiers générés
     */
    private function _minifyCSS (array $aPaths, $sCSSParentDir, $sDestDir, $sImgOutPath, $sSrcTemplateFile)
    {
        list($aPathsToMinify, $aPathsToHash) = $this->_buildPaths($aPaths, $sCSSParentDir);

        $sHash = $this->_getHash(implode('', $aPathsToHash));
        $sPattern = $sDestDir . '/%s' . $sHash . '.css';

        $sTmpDestPath = sprintf($sPattern, 'tmp_');
        try {
            $this->_oMinifier->minify($aPathsToMinify, $sTmpDestPath);
        } catch (RuntimeException $oException) {
            $sErrorMsg = $oException->getMessage();
            if (strpos($sErrorMsg, 'File not found: ') !== 0) {
                throw $oException;
            } else {
                $this->_oLogger->log(
                    "[WARNING] In template '$sSrcTemplateFile'. $sErrorMsg Files '$sPattern' not generated.",
                    Logger_Interface::WARNING
                );
                return 1;
            }
        }

        $iFilesSize = $this->_generateSubdomainsFiles($sPattern, $sTmpDestPath, $sImgOutPath);
        unlink($sTmpDestPath);
        return $iFilesSize;
    }

    /**
     * Duplique le fichier minifié temporaire spécifié selon chaque sous-domaine de $this->_aSubDomains,
     * en prenant soin de redistribuer les URLs qu'il contient sur les serveurs de statique s0 et s1,
     * en accord avec les sous-domaines.
     *
     * @param string $sPattern pattern au sens sprintf() du chemin des fichiers à générer par sous-domaine.
     *     Il doit y avoir un '%s' ou '%1$s' pour insérer le sous-domaine.
     * @param string $sTmpDestPath chemin du fichier minifié temporaire à dupliquer
     * @param string $sImgOutPath sous-répertoire des images à insérer dans les nouvelles URLs d'images
     * @throws RuntimeException si la copie de la source en l'une des versions pour sous-domaine échoue
     * @see getNewImgURL()
     * @return int la taille cumulée en octets des fichiers générés
     */
    private function _generateSubdomainsFiles ($sPattern, $sTmpDestPath, $sImgOutPath)
    {
        $iFilesSize = 0;
        foreach ($this->_aSubDomains as $sSubDomain) {
            $sDestPath = sprintf($sPattern, $sSubDomain);

            $sContents = file_get_contents($sTmpDestPath);
            $sContents = preg_replace_callback(
                '#s0.c4tw.net/([^\'")]*)/([^\'")]*)\.(png|gif|jpg)#i',
                function (array $aMatches) use ($sSubDomain, $sImgOutPath)
                {
                    list(, $sDir, $sFilename, $sExtension) = $aMatches;

                    file_put_contents("/tmp/totola", $sDir.' : '.$sFilename.' : '.$sExtension."\r\n", FILE_APPEND);

                    return Minifier_TemplateMinifier::getNewImgURL(
                        $sDir, $sFilename, $sExtension, $sSubDomain, $sImgOutPath
                    );
                },
                $sContents
            );

	    // Fichier de destination déjà existant
	    if (file_exists($sDestPath)) {
                $iSizeOrigin = filesize($sDestPath);
                $iNewSize = strlen($sContents);
		
		// Fichier de différente taille
		if($iSizeOrigin != $iNewSize) 
                 $this->_oLogger->log(
                    "[WARNING] Duplicate ID for the file: '$sDestPath'. File not generated.",
                    Logger_Interface::WARNING
                );

                continue;
            }

            $iFileSize = file_put_contents($sDestPath, $sContents);
            $iFilesSize += $iFileSize;
        }
        return $iFilesSize;
    }

    /**
     * Extrait les URLs des blocs '{combine}' en les séparant selon qu'elles proviennent du JS ou du CSS.
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
     * @param string $sTemplateFile chemin de template (.tpl) à analyser
     * @return array couple de 2 tableaux, le premier listant les URLs extraites de code CSS, groupées par bloc
     * 		combine, le second listant les URLs extraites de code JS, groupées également par bloc combine.
     */
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
        if ($sDomain == 'cs') {
            $sNewImgPath = 'static.cycling-shopping.co.uk';
        } else {
            $sNewImgPath = 's' . (crc32($sFilename) % 2) . $sDomain . '.c4tw.net';
        }
        $sNewImgPath .= $sImgOutPath . '/' . $sDir . '/' . $sFilename . '.' . $sExtension;

        file_put_contents("/tmp/totola", $sNewImgPath."\r\n", FILE_APPEND);

        return $sNewImgPath;
    }

    /**
     * Retourne un hash de la chaîne spécifiée.
     * Utilisé pour générer un nom de fichier JS ou CSS à partir des fichiers le constituant.
     *
     * @param string $sString
     * @return string un hash de la chaîne spécifiée, compatible processeurs 64bits.
     */
    protected function _getHash ($sString)
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
