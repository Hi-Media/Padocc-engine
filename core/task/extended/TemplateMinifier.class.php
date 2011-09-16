<?php

/**
 * @category TwengaDeploy
 * @package Core
 * @author Geoffroy AUBRY <geoffroy.aubry@twenga.com>
 */
class Task_Extended_TemplateMinifier extends Task
{

    /**
     * Instance Minifier_Interface.
     * @var Minifier_Interface
     */
    private $_oTplMinifier;

    /**
     * Retourne le nom du tag XML correspondant à cette tâche dans les config projet.
     *
     * @return string nom du tag XML correspondant à cette tâche dans les config projet.
     */
    public static function getTagName ()
    {
        return 'tplminify';
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
            'tpldir' => AttributeProperties::DIR | AttributeProperties::REQUIRED
                | AttributeProperties::ALLOW_PARAMETER,
            'cssparentdir' => AttributeProperties::DIR | AttributeProperties::REQUIRED
                | AttributeProperties::ALLOW_PARAMETER,
            'jsparentdir' => AttributeProperties::DIR | AttributeProperties::REQUIRED
                | AttributeProperties::ALLOW_PARAMETER,
            'destdir' => AttributeProperties::DIR | AttributeProperties::REQUIRED
                | AttributeProperties::ALLOW_PARAMETER,
            'imgoutpath' => AttributeProperties::DIR | AttributeProperties::REQUIRED
                | AttributeProperties::ALLOW_PARAMETER
        );
        $oMinifier = Minifier_Factory::getInstance(Minifier_Factory::TYPE_JSMIN, $this->_oShell);
        $this->_oTplMinifier = new Minifier_TemplateMinifier($oMinifier, $this->_oLogger);
    }

    /**
     * Vérifie au moyen de tests basiques que la tâche peut être exécutée.
     * Lance une exception si tel n'est pas le cas.
     *
     * Comme toute les tâches sont vérifiées avant que la première ne soit exécutée,
     * doit permettre de remonter au plus tôt tout dysfonctionnement.
     * Appelé avant la méthode execute().
     *
     * @throws UnexpectedValueException en cas d'attribut ou fichier manquant
     * @throws DomainException en cas de valeur non permise
     */
    public function check ()
    {
        parent::check();

        // Suppression de l'éventuel slash terminal :
        $this->_aAttributes['tpldir'] = preg_replace('#/$#', '', $this->_aAttributes['tpldir']);
        $this->_aAttributes['cssparentdir'] = preg_replace('#/$#', '', $this->_aAttributes['cssparentdir']);
        $this->_aAttributes['jsparentdir'] = preg_replace('#/$#', '', $this->_aAttributes['jsparentdir']);
        $this->_aAttributes['destdir'] = preg_replace('#/$#', '', $this->_aAttributes['destdir']);
        $this->_aAttributes['imgoutpath'] = '/' . preg_replace('#^/|/$#', '', $this->_aAttributes['imgoutpath']);
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

        $sMsg = "Minify '" . $this->_aAttributes['tpldir'] . "' templates directory into '"
              . $this->_aAttributes['destdir'] . "'.";
        $this->_oLogger->log($sMsg);
        $this->_oLogger->indent();

        $sTplDir = $this->_processSimplePath($this->_aAttributes['tpldir']);
        $sCSSParentDir = $this->_processSimplePath($this->_aAttributes['cssparentdir']);
        $sJSParentDir = $this->_processSimplePath($this->_aAttributes['jsparentdir']);
        $sDestDir = $this->_processSimplePath($this->_aAttributes['destdir']);
        $sImgOutPath = $this->_processSimplePath($this->_aAttributes['imgoutpath']);
        $this->_oTplMinifier->process($sTplDir, $sCSSParentDir, $sJSParentDir, $sDestDir, $sImgOutPath);

        $this->_oLogger->unindent();
        $this->_oLogger->unindent();
    }
}
