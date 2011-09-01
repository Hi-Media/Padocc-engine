<?php

/**
 * Classe outil facilitant l'exécution des commandes shell.
 *
 * @category TwengaDeploy
 * @package Lib
 * @author Geoffroy AUBRY <geoffroy.aubry@twenga.com>
 */
class Shell_Adapter implements Shell_Interface
{

    /**
     * Table de hashage de mise en cache des demande de statuts de chemins système.
     * @var array
     * @see getPathStatus()
     * @see Shell_PathStatusInterface
     */
    private $_aFileStatus;

    /**
     * Liste d'exclusions par défaut de toute commande rsync (traduits en --exclude xxx).
     * @var array
     * @see sync()
     */
    private static $_aDefaultRsyncExclude = array(
        '.bzr/', '.cvsignore', '.git/', '.gitignore', '.svn/', 'cvslog.*', 'CVS', 'CVS.adm'
    );

    /**
     * Log adapter, utilisé pour loguer les commandes exécutées.
     * @var Logger_Interface
     * @see exec()
     */
    private $_oLogger;

    /**
     * Constructeur.
     *
     * @param Logger_Interface $oLogger Instance utilisée pour loguer les commandes exécutées
     */
    public function __construct (Logger_Interface $oLogger)
    {
        $this->_oLogger = $oLogger;
        $this->_aFileStatus = array();
    }

    /**
     * Exécute la commande shell spécifiée et retourne la sortie découpée par ligne dans un tableau.
     * En cas d'erreur shell (code d'erreur <> 0), lance une exception incluant le message d'erreur.
     *
     * @param string $sCmd
     * @return array tableau indexé du flux de sortie shell découpé par ligne
     * @throws RuntimeException en cas d'erreur shell
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
     * Exécute la commande shell spécifiée en l'encapsulant au besoin dans une connexion SSH
     * pour atteindre les hôtes distants.
     *
     * @param string $sPatternCmd commande au format printf
     * @param string $sParam paramètre du pattern $sPatternCmd, permettant en plus de décider si l'on
     * doit encapsuler la commande dans un SSH (si serveur distant) ou non.
     * @return array tableau indexé du flux de sortie shell découpé par ligne
     * @throws RuntimeException en cas d'erreur shell
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
     * Retourne l'une des constantes de Shell_PathStatusInterface, indiquant pour le chemin spécifié s'il est
     * inexistant, un fichier, un répertoire, un lien symbolique sur fichier ou encore un lien symbolique sur
     * répertoire.
     *
     * Si le statut est différent de inexistant, l'appel est mis en cache.
     * Un appel à remove() s'efforce de maintenir cohérent ce cache.
     *
     * Le chemin spécifié peut concerner un hôte distant (user@server:/path), auquel cas un appel SSH sera effectué.
     *
     * @param string $sPath chemin à tester, de la forme [user@server:]/path
     * @return int l'une des constantes de Shell_PathStatusInterface
     * @throws RuntimeException en cas d'erreur shell
     * @see Shell_PathStatusInterface
     * @see _aFileStatus
     */
    public function getPathStatus ($sPath)
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
     * @param string $sPath chemin au format [[user@]servername_or_ip:]/path
     * @return array couple dont la 1re valeur indique si le chemin spécifié commence par '[user@]servername_or_ip:'
     * et la 2nde est un tableau indexé contenant le chemin initial, le serveur et le chemin dépourvu du serveur.
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

