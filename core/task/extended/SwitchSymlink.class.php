<?php

/**
 * @category TwengaDeploy
 * @package Core
 * @author Geoffroy AUBRY <geoffroy.aubry@twenga.com>
 */
class Task_Extended_SwitchSymlink extends Task_Base_Link
{

    /**
     * Retourne le nom du tag XML correspondant à cette tâche dans les config projet.
     *
     * @return string nom du tag XML correspondant à cette tâche dans les config projet.
     */
    public static function getTagName ()
    {
        return 'switchsymlink';
    }

    /**
     * Compteur d'instances de la classe.
     * @var int
     * @see getNbInstances()
     */
    private static $_iNbInstances = 0;

    /**
     * Accesseur au compteur d'instances de la classe.
     *
     * @return int nombre d'instances de la classe.
     * @see $iNbInstances
     */
    public static function getNbInstances ()
    {
        return self::$_iNbInstances;
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
            'src' => AttributeProperties::FILE | AttributeProperties::DIR | AttributeProperties::ALLOW_PARAMETER,
            'target' => AttributeProperties::FILE | AttributeProperties::DIR | AttributeProperties::ALLOW_PARAMETER,
            'server' => AttributeProperties::ALLOW_PARAMETER
        );
        self::$_iNbInstances++;
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
        if (
            ! isset($this->_aAttributes['src'])
            && ! isset($this->_aAttributes['target'])
            && ! isset($this->_aAttributes['server'])
        ) {
            $sBaseSymLink = $this->_oProperties->getProperty('basedir');
            $sReleaseSymLink = $sBaseSymLink . DEPLOYMENT_SYMLINK_RELEASES_DIR_SUFFIX
                             . '/' . $this->_oProperties->getProperty('execution_id');
            $this->_aAttributes['src'] = $sBaseSymLink;
            $this->_aAttributes['target'] = $sReleaseSymLink;
            $this->_aAttributes['server'] = '${' . Task_Base_Environment::SERVERS_CONCERNED_WITH_BASE_DIR . '}';
        }

        parent::check();
    }

    /**
     * Phase de traitements centraux de l'exécution de la tâche.
     * Elle devrait systématiquement commencer par "parent::_centralExecute();".
     * Appelé par _execute().
     * @see execute()
     */
    protected function _centralExecute ()
    {
        $this->_oLogger->indent();
        if ($this->_oProperties->getProperty('with_symlinks') === 'true') {
            if ($this->_oProperties->getProperty(Task_Base_Environment::SERVERS_CONCERNED_WITH_BASE_DIR) == '') {
                $sMsg = 'No release found.';
            } else {
                $this->_oProperties->setProperty('with_symlinks', 'false');
                parent::_centralExecute();
                $this->_oProperties->setProperty('with_symlinks', 'true');
                $sMsg = "Change target of base directory's symbolic link to new release: '"
                      . $this->_aAttributes['src'] . "' -> '"
                      . $this->_aAttributes['target'] . "'.";
            }
        } else {
            $sMsg = "Mode 'withsymlinks' is off: nothing to do.";
        }
        $this->_oLogger->log($sMsg);
        $this->_oLogger->unindent();
    }
}
