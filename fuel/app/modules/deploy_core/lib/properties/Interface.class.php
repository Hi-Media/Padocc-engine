<?php

/**
 * Gestionnaire de propriétés (table de hashage).
 * Le nom des propriétés est insensible à la casse.
 * Sait charger les fichiers de configuration PHP au format INI.
 * Sait également charger les fichiers de configuration shell (qui acceptent la factorisation) au format suivant :
 *    PROPRIETE_1="chaîne"
 *    PROPRIETE_2="chaîne $PROPRIETE_1 chaîne"
 *
 * @category TwengaDeploy
 * @package Lib
 * @author Geoffroy AUBRY <geoffroy.aubry@twenga.com>
 */
interface Properties_Interface
{

    /**
     * Retourne la valeur de la propriété spécifiée (insensible à la casse).
     *
     * @param string $sPropertyName propriété dont on recherche la valeur
     * @return string valeur de la propriété spécifiée.
     * @throws UnexpectedValueException si propriété inconnue
     */
    public function getProperty ($sPropertyName);

    /**
     * Initialise ou met à jour la valeur de la propriété spécifiée (insensible à la casse).
     *
     * @param string $sPropertyName propriété
     * @param string $sValue
     * @return Properties_Interface $this
     */
    public function setProperty ($sPropertyName, $sValue);

    /**
     * Charge le fichier INI spécifié en ajoutant ou écrasant ses définitions aux propriétés existantes.
     * Le nom des propriétés sont insensibles à la casse.
     *
     * @param string $sIniPath path du fichier INI à charger
     * @return Properties_Interface cette instance
     * @throws RuntimeException si erreur de chargement du fichier INI
     * @throws UnexpectedValueException si fichier INI introuvable
     */
    public function loadConfigIniFile ($sIniPath);

    /**
     * Charge le fichier shell spécifié en ajoutant ou écrasant ses définitions aux propriétés existantes.
     * Le nom des propriétés sont insensibles à la casse.
     *
     * Format du fichier :
     *    PROPRIETE_1="chaîne"
     *    PROPRIETE_2="chaîne $PROPRIETE_1 chaîne"
     *    ...
     *
     * @param string $sConfigShellPath path du fichier shell à charger
     * @return Properties_Interface cette instance
     * @throws RuntimeException si erreur de chargement du fichier
     * @throws UnexpectedValueException si fichier shell introuvable
     */
    public function loadConfigShellFile ($sConfigShellPath);
}
