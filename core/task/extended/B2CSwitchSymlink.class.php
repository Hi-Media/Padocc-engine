<?php

/**
 * @category TwengaDeploy
 * @package Core
 * @author Geoffroy AUBRY <geoffroy.aubry@twenga.com>
 */
class Task_Extended_B2CSwitchSymlink extends Task_Extended_SwitchSymlink
{

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
            array(
                'sysopsnotifications' => AttributeProperties::BOOLEAN,
                'addSQLTwBuild' => AttributeProperties::BOOLEAN,
                'clusterRemoving' => AttributeProperties::BOOLEAN,
                'clusterReintegration' => AttributeProperties::BOOLEAN
            )
        );

        $this->_oNumbering->addCounterDivision();
        $sURL = 'http://aai.twenga.com/push.php?server=${WEB_SERVERS}&amp;app=${ENVIRONMENT_NAME}';
        $aAttributes = array('url' => $sURL);
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

        $aAttrToInit = array('sysopsnotifications', 'addSQLTwBuild', 'clusterRemoving', 'clusterReintegration');
        foreach ($aAttrToInit as $sAttrName) {
            if ( ! isset($this->_aAttributes[$sAttrName])) {
                $this->_aAttributes[$sAttrName] = 'false';
            }
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
        if ($this->_aAttributes['sysopsnotifications'] == 'true') {
            $sEnv = $this->_oProperties->getProperty('environment_name');
            $sID = $this->_oProperties->getProperty('execution_id');
            $this->_sendSysopsNotification('MEP-activation', 2, "Deploy to $sEnv servers (#$sID) is switching...");
        }
        if ($this->_aAttributes['clusterRemoving'] == 'true') {
            $this->_setCluster(false);
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
        $sEnv = $this->_oProperties->getProperty('environment_name');
        $sID = $this->_oProperties->getProperty('execution_id');
        $this->_oLogger->indent();

        $this->_restartApache();
        $this->_clearSmartyCaches();
        if ($this->_aAttributes['clusterReintegration'] == 'true') {
            $this->_setCluster(true);
        }
        if ($this->_aAttributes['addSQLTwBuild'] == 'true') {
            $this->_addSQLTwBuild($sID, $sEnv);
        }
        if ($this->_aAttributes['sysopsnotifications'] == 'true') {
            $this->_sendSysopsNotification('MEP-activation', 0, "Deploy to $sEnv servers (#$sID) finished.");
        }
        $this->_oHTTPTask->execute();

        $this->_oLogger->unindent();
        parent::_postExecute();
    }

    /**
     * Envoie une notification sur le téléphone des admins.
     *
     * @param string $sService catégorie
     * @param int $iStatus 0 ok, 1 warning, 2 critical
     * @param string $sMessage
     */
    private function _sendSysopsNotification ($sService, $iStatus, $sMessage)
    {
        $this->_oLogger->log("Send notification to Sysops: '$sMessage'");
        $this->_oLogger->indent();
        $sCmd = "/home/prod/twenga/tools/send_nsca_fs3.sh $sService $iStatus \"$sMessage\"";
        $this->_oShell->execSSH($sCmd, 'fs3:foo');
        $this->_oLogger->unindent();
    }

    /**
     * Insère le Twenga build number dans la table SQL centralisée 'TWENGABUILD'.
     *
     * @param string $sID Twenga build number
     * @param string $sEnv Environnement
     * @throws DomainException quand environnement non capturé
     */
    private function _addSQLTwBuild ($sID, $sEnv)
    {
        $aTypes = array('qa' => 'Q', 'bct' => 'B', 'internal' => 'I', 'preprod' => 'P', 'prod' => 'P');
        if ( ! isset($aTypes[$sEnv])) {
            throw new DomainException("Environment not handled: '$sEnv'!");
        }
        $this->_oLogger->log("Add Twenga build number into 'TWENGABUILD' SQL table.");
        $sCmd = "/home/prod/twenga/tools/add_twengabuild $sID " . $aTypes[$sEnv];
        $this->_oShell->execSSH($sCmd, 'fs3:foo');
    }

    /**
     * Redémarre le serveur Apache de tous les serveurs.
     */
    private function _restartApache ()
    {
        $this->_oLogger->log('Restart Apache webservers:');
        $this->_oLogger->indent();
        $aToExec = $this->_processPath('${WEB_SERVERS}:/root/apache_restart');
        foreach ($aToExec as $sToExec) {
            list(, $sServer, ) = $this->_oShell->isRemotePath($sToExec);
            $this->_oLogger->log("Restart Apache webserver '$sServer'.");
            $this->_oLogger->indent();
            $aResult = $this->_oShell->execSSH('sudo %s', $sToExec);
            $this->_oLogger->log(implode("\n", $aResult));
            $this->_oLogger->unindent();
        }
        $this->_oLogger->unindent();
    }

    /**
     * Réinitialise les caches Smarty de tous les serveurs.
     */
    private function _clearSmartyCaches ()
    {
        $this->_oLogger->log('Clear Smarty caches:');
        $this->_oLogger->indent();
        $aServers = $this->_processPath('${WEB_SERVERS}');
        foreach ($aServers as $sServer) {
            $this->_oLogger->log("Clear Smarty cache on server '$sServer':");
            $this->_oLogger->indent();

            $sCmd = "/home/prod/twenga/tools/clear_cache $sServer smarty";
            if (strcasecmp(strrchr($sServer, '.'), '.us1') === 0) {
                $sCmd = 'export FORCE_TWENGA_DC=US && ' . $sCmd . ' && export FORCE_TWENGA_DC=\'\'';
            }
            $aResult = $this->_oShell->execSSH($sCmd, 'fs3:foo');
            $this->_oLogger->log(implode("\n", $aResult));
            $this->_oLogger->unindent();
        }
        $this->_oLogger->unindent();
    }

    /**
     * Sors ou réintègre les serveurs du cluster.
     *
     * @param bool $bStatus true pour réintégrer, false pour sortir.
     */
    private function _setCluster ($bStatus)
    {
        $aMsgs = ($bStatus ? array('Reintegrate', 'into', '-e') : array('Remove', 'from', '-d'));

        $this->_oLogger->log("$aMsgs[0] servers $aMsgs[1] the cluster:");
        $this->_oLogger->indent();
        $aServers = $this->_processPath('${WEB_SERVERS}');
        foreach ($aServers as $sServer) {
            if (preg_match('/wwwtest/i', $sServer) !== 1) {
                $this->_oLogger->log($aMsgs[0] . " '$sServer' server");
                $this->_oLogger->indent();
                $sCmd = "/home/prod/twenga/tools/wwwcluster -s $sServer $aMsgs[2]";
                $aResult = $this->_oShell->exec($sCmd);
                $sResult = implode("\n", $aResult);
                if ($sResult != '') {
                    $this->_oLogger->log(implode("\n", $aResult));
                }
                $this->_oLogger->unindent();
            }
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
