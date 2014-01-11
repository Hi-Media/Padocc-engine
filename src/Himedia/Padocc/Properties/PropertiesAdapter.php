<?php

namespace Himedia\Padocc\Properties;

use GAubry\Shell\ShellAdapter;

/**
 * Gestionnaire de propriétés (table de hashage).
 *
 * Le nom des propriétés est insensible à la casse.
 * Sait charger les fichiers de configuration PHP au format INI.
 * Sait également charger les fichiers de configuration shell (qui acceptent la factorisation) au format suivant :
 *    PROPRIETE_1="chaîne"
 *    PROPRIETE_2="chaîne $PROPRIETE_1 chaîne"
 *
 * @author Geoffroy AUBRY <gaubry@hi-media.com>
 */
class PropertiesAdapter implements PropertiesInterface
{

    /**
     * Table de hashage des propriétés (clé => valeur).
     * @var array
     */
    private $aProperties;

    /**
     * Shell adapter.
     *
     * @var ShellAdapter
     * @see loadConfigShellFile()
     */
    private $oShell;

    /**
     * Application config.
     *
     * @var array
     */
    private $aConfig;

    /**
     * Constructeur.
     *
     * @param ShellAdapter $oShell instance utilisée pour charger les fichiers de configuration shell
     * @param array $aConfig
     */
    public function __construct (ShellAdapter $oShell, array $aConfig)
    {
        $this->aProperties = array();
        $this->oShell = $oShell;
        $this->aConfig = $aConfig;
    }

    /**
     * Retourne la valeur de la propriété spécifiée (insensible à la casse).
     *
     * @param string $sPropertyName propriété dont on recherche la valeur
     * @return string valeur de la propriété spécifiée.
     * @throws \UnexpectedValueException si propriété inconnue
     */
    public function getProperty ($sPropertyName)
    {
        if (! isset($this->aProperties[strtolower($sPropertyName)])) {
            throw new \UnexpectedValueException("Unknown property '$sPropertyName'!");
        }
        return $this->aProperties[strtolower($sPropertyName)];
    }

    /**
     * Initialise ou met à jour la valeur de la propriété spécifiée (insensible à la casse).
     *
     * @param string $sPropertyName propriété
     * @param string $sValue
     * @return PropertiesInterface $this
     */
    public function setProperty ($sPropertyName, $sValue)
    {
        $this->aProperties[strtolower($sPropertyName)] = (string)$sValue;
        return $this;
    }

    /**
     * Charge le fichier INI spécifié en ajoutant ou écrasant ses définitions aux propriétés existantes.
     * Le nom des propriétés sont insensibles à la casse.
     *
     * @param string $sIniPath path du fichier INI à charger
     * @return PropertiesInterface cette instance
     * @throws \RuntimeException si erreur de chargement du fichier INI
     * @throws \UnexpectedValueException si fichier INI introuvable
     */
    public function loadConfigIniFile ($sIniPath)
    {
        if (! file_exists($sIniPath)) {
            throw new \UnexpectedValueException("Property file '$sIniPath' not found!");
        }

        try {
            $aRawProperties = parse_ini_file($sIniPath);
        } catch (\ErrorException $oException) {
            throw new \RuntimeException("Load property file '$sIniPath' failed: " . $oException->getMessage());
        }

        // Normalisation :
        $aProperties = array();
        foreach ($aRawProperties as $sProperty => $sValue) {
            $aProperties[strtolower($sProperty)] = $sValue;
        }

        $this->aProperties = array_merge($this->aProperties, $aProperties);
        return $this;
    }

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
     * @return PropertiesInterface cette instance
     * @throws \RuntimeException si erreur de chargement du fichier
     * @throws \UnexpectedValueException si fichier shell introuvable
     */
    public function loadConfigShellFile ($sConfigShellPath)
    {
        if (! file_exists($sConfigShellPath)) {
            throw new \UnexpectedValueException("Property file '$sConfigShellPath' not found!");
        }
        $sConfigIniPath = tempnam($this->aConfig['dir']['tmp'], 'deploy_configshell2ini_');
        $sCmd = $this->aConfig['bash_path'] . ' ' . $this->aConfig['dir']['inc']
              . "/cfg2ini.sh '$sConfigShellPath' '$sConfigIniPath'";
        $this->oShell->exec($sCmd);
        $this->loadConfigIniFile($sConfigIniPath);
        unlink($sConfigIniPath);
        return $this;
    }
}
