<?php

namespace Himedia\Padocc\Task\Base;

use Himedia\Padocc\AttributeProperties;
use Himedia\Padocc\DIContainer;
use Himedia\Padocc\Task\WithProperties;

/**
 * Tâche mère d'un fichier XML.
 * Contient des tags env ou target.
 *
 * Attribut 'name' doit être identique au nom contenu dans la configuration XML.
 *
 * Exemple : <project name="rts">...</project>
 *
 * @author Original Author Geoffroy AUBRY <gaubry@hi-media.com>
 * @author Another Author Tony Caron <caron.tony@gmail.com>
 */
class Project extends WithProperties
{

    /**
     * Retourne la liste des projets dont le fichier de déploiement XML se trouve dans le chemin spécifié.
     * La liste est triée par ordre alphabétique.
     *
     * @param string $sRessourcesPath chemin hébergeant des configurations de déploiement au format XML
     * @return array la liste des projets dont le fichier de déploiement XML se trouve dans le chemin spécifié.
     * @throws \UnexpectedValueException si chemin non trouvé
     * @throws \UnexpectedValueException si fichier XML mal formaté
     */
    public static function getAllProjectsName ($sRessourcesPath)
    {
        $aProjectNames = array();
        // TODO enlever @
        $rHandle = @opendir($sRessourcesPath);
        if ($rHandle === false) {
            throw new \UnexpectedValueException("Resource path not found: '$sRessourcesPath'.");
        } else {
            while ($file = readdir($rHandle)) {
                clearstatcache();
                $sProjectPath = $sRessourcesPath . '/' . $file;
                if (substr($file, strlen($file)-4, 4) == '.xml' && is_file($sProjectPath)) {
                    try {
                        $oProject = new \SimpleXMLElement($sProjectPath, null, true);
                    } catch (\Exception $oException) {
                        throw new \UnexpectedValueException("Bad project definition: '$sProjectPath'", 1, $oException);
                    }
                    if (isset($oProject['name'])) {
                        $aProjectNames[] = (string)$oProject['name'];
                    }
                }
            }
            closedir($rHandle);
        }
        sort($aProjectNames);
        return $aProjectNames;
    }

    /**
     * Retourne une instance SimpleXMLElement du projet spécifié.
     *
     * @param string $sXmlConfiguration $sXmlConfiguration string XML de la configuration du projet
     * @throws \UnexpectedValueException si fichier XML du projet mal formaté
     * @return \SimpleXMLElement instance du projet spécifié
     */
    public static function getSXEProject ($sXmlConfiguration)
    {
        try {
            $oSXE = new \SimpleXMLElement($sXmlConfiguration, null);
        } catch (\Exception $oException) {
            throw new \UnexpectedValueException("Bad project definition: '$sXmlConfiguration'", 1, $oException);
        }
        return $oSXE;
    }

    /**
     * Tâche appelée.
     * @var Environment
     */
    private $oBoundTask;

    /**
     * Retourne le nom du tag XML correspondant à cette tâche dans les config projet.
     *
     * @return string nom du tag XML correspondant à cette tâche dans les config projet.
     */
    public static function getTagName ()
    {
        return 'project';
    }

    /**
     * Constructeur.
     *
     * @param string $sXmlConfiguration string XML de la configuration du projet
     * @param string $sEnvName Environnement sélectionné.
     * @param DIContainer $oDIContainer Register de services prédéfinis (ShellInterface, ...).
     * @throws \UnexpectedValueException si fichier XML du projet non trouvé
     * @throws \UnexpectedValueException si environnement non trouvé ou non unique
     */
    public function __construct ($sXmlConfiguration, $sEnvName, DIContainer $oDIContainer)
    {
        $oSXEProject = self::getSXEProject($sXmlConfiguration);
        $this->sEnvName = $sEnvName;

        parent::__construct($oSXEProject, $this, $oDIContainer);
        $this->aAttrProperties = array_merge(
            $this->aAttrProperties,
            array('name' => AttributeProperties::REQUIRED)
        );

        // Crée une instance de la tâche environnement appelée :
        $aTargets = $this->oProject->getSXE()->xpath("env[@name='$sEnvName']");
        if (count($aTargets) !== 1) {
            throw new \UnexpectedValueException("Environment '$sEnvName' not found or not unique in this project!");
        }

        $this->oBoundTask = new Environment($aTargets[0], $this->oProject, $this->oDIContainer);
    }

    /**
     * Vérifie au moyen de tests basiques que la tâche peut être exécutée.
     * Lance une exception si tel n'est pas le cas.
     *
     * Comme toute les tâches sont vérifiées avant que la première ne soit exécutée,
     * doit permettre de remonter au plus tôt tout dysfonctionnement.
     * Appelé avant la méthode execute().
     *
     * @throws \UnexpectedValueException en cas d'attribut ou fichier manquant
     * @throws \DomainException en cas d'attribut non permis
     * @see self::$aAttributeProperties
     */
    public function check()
    {
        parent::check();
        $this->oLogger->info('+++');
        foreach ($this->aAttributes as $sAttribute => $sValue) {
            if (! empty($sValue) && $sAttribute !== 'name') {
                $this->oLogger->info("Attribute: $sAttribute = '$sValue'");
            }
        }
        $this->oLogger->info('---');
    }

    /**
     * Prépare la tâche avant exécution : vérifications basiques, analyse des serveurs concernés...
     */
    public function setUp ()
    {
        parent::setUp();
        $this->oBoundTask->setUp();
    }

    /**
     * Phase de pré-traitements de l'exécution de la tâche.
     * Elle devrait systématiquement commencer par "parent::preExecute();".
     * Appelé par _execute().
     * @see execute()
     */
    protected function preExecute ()
    {
        parent::preExecute();
        $this->oLogger->info('+++');
        $this->oShell->mkdir($this->oProperties->getProperty('tmpdir'));
        $this->oLogger->info('---');
    }

    /**
     * Phase de traitements centraux de l'exécution de la tâche.
     * Elle devrait systématiquement commencer par "parent::centralExecute();".
     * Appelé par _execute().
     * @see execute()
     */
    protected function centralExecute ()
    {
        parent::centralExecute();
        $this->oBoundTask->execute();
    }

    /**
     * Phase de post-traitements de l'exécution de la tâche.
     * Elle devrait systématiquement finir par "parent::postExecute();".
     * Appelé par _execute().
     * @see execute()
     */
    protected function postExecute()
    {
        $this->oLogger->info('+++');
        $this->oShell->remove($this->oProperties->getProperty('tmpdir'));
        $this->oLogger->info('---');
        parent::postExecute();
    }

    /**
     * Retourne le contenu XML de la tâche.
     * @return \SimpleXMLElement le contenu XML de la tâche.
     */
    public function getSXE ()
    {
        return $this->oXMLTask;
    }
}
