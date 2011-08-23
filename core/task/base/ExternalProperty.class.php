<?php

class Task_Base_ExternalProperty extends Task
{

    const EXTERNAL_PROPERTY_PREFIX = 'external_property_';

    private static $iCounter = 0;

    private $iNumber;

    protected $aTasks;

    /**
     * Retourne le nom du tag XML correspondant à cette tâche dans les config projet.
     *
     * @return string nom du tag XML correspondant à cette tâche dans les config projet.
     */
    public static function getTagName ()
    {
        return 'externalproperty';
    }

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
            'name' => Task::ATTRIBUTE_REQUIRED,
            'description' => Task::ATTRIBUTE_REQUIRED
        );
        $this->iNumber = ++self::$iCounter;
    }

    public function execute ()
    {
        parent::execute();
        $this->oLogger->indent();
        $this->oLogger->log("Set external property '" . $this->aAttributes['name'] . "' (description: '" . $this->aAttributes['description'] . "')");
        try {
            $sValue = $this->oProperties->getProperty(self::EXTERNAL_PROPERTY_PREFIX . $this->iNumber);
        } catch (UnexpectedValueException $oException) {
            throw new UnexpectedValueException("Property '" . $this->aAttributes['name'] . "' undefined!", 1, $oException);
        }
        $this->oProperties->setProperty($this->aAttributes['name'], $sValue);
        $this->oLogger->unindent();
    }

    public function backup ()
    {
    }
}
