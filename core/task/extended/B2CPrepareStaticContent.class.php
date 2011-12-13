<?php

/**
 * Spécifique B2C.
 *
 * @category TwengaDeploy
 * @package Core
 * @author Geoffroy AUBRY <geoffroy.aubry@twenga.com>
 */
class Task_Extended_B2CPrepareStaticContent extends Task
{

    /**
     * Nom du symlink directory pointant sur le dernier déploiement statique.
     * @var string
     */
    private static $_sLastDir = 'last_deploy';

    /**
     * Retourne le nom du tag XML correspondant à cette tâche dans les config projet.
     *
     * @return string nom du tag XML correspondant à cette tâche dans les config projet.
     */
    public static function getTagName ()
    {
        return 'b2cpreparestaticcontent';
    }

    /**
     * Tâche de création de lien sous-jacente.
     * @var Task_Base_Link
     */
    private $_oLinkTask;

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
        $this->_aAttrProperties = array();

        $this->_oNumbering->addCounterDivision();
        $aAttributes = array(
            'src' => '${STATIC_SERVERS}:${STATIC_BASEDIR}/' . self::$_sLastDir,
            'target' => '${STATIC_SERVERS}:${STATIC_BASEDIR}/${EXECUTION_ID}'
        );
        $this->_oLinkTask = Task_Base_Link::getNewInstance($aAttributes, $oProject, $oServiceContainer);
        $this->_oNumbering->removeCounterDivision();
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
        $this->_oLogger->log('Initialize static content with previous release:');
        $this->_oLogger->indent();

        $aServers = $this->_expandPath('${STATIC_SERVERS}');
        $sBaseDir = $this->_processSimplePath('${STATIC_BASEDIR}/' . self::$_sLastDir);
        $aPathStatusResult = $this->_oShell->getParallelSSHPathStatus($sBaseDir, $aServers);

        // Recherche des serveurs que l'on peut initialiser :
        $aServersToInit = array();
        foreach ($aServers as $sServer) {
            $iPathStatus = $aPathStatusResult[$sServer];
            if ($iPathStatus == Shell_PathStatus::STATUS_SYMLINKED_DIR) {
                $aServersToInit[] = $sServer;
            } else {
                $this->_oLogger->log("Symlink to last release not found on server '$sServer': '$sBaseDir' ($iPathStatus)");
            }
        }

        // Initialisation de ses serveurs :
        $sDestDir = '[]:' . $this->_processSimplePath('${STATIC_BASEDIR}/${EXECUTION_ID}');
        $aResults = $this->_oShell->sync("[]:$sBaseDir/", $sDestDir, $aServersToInit);
        foreach ($aResults as $sResult) {
            $this->_oLogger->log($sResult);
        }

        $this->_oLogger->unindent();
        $this->_oLinkTask->execute();
        $this->_oLogger->unindent();
    }

    /**
     * Prépare la tâche avant exécution : vérifications basiques, analyse des serveurs concernés...
     */
    public function setUp ()
    {
        parent::setUp();
        $this->_oLogger->indent();
        $this->_oLinkTask->setUp();
        $this->_oLogger->unindent();
    }
}
