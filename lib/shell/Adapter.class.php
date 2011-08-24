<?php

class Shell_Adapter implements Shell_Interface
{

    private $_aFileStatus;

    private static $_aDefaultRsyncExclude = array('.bzr/', '.cvsignore', '.git/', '.gitignore', '.svn/', 'cvslog.*',
                                                 'CVS', 'CVS.adm');

    /**
     * Log adapter.
     * @var Logger_Interface
     */
    private $_oLogger;

    public function __construct (Logger_Interface $oLogger)
    {
        $this->_oLogger = $oLogger;
        $this->_aFileStatus = array();
    }

    /**
     * Exécute la commande shell spécifiée et retourne la sortie découpée par ligne dans un tableau.
     * En cas d'erreur shell, lance une exception avec le message d'erreur.
     *
     * @param string $sCmd
     * @throws RuntimeException en cas d'erreur shell
     * @return array Tableau indexé du flux de sortie découpé par ligne
     */
    public function exec ($sCmd)
    {
        $this->_oLogger->log('[DEBUG] shell# ' . trim($sCmd), Logger_Interface::DEBUG);
        $sFullCmd = '( ' . $sCmd . ' ) 2>&1';
        exec($sFullCmd, $aResult, $iReturnCode);
        if ($iReturnCode !== 0) {
            throw new RuntimeException(implode("\n", $aResult), $iReturnCode);
        }
        return $aResult;
    }

    /**
     * Exécute la commande spécifiée en l'encapsulant au besoin dans une connexion SSH.
     *
     * @param string $sPatternCmd commande au format printf
     * @param string $sParam paramètre du pattern $sPatternCmd, permettant en plus de décider si l'on
     * doit encapsuler la commande dans un SSH (si serveur distant) ou non.
     * @return array Tableau indexé du flux de sortie découpé par ligne
     * @see isRemotePath()
     */
    public function execSSH ($sPatternCmd, $sParam)
    {
        list($bIsRemote, $aMatches) = $this->isRemotePath($sParam);
        $sCmd = sprintf($sPatternCmd, $this->escapePath($aMatches[2]));
        //$sCmd = vsprintf($sPatternCmd, array_map(array(self, 'escapePath'), $mParams));
        if ($bIsRemote) {
            $sCmd = 'ssh -T ' . $aMatches[1] . " /bin/bash <<EOF\n$sCmd\nEOF\n";
        }
        return $this->exec($sCmd);
    }

    /**
     * Retourne 0 si le chemin spécifié n'existe pas, 1 si c'est un fichier 'classique', 2 si c'est un répertoire.
     * Si le statut est différent de 0, l'appel est mis en cache.
     * Passe par SSH au besoin.
     *
     * TODO NOTER que marche aussi sur distant.
     * TODO retourne codes lien sym
     * TODO see remove()
     *
     * @param string $sPath chemin à tester
     * @return int 0 si le chemin spécifié n'existe pas, 1 si c'est un fichier, 2 si c'est un répertoire.
     */
    public function getFileStatus ($sPath)
    {
        if (isset($this->_aFileStatus[$sPath])) {
            $iStatus = $this->_aFileStatus[$sPath];
        } else {
            $sFormat = '[ -h %1$s ] && echo -n 1; [ -d %1$s ] && echo 2 || ([ -f %1$s ] && echo 1 || echo 0)';
            $aResult = $this->execSSH($sFormat, $sPath);
            $iStatus = (int)$aResult[0];
            if ($iStatus !== 0) {
                $this->_aFileStatus[$sPath] = $iStatus;
            }
        }
        return $iStatus;
    }

    /**
     * Retourne un couple dont la 1re valeur indique si le chemin spécifié commence par '[user@]servername_or_ip:'
     * et la 2nde est un tableau indexé contenant le chemin initial, le serveur et le chemin dépourvu du serveur.
     *
     * @param string $sPath
     * @return array
     * @throws DomainException si syntaxe invalide
     */
    public function isRemotePath ($sPath)
    {
        if (preg_match('/\$\{[^}]*\}/i', $sPath) === 1) {
            throw new DomainException("Invalid syntax: '$sPath'.");
        }

        $result = preg_match('/^((?:[a-z0-9_.-]+@)?[a-z0-9_.-]+):(.+)$/i', $sPath, $aMatches);
        if ($result !== 1) {
            $aMatches = array($sPath, '', $sPath);
        }
        return array($result === 1, $aMatches);
    }

    // TODO ajouter gestion tar/gz
    // TODO ajouter gestion destfile
    // TODO a priori, $sSrcPath est un $sSrcFilePath
    public function copy ($sSrcPath, $sDestPath, $bIsDestFile=false)
    {
        if ($bIsDestFile) {
            $this->mkdir(pathinfo($sDestPath, PATHINFO_DIRNAME));
        } else {
            $this->mkdir($sDestPath);
        }
        list(, $aSrcMatches) = $this->isRemotePath($sSrcPath);
        list(, $aDestMatches) = $this->isRemotePath($sDestPath);

        if ($aSrcMatches[1] != $aDestMatches[1]) {
            $sCmd = 'scp -rpq ' . $this->escapePath($sSrcPath) . ' ' . $this->escapePath($sDestPath);
            return $this->exec($sCmd);
        } else {
            $sCmd = 'cp -a %s ' . $this->escapePath($aDestMatches[2]);
            return $this->execSSH($sCmd, $sSrcPath);
        }
    }

