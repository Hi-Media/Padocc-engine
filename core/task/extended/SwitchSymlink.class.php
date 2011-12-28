<?php

/**
 * Permute les liens symboliques de la dernière release vers la nouvelle à la fin du déploiement.
 * Tâche ajoutée par défaut en tant que dernière tâche de l'environnement, si et seulement si aucune
 * tâche Task_Extended_SwitchSymlink ou fille (comme Task_Extended_B2CSwitchSymlink) n'est spécifiée dans le XML,
 * et si l'attribut withsymlinks de la tâche env vaut true. À inclure en toute fin de tâche env ou target.
 *
 * Attributs :
 * - 'src' : laisser à vide à moins d'être bien conscient des conséquences
 * - 'target' : laisser à vide à moins d'être bien conscient des conséquences
 * - 'server' : laisser à vide à moins d'être bien conscient des conséquences
 *
 * Exemple : <switchsymlink />
 *
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
            $sRollbackID = $this->_oProperties->getProperty('rollback_id');
            if ($sRollbackID !== '') {
                $this->_oLogger->log("Rollback to '$sRollbackID' requested.");
                $sID = $sRollbackID;
            } else {
                $sID = $this->_oProperties->getProperty('execution_id');
            }
            $sReleaseSymLink = $sBaseSymLink . DEPLOYMENT_SYMLINK_RELEASES_DIR_SUFFIX . '/' . $sID;

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
                $this->_oLogger->log('No release found.');
            } else {
                $this->_oProperties->setProperty('with_symlinks', 'false');
                $this->_checkTargets();
                $this->_oLogger->unindent();
                parent::_centralExecute();
                $this->_oLogger->indent();
                $this->_oProperties->setProperty('with_symlinks', 'true');
            }
        } else {
            $this->_oLogger->log("Mode 'withsymlinks' is off: nothing to do.");
        }
        $this->_oLogger->unindent();
    }

    /**
     * Vérifie que chaque répertoire cible des liens existe.
     * Notamment nécessaire en cas de rollback.
     *
     * @throws RuntimeException si l'un des répertoires cible des liens n'existe pas
     */
    protected function _checkTargets ()
    {
        $this->_oLogger->log('Check that all symlinks targets exists.');
        $this->_oLogger->indent();

        $aValidStatus = array(
            Shell_PathStatus::STATUS_DIR,
            Shell_PathStatus::STATUS_SYMLINKED_DIR
        );

        $sPath = $this->_aAttributes['target'];
        $aServers = $this->_expandPath($this->_aAttributes['server']);
        $aPathStatusResult = $this->_oShell->getParallelSSHPathStatus($sPath, $aServers);
        foreach ($aServers as $sServer) {
            $sExpandedPath = $sServer . ':' . $sPath;
            if ( ! in_array($aPathStatusResult[$sServer], $aValidStatus)) {
                $sMsg = "Target attribute must be a directory or a symlink to a directory: '" . $sExpandedPath . "'";
                throw new RuntimeException($sMsg);
            }
        }

        $this->_oLogger->unindent();
    }
}
