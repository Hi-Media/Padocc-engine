<?php

namespace Himedia\Padocc\Task\Base;

use Himedia\Padocc\AttributeProperties;
use Himedia\Padocc\DIContainerInterface;
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
     * Tâche appelée.
     * @var Environment
     */
    private $oBoundTask;

    /**
     * @var string Selected environment.
     */
    public $sEnvName;

    /**
     * Constructor.
     *
     * @param string               $sXmlProject  XML project path or XML data
     * @param string               $sEnvName     Selected environment.
     * @param DIContainerInterface $oDIContainer Service container.
     *
     * @throws \UnexpectedValueException si fichier XML du projet non trouvé
     * @throws \UnexpectedValueException si environnement non trouvé ou non unique
     */
    public function __construct ($sXmlProject, $sEnvName, DIContainerInterface $oDIContainer)
    {
        $this->sEnvName = $sEnvName;

        $oSXEProject = self::getSXEProject($sXmlProject);

        parent::__construct($oSXEProject, $this, $oDIContainer);
    }

    /**
     * {@inheritdoc}
     *
     * @throws \UnexpectedValueException si fichier XML du projet non trouvé
     * @throws \UnexpectedValueException si environnement non trouvé ou non unique
     */
    protected function init()
    {
        parent::init();

        $this->aAttrProperties = array_merge(
            $this->aAttrProperties,
            array('name' => AttributeProperties::REQUIRED)
        );

        // Crée une instance de la tâche environnement appelée :
        $aTargets = $this->oProject->getSXE()->xpath("env[@name='" . $this->sEnvName . "']");
        if (count($aTargets) !== 1) {
            throw new \UnexpectedValueException("Environment '" . $this->sEnvName . "' not found or not unique in this project!");
        }

        $this->oBoundTask = new Environment($aTargets[0], $this->oProject, $this->oDIContainer);
    }

    /**
     * {@inheritdoc}
     * @codeCoverageIgnore
     */
    public static function getTagName ()
    {
        return 'project';
    }

    /**
     * Retourne une instance SimpleXMLElement du projet spécifié.
     *
     * @param string $sXmlProject XML project path or XML data
     * @throws \UnexpectedValueException si XML du projet mal formaté
     * @return \SimpleXMLElement instance du projet spécifié
     */
    public static function getSXEProject ($sXmlProject)
    {
        $bIsURL = (substr($sXmlProject, 0, 5) != '<?xml');
        try {
            $oSXE = new \SimpleXMLElement($sXmlProject, null, $bIsURL);
        } catch (\Exception $oException) {
            throw new \UnexpectedValueException("Bad project definition: '$sXmlProject'", 1, $oException);
        }
        return $oSXE;
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
        $this->getLogger()->info('+++');
        foreach ($this->aAttValues as $sAttribute => $sValue) {
            if (! empty($sValue) && $sAttribute !== 'name') {
                $this->getLogger()->info("Attribute: $sAttribute = '$sValue'");
            }
        }
        $this->getLogger()->info('---');
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
     * Appelé par execute().
     * @see execute()
     */
    protected function preExecute ()
    {
        parent::preExecute();
        $this->getLogger()->info('+++');
        $this->oShell->mkdir($this->oProperties->getProperty('tmpdir'));
        $this->getLogger()->info('---');
    }

    /**
     * Phase de traitements centraux de l'exécution de la tâche.
     * Elle devrait systématiquement commencer par "parent::centralExecute();".
     * Appelé par execute().
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
     * Appelé par execute().
     * @see execute()
     */
    protected function postExecute()
    {
        $this->getLogger()->info('+++');
        $this->oShell->remove($this->oProperties->getProperty('tmpdir'));
        $this->getLogger()->info('---');
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
