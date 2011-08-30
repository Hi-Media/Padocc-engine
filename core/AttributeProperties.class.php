<?php

/**
 * Collection des propriétés possibles pour un attribut de tâche.
 * Ces propriétés sont manipulées au sein de champs de bits dans la classe Task.
 *
 * @category TwengaDeploy
 * @package Core
 * @author Geoffroy AUBRY <geoffroy.aubry@twenga.com>
 * @see Task::$_aAttributeProperties()
 */
final class AttributeProperties
{
    /**
     * Propriété d'attribut : autorise l'utilisation des '${parameter}'.
     * @var int
     */
    const ALLOW_PARAMETER = 1;

    /**
     * Propriété d'attribut : l'attribut désigne un répertoire.
     * @var int
     */
    const DIR = 2;

    /**
     * Propriété d'attribut : autorise l'utilisation des jokers shell ? et * pour les répertoires.
     * @var int
     */
    const DIRJOKER = 4;

    /**
     * Propriété d'attribut : l'attribut désigne un fichier.
     * @var int
     */
    const FILE = 8;

    /**
     * Propriété d'attribut : autorise l'utilisation des jokers shell ? et * pour les fichiers.
     * @var int
     */
    const FILEJOKER = 16;

    /**
     * Propriété d'attribut : l'attribut est obligatoire.
     * @var int
     */
    const REQUIRED = 32;

    /**
     * Propriété d'attribut : l'attribut est un fichier ou un répertoire source et doit donc exister.
     * @var int
     */
    const SRC_PATH = 64;

    /**
     * Propriété d'attribut : l'attribut est un booléen sous forme de chaîne de caractères,
     * valant soit 'true' soit 'false'.
     * @var int
     */
    const BOOLEAN = 128;

    /**
     * Classe de constantes non instanciable.
     */
    private function __construct()
    {
    }
}
