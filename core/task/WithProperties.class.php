<?php

/**
 * @category TwengaDeploy
 * @package Core
 * @author Geoffroy AUBRY
 */
abstract class Task_WithProperties extends Task
{

    /**
     * Tâche de chargement des listes de serveurs Twenga sous-jacente.
     * @var Task_Extended_TwengaServers
     */
    private $_oTwengaServersTask;

    /**
     * Constructeur.
     *
     * @param SimpleXMLElement $oTask Contenu XML de la tâche.
     * @param Task_Base_Project $oProject Super tâche projet.
     * @param string $sBackupPath répertoire hôte pour le backup de la tâche.
     * @param ServiceContainer $oServiceContainer Register de services prédéfinis (Shell_Interface, ...).
     */
    public function __construct (SimpleXMLElement $oTask, Task_Base_Project $oProject, $sBackupPath,
        ServiceContainer $oServiceContainer)
    {
        parent::__construct($oTask, $oProject, $sBackupPath, $oServiceContainer);
        $this->_aAttributeProperties = array(
            'propertyinifile' => Task::ATTRIBUTE_SRC_PATH,
            'propertyshellfile' => Task::ATTRIBUTE_SRC_PATH,
            'loadtwengaservers' => Task::ATTRIBUTE_BOOLEAN
        );

        // Création de la tâche de chargement des listes de serveurs Twenga sous-jacente :
        if ( ! empty($this->_aAttributes['loadtwengaservers']) && $this->_aAttributes['loadtwengaservers'] == 'true') {
            $this->_oNumbering->addCounterDivision();
            $this->_oTwengaServersTask = Task_Extended_TwengaServers::getNewInstance(
                array(), $oProject, $sBackupPath, $oServiceContainer
            );
            $this->_oNumbering->removeCounterDivision();
        } else {
            $this->_oTwengaServersTask = NULL;
        }
    }

    private function _loadProperties ()
    {
        if ( ! empty($this->_aAttributes['loadtwengaservers']) && $this->_aAttributes['loadtwengaservers'] == 'true') {
            $this->_oTwengaServersTask->execute();
        }
        if ( ! empty($this->_aAttributes['propertyshellfile'])) {
            $this->_oLogger->log('Load shell properties: ' . $this->_aAttributes['propertyshellfile']);
            $this->_oLogger->indent();
            $this->_oProperties->loadConfigShellFile($this->_aAttributes['propertyshellfile']);
            $this->_oLogger->unindent();
        }
        if ( ! empty($this->_aAttributes['propertyinifile'])) {
            $this->_oLogger->log('Load ini properties: ' . $this->_aAttributes['propertyinifile']);
            $this->_oProperties->loadConfigIniFile($this->_aAttributes['propertyinifile']);
        }
    }

    public function setUp ()
    {
        parent::setUp();
        if ($this->_oTwengaServersTask !== NULL) {
            $this->_oLogger->indent();
            $this->_oTwengaServersTask->setUp();
            $this->_oLogger->unindent();
        }
    }

    protected function _preExecute ()
    {
        parent::_preExecute();
        $this->_oLogger->indent();
        $this->_loadProperties();
        $this->_oLogger->unindent();
    }
}
