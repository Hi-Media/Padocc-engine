<?php

/**
 * @category TwengaDeploy
 * @package Core
 * @author Geoffroy AUBRY <geoffroy.aubry@twenga.com>
 */
class Task_Base_Environment extends Task_Base_Target
{
    static private $_iDefaultMaxNbReleases = DEPLOYMENT_SYMLINK_MAX_NB_RELEASES;

    /**
     * Retourne le nom du tag XML correspondant à cette tâche dans les config projet.
     *
     * @return string nom du tag XML correspondant à cette tâche dans les config projet.
     */
    public static function getTagName ()
    {
        return 'env';
    }

    private $_oLinkTask;

    /**
     * Constructeur.
     *
     * @param SimpleXMLElement $oTask Contenu XML de la tâche.
     * @param Task_Base_Project $oProject Super tâche projet.
     * @param string $sBackupPath répertoire hôte pour le backup de la tâche.
     * @param ServiceContainer $oServiceContainer Register de services prédéfinis (Shell_Interface, ...).
     */
    public function __construct (SimpleXMLElement $oTask, Task_Base_Project $oProject, $sBackupPath,
        ServiceContainer $oServiceContainer)
    {
        parent::__construct($oTask, $oProject, $sBackupPath, $oServiceContainer);
        $this->_aAttrProperties = array_merge(
            $this->_aAttrProperties,
            array(
                'name' => AttributeProperties::REQUIRED,
                'mailto' => 0,
                'withsymlinks' => AttributeProperties::BOOLEAN,
                'basedir' => AttributeProperties::DIR | AttributeProperties::REQUIRED
            )
        );

        // Positionnement des 2 propriétés basedir et withsymlinks :
        $sBaseDir = (empty($this->_aAttributes['basedir']) ? '[check() will failed]' : $this->_aAttributes['basedir']);
        $this->_oProperties->setProperty('base_dir', $sBaseDir);
        $sWithSymlinks = (empty($this->_aAttributes['withsymlinks']) ? 'false' : $this->_aAttributes['withsymlinks']);
        $this->_oProperties->setProperty('with_symlinks', $sWithSymlinks);

        // Création de switch de symlink sous-jacente :
        if ($this->_oProperties->getProperty('with_symlinks') === 'true') {
            $this->_oNumbering->addCounterDivision();
            $sBaseSymLink = $this->_oProperties->getProperty('base_dir');
            $sReleaseSymLink = $sBaseSymLink . self::RELEASES_DIRECTORY_SUFFIX
                             . '/' . $this->_oProperties->getProperty('execution_id');
            $this->_oLinkTask = Task_Base_Link::getNewInstance(
                array(
                    'src' => $sBaseSymLink,
                    'target' => $sReleaseSymLink,
                    'server' => '${SERVERS_CONCERNED_WITH_BASE_DIR}'
                ),
                $oProject, $sBackupPath, $oServiceContainer
            );
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

        $this->_oLogger->indent();
        foreach ($this->_aAttributes as $sAttribute => $sValue) {
            if ( ! empty($sValue) && $sAttribute !== 'name') {
                $this->_oLogger->log("Attribute: $sAttribute = '$sValue'");
            }
        }
        $this->_oLogger->unindent();
    }

    private $_aPathsToHandle;

    private function analyzeRegisteredPaths ()
    {
        $this->_aPathsToHandle = array();
        $aPaths = array_keys(self::$_aRegisteredPaths);
        //$this->_oLogger->log(print_r($aPaths, true));

        $sBaseSymLink = $this->_oProperties->getProperty('base_dir');
        foreach ($aPaths as $sPath) {
            $aExpandedPaths = $this->_expandPath($sPath);
            foreach ($aExpandedPaths as $sExpandedPath) {
                list($bIsRemote, $sServer, $sRealPath) = $this->_oShell->isRemotePath($sExpandedPath);
                if ($bIsRemote && strpos($sRealPath, $sBaseSymLink) !== false) {
                    $this->_aPathsToHandle[$sServer][] = $sRealPath;
                }
            }
        }

        //$this->_oLogger->log(print_r($this->_aPathsToHandle, true));
        $aServersWithSymlinks = array_keys($this->_aPathsToHandle);
        sort($aServersWithSymlinks);
        $sMsg = "Servers concerned with base directory: '" . implode("', '", $aServersWithSymlinks) . "'.";
        $this->_oLogger->log($sMsg);
        $this->_oProperties->setProperty('servers_concerned_with_base_dir', implode(' ', $aServersWithSymlinks));
    }

    private function makeTransitionToSymlinks ()
    {
        $this->_oLogger->log('If needed, make transition to symlinks');
        $this->_oLogger->indent();
        $this->_oProperties->setProperty('with_symlinks', 'false');
        $sBaseSymLink = $this->_oProperties->getProperty('base_dir');
        $sPath = '${SERVERS_CONCERNED_WITH_BASE_DIR}' . ':' . $sBaseSymLink;
        foreach ($this->_expandPath($sPath) as $sExpandedPath) {
            if ($this->_oShell->getPathStatus($sExpandedPath) === Shell_PathStatus::STATUS_DIR) {
                list(, $sServer, ) = $this->_oShell->isRemotePath($sExpandedPath);
                $sDir = $sExpandedPath . '/*';
                $sOriginRelease = $sServer . ':' . $sBaseSymLink . self::RELEASES_DIRECTORY_SUFFIX
                                . '/' . $this->_oProperties->getProperty('execution_id') . '_origin';
                $this->_oLogger->log("Backup '$sDir' to '$sOriginRelease'.");
                $this->_oShell->sync($sDir, $sOriginRelease, array(), array('smarty/*/wrt*', 'smarty/**/wrt*'));
                $this->_oShell->remove($sExpandedPath);
                $this->_oShell->createLink($sExpandedPath, $sOriginRelease);
            }
        }

        $this->_oProperties->setProperty('with_symlinks', 'true');
        $this->_oLogger->unindent();
    }

    private function makeTransitionFromSymlinks ()
    {
        $this->_oLogger->log('If needed, make transition from symlinks');
        $this->_oLogger->indent();
        $sBaseSymLink = $this->_oProperties->getProperty('base_dir');
        $sPath = '${SERVERS_CONCERNED_WITH_BASE_DIR}' . ':' . $sBaseSymLink;
        foreach ($this->_expandPath($sPath) as $sExpandedPath) {
            if ($this->_oShell->getPathStatus($sExpandedPath) === Shell_PathStatus::STATUS_SYMLINKED_DIR) {
                list(, , $sRealPath) = $this->_oShell->isRemotePath($sExpandedPath);
                $sDir = $sExpandedPath . '/*';
                $sTmpDest = $sExpandedPath . '_tmp';
                $sMsg = "Remove symlink on '$sExpandedPath' base directory"
                      . " and initialize it with last release's content.";
                $this->_oLogger->log($sMsg);
                $this->_oShell->sync($sDir, $sTmpDest, array(), array('smarty/*/wrt*', 'smarty/**/wrt*'));
                $this->_oShell->remove($sExpandedPath);
                $this->_oShell->execSSH("mv %s '" . $sRealPath . "'", $sTmpDest);
            }
        }
        $this->_oLogger->unindent();
    }

    private function initNewRelease ()
    {
        $this->_oLogger->log('Initialize with content of previous deployment');
        $this->_oLogger->indent();
        $this->_oProperties->setProperty('with_symlinks', 'false');
        $sBaseSymLink = $this->_oProperties->getProperty('base_dir');
        $sPath = '${SERVERS_CONCERNED_WITH_BASE_DIR}' . ':' . $sBaseSymLink;
        $sReleaseSymLink = $sBaseSymLink . self::RELEASES_DIRECTORY_SUFFIX
                         . '/' . $this->_oProperties->getProperty('execution_id');
        foreach ($this->_expandPath($sPath) as $sExpandedPath) {
            list(, $sServer, ) = $this->_oShell->isRemotePath($sExpandedPath);
            $sDir = $sExpandedPath . '/*';
            $sDest = $sServer . ':' . $sReleaseSymLink;
            if ($this->_oShell->getPathStatus($sExpandedPath) === Shell_PathStatus::STATUS_SYMLINKED_DIR) {
                $this->_oLogger->log("Initialize '$sDest' with previous deployment.");
                $this->_oLogger->indent();
                $aResults = $this->_oShell->sync($sDir, $sDest, array(), array('smarty/*/wrt*', 'smarty/**/wrt*'));
                foreach ($aResults as $sResult) {
                    $this->_oLogger->log($sResult);
                }
                $this->_oLogger->unindent();
            } else {
                $this->_oLogger->log("No previous deployment to initialize '$sDest'.");
            }
        }
        $this->_oProperties->setProperty('with_symlinks', 'true');
        $this->_oLogger->unindent();
    }

    private function removeOldestReleases ()
    {
        $this->_oLogger->log('Remove too old releases');
        $this->_oLogger->indent();
        $this->_oProperties->setProperty('with_symlinks', 'false');
        $sBaseSymLink = $this->_oProperties->getProperty('base_dir');
        $sPath = '${SERVERS_CONCERNED_WITH_BASE_DIR}' . ':' . $sBaseSymLink . self::RELEASES_DIRECTORY_SUFFIX;
        foreach ($this->_expandPath($sPath) as $sExpandedPath) {
            list(, $sServer, $sRealPath) = $this->_oShell->isRemotePath($sExpandedPath);
            $this->_oLogger->log("Check " . $sServer);
            $this->_oLogger->indent();

            $sCmd = "if [ -d %1\$s ]; then ls -t %1\$s | grep -E '^[0-9]{14}_[0-9]{5}(_origin)?$'; fi";
            $aAllReleases = $this->_oShell->execSSH($sCmd, $sExpandedPath);
            $iNbReleases = count($aAllReleases);
            if ($iNbReleases === 0) {
                $this->_oLogger->log('No release found.');
            } else {
                $bIsQuotaExceeded = ($iNbReleases > self::$_iDefaultMaxNbReleases);
                $sMsg = $iNbReleases . ' release' . ($iNbReleases > 1 ? 's' : '') . ' found: quota '
                      . ($bIsQuotaExceeded ? 'exceeded' : 'not reached')
                      . ' (' . self::$_iDefaultMaxNbReleases . ' backups max).';
                $this->_oLogger->log($sMsg);
                if ($bIsQuotaExceeded) {
                    $aReleasesToDelete = array_slice($aAllReleases, self::$_iDefaultMaxNbReleases);
                    $sMsg = 'Release' . (count($aReleasesToDelete) > 1 ? 's' : '') . ' deleted: '
                          . implode(', ', $aReleasesToDelete) . '.';
                    $sFirst = $sExpandedPath . '/' . array_shift($aReleasesToDelete);
                    $sCmd = 'rm -rf %s';
                    if (count($aReleasesToDelete) > 0) {
                        $sCmd .= ' ' . $sRealPath . '/' . implode(' ' . $sRealPath . '/', $aReleasesToDelete);
                    }
                    $this->_oShell->execSSH($sCmd, $sFirst);
                    $this->_oLogger->log($sMsg);
                }
            }
            $this->_oLogger->unindent();
        }
        $this->_oProperties->setProperty('with_symlinks', 'true');
        $this->_oLogger->unindent();
    }

    public function setUp ()
    {
        if ($this->_oProperties->getProperty('with_symlinks') === 'true') {
            array_push($this->_aTasks, $this->_oLinkTask);
        }

        parent::setUp();

        if ($this->_oProperties->getProperty('with_symlinks') === 'true') {
            array_pop($this->_aTasks);
        }
    }

    protected function _preExecute ()
    {
        parent::_preExecute();
        $this->_oLogger->indent();
        $this->analyzeRegisteredPaths();
        if ($this->_oProperties->getProperty('with_symlinks') === 'true') {
            $this->makeTransitionToSymlinks();
            $this->initNewRelease();
            $this->removeOldestReleases();
        } else {
            $this->makeTransitionFromSymlinks();
        }
        $this->_oLogger->unindent();
    }

    protected function _postExecute()
    {
        if ($this->_oProperties->getProperty('with_symlinks') === 'true') {
            $this->_oLogger->indent();
            $this->_oProperties->setProperty('with_symlinks', 'false');
            $this->_oLinkTask->execute();
            $this->_oProperties->setProperty('with_symlinks', 'true');

            $sBaseSymLink = $this->_oProperties->getProperty('base_dir');
            $sReleaseSymLink = $sBaseSymLink . self::RELEASES_DIRECTORY_SUFFIX
                             . '/' . $this->_oProperties->getProperty('execution_id');
            $sMsg = "Change target of base directory's symbolic link to new deployment: '$sReleaseSymLink'";
            $this->_oLogger->indent();
            $this->_oLogger->log($sMsg);
            $this->_oLogger->unindent();
            $this->_oLogger->unindent();
        }
        parent::_postExecute();
    }
}
