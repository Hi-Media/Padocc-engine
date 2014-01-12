<?php

namespace Himedia\Padocc\Task\Extended;

use Himedia\Padocc\DIContainer;
use Himedia\Padocc\Task;
use Himedia\Padocc\Task\Base\Project;

/**
 * @author Geoffroy AUBRY <gaubry@hi-media.com>
 * @author Another Author Tony Caron <caron.tony@gmail.com>
 */
class TwengaServers extends Task
{

    /**
     * Retourne le nom du tag XML correspondant à cette tâche dans les config projet.
     *
     * @return string nom du tag XML correspondant à cette tâche dans les config projet.
     */
    public static function getTagName ()
    {
        return 'twengaserverexport';
    }

    /**
     * Tâche d'export Git sous-jacente.
     *
*@var GitExport
     */
    private $_oGitExportTask;

    /**
     * Répertoire temporaire où extraire master_synchro.cfg.
     * @var string
     */
    private $_sTmpDir;

    /**
     * Constructeur.
     *
     * @param \SimpleXMLElement $oTask Contenu XML de la tâche.
     * @param Project $oProject Super tâche projet.
     * @param DIContainer $oDIContainer Register de services prédéfinis (ShellInterface, ...).
     */
    public function __construct (\SimpleXMLElement $oTask, Project $oProject, DIContainer $oDIContainer)
    {
        parent::__construct($oTask, $oProject, $oDIContainer);
        $this->aAttrProperties = array();
        $this->_sTmpDir = DEPLOYMENT_TMP_DIR . '/'
                        . $this->oProperties->getProperty('execution_id') . '_' . self::getTagName();

        // Création de la tâche de synchronisation sous-jacente :
        $this->oNumbering->addCounterDivision();
        $this->_oGitExportTask = GitExport::getNewInstance(
            array(
                'repository' => 'git@git.twenga.com:aa/server_config.git',
                'ref' => 'master',
                'destdir' => $this->_sTmpDir
            ),
            $oProject,
            $oDIContainer
        );
        $this->oNumbering->removeCounterDivision();
    }

    /**
     * Prépare la tâche avant exécution : vérifications basiques, analyse des serveurs concernés...
     */
    public function setUp ()
    {
        parent::setUp();
        $this->oLogger->info('+++');
        $this->_oGitExportTask->setUp();
        $this->oLogger->info('---');
    }

    /**
     * Vérifie au moyen de tests basiques que la tâche peut être exécutée.
     * Lance une exception si tel n'est pas le cas.
     *
     * Comme toute les tâches sont vérifiées avant que la première ne soit exécutée,
     * doit permettre de remonter au plus tôt tout dysfonctionnement.
     * Appelé avant la méthode execute().
     */
    protected function check ()
    {
        parent::centralExecute();
        $this->oLogger->info('+++');
        $this->_oGitExportTask->execute();
        $sPathToLoad = $this->_sTmpDir . '/master_synchro.cfg';
        $this->oLogger->info("Load shell properties: $sPathToLoad+++");
        $this->oProperties->loadConfigShellFile($sPathToLoad);
        $this->oShell->remove($this->_sTmpDir);
        $this->oLogger->info('------');
    }

    /**
     * Phase de traitements centraux de l'exécution de la tâche.
     * Elle devrait systématiquement commencer par "parent::centralExecute();".
     * Appelé par execute().
     * @see execute()
     */
    protected function centralExecute ()
    {
        parent::centralExecute();
    }
}
