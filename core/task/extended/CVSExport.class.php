<?php

/**
 * Exporte tout ou partie du contenu d'un dépôt CVS vers une ou plusieurs destinations.
 * À inclure dans une tâche env ou target.
 *
 * Exploite le script DEPLOYMENT_LIB_DIR . '/cvsexport.inc.sh'.
 * Réalise la synchronisation à l'aide d'une tâche ''sync''.
 *
 * Attributs :
 * - 'repository'
 * - 'module' : sous-répertoire du dépôt qui nous intéresse
 * - 'src' : lieu temporaire d'extraction du contenu qui nous intéresse du dépôt avant de l'envoyer
 *   vers la destination => laisser à vide de manière générale,
 *   l'outil utilisera alors le répertoire DEPLOYMENT_REPOSITORIES_DIR
 * - 'dest'
 *
 * Exemple :
 * <cvsexport repository=":extssh:gaubry@fs1.twenga.com:/home/cvsroot"
 *     module="twengaweb/common" destdir="${SERVERS}:${COMMONDIR}" />
 *
 * @category TwengaDeploy
 * @package Core
 * @author Geoffroy AUBRY <geoffroy.aubry@twenga.com>
 */
class Task_Extended_CVSExport extends Task
{

    /**
     * Retourne le nom du tag XML correspondant à cette tâche dans les config projet.
     *
     * @return string nom du tag XML correspondant à cette tâche dans les config projet.
     */
    public static function getTagName ()
    {
        return 'cvsexport';
    }

    /**
     * Tâche de synchronisation sous-jacente.
     * @var Task_Base_Sync
     */
    private $_oSyncTask;

    /**
     * Constructeur.
     *
     * @param SimpleXMLElement $oTask Contenu XML de la tâche.
     * @param Task_Base_Project $oProject Super tâche projet.
     * @param ServiceContainer $oServiceContainer Register de services prédéfinis (Shell_Interface, ...).
     */
    public function __construct (SimpleXMLElement $oTask, Task_Base_Project $oProject,
        ServiceContainer $oServiceContainer)
    {
        parent::__construct($oTask, $oProject, $oServiceContainer);
        $this->_aAttrProperties = array(
            'repository' => AttributeProperties::FILE | AttributeProperties::REQUIRED,
            'module' => AttributeProperties::DIR | AttributeProperties::REQUIRED,
            'srcdir' => AttributeProperties::DIR,
            'destdir' => AttributeProperties::DIR | AttributeProperties::REQUIRED
                | AttributeProperties::ALLOW_PARAMETER
        );

        if (empty($this->_aAttributes['srcdir'])) {
            $this->_aAttributes['srcdir'] =
                DEPLOYMENT_REPOSITORIES_DIR . '/cvs/'
                . $this->_oProperties->getProperty('project_name') . '_'
                . $this->_oProperties->getProperty('environment_name') . '_'
                . $this->_sCounter;
        } else {
            $this->_aAttributes['srcdir'] =
                preg_replace('#/$#', '', $this->_aAttributes['srcdir']);
        }

        // Création de la tâche de synchronisation sous-jacente :
        $this->_oNumbering->addCounterDivision();
        $this->_oSyncTask = Task_Base_Sync::getNewInstance(
            array(
                'src' => $this->_aAttributes['srcdir'] . '/' . $this->_aAttributes['module'] . '/',
                'destdir' => $this->_aAttributes['destdir']
            ),
            $oProject,
            $oServiceContainer
        );
        $this->_oNumbering->removeCounterDivision();
    }

    /**
     * Prépare la tâche avant exécution : vérifications basiques, analyse des serveurs concernés...
     */
    public function setUp ()
    {
        parent::setUp();
        $this->_oLogger->indent();
        try {
            $this->_oSyncTask->setUp();
        } catch (UnexpectedValueException $oException) {
            if ($oException->getMessage() !== "File or directory '" . $this->_aAttributes['srcdir']
                                            . '/' . $this->_aAttributes['module'] . '/' . "' not found!") {
                throw $oException;
            }
        }

        $this->_oLogger->unindent();
    }

    /**
     * Phase de traitements centraux de l'exécution de la tâche.
     * Elle devrait systématiquement commencer par "parent::_centralExecute();".
     * Appelé par _execute().
     * @see execute()
     */
    protected function _centralExecute ()
    {
        parent::_centralExecute();
        $this->_oLogger->indent();

        $this->_oLogger->log("Export from '" . $this->_aAttributes['repository'] . "' CVS repository");
        $this->_oLogger->indent();
        $aResult = $this->_oShell->exec(
            DEPLOYMENT_BASH_PATH . ' ' . DEPLOYMENT_LIB_DIR . '/cvsexport.inc.sh'
            . ' "' . $this->_aAttributes['repository'] . '"'
            . ' "' . $this->_aAttributes['module'] . '"'
            . ' "' . $this->_aAttributes['srcdir'] . '"'
        );
        $this->_oLogger->log(implode("\n", $aResult));
        $this->_oLogger->unindent();

        $this->_oSyncTask->execute();
        $this->_oLogger->unindent();
    }
}