    /**
     * Copie un chemin vers un autre.
     * Les jokers '*' et '?' sont autorisés.
     * Par exemple copiera le contenu de $sSrcPath si celui-ci se termine par '/*'.
     * Si le chemin de destination n'existe pas, il sera créé.
     *
     * TODO ajouter gestion tar/gz
     *
     * @param string $sSrcPath chemin source, au format [[user@sername_or_ip:]/path
     * @param string $sDestPath chemin de destination, au format [[user@sername_or_ip:]/path
     * @param bool $bIsDestFile précise si le chemin de destination est un simple fichier ou non,
     * information nécessaire si l'on doit créer une partie de ce chemin si inexistant
     * @return array tableau indexé du flux de sortie shell découpé par ligne
     * @throws RuntimeException en cas d'erreur shell
     */
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
     * @param string $sLinkPath nom du lien, au format [[user@sername_or_ip:]/path
     * @param string $sTargetPath cible sur laquelle faire pointer le lien, au format [[user@sername_or_ip:]/path
     * @return array tableau indexé du flux de sortie shell découpé par ligne
     * @throws DomainException si les chemins référencent des serveurs différents
     * @throws RuntimeException en cas d'erreur shell
     */
    public function createLink ($sLinkPath, $sTargetPath)
    {
        list(, $aLinkMatches) = $this->isRemotePath($sLinkPath);
        list(, $aTargetMatches) = $this->isRemotePath($sTargetPath);
        if ($aLinkMatches[1] != $aTargetMatches[1]) {
            throw new DomainException("Hosts must be equals. Link='$sLinkPath'. Target='$sTargetPath'.");
        }
        return $this->execSSH('mkdir -p "$(dirname %1$s)" && ln -snf "' . $aTargetMatches[2] . '" %1$s', $sLinkPath);
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
     * S'efforce de maintenir cohérent le cache de statut de chemins rempli par getPathStatus().
     *
     * @param string $sPath chemin à supprimer, au format [[user@sername_or_ip:]/path
     * @return array tableau indexé du flux de sortie shell découpé par ligne
     * @throws DomainException si chemin invalide (garde-fou)
     * @throws RuntimeException en cas d'erreur shell
     * @see getPathStatus()
     */
    public function remove ($sPath)
    {
        $sPath = trim($sPath);

        // Garde-fou :
        if (empty($sPath) || strlen($sPath) < 4) {
            throw new DomainException("Illegal path: '$sPath'");
        }

        // Supprimer du cache de getPathStatus() :
        foreach (array_keys($this->_aFileStatus) as $sCachedPath) {
            if (substr($sCachedPath, 0, strlen($sPath)+1) === $sPath . '/') {
                unset($this->_aFileStatus[$sCachedPath]);
            }
        }
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

    /**
     * Crée le chemin spécifié s'il n'existe pas déjà, avec les droits éventuellement transmis dans tous les cas.
     *
     * @param string $sPath chemin à créer, au format [[user@sername_or_ip:]/path
     * @param string $sMode droits utilisateur du chemin, par ex. '644', appliqués même si ce dernier existe déjà
     * @return array tableau indexé du flux de sortie shell découpé par ligne
     * @throws RuntimeException en cas d'erreur shell
     */
    public function mkdir ($sPath, $sMode='')
    {
        // On passe par 'chmod' car 'mkdir -m xxx' exécuté ssi répertoire inexistant :
        if ($sMode !== '') {
            $sMode = " && chmod $sMode %1\$s";
        }
        return $this->execSSH("mkdir -p %1\$s$sMode", $sPath);
    }

    /**
     * Synchronise une source avec une ou plusieurs destinations.
     *
     * @param string $sSrcPath, au format [[user@sername_or_ip:]/path
     * @param string|array $mDestPath, chaque destination au format [[user@sername_or_ip:]/path
     * @param array $aExcludedPaths chemins à transmettre aux paramètres --exclude de la commande shell rsync
     * @return array tableau indexé du flux de sortie shell des commandes rsync exécutées,
     * découpé par ligne et analysé par _resumeSyncResult()
     * @throws RuntimeException en cas d'erreur shell
     * @throws RuntimeException car non implémenté quand plusieurs $mDestPath et $sSrcPath est distant
     * @throws RuntimeException car non implémenté quand un seul $mDestPath mais $sSrcPath et $mDestPath
     * pointent sur deux serveurs distants différents
     */
    public function sync ($sSrcPath, $mDestPath, array $aExcludedPaths=array())
    {
        $aPaths = (is_array($mDestPath) ? $mDestPath : array($mDestPath));

        // Cas non gérés :
        list($bIsSrcRemote, $aSrcMatches) = $this->isRemotePath($sSrcPath);
        list($bIsDestRemote, $aDestMatches) = $this->isRemotePath(reset($aPaths));
        if (
            (count($aPaths) > 1 && $bIsSrcRemote)
            || (count($aPaths) === 1 && $bIsSrcRemote && $bIsDestRemote && $aSrcMatches[1] != $aDestMatches[1])
        ) {
            throw new RuntimeException('Not yet implemented!');
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
        if (substr($sSrcPath, -2) === '/*') {
            $sRsyncCmd = 'if ls -1 "' . substr($aSrcMatches[2], 0, -2) . '" | grep -q .; then ' . $sRsyncCmd . '; fi';
        }

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
                }

                $i = $j-1;
                if (count($aCmds) > 1) {
                    $sCmd = implode(" & \\\n", $aCmds) . " & \\\nwait";
                } else {
                    $sCmd = reset($aCmds);
                }
                $aRawResult = $this->exec($sCmd);
                $aResult = $this->_resumeSyncResult($aRawResult);
                $aAllResults = array_merge($aAllResults, $aResult);
            }
        }

        return $aAllResults;
    }

    /**
     * Analyse la sortie shell de commandes rsync et en propose un résumé.
     *
     * Exemple :
     *  - entrée :
     *  	Number of files: 1774
     *  	Number of files transferred: 2
     *  	Total file size: 64093953 bytes
     *  	Total transferred file size: 178 bytes
     *  	Literal data: 178 bytes
     *  	Matched data: 0 bytes
     *  	File list size: 39177
     *  	File list generation time: 0.013 seconds
     *  	File list transfer time: 0.000 seconds
     *  	Total bytes sent: 39542
     *  	Total bytes received: 64
     *  	sent 39542 bytes  received 64 bytes  26404.00 bytes/sec
     *  	total size is 64093953  speedup is 1618.29
     *  - sortie :
     *  	Number of transferred files ( / total): 2 / 1774
     *  	Total transferred file size ( / total): 0 / 61 Mio
     *
     * @param array $aRawResult tableau indexé du flux de sortie shell de la commande rsync, découpé par ligne
     * @return array du tableau indexé du flux de sortie shell de commandes rsync résumé
     * et découpé par ligne
     */
    private function _resumeSyncResult (array $aRawResult)
    {
        if (count($aRawResult) === 0) {
            $aResult = array('Empty source directory.');
        } else {
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
        }
        return $aResult;
    }
}
