<?php

namespace Himedia\Padocc\Minifier;

use GAubry\Shell\ShellAdapter;

/**
 * Compresser les fichiers JS et CSS.
 *
 *
 *
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
 * @license Apache License, Version 2.0
 */
class JSMinAdapter implements MinifierInterface
{

    /**
     * Shell adapter.
     *
     * @var ShellAdapter
     * @see minifyJS()
     */
    private $oShell;

    /**
     * Chemin du binaire JSMin
     *
     * @var string
     * @see minifyJS()
     */
    private $sBinPath;

    /**
     * Constructeur.
     *
     * @param string $sJSMinBinPath chemin du binaire JSMin
     * @param ShellAdapter $oShell instance utilisée pour exécuter le binaire jsmin
     */
    public function __construct($sJSMinBinPath, ShellAdapter $oShell)
    {
        $this->sBinPath = $sJSMinBinPath;
        $this->oShell = $oShell;
    }

    /**
     * Minifie la liste de fichiers JS ou CSS spécifiée et enregistre le résultat dans $sDestPath.
     *
     * @param array $aSrcPaths liste de fichiers se finissant tous par '.js', ou tous par '.css'
     * @param string $sDestPath chemin/fichier dans lequel enregistrer le résultat du minify
     * @return MinifierInterface $this
     * @throws \BadMethodCallException si $aSrcPaths vide
     * @throws \UnexpectedValueException si les sources n'ont pas toutes la même extension de fichier
     * @throws \UnexpectedValueException si la destination est un CSS quand les sources sont des JS ou inversement
     * @throws \DomainException si des fichiers ne se terminent ni par '.js', ni par '.css'
     */
    public function minify(array $aSrcPaths, $sDestPath)
    {
        if (count($aSrcPaths) === 0) {
            throw new \BadMethodCallException('Source files missing!');
        }

        // Est-ce que les fichiers en entrée sont tous des JS ou tous des CSS ?
        $sFirstExtension = strrchr(reset($aSrcPaths), '.');
        foreach ($aSrcPaths as $sSrcPath) {
            $sExtension = strrchr($sSrcPath, '.');
            if ($sExtension !== $sFirstExtension) {
                throw new \UnexpectedValueException('All files must be either JS or CSS: ' . print_r($aSrcPaths, true));
            }
        }

        // La destination est-elle en accord avec les entrées ?
        if (strrchr($sDestPath, '.') !== $sFirstExtension) {
            $sMsg = "Destination file must be same type of input files: '$sDestPath' : Src :"
                  . print_r($aSrcPaths, true);
            throw new \UnexpectedValueException($sMsg);
        }

        // On redirige vers le service idoine :
        switch ($sFirstExtension) {
            case '.js':
                $this->minifyJS($aSrcPaths, $sDestPath);
                break;

            case '.css':
                $this->minifyCSS($aSrcPaths, $sDestPath);
                break;

            default:
                $sMsg = "All specified paths must finish either by '.js' or '.css': '$sFirstExtension'!";
                throw new \DomainException($sMsg);
                break;
        }

        return $this;
    }

    /**
     * Minifie la liste des fichiers JS spécifiée et enregistre le résultat dans $sDestPath.
     *
     * @param array $aSrcPaths liste de fichiers se finissant tous par '.js'
     * @param string $sDestPath chemin/fichier dans lequel enregistrer le résultat du minify
     * @throws \RuntimeException en cas d'erreur shell
     */
    protected function minifyJS(array $aSrcPaths, $sDestPath)
    {
        $sHeader = $this->getHeader($aSrcPaths);
        $sCmd = 'cat';
        foreach ($aSrcPaths as $sSrcPath) {
            $sCmd .= ' ' . $this->oShell->escapePath($sSrcPath);
        }
        $sCmd .= " | $this->sBinPath >'$sDestPath' && sed --in-place '1i$sHeader' '$sDestPath'";
        $this->oShell->exec($sCmd);
    }

    /**
     * Minifie la liste des fichiers CSS spécifiée et enregistre le résultat dans $sDestPath.
     *
     * @param array $aSrcPaths liste de fichiers se finissant tous par '.css'
     * @param string $sDestPath chemin/fichier dans lequel enregistrer le résultat du minify
     * @throws \RuntimeException si l'un des fichiers est introuvable
     */
    protected function minifyCSS(array $aSrcPaths, $sDestPath)
    {
        $sContent = $this->getContent($aSrcPaths);

        // remove comments:
        $sContent = preg_replace('#/\*[^*]*\*+([^/][^*]*\*+)*/#', '', $sContent);

        // remove tabs, spaces, newlines, etc.
        $sContent = str_replace(array("\r" , "\n" , "\t"), '', $sContent);
        $sContent = str_replace(array('    ' , '   ' , '  '), ' ', $sContent);

        $sContent = $this->getHeader($aSrcPaths) . $sContent;
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
    private function getHeader(array $aSrcPaths)
    {
        if (count($aSrcPaths) === 1) {
            $sHeader = "/* Contains: " . reset($aSrcPaths) . ' */' . "\n";
        } else {
            $sCommonPrefix = $this->getLargestCommonPrefix($aSrcPaths);
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
    private function getLargestCommonPrefix(array $aStrings)
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
     * @throws \RuntimeException si l'un des fichiers est introuvable
     * @see minifyCSS()
     */
    private function getContent(array $aSrcPaths)
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
            } catch (\Exception $oException) {
                throw new \RuntimeException("File not found: '$sPath'!", 1, $oException);
            }
        }
        return $sContent;
    }
}