    /**
     * Crée un lien symbolique de chemin $sLinkPath vers la cible $sTargetPath.
     *
     * @param string $sLinkPath nom du lien
     * @param string $sTargetPath cible sur laquelle faire pointer le lien
     * @see Shell_Interface::createLink()
     */
    public function createLink ($sLinkPath, $sTargetPath)
    {
        list(, $aSrcMatches) = $this->isRemotePath($sTargetPath);
        return $this->execSSH('mkdir -p "$(dirname %1$s)" && ln -snf "' . $aSrcMatches[2] . '" %1$s', $sLinkPath);
    }

    /**
     * Entoure le chemin de guillemets doubles en tenant compte des jokers '*' et '?' qui ne les supportent pas.
     * Par exemple : '/a/b/img*jpg', donnera : '"/a/b/img"*"jpg"'.
     * Pour rappel, '*' vaut pour 0 à n caractères, '?' vaut pour exactement 1 caractère (et non 0 à 1).
     *
     * @param string $sPath
     * @return string
     */
    public function escapePath ($sPath)
    {
        $sEscapedPath = preg_replace('#(\*|\?)#', '"\1"', '"' . $sPath . '"');
        $sEscapedPath = str_replace('""', '', $sEscapedPath);
        return $sEscapedPath;
    }

    /**
     * Supprime le chemin spécifié, répertoire ou fichier, distant ou local.
     * Exemple : 'aai@aai-01:/path/to/delete'
     *
     * @param string $sPath chemin à spécifier
     * @return array Tableau indexé du flux de sortie découpé par ligne
     * @throws DomainException si chemin invalide
     */
    public function remove ($sPath)
    {
        $sPath = trim($sPath);

        // Garde-fou :
        if (empty($sPath) || strlen($sPath) < 4) {
            throw new DomainException("Illegal path: '$sPath'");
        }

        // Supprimer du cache de getFileStatus() :
        unset($this->_aFileStatus[$sPath]);

        return $this->execSSH('rm -rf %s', $sPath);
    }

    public function backup ($sSrcPath, $sBackupPath)
    {
        list($bIsSrcRemote, $aSrcMatches) = $this->isRemotePath($sSrcPath);
        list(, $aBackupMatches) = $this->isRemotePath($sBackupPath);

        if ($aSrcMatches[1] != $aBackupMatches[1]) {
            $sTmpDir = ($bIsSrcRemote ? $aSrcMatches[1]. ':' : '') . '/tmp/' . uniqid('deployment_', true);
            $sTmpPath = $sTmpDir . '/' . pathinfo($sBackupPath, PATHINFO_BASENAME);
            return array_merge(
                $this->backup($sSrcPath, $sTmpPath),
                $this->copy($sTmpPath, $sBackupPath, true),
                $this->remove($sTmpDir)
            );
        } else {
            $this->mkdir(pathinfo($sBackupPath, PATHINFO_DIRNAME));
            $sSrcFile = pathinfo($aSrcMatches[2], PATHINFO_BASENAME);
            $sFormat = 'cd %1$s; tar cfpz %2$s ./%3$s';
            if ($bIsSrcRemote) {
                $sSrcDir = pathinfo($aSrcMatches[2], PATHINFO_DIRNAME);
                $sFormat = 'ssh %4$s <<EOF' . "\n" . $sFormat . "\nEOF\n";
                $sCmd = sprintf(
                    $sFormat,
                    $this->escapePath($sSrcDir),
                    $this->escapePath($aBackupMatches[2]),
                    $this->escapePath($sSrcFile), $aSrcMatches[1]
                );
            } else {
                $sSrcDir = pathinfo($sSrcPath, PATHINFO_DIRNAME);
                $sCmd = sprintf(
                    $sFormat,
                    $this->escapePath($sSrcDir),
                    $this->escapePath($sBackupPath),
                    $this->escapePath($sSrcFile)
                );
            }
            return $this->exec($sCmd);
        }
    }

    // mkdir -m xxx exécuté ssi répertoire inexistant...
    public function mkdir ($sPath, $sMode='')
    {
        if ($sMode !== '') {
            $sMode = " && chmod $sMode %1\$s";
        }
        return $this->execSSH("mkdir -p %1\$s$sMode", $sPath);
    }

