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
        // Parce qu'évidemment il n'y a pas de logique commune :
        $aMappingAAI = array('qa' => 'qa', 'prod' => 'web');
        $sEnv = $this->_oProperties->getProperty('environment_name');
        $sAppParameter = (isset($aMappingAAI[$sEnv]) ? $aMappingAAI[$sEnv] : $sEnv);
        $sURL = 'http://aai.twenga.com/push.php?server=${WEB_SERVERS}&amp;app=' . $sAppParameter;
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
        $this->_oLogger->unindent();
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

                // Pour chaque serveur :
                $aServers = $this->_processPath('${WEB_SERVERS}');
                foreach ($aServers as $sServer) {
                    $this->_oLogger->log("Switch '$sServer' server:");
                    $this->_oLogger->indent();

                    if ($this->_aAttributes['clusterRemoving'] == 'true') {
                        $this->_setCluster($sServer, false);
                    }

                    // Switch du lien symbolique :
                    $aAttributes = array(
                        'src' => $this->_aAttributes['src'],
                        'target' => $this->_aAttributes['target'],
                        'server' => $sServer
                    );
                    $oLinkTask = Task_Base_Link::getNewInstance(
                        $aAttributes, $this->_oProject, $this->_oServiceContainer
                    );
                    $oLinkTask->setUp();
                    $oLinkTask->execute();

                    $this->_restartServerApache($sServer);
                    $this->_clearServerSmartyCaches($sServer);
                    if ($this->_aAttributes['clusterReintegration'] == 'true') {
                        $this->_setCluster($sServer, true);
                    }
                    $this->_oLogger->unindent();
                }

                // Switch des symlinks
                // des éventuels serveurs de Task_Base_Environment::SERVERS_CONCERNED_WITH_BASE_DIR
                // non inclus dans ${WEB_SERVERS}, comme les schedulers par exemple...
                $aAllServers = $this->_expandPath($this->_aAttributes['server']);
                $aDiff = array_diff($aAllServers, $aServers);
                if (count($aDiff) > 0) {
                    $this->_oLogger->log('Switch other servers: ' . implode(', ', $aDiff) . '.');
                    $this->_oLogger->indent();
                    $this->_oProperties->setProperty('remaining_servers_to_switch', implode(' ', $aDiff));
                    $aAttributes = array(
                        'src' => $this->_aAttributes['src'],
                        'target' => $this->_aAttributes['target'],
                        'server' => '${remaining_servers_to_switch}'
                    );
                    $oLinkTask = Task_Base_Link::getNewInstance(
                        $aAttributes, $this->_oProject, $this->_oServiceContainer
                    );
                    $oLinkTask->setUp();
                    $oLinkTask->execute();
                    $this->_oLogger->unindent();
                }

                $this->_oProperties->setProperty('with_symlinks', 'true');
            }
        } else {
            $this->_oLogger->log("Mode 'withsymlinks' is off: nothing to do.");
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
        $aTypes = array('qa' => 'Q', 'bct' => 'B', 'internal' => 'I', 'preprod' => 'X', 'prod' => 'P');
        if ( ! isset($aTypes[$sEnv])) {
            throw new DomainException("Environment not handled: '$sEnv'!");
        }
        $this->_oLogger->log("Add Twenga build number $sID into 'TWENGABUILD' SQL table.");
        $this->_oLogger->indent();
        $sCmd = "/home/prod/twenga/tools/add_twengabuild $sID " . $aTypes[$sEnv];
        $this->_oShell->execSSH($sCmd, 'fs3:foo');
        $this->_oLogger->unindent();
    }

    /**
     * Redémarre le serveur Apache de tous les serveurs.
     */
    /*private function _restartServersApache ()
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
    }*/

    /**
     * Redémarre le serveur Apache du serveur spécifié.
     *
     * @param string $sServer au format [user@]servername_or_ip
     */
    private function _restartServerApache ($sServer)
    {
        $this->_oLogger->log("Restart Apache webserver '$sServer'.");
        $this->_oLogger->indent();
        $sToExec = $this->_processSimplePath($sServer . ':/root/apache_restart');
        $aResult = $this->_oShell->execSSH('sudo %s', $sToExec);
        $this->_oLogger->log(implode("\n", $aResult));
        $this->_oLogger->unindent();
    }

    /**
     * Réinitialise les caches Smarty de tous les serveurs.
     */
    /*private function _clearServersSmartyCaches ()
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
    }*/

    /**
     * Réinitialise les caches Smarty du serveur spécifié.
     *
     * @param string $sServer au format [user@]servername_or_ip
     */
    private function _clearServerSmartyCaches ($sServer)
    {
        $this->_oLogger->log("Clear Smarty caches of server '$sServer':");
        $this->_oLogger->indent();

        $sCmd = "/home/prod/twenga/tools/clear_cache $sServer smarty";
        if (strcasecmp(strrchr($sServer, '.'), '.us1') === 0) {
            $sCmd = 'export FORCE_TWENGA_DC=US && ' . $sCmd . ' && export FORCE_TWENGA_DC=\'\'';
        }
        $aResult = $this->_oShell->execSSH($sCmd, 'fs3:foo');
        $this->_oLogger->log(implode("\n", $aResult));
        $this->_oLogger->unindent();
    }

    /**
     * Sors ou réintègre les serveurs du cluster.
     *
     * @param bool $bStatus true pour réintégrer, false pour sortir.
     */
    /*private function _setClusters ($bStatus)
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
    }*/

    /**
     * Sors ou réintègre le serveur spécifié du cluster.
     *
     * @param string $sServer au format [user@]servername_or_ip
     * @param bool $bStatus true pour réintégrer, false pour sortir.
     */
    private function _setCluster ($sServer, $bStatus)
    {
        $aMsgs = ($bStatus ? array('Reintegrate', 'into', '-e') : array('Remove', 'from', '-d'));

        if (preg_match('/wwwtest/i', $sServer) !== 1) {
            $this->_oLogger->log($aMsgs[0] . " '$sServer' server $aMsgs[1] the cluster.");
            $this->_oLogger->indent();
            $sCmd = "/home/prod/twenga/tools/wwwcluster -s $sServer $aMsgs[2]";
            $aResult = $this->_oShell->exec($sCmd);
            $sResult = implode("\n", $aResult);
            if ($sResult != '') {
                $this->_oLogger->log(implode("\n", $aResult));
            }
            $this->_oLogger->unindent();
        } else {
            $this->_oLogger->log(" '$sServer' server is not handled by the cluster.");
        }
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
