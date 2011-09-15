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
        $sHeader = $this->_getHeader($aSrcPaths);
        $sCmd = 'cat';
        foreach ($aSrcPaths as $sSrcPath) {
            $sCmd .= ' ' . $this->_oShell->escapePath($sSrcPath);
        }
        $sCmd .= " | $this->_sBinPath >'$sDestPath' && sed --in-place '1i$sHeader' '$sDestPath'";
        $this->_oShell->exec($sCmd);
    }

    /**
     * Minifie la liste des fichiers CSS spécifiée et enregistre le résultat dans $sDestPath.
     *
     * @param array $aSrcPaths liste de fichiers se finissant tous par '.css'
     * @param string $sDestPath chemin/fichier dans lequel enregistrer le résultat du minify
     * @throws RuntimeException si l'un des fichiers est introuvable
     */
    protected function _minifyCSS (array $aSrcPaths, $sDestPath)
    {
        $sContent = $this->_getContent($aSrcPaths);

        // remove comments:
        $sContent = preg_replace('#/\*[^*]*\*+([^/][^*]*\*+)*/#', '', $sContent);

        // remove tabs, spaces, newlines, etc.
        $sContent = str_replace(array("\r" , "\n" , "\t"), '', $sContent);
        $sContent = str_replace(array('    ' , '   ' , '  '), ' ', $sContent);

        $sContent = $this->_getHeader($aSrcPaths) . $sContent;
        file_put_contents($sDestPath, $sContent);
    }

    /**
     * Retourne une ligne de commentaire, à insérer en 1re ligne d'un fichier CSS ou JS minifié,
     * énumérant tous les fichiers sources le constituant.
     *
     * Par exemple :
     * "/* Contains: /home/resources/a.css *[slash]\n"
     * "/* Contains (basedir='/path/to/resources/'): a.txt, b.txt *[slash]\n"
     *
     * @param array $aSrcPaths liste de fichiers sources
     * @return string une ligne de commentaire, à insérer en 1re ligne d'un fichier CSS ou JS minifié,
     * énumérant tous les fichiers sources le constituant.
     */
    private function _getHeader (array $aSrcPaths)
    {
        if (count($aSrcPaths) === 1) {
            $sHeader = "/* Contains: " . reset($aSrcPaths) . ' */' . "\n";
        } else {
            $sCommonPrefix = $this->_getLargestCommonPrefix($aSrcPaths);
            $iPrefixLength = strlen($sCommonPrefix);
            $aShortPaths = array();
            foreach ($aSrcPaths as $sSrcPath) {
                $aShortPaths[] = substr($sSrcPath, $iPrefixLength);
            }
            $sHeader = "/* Contains (basedir='$sCommonPrefix'): " . implode(', ', $aShortPaths) . ' */' . "\n";
        }
        return $sHeader;
    }

    /**
     * Retourne le plus long préfixe commun aux chaînes fournies.
     *
     * @param array $aStrings liste de chaînes à comparer
     * @return string le plus long préfixe commun aux chaînes fournies.
     * @see http://stackoverflow.com/questions/1336207/finding-common-prefix-of-array-of-strings/1336357#1336357
     */
    private function _getLargestCommonPrefix (array $aStrings)
    {
        // take the first item as initial prefix:
        $sPrefix = array_shift($aStrings);
        $iLength = strlen($sPrefix);

        // compare the current prefix with the prefix of the same length of the other items
        foreach ($aStrings as $sItem) {

            // check if there is a match; if not, decrease the prefix by one character at a time
            while ($iLength > 0 && substr($sItem, 0, $iLength) !== $sPrefix) {
                $iLength--;
                $sPrefix = substr($sPrefix, 0, -1);
            }

            if ($iLength === 0) {
                break;
            }
        }

        return $sPrefix;
    }

    /**
     * Retourne la concaténation du contenu des fichiers spécifiés.
     *
     * @param array $aSrcPaths liste de chemins dont on veut concaténer le contenu
     * @return string la concaténation du contenu des fichiers spécifiés.
     * @throws RuntimeException si l'un des fichiers est introuvable
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
            try {
                $sContent .= file_get_contents($sPath);
            } catch (Exception $oException) {
                throw new RuntimeException("File not found: '$sPath'!", 1, $oException);
            }
        }
        return $sContent;
    }
}
