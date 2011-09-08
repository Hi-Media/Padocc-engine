<?php

/**
 * @category TwengaDeploy
 * @package Core
 * @author Geoffroy AUBRY <geoffroy.aubry@twenga.com>
 */
class Task_Base_ExternalProperty extends Task
{

    /**
     * Préfixe de propriété externe, c.-à-d. fournie par l'utilisateur.
     * @var string
     * @see _centralExecute()
     */
    const EXTERNAL_PROPERTY_PREFIX = 'external_property_';

    /**
     * Compteur général du nombre de propriétés externes résolues, c.-à-d. associées à une variable
     * du fichier de configuration XML (les '${my_var}').
     * @var int
     */
    private static $_iCounter = 0;

    /**
     * Numéro de cette instance de propriété externe : c'est la valeur de self::$_iCounter à la création.
     * La première instance vaudra donc 1.
     * @var int
     */
    private $_iNumber;

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
     * @param ServiceContainer $oServiceContainer Register de services prédéfinis (Shell_Interface, ...).
     */
    public function __construct (SimpleXMLElement $oTask, Task_Base_Project $oProject,
        ServiceContainer $oServiceContainer)
    {
        parent::__construct($oTask, $oProject, $oServiceContainer);
        $this->_aAttrProperties = array(
            'name' => AttributeProperties::REQUIRED,
            'description' => AttributeProperties::REQUIRED
        );
        $this->_iNumber = ++self::$_iCounter;
    }

    protected function _centralExecute ()
    {
        parent::_centralExecute();
        $this->_oLogger->indent();
        $sMsg = "Set external property '" . $this->_aAttributes['name'] . "' (description: '"
              . $this->_aAttributes['description'] . "')";
        $this->_oLogger->log($sMsg);
        try {
            $sValue = $this->_oProperties->getProperty(self::EXTERNAL_PROPERTY_PREFIX . $this->_iNumber);
        } catch (UnexpectedValueException $oException) {
            $sMsg = "Property '" . $this->_aAttributes['name'] . "' undefined!";
            throw new UnexpectedValueException($sMsg, 1, $oException);
        }
        $this->_oProperties->setProperty($this->_aAttributes['name'], $sValue);
        $this->_oLogger->unindent();
    }
}
