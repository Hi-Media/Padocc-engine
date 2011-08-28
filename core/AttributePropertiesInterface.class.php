<?php

/**
 * Collection des propriétés possibles pour un attribut de tâche.
 * Ces propriétés sont manipulées au sein de champs de bits dans la classe Task.
 *
 * @category TwengaDeploy
 * @package Core
 * @author Geoffroy AUBRY
 * @see Task::$_aAttributeProperties()
 */
interface AttributePropertiesInterface
{
    /**
     * Propriété d'attribut : autorise l'utilisation des '${parameter}'.
     * @var int
     */
    const ATTRIBUTE_ALLOW_PARAMETER = 1;

    /**
     * Propriété d'attribut : l'attribut désigne un répertoire.
     * @var int
     */
    const ATTRIBUTE_DIR = 2;

    /**
     * Propriété d'attribut : autorise l'utilisation des jokers shell ? et * pour les répertoires.
     * @var int
     */
    const ATTRIBUTE_DIRJOKER = 4;

    /**
     * Propriété d'attribut : l'attribut désigne un fichier.
     * @var int
     */
    const ATTRIBUTE_FILE = 8;

    /**
     * Propriété d'attribut : autorise l'utilisation des jokers shell ? et * pour les fichiers.
     * @var int
     */
    const ATTRIBUTE_FILEJOKER = 16;

    /**
     * Propriété d'attribut : l'attribut est obligatoire.
     * @var int
     */
    const ATTRIBUTE_REQUIRED = 32;

    /**
     * Propriété d'attribut : l'attribut est un fichier ou un répertoire source et doit donc exister.
     * @var int
     */
    const ATTRIBUTE_SRC_PATH = 64;

    /**
     * Propriété d'attribut : l'attribut est un booléen sous forme de chaîne de caractères,
     * valant soit 'true' soit 'false'.
     * @var int
     */
    const ATTRIBUTE_BOOLEAN = 128;
}
