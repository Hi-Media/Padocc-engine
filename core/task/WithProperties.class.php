<?php

abstract class Task_WithProperties extends Task
{

    /**
     * Tâche de chargement des listes de serveurs Twenga sous-jacente.
     * @var Task_Extended_TwengaServers
     */
    private $oTwengaServersTask;

    /**
     * Constructeur.
     *
     * @param SimpleXMLElement $oTask Contenu XML de la tâche.
     * @param Task_Base_Project $oProject Super tâche projet.
     * @param string $sBackupPath répertoire hôte pour le backup de la tâche.
     * @param ServiceContainer $oServiceContainer Register de services prédéfinis (Shell_Interface, Logger_Interface, ...).
     */
    public function __construct (SimpleXMLElement $oTask, Task_Base_Project $oProject, $sBackupPath, ServiceContainer $oServiceContainer)
    {
        parent::__construct($oTask, $oProject, $sBackupPath, $oServiceContainer);
        $this->aAttributeProperties = array(
            'propertyinifile' => Task::ATTRIBUTE_SRC_PATH,
            'propertyshellfile' => Task::ATTRIBUTE_SRC_PATH,
            'loadtwengaservers' => Task::ATTRIBUTE_BOOLEAN
        );

        // Création de la tâche de chargement des listes de serveurs Twenga sous-jacente :
        if ( ! empty($this->aAttributes['loadtwengaservers']) && $this->aAttributes['loadtwengaservers'] == 'true') {
            $this->oNumbering->addCounterDivision();
            $this->oTwengaServersTask = Task_Extended_TwengaServers::getNewInstance(array(), $oProject, $sBackupPath, $oServiceContainer);
            $this->oNumbering->removeCounterDivision();
        } else {
            $this->oTwengaServersTask = NULL;
        }
    }

    protected function _loadProperties ()
    {
        if ( ! empty($this->aAttributes['loadtwengaservers']) && $this->aAttributes['loadtwengaservers'] == 'true') {
            //$this->oLogger->log('Load Twenga servers');
            $this->oTwengaServersTask->execute();
        }
        if ( ! empty($this->aAttributes['propertyshellfile'])) {
            $this->oLogger->log('Load shell properties: ' . $this->aAttributes['propertyshellfile']);
            $this->oProperties->loadConfigShellFile($this->aAttributes['propertyshellfile']);
        }
        if ( ! empty($this->aAttributes['propertyinifile'])) {
            $this->oLogger->log('Load ini properties: ' . $this->aAttributes['propertyinifile']);
            $this->oProperties->loadConfigIniFile($this->aAttributes['propertyinifile']);
        }
    }

    public function setUp ()
    {
        parent::setUp();
        if ($this->oTwengaServersTask !== NULL) {
            $this->oLogger->indent();
            $this->oTwengaServersTask->setUp();
            $this->oLogger->unindent();
        }
    }

    public function execute ()
    {
        parent::execute();

        $this->oLogger->indent();
        $this->_loadProperties();
        $this->oLogger->unindent();
    }

    public function backup ()
    {
    }
}