    /*
time ( \
    rsync -axz --delete --delete-excluded --cvs-exclude --exclude=.cvsignore --stats -e ssh "/home/gaubry/deployment_test/src/test_gitexport1/"* "aai@aai-01:/home/aai/deployment_test/dest/test_gitexport1" & \
    rsync -axz --delete --delete-excluded --cvs-exclude --exclude=.cvsignore --stats -e ssh "/home/gaubry/deployment_test/src/test_gitexport1/"* "aai@aai-02:/home/aai/deployment_test/dest/test_gitexport1" & \
    rsync -axz --delete --delete-excluded --cvs-exclude --exclude=.cvsignore --stats -e ssh "/home/gaubry/deployment_test/src/test_gitexport1/"* "gaubry@dv2:/home/gaubry/deployment_test/dest/test_gitexport1" & \
    wait)

t="$(tempfile)"; ls sss 2>>$t & ls dfhdfh 2>>$t & wait; [ ! -s "$t" ] && echo ">>OK" || (cat $t; rm -f $t; exit 2)

rsync  --bwlimit=4000
     */
    public function sync ($sSrcPath, $mDestPath, array $aExcludedPaths=array())
    {
        $aPaths = (is_array($mDestPath) ? $mDestPath : array($mDestPath));

        // Cas non gérés :
        list($bIsSrcRemote, $aSrcMatches) = $this->isRemotePath($sSrcPath);
        list($bIsDestRemote, $aDestMatches) = $this->isRemotePath(reset($aPaths));
        if (
            (count($aPaths) > 1 && $bIsSrcRemote)
            || (count($aPaths) === 1 && $bIsSrcRemote && $bIsDestRemote && $aSrcMatches[1] != $aDestMatches[1]))
        {
            throw new RuntimeException('Not yet implemented!', $code, $previous);

        }

        $aAllResults = array();
        for ($i=0; $i<count($aPaths); $i++) {
            $aResult = $this->mkdir($aPaths[$i]);
            $aAllResults = array_merge($aAllResults, $aResult);
        }

        $aExcludedPaths = array_merge(self::$_aDefaultRsyncExclude, $aExcludedPaths);
        $sAdditionalExclude = (count($aExcludedPaths) === 0
                              ? ''
                              : '--exclude="' . implode('" --exclude="', $aExcludedPaths) . '" ');
        $sRsyncCmd = 'rsync -axz --delete ' . $sAdditionalExclude . '--stats -e ssh %1$s %2$s';

        if (count($aPaths) === 1 && $bIsSrcRemote && $bIsDestRemote && $aSrcMatches[1] == $aDestMatches[1]) {
            $sCmd = sprintf($sRsyncCmd, '%1$s', $this->escapePath($aDestMatches[2]));
            $aRawResult = $this->execSSH($sCmd, $sSrcPath);
            $aResult = $this->_resumeSyncResult($aRawResult);
            $aAllResults = array_merge($aAllResults, $aResult);

        } else {
            for ($i=0; $i<count($aPaths); $i++) {
                $aCmds = array();
                for ($j=$i; $j<count($aPaths) && $j<$i+DEPLOYMENT_RSYNC_MAX_NB_PROCESSES; $j++) {
                    $aCmds[] = sprintf($sRsyncCmd, $this->escapePath($sSrcPath), $this->escapePath($aPaths[$j]));
                        /*'rsync -axz --delete ' . $sAdditionalExclude . '--stats -e'
                        . ' ssh ' . $this->escapePath($sSrcPath) . ' ' . $this->escapePath($aPaths[$j]);*/
                }
                $i = $j-1;
                $sCmd = implode(" & \\\n", $aCmds) . (count($aCmds) > 1 ? " & \\\nwait" : '');
                $aRawResult = $this->exec($sCmd);
                $aResult = $this->_resumeSyncResult($aRawResult);
                $aAllResults = array_merge($aAllResults, $aResult);
            }
        }

        return $aAllResults;
    }

    private function _resumeSyncResult (array $aRawResult)
    {
        $aKeys = array(
            'number of files',
            'number of files transferred',
            'total file size',
            'total transferred file size',
        );
        $aEmptyStats = array_fill_keys($aKeys, '?');

        $aAllStats = array();
        $aStats = NULL;
        foreach ($aRawResult as $sLine) {
            if (preg_match('/^([^:]+):\s(\d+)\b/i', $sLine, $aMatches) === 1) {
                $sKey = strtolower($aMatches[1]);
                if ($sKey === 'number of files') {
                    if ($aStats !== NULL) {
                        $aAllStats[] = $aStats;
                    }
                    $aStats = $aEmptyStats;
                }
                if (isset($aStats[$sKey])) {
                    $aStats[$sKey] = (int)$aMatches[2];
                }
            }
        }
        if ($aStats !== NULL) {
            $aAllStats[] = $aStats;
        }

        $aResult = array();
        foreach ($aAllStats as $aStats) {
            $aResult[] = 'Number of transferred files ( / total): ' . $aStats['number of files transferred']
                       . ' / ' . $aStats['number of files'] . "\n"
                       . 'Total transferred file size ( / total): '
                       . round($aStats['total transferred file size']/1024/1024)
                       . ' / ' . round($aStats['total file size']/1024/1024) . " Mio\n";
        }
        return $aResult;
    }
}
