<?php

namespace Himedia\Padocc\Task\Base;

use GAubry\Shell\PathStatus;
use Himedia\Padocc\AttributeProperties;
use Himedia\Padocc\DIContainer;
use Himedia\Padocc\Task\Extended\SwitchSymlink;

/**
 * Sous-division d'une tâche projet, décrit ce qu'est un déploiement pour un environnement donné.
 *
 * Attributs :
 * - 'name' : nom de l'environnement à préciser lors ddu déploiement
 * - 'mailto' : liste d'adresses email séparées par une virgule à qui adresser le mail de fin de déploiement
 *   en plus de l'instigateur
 * - 'withsymlinks' : activer ou non la gestion de déploiement par lien symbolique (false par défaut)
 * - 'basedir' : répertoire de base, accessible par la propriété ${BASEDIR}.
 *   Tous les répertoires mentionnés dans le XML, quel que soit le serveur, et qui se trouvent à l'intérieur
 *   dans celui-ci, seront automatiquement redirigés en cas de withsymlinks=“true”.
 * - 'loadtwengaservers' : charger ou non les alias de serveurs définis dans master_synchro.cfg
 *   afin qu'ils soient accessibles en tant que propriété. Par exemple si master_synchro.cfg contient
 *   SERVER_QA_PHPWEB_DC_EU1="www17.eu1"
 *   SERVER_QA_PHPWEB_DC_US1="www-07.us1"
 *   SERVER_QA_PHPWEB_ALL="$SERVER_QA_PHPWEB_DC_EU1 $SERVER_QA_PHPWEB_DC_US1"
 *   alors la propriété ${SERVER_QA_PHPWEB_ALL} permettra d'adresser ces 2 serveurs.
 *
 * Exemple :
 * <env name="prod"
 *   mailto="devaa@twenga.com, qateam@twenga.com, herve.gouchet@twenga.com, sysops@twenga.com"
 *   withsymlinks="true"
 *   loadtwengaservers="true"
 *   basedir="/home/httpd/www.twenga"
 * >...</env>
 *
 * @author Geoffroy AUBRY <gaubry@hi-media.com>
 */
class Environment extends Target
{

    /**
     * Liste d'exclusions Smarty pour les rsync réalisés lors de l'initialisation des déploiements.
     * @var array
     * @see makeTransitionToSymlinks()
     * @see makeTransitionFromSymlinks()
     * @see initNewRelease()
     */
    private static $aSmartyRsyncExclude = array('smarty/templates_c', 'smarty/*/wrt*', 'smarty/**/wrt*');

    /**
     * Propriété (au sens PropertiesInterface) contenant la liste des serveurs concernés par le déploiement.
     * @var string
     */
    const SERVERS_CONCERNED_WITH_BASE_DIR = 'SERVERS_CONCERNED_WITH_BASE_DIR';

    /**
     * Retourne le nom du tag XML correspondant à cette tâche dans les config projet.
     *
     * @return string nom du tag XML correspondant à cette tâche dans les config projet.
     * @codeCoverageIgnore
     */
    public static function getTagName ()
    {
        return 'env';
    }

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
        $this->aAttrProperties = array_merge(
            $this->aAttrProperties,
            array(
                'name' => AttributeProperties::REQUIRED,
                'mailto' => AttributeProperties::EMAIL | AttributeProperties::MULTI_VALUED,
                'withsymlinks' => AttributeProperties::BOOLEAN,
                'basedir' => AttributeProperties::DIR | AttributeProperties::REQUIRED
            )
        );

        // Positionnement des 2 propriétés basedir et withsymlinks :
        $sBaseDir = (empty($this->aAttValues['basedir']) ? '[setUp() will failed]' : $this->aAttValues['basedir']);
        $this->oProperties->setProperty('basedir', $sBaseDir);
        $sWithSymlinks = (empty($this->aAttValues['withsymlinks']) ? 'false' : $this->aAttValues['withsymlinks']);
        $this->oProperties->setProperty('with_symlinks', $sWithSymlinks);

