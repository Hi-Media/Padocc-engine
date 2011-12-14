<?php

/**
 * Sous-division d'une tâche projet, décrit ce qu'est un déploiement pour un environnement donné.
 *
 * Liste des attributs XML :
 * - 'name', obligatoire, précise la valeur à fournir lors d'un déploiement (par ex. 'qa', 'prod', ...).
 * - 'mailto', optionnel, permet d'ajouter des destinataires (séparés par ',') au mail de fin de déploiement.
 * - 'basedir', obligatoire, est le répertoire racine de déploiement sur le(s) serveur(s) cible(s).
 *      Par exemple : basedir="/home/httpd/my_app".
 * - 'withsymlinks', optionnel, "true" ou "false" (défaut), précise si l'on souhaite utiliser la technique
 *      des liens symboliques lors des déploiements ou non.
 *
 * Dérive Task_WithProperties et supporte donc les attributs XML 'loadtwengaservers', 'propertyshellfile'
 * et 'propertyinifile'.
 *
 * @category TwengaDeploy
 * @package Core
 * @author Geoffroy AUBRY <geoffroy.aubry@twenga.com>
 */
class Task_Base_Environment extends Task_Base_Target
{

    /**
     * Liste d'exclusions Smarty pour les rsync réalisés lors de l'initialisation des déploiements.
     * @var array
     * @see _makeTransitionToSymlinks()
     * @see _makeTransitionFromSymlinks()
     * @see _initNewRelease()
     */
    private static $_aSmartyRsyncExclude = array('smarty/templates_c', 'smarty/*/wrt*', 'smarty/**/wrt*');

    /**
     * Nombre maximal de déploiement à garder dans les répertoires de releases.
     * @var int
     */
    private static $_iDefaultMaxNbReleases = DEPLOYMENT_SYMLINK_MAX_NB_RELEASES;

    /**
     * Propriété (au sens Properties_Interface) contenant la liste des serveurs concernés par le déploiement.
     * @var string
     */
    const SERVERS_CONCERNED_WITH_BASE_DIR = 'SERVERS_CONCERNED_WITH_BASE_DIR';

    /**
     * Retourne le nom du tag XML correspondant à cette tâche dans les config projet.
     *
     * @return string nom du tag XML correspondant à cette tâche dans les config projet.
     */
    public static function getTagName ()
    {
        return 'env';
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
        $this->_aAttrProperties = array_merge(
            $this->_aAttrProperties,
            array(
                'name' => AttributeProperties::REQUIRED,
                'mailto' => AttributeProperties::EMAIL | AttributeProperties::MULTI_VALUED,
                'withsymlinks' => AttributeProperties::BOOLEAN,
                'basedir' => AttributeProperties::DIR | AttributeProperties::REQUIRED
            )
        );

        // Positionnement des 2 propriétés basedir et withsymlinks :
        $sBaseDir = (empty($this->_aAttributes['basedir']) ? '[setUp() will failed]' : $this->_aAttributes['basedir']);
        $this->_oProperties->setProperty('basedir', $sBaseDir);
        $sWithSymlinks = (empty($this->_aAttributes['withsymlinks']) ? 'false' : $this->_aAttributes['withsymlinks']);
        $this->_oProperties->setProperty('with_symlinks', $sWithSymlinks);

        $this->_addSwithSymlinkTask();
    }

