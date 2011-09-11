<?php

/**
 * @category TwengaDeploy
 * @package Core
 * @author Geoffroy AUBRY <geoffroy.aubry@twenga.com>
 */
class Task_Extended_GitExport extends Task
{

    /**
     * Retourne le nom du tag XML correspondant à cette tâche dans les config projet.
     *
     * @return string nom du tag XML correspondant à cette tâche dans les config projet.
     */
    public static function getTagName ()
    {
        return 'gitexport';
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
            'ref' => AttributeProperties::REQUIRED | AttributeProperties::ALLOW_PARAMETER,
            'localrepositorydir' => AttributeProperties::DIR,
            'srcsubdir' => AttributeProperties::DIR,
            'destdir' => AttributeProperties::DIR | AttributeProperties::REQUIRED
                | AttributeProperties::ALLOW_PARAMETER,
            // TODO AttributeProperties::DIRJOKER abusif ici, mais à cause du multivalué :
            'include' => AttributeProperties::FILEJOKER | AttributeProperties::DIRJOKER,
            'exclude' => AttributeProperties::FILEJOKER | AttributeProperties::DIRJOKER,
        );

        // Valeur par défaut de l'attribut localrepositorydir :
        if (empty($this->_aAttributes['localrepositorydir'])) {
            $this->_aAttributes['localrepositorydir'] =
                DEPLOYMENT_REPOSITORIES_DIR . '/git/'
                . $this->_oProperties->getProperty('project_name') . '_'
                . $this->_oProperties->getProperty('environment_name') . '_'
                . $this->_sCounter;
        } else {
            $this->_aAttributes['localrepositorydir'] =
                preg_replace('#/$#', '', $this->_aAttributes['localrepositorydir']);
        }

        // Création de la tâche de synchronisation sous-jacente :
        $this->_oNumbering->addCounterDivision();
        if (empty($this->_aAttributes['srcsubdir'])) {
            $this->_aAttributes['srcsubdir'] = '';
        } else {
            $this->_aAttributes['srcsubdir'] = '/' . preg_replace('#^/|/$#', '', $this->_aAttributes['srcsubdir']);
        }
        $aSyncAttributes = array(
            'src' => $this->_aAttributes['localrepositorydir'] . $this->_aAttributes['srcsubdir'] . '/',
            'destdir' => $this->_aAttributes['destdir'],
        );
        if ( ! empty($this->_aAttributes['include'])) {
            $aSyncAttributes['include'] = $this->_aAttributes['include'];
        }
        if ( ! empty($this->_aAttributes['exclude'])) {
            $aSyncAttributes['exclude'] = $this->_aAttributes['exclude'];
        }
        $this->_oSyncTask = Task_Base_Sync::getNewInstance($aSyncAttributes, $oProject, $oServiceContainer);
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
            if ($oException->getMessage() !== "File or directory '" . $this->_aAttributes['localrepositorydir']
                                            . $this->_aAttributes['srcsubdir'] . '/' . "' not found!") {
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

        $aRef = $this->_processPath($this->_aAttributes['ref']);
        $sRef = $aRef[0];

        $sMsg = "Export '$sRef' reference from '" . $this->_aAttributes['repository'] . "' git repository";
        $this->_oLogger->log($sMsg);
        $this->_oLogger->indent();
        $result = $this->_oShell->exec(
            DEPLOYMENT_BASH_PATH . ' ' . DEPLOYMENT_LIB_DIR . '/gitexport.inc.sh'
            . ' "' . $this->_aAttributes['repository'] . '"'
            . ' "' . $sRef . '"'
            . ' "' . $this->_aAttributes['localrepositorydir'] . '"'
        );
        $this->_oLogger->log(implode("\n", $result));
        $this->_oLogger->unindent();

        $this->_oSyncTask->execute();
        $this->_oLogger->unindent();
    }
}
