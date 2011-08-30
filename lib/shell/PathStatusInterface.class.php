<?php

/**
 * Collection des statuts possibles pour un chemin du système de fichiers.
 *
 * @category TwengaDeploy
 * @package Lib
 * @author Geoffroy AUBRY <geoffroy.aubry@twenga.com>
 * @see Shell_Interface::getPathStatus()
 */
interface Shell_PathStatusInterface
{
    /**
     * Le chemin n'existe pas.
     * @var int
     */
    const STATUS_NOT_EXISTS = 0;

    /**
     * Le chemin est un fichier.
     * @var int
     */
    const STATUS_FILE = 1;

    /**
     * Le chemin est un répertoire.
     * @var int
     */
    const STATUS_DIR = 2;

    /**
     * Le chemin est un lien symbolique pointant sur un fichier.
     * @var int
     */
    const STATUS_SYMLINKED_FILE = 11;

    /**
     * Le chemin est un lien symbolique pointant sur un répertoire.
     * @var int
     */
    const STATUS_SYMLINKED_DIR = 12;
}