    /**
     * Ajoute une tâche Task_Extended_SwitchSymlink en toute dernière étape de déploiement
     * si le XML du projet n'en a pas spécifié.
     */
    private function _addSwithSymlinkTask ()
    {
        if (
            Task_Extended_SwitchSymlink::getNbInstances() === 0
            && $this->_oProperties->getProperty('with_symlinks') === 'true'
        ) {
            $this->_oNumbering->addCounterDivision();
            $oLinkTask = Task_Extended_SwitchSymlink::getNewInstance(
                array(),
                $this->_oProject,
                $this->_oServiceContainer
            );
            array_push($this->_aTasks, $oLinkTask);
            $this->_oNumbering->removeCounterDivision();
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
     * @throws UnexpectedValueException en cas d'attribut ou fichier manquant
     * @throws DomainException en cas de valeur non permise
     */
    public function check ()
    {
        parent::check();
        if ($this->_aAttributes['basedir'][0] !== '/') {
            throw new DomainException("Attribute 'basedir' must begin by a '/'!");
        }

        $aMsg = array();
        foreach ($this->_aAttributes as $sAttribute => $sValue) {
            if ( ! empty($sValue) && $sAttribute !== 'name') {
                $aMsg[] = "Attribute: $sAttribute = '$sValue'";
            }
        }
        if (count($aMsg) > 0) {
            $this->_oLogger->indent();
            $this->_oLogger->log(implode("\n", $aMsg));
            $this->_oLogger->unindent();
        }
    }

    /**
     * Extrait la liste des serveurs concernés par le déploiement à partir de self::$_aRegisteredPaths
     * et l'enregistre dans la propriété self::SERVERS_CONCERNED_WITH_BASE_DIR.
     */
    private function _analyzeRegisteredPaths ()
    {
        $aPathsToHandle = array();
        $aPaths = array_keys(self::$_aRegisteredPaths);

        $sBaseSymLink = $this->_oProperties->getProperty('basedir');
        foreach ($aPaths as $sPath) {
            $aExpandedPaths = $this->_expandPath($sPath);
            foreach ($aExpandedPaths as $sExpandedPath) {
                list($bIsRemote, $sServer, $sRealPath) = $this->_oShell->isRemotePath($sExpandedPath);
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
        $this->_oLogger->log($sMsg);
        $this->_oProperties->setProperty(self::SERVERS_CONCERNED_WITH_BASE_DIR, implode(' ', $aServersWithSymlinks));
    }

    /**
     * Gère la transition d'un déploiement sans stratégie de liens symboliques vers cette stratégie.
     */
    private function _makeTransitionToSymlinks ()
    {
        $this->_oLogger->log('If needed, make transition to symlinks:');
        $this->_oLogger->indent();
        $sBaseSymLink = $this->_oProperties->getProperty('basedir');
        $aServers = $this->_expandPath('${' . self::SERVERS_CONCERNED_WITH_BASE_DIR . '}');
        $bTransitionMade = false;

        $aPathStatusResult = $this->_oShell->getParallelSSHPathStatus($sBaseSymLink, $aServers);
        foreach ($aServers as $sServer) {
            $sExpandedPath = $sServer . ':' . $sBaseSymLink;
            if ($aPathStatusResult[$sServer] === Shell_PathStatus::STATUS_DIR) {
                $bTransitionMade = true;
                $sDir = $sExpandedPath . '/';
                $sOriginRelease = $sServer . ':' . $sBaseSymLink . DEPLOYMENT_SYMLINK_RELEASES_DIR_SUFFIX
                                . '/' . $this->_oProperties->getProperty('execution_id') . '_origin';
                $this->_oLogger->log("Backup '$sDir' to '$sOriginRelease'.");
                $this->_oLogger->indent();
                $this->_oShell->sync($sDir, $sOriginRelease, array(), self::$_aSmartyRsyncExclude);
                $this->_oShell->remove($sExpandedPath);
                $this->_oShell->createLink($sExpandedPath, $sOriginRelease);
                $this->_oLogger->unindent();
            }
        }
        if ( ! $bTransitionMade) {
            $this->_oLogger->log('No transition.');
        }
        $this->_oLogger->unindent();
    }

    /**
     * Gère la transition d'un déploiement avec stratégie de liens symboliques vers une approche sans.
     */
    private function _makeTransitionFromSymlinks ()
    {
        $this->_oLogger->log('If needed, make transition from symlinks:');
        $this->_oLogger->indent();
        $sBaseSymLink = $this->_oProperties->getProperty('basedir');
        $sPath = '${' . self::SERVERS_CONCERNED_WITH_BASE_DIR . '}:' . $sBaseSymLink;
        $bTransitionMade = false;
        foreach ($this->_expandPath($sPath) as $sExpandedPath) {
            if ($this->_oShell->getPathStatus($sExpandedPath) === Shell_PathStatus::STATUS_SYMLINKED_DIR) {
                $bTransitionMade = true;
                list(, , $sRealPath) = $this->_oShell->isRemotePath($sExpandedPath);
                $sDir = $sExpandedPath . '/';
                $sTmpDest = $sExpandedPath . '_tmp';
                $sMsg = "Remove symlink on '$sExpandedPath' base directory"
                      . " and initialize it with last release's content.";
                $this->_oLogger->log($sMsg);
                $this->_oShell->sync($sDir, $sTmpDest, array(), self::$_aSmartyRsyncExclude);
                $this->_oShell->remove($sExpandedPath);
                $this->_oShell->execSSH("mv %s '" . $sRealPath . "'", $sTmpDest);
            }
        }
        if ( ! $bTransitionMade) {
            $this->_oLogger->log('No transition.');
        }
        $this->_oLogger->unindent();
    }

    /**
     * Initialise la nouvelle release avec le contenu de l'ancienne, dans le but d'accélerer le déploiement.
     */
    private function _initNewRelease ()
    {
        $this->_oLogger->log('Initialize with content of previous release:');
        $this->_oLogger->indent();
        $sBaseSymLink = $this->_oProperties->getProperty('basedir');
        $aServers = $this->_expandPath('${' . self::SERVERS_CONCERNED_WITH_BASE_DIR . '}');
        $sReleaseSymLink = $sBaseSymLink . DEPLOYMENT_SYMLINK_RELEASES_DIR_SUFFIX
                         . '/' . $this->_oProperties->getProperty('execution_id');
        $aPathStatusResult = $this->_oShell->getParallelSSHPathStatus($sBaseSymLink, $aServers);

        // Recherche des serveurs que l'on peut initialiser :
        $aServersToInit = array();
        foreach ($aServers as $sServer) {
            if ($aPathStatusResult[$sServer] == Shell_PathStatus::STATUS_SYMLINKED_DIR) {
                $aServersToInit[] = $sServer;
            } else {
                $this->_oLogger->log("No previous release to initialize '$sServer:$sReleaseSymLink'.");
            }
        }

        // Initialisation de ces serveurs :
        $aResults = $this->_oShell->sync("[]:$sBaseSymLink/", '[]:' . $sReleaseSymLink, $aServersToInit);
        foreach ($aResults as $sResult) {
            $this->_oLogger->log($sResult);
        }

        $this->_oLogger->unindent();
    }

    /**
     * Retourne la liste triée chronologiquement des différentes releases présentes à l'endroit spécifié.
     *
     * @param string $sExpandedPath chemin sans serveur
     * @param array $aServers liste de serveurs au format [user@]servername_or_ip
     * @return array tableau associatif "sServer" => aReleases,
     * où aReleases est la liste des releases du serveur associé, de la plus jeune à la plus vieille.
     */
    private function _getAllReleases ($sExpandedPath, array $aServers)
    {
        $sPattern = '^[0-9]{14}_[0-9]{5}(_origin)?$';
        $sCmd = "if [ -d %1\$s ] && ls -1 %1\$s | grep -qE '$sPattern'; "
              . "then ls -1 %1\$s | grep -E '$sPattern'; fi";
        $sSSHCmd = $this->_oShell->buildSSHCmd($sCmd, '[]:' . $sExpandedPath);
        $aParallelResult = $this->_oShell->parallelize($aServers, $sSSHCmd);

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
    private function _removeOldestReleases ()
    {
        $this->_oLogger->log('Remove too old releases:');
        $this->_oLogger->indent();

        if ($this->_oProperties->getProperty(self::SERVERS_CONCERNED_WITH_BASE_DIR) == '') {
            $this->_oLogger->log('No release found.');
        } else {

            // Check releases:
            $sBaseSymLink = $this->_oProperties->getProperty('basedir') . DEPLOYMENT_SYMLINK_RELEASES_DIR_SUFFIX;
            $aServers = $this->_expandPath('${' . self::SERVERS_CONCERNED_WITH_BASE_DIR . '}');
            $this->_oLogger->log('Check releases on each server.');
            $this->_oLogger->indent();
            $aAllReleases = $this->_getAllReleases($sBaseSymLink, $aServers);
            $this->_oLogger->unindent();

            // Identification des releases à supprimer :
            $aAllReleasesToDelete = array();
            foreach ($aAllReleases as $sServer => $aReleases) {
                $iNbReleases = count($aReleases);
                if ($iNbReleases === 0) {
                    $this->_oLogger->log("No release found on server '$sServer'.");
                } else {
                    $bIsQuotaExceeded = ($iNbReleases > self::$_iDefaultMaxNbReleases);
                    $sMsg = $iNbReleases . " release(s) found on server '$sServer': quota "
                          . ($bIsQuotaExceeded ? 'exceeded' : 'not exceeded')
                          . ' (' . self::$_iDefaultMaxNbReleases . ' backups max).';
                    $this->_oLogger->log($sMsg);

                    if ($bIsQuotaExceeded) {
                        $aReleasesToDelete = array_slice($aReleases, self::$_iDefaultMaxNbReleases);
                        foreach ($aReleasesToDelete as $sReleaseToDelete) {
                            $aAllReleasesToDelete[$sReleaseToDelete][] = $sServer;
                        }
                    }
                }
            }

            // Suppression des releases surnuméraires les plus vieilles :
            foreach ($aAllReleasesToDelete as $sRelease => $aServers) {
                if ( ! empty($sRelease)) {
                    $sMsg = "Remove release '$sRelease' on following server(s): " . implode(', ', $aServers) . '.';
                    $this->_oLogger->log($sMsg);
                    $sPath = "[]:$sBaseSymLink/$sRelease";
                    $sSSHCmd = $this->_oShell->buildSSHCmd('rm -rf %s', $sPath);
                    $this->_oShell->parallelize($aServers, $sSSHCmd);
                }
            }

        }
        $this->_oLogger->unindent();
    }

    /**
     * Supprime les tâches qui ne sont plus nécessaires pour le rollback.
     *
     * @see $this->_aTasks
     */
    private function _removeUnnecessaryTasksForRollback ()
    {
        if ($this->_oProperties->getProperty('rollback_id') !== '') {
            $this->_oLogger->log('Remove unnecessary tasks for rollback.');
            $aKeptTasks = array();
            foreach ($this->_aTasks as $oTask) {
               if (
                       ($oTask instanceof Task_Base_Property)
                       || ($oTask instanceof Task_Base_ExternalProperty)
                       || ($oTask instanceof Task_Extended_SwitchSymlink)
               ) {
                   $aKeptTasks[] = $oTask;
               }
            }
            $this->_aTasks = $aKeptTasks;
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

        // Supprime les tâches qui ne sont plus nécessaires pour le rollback :
        $this->_removeUnnecessaryTasksForRollback();

        // Exécute tout de suite toutes les tâches Task_Base_Property ou Task_Base_ExternalProperty qui
        // suivent directement :
        $oTask = reset($this->_aTasks);
        while (($oTask instanceof Task_Base_Property) || ($oTask instanceof Task_Base_ExternalProperty)) {
            $oTask->execute();
            array_shift($this->_aTasks);
            $oTask = reset($this->_aTasks);
        }

        // Déduit les serveurs concernés par ce déploiement et prépare le terrain :
        $this->_analyzeRegisteredPaths();
        if ($this->_oProperties->getProperty('with_symlinks') === 'true') {
            $this->_oProperties->setProperty('with_symlinks', 'false');
            if ($this->_oProperties->getProperty('rollback_id') === '') {
                $this->_makeTransitionToSymlinks();
                $this->_initNewRelease();
                $this->_removeOldestReleases();
            }
            $this->_oProperties->setProperty('with_symlinks', 'true');
        } else {
            $this->_makeTransitionFromSymlinks();
        }
        $this->_oLogger->unindent();
    }
}
