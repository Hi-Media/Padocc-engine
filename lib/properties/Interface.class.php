<?php

interface Properties_Interface
{

    /**
     * Retourne la valeur de la propriété spécifiée.
     *
     * @param string $sPropertyName propriété dont on recherche la valeur
     * @return string valeur de la propriété spécifiée.
     */
    public function getProperty ($sPropertyName);

    /**
     * Initialise ou met à jour la valeur de la propriété spécifiée.
     *
     * @param string $sPropertyName propriété
     * @param string $sValue
     * @return Properties_Interface cette instance
     */
    public function setProperty ($sPropertyName, $sValue);

    /**
     * Charge le fichier INI spécifié en ajoutant ou écrasant ses définitions aux propriétés existantes.
     *
     * @param string $sIniPath path du fichier INI à charger
     * @return Properties_Interface cette instance
     */
    public function loadConfigIniFile ($sIniPath);

    /**
     * Charge le fichier shell spécifié en ajoutant ou écrasant ses définitions aux propriétés existantes.
     *
     * Format du fichier :
     *    PROPRIETE_1="chaîne"
     *    PROPRIETE_2="chaîne $PROPRIETE_1 chaîne"
     *    ...
     *
     * @param string $sConfigShellPath path du fichier shell à charger
     * @return Properties_Interface cette instance
     */
    public function loadConfigShellFile ($sConfigShellPath);
}
