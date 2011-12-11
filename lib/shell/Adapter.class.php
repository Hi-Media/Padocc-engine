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
     * @see Shell_PathStatus
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
     * Exécute dans des processus parallèles les déclinaisons du pattern spécifié en fonction des valeurs.
     *
     * Exemple : $this->parallelize(array('aai@aai-01', 'prod@aai-01'), "ssh [] /bin/bash <<EOF\nls -l\nEOF\n");
     * Exemple : $this->parallelize(array('a', 'b'), 'cat /.../resources/[].txt');
     *
     * @param array $aValues liste de valeurs qui viendront remplacer le '[]' du pattern
     * @param string $sPattern pattern possédant une ou plusieurs occurences de paires de crochets vides '[]'
     * qui seront remplacées dans les processus lancés en parallèle par l'une des valeurs spécifiées.
     * @return array TODO
     */
    public function parallelize (array $aValues, $sPattern) {
        $sCmdPattern = DEPLOYMENT_BASH_PATH . ' ' . DEPLOYMENT_LIB_DIR . '/parallelize.inc.sh "%s" "%s"';
        $sCmd = sprintf($sCmdPattern, implode(' ', $aValues), $sPattern);
        //var_dump($sCmd);
        $aResult = $this->exec($sCmd);
        //var_dump($aResult);

        $sResult = implode("\n", $aResult) . "\n";
        //var_dump($sResult);
        preg_match_all(
            '#^---\[(.*?)\]-->(\d+)\|(\d+)s\n\[CMD\]\n(.*?)\n\[OUT\]\n(.*?)\[ERR\]\n(.*?)///#ms',
            $sResult,
            $aMatches,
            PREG_SET_ORDER
        );

        $aResult = array();
        foreach ($aMatches as $aSet) {
            $aResult[] = array(
                'value' => $aSet[1],
                'error_code' => $aSet[2],
                'elapsed_time' => $aSet[3],
                'cmd' => $aSet[4],
                'output' => (strlen($aSet[5]) > 0 ? substr($aSet[5], 0, -1) : ''),
                'error' => (strlen($aSet[6]) > 0 ? substr($aSet[6], 0, -1) : '')
            );
        }
        return $aResult;
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
        $this->_oLogger->log('[DEBUG] shell# ' . trim($sCmd, " \t"), Logger_Interface::DEBUG);
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
        list($bIsRemote, $sServer, $sRealPath) = $this->isRemotePath($sParam);
        $sCmd = sprintf($sPatternCmd, $this->escapePath($sRealPath));
        //$sCmd = vsprintf($sPatternCmd, array_map(array(self, 'escapePath'), $mParams));
        if ($bIsRemote) {
            $sSSHOptions = ' -o StrictHostKeyChecking=no'
                         . ' -o ConnectTimeout=' . DEPLOYMENT_SSH_CONNECTION_TIMEOUT
                         . ' -o BatchMode=yes';
            $sCmd = 'ssh' . $sSSHOptions . ' -T ' . $sServer . " /bin/bash <<EOF\n$sCmd\nEOF\n";
        }
        return $this->exec($sCmd);
    }

    /**
     * Retourne l'une des constantes de Shell_PathStatus, indiquant pour le chemin spécifié s'il est
     * inexistant, un fichier, un répertoire, un lien symbolique sur fichier ou encore un lien symbolique sur
     * répertoire.
     *
     * Les éventuels slash terminaux sont supprimés.
     * Si le statut est différent de inexistant, l'appel est mis en cache.
     * Un appel à remove() s'efforce de maintenir cohérent ce cache.
     *
     * Le chemin spécifié peut concerner un hôte distant (user@server:/path), auquel cas un appel SSH sera effectué.
     *
     * @param string $sPath chemin à tester, de la forme [user@server:]/path
     * @return int l'une des constantes de Shell_PathStatus
     * @throws RuntimeException en cas d'erreur shell
     * @see Shell_PathStatus
     * @see _aFileStatus
     */
    public function getPathStatus ($sPath)
    {
        if (substr($sPath, -1) === '/') {
            $sPath = substr($sPath, 0, -1);
        }
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
     * Retourne un triplet dont la 1re valeur (bool) indique si le chemin spécifié commence par
     * '[user@]servername_or_ip:', la 2e (string) est le serveur (ou chaîne vide si $sPath est local),
     * et la 3e (string) est le chemin dépourvu de l'éventuel serveur.
     *
     * @param string $sPath chemin au format [[user@]servername_or_ip:]/path
     * @return array triplet dont la 1re valeur (bool) indique si le chemin spécifié commence par
     * '[user@]servername_or_ip:', la 2e (string) est le serveur (ou chaîne vide si $sPath est local),
     * et la 3e (string) est le chemin dépourvu de l'éventuel serveur.
     */
    public function isRemotePath ($sPath)
    {
        $result = preg_match('/^((?:[^@]+@)?[^:]+):(.+)$/i', $sPath, $aMatches);
        $bIsRemotePath = ($result === 1);
        if ($bIsRemotePath) {
            $sServer = $aMatches[1];
            $sRealPath = $aMatches[2];
        } else {
            $sServer = '';
            $sRealPath = $sPath;
        }

        return array($bIsRemotePath, $sServer, $sRealPath);
    }

    /**
     * Copie un chemin vers un autre.
     * Les jokers '*' et '?' sont autorisés.
     * Par exemple copiera le contenu de $sSrcPath si celui-ci se termine par '/*'.
     * Si le chemin de destination n'existe pas, il sera créé.
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
        list(, $sSrcServer, ) = $this->isRemotePath($sSrcPath);
        list(, $sDestServer, $sDestRealPath) = $this->isRemotePath($sDestPath);

        if ($sSrcServer != $sDestServer) {
            $sCmd = 'scp -rpq ' . $this->escapePath($sSrcPath) . ' ' . $this->escapePath($sDestPath);
            return $this->exec($sCmd);
        } else {
            $sCmd = 'cp -a %s ' . $this->escapePath($sDestRealPath);
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
        list(, $sLinkServer, ) = $this->isRemotePath($sLinkPath);
        list(, $sTargetServer, $sTargetRealPath) = $this->isRemotePath($sTargetPath);
        if ($sLinkServer != $sTargetServer) {
            throw new DomainException("Hosts must be equals. Link='$sLinkPath'. Target='$sTargetPath'.");
        }
        return $this->execSSH('mkdir -p "$(dirname %1$s)" && ln -snf "' . $sTargetRealPath . '" %1$s', $sLinkPath);
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

    /**
     * Effectue un tar gzip du répertoire $sSrcPath dans $sBackupPath.
     *
     * @param string $sSrcPath au format [[user@sername_or_ip:]/path
     * @param string $sBackupPath au format [[user@sername_or_ip:]/path
     * @return array tableau indexé du flux de sortie shell découpé par ligne
     * @throws RuntimeException en cas d'erreur shell
     */
    public function backup ($sSrcPath, $sBackupPath)
    {
        list($bIsSrcRemote, $sSrcServer, $sSrcRealPath) = $this->isRemotePath($sSrcPath);
        list(, $sBackupServer, $sBackupRealPath) = $this->isRemotePath($sBackupPath);

        if ($sSrcServer != $sBackupServer) {
            $sTmpDir = ($bIsSrcRemote ? $sSrcServer. ':' : '') . DEPLOYMENT_TMP_DIR . '/'
                     . uniqid('deployment_', true);
            $sTmpPath = $sTmpDir . '/' . pathinfo($sBackupPath, PATHINFO_BASENAME);
            return array_merge(
                $this->backup($sSrcPath, $sTmpPath),
                $this->copy($sTmpPath, $sBackupPath, true),
                $this->remove($sTmpDir)
            );
        } else {
            $this->mkdir(pathinfo($sBackupPath, PATHINFO_DIRNAME));
            $sSrcFile = pathinfo($sSrcRealPath, PATHINFO_BASENAME);
            $sFormat = 'cd %1$s; tar cfpz %2$s ./%3$s';
            if ($bIsSrcRemote) {
                $sSrcDir = pathinfo($sSrcRealPath, PATHINFO_DIRNAME);
                $sFormat = 'ssh %4$s <<EOF' . "\n" . $sFormat . "\nEOF\n";
                $sCmd = sprintf(
                    $sFormat,
                    $this->escapePath($sSrcDir),
                    $this->escapePath($sBackupRealPath),
                    $this->escapePath($sSrcFile),
                    $sSrcServer
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
     * @param string $sMode droits utilisateur du chemin appliqués même si ce dernier existe déjà.
     * Par exemple '644'.
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
     * @param string $sSrcPath au format [[user@sername_or_ip:]/path
     * @param string|array $mDestPath chaque destination au format [[user@sername_or_ip:]/path
     * @param array $aIncludedPaths chemins à transmettre aux paramètres --include de la commande shell rsync.
     * Il précéderons les paramètres --exclude.
     * @param array $aExcludedPaths chemins à transmettre aux paramètres --exclude de la commande shell rsync
     * @return array tableau indexé du flux de sortie shell des commandes rsync exécutées,
     * découpé par ligne et analysé par _resumeSyncResult()
     * @throws RuntimeException en cas d'erreur shell
     * @throws RuntimeException car non implémenté quand plusieurs $mDestPath et $sSrcPath sont distants
     */
    public function sync ($sSrcPath, $mDestPath, array $aIncludedPaths=array(), array $aExcludedPaths=array())
    {
        $aDestPaths = (is_array($mDestPath) ? $mDestPath : array($mDestPath));

        // Cas non gérés :
        list($bIsSrcRemote, $sSrcServer, $sSrcRealPath) = $this->isRemotePath($sSrcPath);
        list($bIsDestRemote, $sDestServer, $sDestRealPath) = $this->isRemotePath(reset($aDestPaths));
        if (count($aDestPaths) > 1 && $bIsSrcRemote) {
            throw new RuntimeException('Not yet implemented!');
        }

        $aAllResults = array();
        for ($i=0; $i<count($aDestPaths); $i++) {
            $aResult = $this->mkdir($aDestPaths[$i]);
            $aAllResults = array_merge($aAllResults, $aResult);
        }

        // Inclusions / exclusions :
        $sIncludedPaths = (count($aIncludedPaths) === 0
                              ? ''
                              : '--include="' . implode('" --include="', array_unique($aIncludedPaths)) . '" ');
        $aExcludedPaths = array_unique(array_merge(self::$_aDefaultRsyncExclude, $aExcludedPaths));
        $sExcludedPaths = (count($aExcludedPaths) === 0
                              ? ''
                              : '--exclude="' . implode('" --exclude="', $aExcludedPaths) . '" ');

        // Construction de la commande :
        $sRsyncCmd = 'rsync -axz --delete ' . $sIncludedPaths . $sExcludedPaths . '--stats -e ssh %1$s %2$s';
        if (substr($sSrcPath, -2) === '/*') {
            $sRsyncCmd = 'if ls -1 "' . substr($sSrcRealPath, 0, -2) . '" | grep -q .; then ' . $sRsyncCmd . '; fi';
        }

        if (count($aDestPaths) === 1 && $bIsSrcRemote && $bIsDestRemote) {
            $sDestPath = ($sSrcServer == $sDestServer ? $sDestRealPath : reset($aDestPaths));
            $sCmd = sprintf($sRsyncCmd, '%1$s', $this->escapePath($sDestPath));
            $aRawResult = $this->execSSH($sCmd, $sSrcPath);
            $aResult = $this->_resumeSyncResult($aRawResult);
            $aAllResults = array_merge($aAllResults, $aResult);

        } else {
            for ($i=0; $i<count($aDestPaths); $i++) {
                $aCmds = array();
                for ($j=$i; $j<count($aDestPaths) && $j<$i+DEPLOYMENT_RSYNC_MAX_NB_PROCESSES; $j++) {
                    $aCmds[] = sprintf($sRsyncCmd, $this->escapePath($sSrcPath), $this->escapePath($aDestPaths[$j]));
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
     *  	Total transferred file size ( / total): <1 / 61 Mio
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
                list($sTransferred, ) = Tools::convertFileSize2String(
                    $aStats['total transferred file size'],
                    $aStats['total file size']
                );
                list($sTotal, $sUnit) = Tools::convertFileSize2String($aStats['total file size']);

                $aResult[] = 'Number of transferred files ( / total): ' . $aStats['number of files transferred']
                           . ' / ' . $aStats['number of files'] . "\n"
                           . 'Total transferred file size ( / total): '
                           . $sTransferred . ' / ' . $sTotal . " $sUnit\n";
            }
        }
        return $aResult;
    }
}
