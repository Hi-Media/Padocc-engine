<?php

namespace Himedia\Padocc\Task\Extended;

use GAubry\Shell\PathStatus;
use Himedia\Padocc\DIContainer;
use Himedia\Padocc\Task;
use Himedia\Padocc\Task\Base\Link;
use Himedia\Padocc\Task\Base\Project;

/**
 * Initialise le contenu statique de la nouvelle release à partir de la précédente (via rsync),
 * et met à jour le lien symbolique /last_deploy vers cette nouvelle release.
 * À inclure dans une tâche env ou target.
 *
 * Tâche adhoc pour le projet front à cause de la logique différente dans la gestion des statiques.
 *
 * Contient une tâche sync :
 * <sync src="${STATIC_SERVERS}:${STATIC_BASEDIR}/last_deploy"
 *     destdir="${STATIC_SERVERS}:${STATIC_BASEDIR}/${EXECUTION_ID}" />
 * et un link :
 * <link src="${STATIC_BASEDIR}/last_deploy"
 *     target="${STATIC_BASEDIR}/${EXECUTION_ID}" server="${STATIC_SERVERS}" />
 *
 * Exemple : <b2cpreparestaticcontent />
 *
 * @author Geoffroy AUBRY <gaubry@hi-media.com>
 */
class B2CPrepareStaticContent extends Task
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
     * @var Link
     */
    private $_oLinkTask;

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

        $this->oNumbering->addCounterDivision();
        $aAttributes = array(
            'src' => '${STATIC_SERVERS}:${STATIC_BASEDIR}/' . self::$_sLastDir,
            'target' => '${STATIC_SERVERS}:${STATIC_BASEDIR}/${EXECUTION_ID}'
        );
        $this->_oLinkTask = Link::getNewInstance($aAttributes, $oProject, $oDIContainer);
        $this->oNumbering->removeCounterDivision();
    }

    /**
     * Phase de traitements centraux de l'exécution de la tâche.
     * Elle devrait systématiquement commencer par "parent::centralExecute();".
     * Appelé par _execute().
     * @see execute()
     */
    protected function centralExecute ()
    {
        parent::centralExecute();
        $this->oLogger->info('+++Initialize static content with previous release:+++');

        $aServers = $this->expandPath('${STATIC_SERVERS}');
        $sBaseDir = $this->processSimplePath('${STATIC_BASEDIR}/' . self::$_sLastDir);
        $aPathStatusResult = $this->oShell->getParallelSSHPathStatus($sBaseDir, $aServers);

        // Recherche des serveurs que l'on peut initialiser :
        $aServersToInit = array();
        foreach ($aServers as $sServer) {
            $iPathStatus = $aPathStatusResult[$sServer];
            if ($iPathStatus == PathStatus::STATUS_SYMLINKED_DIR) {
                $aServersToInit[] = $sServer;
            } else {
                $sMsg = "Symlink to last release not found on server '$sServer': '$sBaseDir' ($iPathStatus)";
                $this->oLogger->info($sMsg);
            }
        }

        // Initialisation de ces serveurs :
        $sDestDir = '[]:' . $this->processSimplePath('${STATIC_BASEDIR}/${EXECUTION_ID}');
        $aResults = $this->oShell->sync("[]:$sBaseDir/", $sDestDir, $aServersToInit);
        foreach ($aResults as $sResult) {
            $this->oLogger->info($sResult);
        }

        $this->oLogger->info('---');
        $this->_oLinkTask->execute();
        $this->oLogger->info('---');
    }

    /**
     * Prépare la tâche avant exécution : vérifications basiques, analyse des serveurs concernés...
     */
    public function setUp ()
    {
        parent::setUp();
        $this->oLogger->info('+++');
        $this->_oLinkTask->setUp();
        $this->oLogger->info('---');
    }
}
