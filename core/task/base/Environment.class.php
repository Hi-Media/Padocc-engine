<?php

class Task_Base_Environment extends Task_Base_Target
{

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
        $this->_aAttributeProperties = array_merge(
            $this->_aAttributeProperties,
            array(
                'name' => Task::ATTRIBUTE_REQUIRED,
                'mailto' => 0,
                'withsymlinks' => Task::ATTRIBUTE_BOOLEAN,
                'basedir' => Task::ATTRIBUTE_DIR | Task::ATTRIBUTE_REQUIRED
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
                list($bIsRemote, $aMatches) = $this->_oShell->isRemotePath($sExpandedPath);
                if ($bIsRemote && strpos($aMatches[2], $sBaseSymLink) !== false) {
                    $this->_aPathsToHandle[$aMatches[1]][] = $aMatches[2];
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
        $this->_oProperties->setProperty('with_symlinks', 'false');

        $sBaseSymLink = $this->_oProperties->getProperty('base_dir');
        $sPath = '${SERVERS_CONCERNED_WITH_BASE_DIR}' . ':' . $sBaseSymLink;
        foreach ($this->_expandPath($sPath) as $sExpandedPath) {
            if ($this->_oShell->getFileStatus($sExpandedPath) === 2) {
                list(, $aMatches) = $this->_oShell->isRemotePath($sExpandedPath);
                $sDir = $sExpandedPath . '/*';
                $sOriginRelease = $aMatches[1] . ':' . $sBaseSymLink . self::RELEASES_DIRECTORY_SUFFIX
                                . '/' . $this->_oProperties->getProperty('execution_id') . '_origin';
                $this->_oLogger->log("Backup '$sDir' to '$sOriginRelease'.");
                $this->_oShell->sync($sDir, $sOriginRelease, array('smarty/*/wrt*', 'smarty/**/wrt*'));
                $this->_oShell->remove($sExpandedPath);
                $this->_oShell->createLink($sExpandedPath, $sOriginRelease);
            }
        }

        $this->_oProperties->setProperty('with_symlinks', 'true');
    }

    private function makeTransitionFromSymlinks ()
    {
        $sBaseSymLink = $this->_oProperties->getProperty('base_dir');
        $sPath = '${SERVERS_CONCERNED_WITH_BASE_DIR}' . ':' . $sBaseSymLink;
        foreach ($this->_expandPath($sPath) as $sExpandedPath) {
            if ($this->_oShell->getFileStatus($sExpandedPath) === 12) {
                list(, $aMatches) = $this->_oShell->isRemotePath($sExpandedPath);
                $sDir = $sExpandedPath . '/*';
                $sTmpDest = $sExpandedPath . '_tmp';
                $sMsg = "Remove symlink on '$sExpandedPath' base directory"
                      . " and initialize it with last release's content.";
                $this->_oLogger->log($sMsg);
                $this->_oShell->sync($sDir, $sTmpDest, array('smarty/*/wrt*', 'smarty/**/wrt*'));
                $this->_oShell->remove($sExpandedPath);
                $this->_oShell->execSSH("mv %s '" . $aMatches[2] . "'", $sTmpDest);
            }
        }
    }

    private function initNewRelease ()
    {
        $this->_oProperties->setProperty('with_symlinks', 'false');

        $sBaseSymLink = $this->_oProperties->getProperty('base_dir');
        $sPath = '${SERVERS_CONCERNED_WITH_BASE_DIR}' . ':' . $sBaseSymLink;
        $sReleaseSymLink = $sBaseSymLink . self::RELEASES_DIRECTORY_SUFFIX
                         . '/' . $this->_oProperties->getProperty('execution_id');
        foreach ($this->_expandPath($sPath) as $sExpandedPath) {
            list(, $aMatches) = $this->_oShell->isRemotePath($sExpandedPath);
            $sDir = $sExpandedPath . '/*';
            $sDest = $aMatches[1] . ':' . $sReleaseSymLink;
            if ($this->_oShell->getFileStatus($sExpandedPath) === 12) {
                $this->_oLogger->log("Initialize '$sDest' with previous deployment: '$sExpandedPath'.");
                $this->_oShell->sync($sDir, $sDest, array('smarty/*/wrt*', 'smarty/**/wrt*'));
            } else {
                $this->_oLogger->log("No previous deployment to initialize '$sDest'.");
            }
        }

        $this->_oProperties->setProperty('with_symlinks', 'true');
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
        } else {
            $this->makeTransitionFromSymlinks();
        }
        $this->_oLogger->unindent();
    }

    protected function _centralExecute ()
    {
        parent::_centralExecute();
        if ($this->_oProperties->getProperty('with_symlinks') === 'true') {
            $this->_oProperties->setProperty('with_symlinks', 'false');
            $this->_oLinkTask->execute();
            $this->_oProperties->setProperty('with_symlinks', 'true');
        }
    }
}
