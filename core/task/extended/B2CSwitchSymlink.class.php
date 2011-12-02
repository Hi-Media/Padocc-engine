<?php

/**
 * @category TwengaDeploy
 * @package Core
 * @author Geoffroy AUBRY <geoffroy.aubry@twenga.com>
 */
class Task_Extended_B2CSwitchSymlink extends Task_Extended_SwitchSymlink
{

    private $_bWithSysopsNotifications;
    private $_bWithAddSQLTwBuild;
    private $_bWithClusterExiting;
    private $_bWithClusterReintegration;

    /**
     * Retourne le nom du tag XML correspondant à cette tâche dans les config projet.
     *
     * @return string nom du tag XML correspondant à cette tâche dans les config projet.
     */
    public static function getTagName ()
    {
        return 'b2cswitchsymlink';
    }

    /**
     * Tâche de création d'appel cURL AAI sous-jacente.
     * @var Task_Base_HTTP
     */
    private $_oHTTPTask;

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
        $this->_aAttrProperties = array_merge(
            $this->_aAttrProperties,
            array('mode' => 0)
        );

        $this->_oNumbering->addCounterDivision();
        $aAttributes = array('url' => 'http://aai.twenga.com/push.php?server=${WEB_SERVERS}&amp;app=qa');
        $this->_oHTTPTask = Task_Base_HTTP::getNewInstance($aAttributes, $oProject, $oServiceContainer);
        $this->_oNumbering->removeCounterDivision();
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

           if ( ! isset($this->_aAttributes['mode'])) {
            $this->_aAttributes['mode'] = '';
        }

        switch ($this->_aAttributes['mode']) {
            case 'preprod':
                $this->_bWithSysopsNotifications = true;
                $this->_bWithAddSQLTwBuild = false;
                $this->_bWithClusterExiting = true;
                $this->_bWithClusterReintegration = false;
                break;

            case 'prod':
                $this->_bWithSysopsNotifications = true;
                $this->_bWithAddSQLTwBuild = true;
                $this->_bWithClusterExiting = true;
                $this->_bWithClusterReintegration = true;
                break;

            default:
                $this->_bWithSysopsNotifications = false;
                $this->_bWithAddSQLTwBuild = false;
                $this->_bWithClusterExiting = false;
                $this->_bWithClusterReintegration = false;
                break;
        }
    }

    /**
     * Phase de pré-traitements de l'exécution de la tâche.
     * Elle devrait systématiquement commencer par "parent::_preExecute();".
     * Appelé par _execute().
     * @see execute()
     */
    protected function _preExecute ()
    {
        parent::_preExecute();

        $this->_oLogger->indent();
        if ($this->_bWithSysopsNotifications) {
            $sEnv = $this->_oProperties->getProperty('environment_name');
            $sID = $this->_oProperties->getProperty('execution_id');
            $this->_sendSysopsNotification('MEP-activation', 2, "Deploy to $sEnv servers (#$sID) is switching...");
        }
        $this->_oLogger->unindent();
    }

    /**
     * Phase de post-traitements de l'exécution de la tâche.
     * Elle devrait systématiquement finir par "parent::_postExecute();".
     * Appelé par _execute().
     * @see execute()
     */
    protected function _postExecute ()
    {
        $this->_oLogger->indent();

        $this->_restartApache();
        $this->_clearSmartyCaches();
        if ($this->_bWithSysopsNotifications) {
            $sEnv = $this->_oProperties->getProperty('environment_name');
            $sID = $this->_oProperties->getProperty('execution_id');
            $this->_sendSysopsNotification('MEP-activation', 0, "Deploy to $sEnv servers (#$sID) finished.");
        }
        $this->_oHTTPTask->execute();

        $this->_oLogger->unindent();
        parent::_postExecute();
    }

    private function _sendSysopsNotification ($sService, $iStatus, $sMessage)
    {
        $this->_oLogger->log("Send notification to Sysops: '$sMessage'");
        $sCmd = "/home/prod/twenga/tools/send_nsca_fs3.sh $sService $iStatus \"$sMessage\"";
        $this->_oShell->execSSH($sCmd, 'fs3:foo');
    }

    private function _restartApache ()
    {
        $this->_oLogger->log('Restart Apache webservers:');
        $this->_oLogger->indent();
        $aToExec = $this->_processPath('${WEB_SERVERS}:/root/apache_restart');
        foreach ($aToExec as $sToExec) {
            list(, $sServer, ) = $this->_oShell->isRemotePath($sToExec);
            $this->_oLogger->log("Restart Apache webserver '$sServer'.");
            $aResult = $this->_oShell->execSSH('sudo %s', $sToExec);
            $this->_oLogger->indent();
            $this->_oLogger->log(implode("\n", $aResult));
            $this->_oLogger->unindent();
        }
        $this->_oLogger->unindent();
    }

    private function _clearSmartyCaches ()
    {
        $this->_oLogger->log('Clear Smarty caches:');
        $this->_oLogger->indent();
        $aServers = $this->_processPath('${WEB_SERVERS}');
        foreach ($aServers as $sServer) {
            $this->_oLogger->log("Clear Smarty cache on server '$sServer':");
            $aResult = $this->_oShell->execSSH("/home/prod/twenga/tools/clear_cache $sServer smarty", 'fs3:foo');
            $this->_oLogger->indent();
            $this->_oLogger->log(implode("\n", $aResult));
            $this->_oLogger->unindent();
        }
        $this->_oLogger->unindent();
    }

    /**
     * Prépare la tâche avant exécution : vérifications basiques, analyse des serveurs concernés...
     */
    public function setUp ()
    {
        parent::setUp();
        $this->_oLogger->indent();
        $this->_oHTTPTask->setUp();
        $this->_oLogger->unindent();
    }
}
