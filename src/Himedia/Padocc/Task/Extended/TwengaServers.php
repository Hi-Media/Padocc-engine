<?php

namespace Himedia\Padocc\Task\Extended;

use Himedia\Padocc\Task;

/**
 * @author Geoffroy AUBRY <gaubry@hi-media.com>
 * @author Another Author Tony Caron <caron.tony@gmail.com>
 */
class TwengaServers extends Task
{
    /**
     * Tâche d'export Git sous-jacente.
     *
     * @var GitExport
     */
    private $oGitExportTask;

    /**
     * Répertoire temporaire où extraire master_synchro.cfg.
     * @var string
     */
    private $sTmpDir;

    /**
     * {@inheritdoc}
     */
    protected function init()
    {
        parent::init();

        $this->aAttrProperties = array();
        $this->sTmpDir = $this->aConfig['dir']['tmp'] . '/'
            . $this->oProperties->getProperty('execution_id') . '_' . self::getTagName();

        // Création de la tâche de synchronisation sous-jacente :
        $this->oNumbering->addCounterDivision();
        $this->oGitExportTask = GitExport::getNewInstance(
            array(
                'repository' => 'git@git.twenga.com:aa/server_config.git',
                'ref' => 'master',
                'destdir' => $this->sTmpDir
            ),
            $this->oProject,
            $this->oDIContainer
        );
        $this->oNumbering->removeCounterDivision();
    }

    /**
     * {@inheritdoc}
     * @codeCoverageIgnore
     */
    public static function getTagName ()
    {
        return 'twengaserverexport';
    }

    /**
     * Prépare la tâche avant exécution : vérifications basiques, analyse des serveurs concernés...
     */
    public function setUp ()
    {
        parent::setUp();
        $this->getLogger()->info('+++');
        $this->oGitExportTask->setUp();
        $this->getLogger()->info('---');
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
        $this->getLogger()->info('+++');
        $this->oGitExportTask->execute();
        $sPathToLoad = $this->sTmpDir . '/master_synchro.cfg';
        $this->getLogger()->info("Load shell properties: $sPathToLoad+++");
        $this->oProperties->loadConfigShellFile($sPathToLoad);
        $this->oShell->remove($this->sTmpDir);
        $this->getLogger()->info('------');
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
