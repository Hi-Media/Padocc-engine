<?php

/**
 * Compresser les fichiers JS et CSS.
 *
 * @category TwengaDeploy
 * @package Lib
 * @author Geoffroy AUBRY <geoffroy.aubry@twenga.com>
 */
interface Minifier_Interface
{

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
    public function minify (array $aSrcPaths, $sDestPath);
}
