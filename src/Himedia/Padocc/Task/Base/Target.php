<?php

namespace Himedia\Padocc\Task\Base;

use Himedia\Padocc\AttributeProperties;
use Himedia\Padocc\DIContainer;
use Himedia\Padocc\Task\WithProperties;
use Himedia\Padocc\Task;

/**
 * Définit une section (factorisation) adressable via la tâche call.
 * À inclure dans une tâche project.
 *
 * Exemple : <call target="static_content" />...<target name="static_content">...</target>
 *
 * @author Geoffroy AUBRY <gaubry@hi-media.com>
 */
class Target extends WithProperties
{

    /**
     * Liste d'instances de type Task ordonnées constituant la cible.
     * @var Task[]
     */
    protected $aTasks;

    /**
     * Retourne le nom du tag XML correspondant à cette tâche dans les config projet.
     *
     * @return string nom du tag XML correspondant à cette tâche dans les config projet.
     */
    public static function getTagName ()
    {
        return 'target';
    }

    /**
     * Retourne la liste ordonnée des noeuds XML de type <externalproperty />, sous forme de SimpleXMLElement.
     * Les débusque également dans les noeuds <target /> au travers des <call />.
     *
     * @param \SimpleXMLElement $oSXEProject instance du projet
     * @param \SimpleXMLElement $oNode noeud à explorer à la recherche des propriétés externes
     * @return array la liste ordonnée des noeuds XML de type <externalproperty />, sous forme de SimpleXMLElement.
     * @throws \UnexpectedValueException si noeud <target /> non trouvé ou non unique mais référencé par un
     * 		noeud <call />.
     */
    private static function getSXEExternalProperties (\SimpleXMLElement $oSXEProject, \SimpleXMLElement $oNode)
    {
        // Récupération du nom des balises XML :
        $sExtPropertyTagName = ExternalProperty::getTagName();
        $sCallTagName = Call::getTagName();
        $sTargetTagName = Target::getTagName();

        $aSXEExtProperties = array();
        /* @var $oSXEChild \SimpleXMLElement */
        foreach ($oNode->children() as $oSXEChild) {
            $sName = (string)$oSXEChild->getName();
            if ($sName === $sCallTagName && isset($oSXEChild['target'])) {
                $sTargetName = (string)$oSXEChild['target'];
                $aTargets = $oSXEProject->xpath("//{$sTargetTagName}[@name='$sTargetName']");
                if (count($aTargets) !== 1) {
                    $sMsg = "Target '$sTargetName' not found or not unique in this project!";
                    throw new \UnexpectedValueException($sMsg);
                }
                $aSXEExtProperties = array_merge(
                    $aSXEExtProperties,
                    self::getSXEExternalProperties($oSXEProject, $aTargets[0])
                );
            } elseif ($sName === $sExtPropertyTagName) {
                $aSXEExtProperties[] = $oSXEChild;
            }
        }
        return $aSXEExtProperties;
    }

    /**
     * Retourne la liste des environnements et de leurs propriétés externes pour le projet spécifié.
     *
     * Exemple de retour : array(
     * 		'dev' => array(),
     * 		'qa' => array(
     * 			'ref' => "Branch or tag to deploy",
     * 			...
     * 		)
     * )
     *
     *
     * @param string $sXmlProjectConf chemin de configuration XML du projet à analyser
     * @return array la liste des environnements et de leurs propriétés externes pour le projet spécifié.
     * @throws \UnexpectedValueException si fichier XML du projet non trouvé
     * @throws \UnexpectedValueException si fichier XML du projet mal formaté
     * @throws \UnexpectedValueException si pas d'environnement trouvé
     * @throws \UnexpectedValueException si noeud <externalproperty /> invalide
     * @throws \UnexpectedValueException si noeud <target /> non trouvé ou non unique mais référencé par un
     * 		noeud <call />.
     */
    public static function getAvailableEnvsList ($sXmlProjectConf)
    {
        $oSXEProject = Project::getSXEProject($sXmlProjectConf);
        $aTargets = $oSXEProject->xpath("//env");
        if (count($aTargets) === 0) {
            throw new \UnexpectedValueException("No environment found in specified project!");
        }
        $aEnvsList = array();
        foreach ($aTargets as $oTarget) {
            $aSXEExtProperties = self::getSXEExternalProperties($oSXEProject, $oTarget);
            $aExtProperties = array();
            foreach ($aSXEExtProperties as $oSXEExtProperty) {
                if (! isset($oSXEExtProperty['name']) || ! isset($oSXEExtProperty['description'])) {
                    throw new \UnexpectedValueException("Invalid external property in specified project!");
                }
                $sName = (string)$oSXEExtProperty['name'];
                $sDesc = (string)$oSXEExtProperty['description'];
                $aExtProperties[$sName] = $sDesc;
            }

            $sEnvName = (string)$oTarget['name'];
            $aEnvsList[$sEnvName] = $aExtProperties;
        }

        return $aEnvsList;
    }

