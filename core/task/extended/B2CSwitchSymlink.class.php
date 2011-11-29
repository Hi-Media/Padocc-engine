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
        $this->_oNumbering->addCounterDivision();
        $aAttributes = array('url' => 'http://aai.twenga.com/push.php?server=${WEB_SERVERS}&amp;app=qa');
        $this->_oHTTPTask = Task_Base_HTTP::getNewInstance($aAttributes, $oProject, $oServiceContainer);
        $this->_oNumbering->removeCounterDivision();
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

        // Restart Apache
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

        // Clear Smarty caches
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

        $this->_oHTTPTask->execute();

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