        $this->addSwithSymlinkTask();
    }

    /**
     * Ajoute une tâche SwitchSymlink en toute dernière étape de déploiement
     * si le XML du projet n'en a pas spécifié.
     */
    private function addSwithSymlinkTask ()
    {
        if (SwitchSymlink::getNbInstances() === 0
            && $this->oProperties->getProperty('with_symlinks') === 'true'
        ) {
            $this->oNumbering->addCounterDivision();
            $oLinkTask = SwitchSymlink::getNewInstance(
                array(),
                $this->oProject,
                $this->oDIContainer
            );
            array_push($this->aTasks, $oLinkTask);
            $this->oNumbering->removeCounterDivision();
        }
    }

    /**
     * Vérifie au moyen de tests basiques que la tâche peut être exécutée.
     * Lance une exception si tel n'est pas le cas.
     *
     * Comme toute les tâches sont vérifiées avant que la première ne soit exécutée,
     * doit permettre de remonter au plus tôt tout dysfonctionnement.
     * Appelé avant la méthode execute().
     *
     * @throws \UnexpectedValueException en cas d'attribut ou fichier manquant
     * @throws \DomainException en cas de valeur non permise
     */
    public function check ()
    {
        parent::check();
        if ($this->aAttValues['basedir'][0] !== '/') {
            throw new \DomainException("Attribute 'basedir' must begin by a '/'!");
        }

        $aMsg = array();
        foreach ($this->aAttValues as $sAttribute => $sValue) {
            if (! empty($sValue) && $sAttribute !== 'name') {
                $aMsg[] = "Attribute: $sAttribute = '$sValue'";
            }
        }
        if (count($aMsg) > 0) {
            $this->oLogger->info('+++' . implode("\n", $aMsg) . '---');
        }
    }

    /**
     * Extrait la liste des serveurs concernés par le déploiement à partir de self::$aRegisteredPaths
     * et l'enregistre dans la propriété self::SERVERS_CONCERNED_WITH_BASE_DIR.
     */
    private function analyzeRegisteredPaths ()
    {
        $aPathsToHandle = array();
        $aPaths = array_keys(self::$aRegisteredPaths);

        $sBaseSymLink = $this->oProperties->getProperty('basedir');
        foreach ($aPaths as $sPath) {
            $aExpandedPaths = $this->expandPath($sPath);
            foreach ($aExpandedPaths as $sExpandedPath) {
                list($bIsRemote, $sServer, $sRealPath) = $this->oShell->isRemotePath($sExpandedPath);
                if ($bIsRemote && strpos($sRealPath, $sBaseSymLink) !== false) {
                    $aPathsToHandle[$sServer][] = $sRealPath;
                }
            }
        }

        $aServersWithSymlinks = array_keys($aPathsToHandle);
        if (count($aServersWithSymlinks) > 0) {
            sort($aServersWithSymlinks);
            $sMsg = "Servers concerned with base directory (#"
                  . count($aServersWithSymlinks) . "): '" . implode("', '", $aServersWithSymlinks) . "'.";
        } else {
            $sMsg = 'No server concerned with base directory.';
        }
        $this->oLogger->info($sMsg);
        $this->oProperties->setProperty(self::SERVERS_CONCERNED_WITH_BASE_DIR, implode(' ', $aServersWithSymlinks));
    }

    /**
     * Gère la transition d'un déploiement sans stratégie de liens symboliques vers cette stratégie.
     */
    private function makeTransitionToSymlinks ()
    {
        $this->oLogger->info('If needed, make transition to symlinks:+++');
        $sBaseSymLink = $this->oProperties->getProperty('basedir');
        $aServers = $this->expandPath('${' . self::SERVERS_CONCERNED_WITH_BASE_DIR . '}');
        $bTransitionMade = false;

        $aPathStatusResult = $this->oShell->getParallelSSHPathStatus($sBaseSymLink, $aServers);
        foreach ($aServers as $sServer) {
            $sExpandedPath = $sServer . ':' . $sBaseSymLink;
            if ($aPathStatusResult[$sServer] === PathStatus::STATUS_DIR) {
                $bTransitionMade = true;
                $sDir = $sExpandedPath . '/';
                $sOriginRelease = $sServer . ':' . $sBaseSymLink . $this->aConfig['symlink_releases_dir_suffix']
                                . '/' . $this->oProperties->getProperty('execution_id') . '_origin';
                $this->oLogger->info("Backup '$sDir' to '$sOriginRelease'.+++");
                $this->oShell->sync($sDir, $sOriginRelease, array(), array(), self::$aSmartyRsyncExclude);
                $this->oShell->remove($sExpandedPath);
                $this->oShell->createLink($sExpandedPath, $sOriginRelease);
                $this->oLogger->info('---');
            }
        }
        if (! $bTransitionMade) {
            $this->oLogger->info('No transition.');
        }
        $this->oLogger->info('---');
    }

    /**
     * Gère la transition d'un déploiement avec stratégie de liens symboliques vers une approche sans.
     */
    private function makeTransitionFromSymlinks ()
    {
        $this->oLogger->info('If needed, make transition from symlinks:+++');
        $sBaseSymLink = $this->oProperties->getProperty('basedir');
        $sPath = '${' . self::SERVERS_CONCERNED_WITH_BASE_DIR . '}:' . $sBaseSymLink;
        $bTransitionMade = false;
        foreach ($this->expandPath($sPath) as $sExpandedPath) {
            if ($this->oShell->getPathStatus($sExpandedPath) === PathStatus::STATUS_SYMLINKED_DIR) {
                $bTransitionMade = true;
                list(, , $sRealPath) = $this->oShell->isRemotePath($sExpandedPath);
                $sDir = $sExpandedPath . '/';
                $sTmpDest = $sExpandedPath . '_tmp';
                $sMsg = "Remove symlink on '$sExpandedPath' base directory"
                      . " and initialize it with last release's content.";
                $this->oLogger->info($sMsg);
                $this->oShell->sync($sDir, $sTmpDest, array(), array(), self::$aSmartyRsyncExclude);
                $this->oShell->remove($sExpandedPath);
                $this->oShell->execSSH("mv %s '" . $sRealPath . "'", $sTmpDest);
            }
        }
        if (! $bTransitionMade) {
            $this->oLogger->info('No transition.');
        }
        $this->oLogger->info('---');
    }

    /**
     * Initialise la nouvelle release avec le contenu de l'ancienne, dans le but d'accélerer le déploiement.
     */
    private function initNewRelease ()
    {
        $this->oLogger->info('Initialize with content of previous release:+++');
        $sBaseSymLink = $this->oProperties->getProperty('basedir');
        $aServers = $this->expandPath('${' . self::SERVERS_CONCERNED_WITH_BASE_DIR . '}');
        $sReleaseSymLink = $sBaseSymLink . $this->aConfig['symlink_releases_dir_suffix']
                         . '/' . $this->oProperties->getProperty('execution_id');
        $aPathStatusResult = $this->oShell->getParallelSSHPathStatus($sBaseSymLink, $aServers);

        // Recherche des serveurs que l'on peut initialiser :
        $aServersToInit = array();
        foreach ($aServers as $sServer) {
            if ($aPathStatusResult[$sServer] == PathStatus::STATUS_SYMLINKED_DIR) {
                $aServersToInit[] = $sServer;
            } else {
                $this->oLogger->info("No previous release to initialize '$sServer:$sReleaseSymLink'.");
            }
        }

        // Initialisation de ces serveurs :
        if (count($aServersToInit) > 0) {
            $aResults = $this->oShell->sync(
                "[]:$sBaseSymLink/",
                '[]:' . $sReleaseSymLink,
                $aServersToInit,
                array(),
                self::$aSmartyRsyncExclude
            );
            foreach ($aResults as $sResult) {
                $this->oLogger->info($sResult);
            }
        }

        $this->oLogger->info('---');
    }

    /**
     * Retourne la liste triée chronologiquement des différentes releases présentes à l'endroit spécifié.
     *
     * @param string $sExpandedPath chemin sans serveur
     * @param array $aServers liste de serveurs au format [user@]servername_or_ip
     * @return array tableau associatif "sServer" => aReleases,
     * où aReleases est la liste des releases du serveur associé, de la plus jeune à la plus vieille.
     */
    private function getAllReleases ($sExpandedPath, array $aServers)
    {
        $sPattern = '^[0-9]{14}_[0-9]{5}(_origin)?$';
        $sCmd = "if [ -d %1\$s ] && ls -1 %1\$s | grep -qE '$sPattern'; "
              . "then ls -1 %1\$s | grep -E '$sPattern'; fi";
        $sSSHCmd = $this->oShell->buildSSHCmd($sCmd, '[]:' . $sExpandedPath);
        $aParallelResult = $this->oShell->parallelize(
            $aServers,
            $sSSHCmd,
            $this->aConfig['parallelization_max_nb_processes']
        );

        $aAllReleases = array();
        foreach ($aParallelResult as $aServerResult) {
            $sServer = $aServerResult['value'];
            $aReleases = explode("\n", trim($aServerResult['output']));
            sort($aReleases);
            $aAllReleases[$sServer] = array_reverse($aReleases);
        }
        return $aAllReleases;
    }

    /**
     * Supprime les vieilles releases surnuméraires sur chaque serveur concerné par le déploiement.
     */
    private function removeOldestReleases ()
    {
        $this->oLogger->info('Remove too old releases:+++');

        if ($this->oProperties->getProperty(self::SERVERS_CONCERNED_WITH_BASE_DIR) == '') {
            $this->oLogger->info('No release found.');
        } else {

            // Check releases:
            $sBaseSymLink = $this->oProperties->getProperty('basedir') . $this->aConfig['symlink_releases_dir_suffix'];
            $aServers = $this->expandPath('${' . self::SERVERS_CONCERNED_WITH_BASE_DIR . '}');
            $this->oLogger->info('Check releases on each server.+++');
            $aAllReleases = $this->getAllReleases($sBaseSymLink, $aServers);
            $this->oLogger->info('---');

            // Identification des releases à supprimer :
            $aAllReleasesToDelete = array();
            foreach ($aAllReleases as $sServer => $aReleases) {
                $iNbReleases = count($aReleases);
                if ($iNbReleases === 0) {
                    $this->oLogger->info("No release found on server '$sServer'.");
                } else {
                    $bIsQuotaExceeded = ($iNbReleases > $this->aConfig['symlink_max_nb_releases']);
                    $sMsg = $iNbReleases . " release(s) found on server '$sServer': quota "
                          . ($bIsQuotaExceeded ? 'exceeded' : 'not exceeded')
                          . ' (' . $this->aConfig['symlink_max_nb_releases'] . ' backups max).';
                    $this->oLogger->info($sMsg);

                    if ($bIsQuotaExceeded) {
                        $aReleasesToDelete = array_slice($aReleases, $this->aConfig['symlink_max_nb_releases']);
                        foreach ($aReleasesToDelete as $sReleaseToDelete) {
                            $aAllReleasesToDelete[$sReleaseToDelete][] = $sServer;
                        }
                    }
                }
            }

            // Suppression des releases surnuméraires les plus vieilles :
            foreach ($aAllReleasesToDelete as $sRelease => $aServers) {
                if (! empty($sRelease)) {
                    $sMsg = "Remove release '$sRelease' on following server(s): " . implode(', ', $aServers) . '.';
                    $this->oLogger->info($sMsg);
                    $sPath = "[]:$sBaseSymLink/$sRelease";
                    $sSSHCmd = $this->oShell->buildSSHCmd('rm -rf %s', $sPath);
                    $this->oShell->parallelize($aServers, $sSSHCmd, $this->aConfig['parallelization_max_nb_processes']);
                }
            }

        }
        $this->oLogger->info('---');
    }

    /**
     * Supprime les tâches qui ne sont plus nécessaires pour le rollback.
     *
     * @see $this->aTasks
     */
    private function removeUnnecessaryTasksForRollback ()
    {
        if ($this->oProperties->getProperty('rollback_id') !== '') {
            $this->oLogger->info('Remove unnecessary tasks for rollback.');
            $aKeptTasks = array();
            foreach ($this->aTasks as $oTask) {
                if (($oTask instanceof Property)
                    || ($oTask instanceof ExternalProperty)
                    || ($oTask instanceof SwitchSymlink)
                ) {
                    $aKeptTasks[] = $oTask;
                }
            }
            $this->aTasks = $aKeptTasks;
        }
    }

    /**
     * Phase de pré-traitements de l'exécution de la tâche.
     * Elle devrait systématiquement commencer par "parent::preExecute();".
     * Appelé par execute().
     * @see execute()
     */
    protected function preExecute ()
    {
        parent::preExecute();
        $this->oLogger->info('+++');

        // Supprime les tâches qui ne sont plus nécessaires pour le rollback :
        $this->removeUnnecessaryTasksForRollback();

        // Exécute tout de suite toutes les tâches Property ou ExternalProperty qui
        // suivent directement :
        $oTask = reset($this->aTasks);
        while (($oTask instanceof Property) || ($oTask instanceof ExternalProperty)) {
            $oTask->execute();
            array_shift($this->aTasks);
            $oTask = reset($this->aTasks);
        }

        // Déduit les serveurs concernés par ce déploiement et prépare le terrain :
        $this->analyzeRegisteredPaths();
        if ($this->oProperties->getProperty('with_symlinks') === 'true') {
            $this->oProperties->setProperty('with_symlinks', 'false');
            if ($this->oProperties->getProperty('rollback_id') === '') {
                $this->makeTransitionToSymlinks();
                $this->initNewRelease();
                $this->removeOldestReleases();
            }
            $this->oProperties->setProperty('with_symlinks', 'true');
        } else {
            $this->makeTransitionFromSymlinks();
        }
        $this->oLogger->info('---');
    }
}