    /**
     * Constructeur.
     *
     * @param \SimpleXMLElement $oTask Contenu XML de la tâche.
     * @param Project $oProject Super tâche projet.
     * @param DIContainer $oDIContainer Register de services prédéfinis (ShellInterface, ...).
     */
    public function __construct (\SimpleXMLElement $oTask, Project $oProject,
        DIContainer $oDIContainer)
    {
        parent::__construct($oTask, $oProject, $oDIContainer);
        $this->aAttrProperties = array_merge(
            $this->aAttrProperties,
            array('name' => AttributeProperties::REQUIRED)
        );

        $this->oNumbering->addCounterDivision();
        $this->aTasks = $this->getTaskInstances($oTask, $this->oProject);
        $this->oNumbering->removeCounterDivision();
    }

    /**
     *
     * Enter description here ...
     * @var array
     * @see getAvailableTasks()
     */
    private static $aAvailableTasks = array();

    /**
     * Retourne un tableau associatif décrivant les tâches disponibles.
     *
     * @throws \RuntimeException si classe inexistante
     * @throws \LogicException si collision de nom de tag XML
     * @return array tableau associatif des tâches disponibles : array('sTag' => 'sClassName', ...)
     */
    private static function getAvailableTasks ()
    {
        if (count(self::$aAvailableTasks) === 0) {
            $aAvailableTasks = array();
            foreach (array('base', 'extended') as $sTaskType) {
                $sTaskPaths = glob(DEPLOYMENT_TASKS_DIR . "/$sTaskType/*.class.php");
                foreach ($sTaskPaths as $sTaskPath) {
                    $sClassName = strstr(substr(strrchr($sTaskPath, '/'), 1), '.', true);
                    $sFullClassName = __NAMESPACE__.'\\'.'Task_' . ucfirst($sTaskType) . '_' . $sClassName;

                    if(!class_exists($sFullClassName)){
                        throw new \RuntimeException($sFullClassName." doesn't exist!");
                    }

                    /* @var $sFullClassName Task */
                    $sTag = $sFullClassName::getTagName();
                    if (isset($aAvailableTasks[$sTag])) {
                        throw new \LogicException("Already defined task tag '$sTag' in '$aAvailableTasks[$sTag]'!");
                    } elseif ($sTag != 'project') {
                        $aAvailableTasks[$sTag] = $sFullClassName;
                    }
                }
            }
            self::$aAvailableTasks = $aAvailableTasks;
        }
        return self::$aAvailableTasks;
    }

    /**
     * Retourne la liste des instances de tâches correspondant à chacune des tâches XML devant être exécutée
     * à l'intérieur du noeud XML spécifié.
     *
     * @param \SimpleXMLElement $oTarget
     * @param Project $oProject
     * @return array liste d'instances de type Task
     * @throws \Exception si tag XML inconnu.
     * @see Task
     */
    private function getTaskInstances (\SimpleXMLElement $oTarget, Project $oProject)
    {
        $this->oLogger->info("Initialize tasks of target '" . $this->aAttValues['name'] . "'.");
        $aAvailableTasks = self::getAvailableTasks();

        // Mise à plat des tâches car SimpleXML regroupe celles successives de même nom
        // dans un tableau et les autres sont hors tableau :
        $aTasks = array();
        foreach ($oTarget->children() as $sTag => $mTasks) {
            if (is_array($mTasks)) {
                foreach ($mTasks as $oTask) {
                    $aTasks[] = array($sTag, $oTask);
                }
            } else {
                $aTasks[] = array($sTag, $mTasks);
            }
        }

        // Création des instances de tâches :
        $aTaskInstances = array();
        foreach ($aTasks as $aTask) {
            list($sTag, $oTask) = $aTask;
            if (! isset($aAvailableTasks[$sTag])) {
                throw new \UnexpectedValueException("Unkown task tag: '$sTag'!");
            } else {
                $aTaskInstances[] = new $aAvailableTasks[$sTag]($oTask, $oProject, $this->oDIContainer);
            }
        }

        return $aTaskInstances;
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
     * @throws \DomainException en cas de valeur non permise
     */
    public function check ()
    {
        parent::check();

        if (! empty($this->aAttValues['mailto'])) {
            $aSplittedValues = preg_split(
                AttributeProperties::$sMultiValuedSep,
                trim($this->aAttValues['mailto']),
                -1,
                PREG_SPLIT_NO_EMPTY
            );
            $this->aAttValues['mailto'] = implode(' ', $aSplittedValues);
        }
    }

    /**
     * Prépare la tâche avant exécution : vérifications basiques, analyse des serveurs concernés...
     */
    public function setUp ()
    {
        parent::setUp();
        $this->oLogger->info('+++');
        foreach ($this->aTasks as $oTask) {
            $oTask->setUp();
        }
        $this->oLogger->info('---');
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
        if (! empty($this->aAttValues['mailto'])) {
            $this->oLogger->info('+++[MAILTO] ' . $this->aAttValues['mailto'] . '---');
        }
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
        $this->oLogger->info('+++');
        foreach ($this->aTasks as $oTask) {
            $oTask->execute();
        }
        $this->oLogger->info('---');
    }
}
