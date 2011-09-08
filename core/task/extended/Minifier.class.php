<?php

/**
 * @category TwengaDeploy
 * @package Core
 * @author Geoffroy AUBRY <geoffroy.aubry@twenga.com>
 */
class Task_Extended_Minifier extends Task
{

    /**
     * Instance Minifier_Interface.
     * @var Minifier_Interface
     */
    private $_oMinifier;

    /**
     * Retourne le nom du tag XML correspondant à cette tâche dans les config projet.
     *
     * @return string nom du tag XML correspondant à cette tâche dans les config projet.
     */
    public static function getTagName ()
    {
        return 'minify';
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
            'srcfile' => AttributeProperties::FILEJOKER | AttributeProperties::ALLOW_PARAMETER,
            'destfile' => AttributeProperties::FILE | AttributeProperties::ALLOW_PARAMETER
        );

        //$this->_oMinifier = new Minifier_JSMinAdapter(DEPLOYMENT_JSMIN_BIN_PATH, $this->_oShell);
        $this->_oMinifier = Minifier_Factory::getInstance(Minifier_Factory::TYPE_JSMIN, $this->_oShell);
    }

    protected function _centralExecute ()
    {
        parent::_centralExecute();
        $this->_oLogger->indent();

        $this->_oLogger->log('Minify');

        $aSrcPaths = $this->_processPath($this->_aAttributes['srcfile']);
        $sDestPaths = $this->_processSimplePath($this->_aAttributes['destfile']);
        $this->_oMinifier->minify($aSrcPaths, $sDestPaths);

        $this->_oLogger->unindent();
    }
}
