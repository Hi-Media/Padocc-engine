<?php

class Shell_Adapter implements Shell_Interface {

	private $aFileStatus;

	private static $aDefaultRsyncExclude = array('.bzr/', '.git/', '.svn/', 'cvslog.*', 'CVS', 'CVS.adm');

	/**
	 * Log adapter.
	 * @var Logger_Interface
	 */
	private $oLogger;

	public function __construct (Logger_Interface $oLogger) {
		$this->oLogger = $oLogger;
		$this->aFileStatus = array();
	}

	/**
	 * Exécute la commande shell spécifiée et retourne la sortie découpée par ligne dans un tableau.
	 * En cas d'erreur shell, lance une exception avec le message d'erreur.
	 *
	 * @param string $sCmd
	 * @throws Exception en cas d'erreur shell
	 * @return array Tableau indexé du flux de sortie découpé par ligne
	 */
	public function exec ($sCmd) {
		$this->oLogger->log('[DEBUG] shell# ' . trim($sCmd), Logger_Interface::DEBUG);
		$sFullCmd = '( ' . $sCmd . ' ) 2>&1';
		$sErrorMsg = exec($sFullCmd, $aResult, $iReturnCode);
		if ($iReturnCode !== 0) {
			//throw new Exception($sErrorMsg);
			throw new RuntimeException(implode("\n", $aResult), $iReturnCode);
		}
		return $aResult;
	}

	public function execSSH ($sPatternCmd, $sParam) {
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
	 * Passe par ssh au besoin.
	 *
	 * @param string $sPath chemin à tester
	 * @return int 0 si le chemin spécifié n'existe pas, 1 si c'est un fichier, 2 si c'est un répertoire.
	 */
	public function getFileStatus ($sPath) {
		if (isset($this->aFileStatus[$sPath])) {
			$iStatus = $this->aFileStatus[$sPath];
		} else {
			$sFormat = '[ -d %1$s ] && echo 2 || ( [ -f %1$s ] && echo 1 || echo 0 )';
			$aResult = $this->execSSH($sFormat, $sPath);
			$iStatus = (int)$aResult[0];
			if ($iStatus !== 0) {
				$this->aFileStatus[$sPath] = $iStatus;
			}
		}
		return $iStatus;
	}

	/**
	 * Retourne un couple dont la 1re valeur indique si oui ou non le chemin spécifié commence par '[user@]servername_or_ip:'
	 * et la 2nde est un tableau indexé contenant le chemin initial, le serveur et le chemin dépourvu du serveur.
	 *
	 * @param string $sPath
	 * @return array
	 */
	public function isRemotePath ($sPath) {
		if (preg_match('/\$\{[^}]*\}/i', $sPath) === 1) {
			throw new RuntimeException("Invalid syntax: '$sPath'.");
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
	public function copy ($sSrcPath, $sDestPath, $bIsDestFile=false) {
		if ($bIsDestFile) {
			$this->mkdir(pathinfo($sDestPath, PATHINFO_DIRNAME));
		} else {
			$this->mkdir($sDestPath);
		}
		list($bIsSrcRemote, $aSrcMatches) = $this->isRemotePath($sSrcPath);
		list($bIsDestRemote, $aDestMatches) = $this->isRemotePath($sDestPath);

		if ($aSrcMatches[1] != $aDestMatches[1]) {
			$sCmd = 'scp -rpq ' . $this->escapePath($sSrcPath) . ' ' . $this->escapePath($sDestPath);
			return $this->exec($sCmd);
		} else {
			$sCmd = 'cp -ar %s ' . $this->escapePath($aDestMatches[2]);
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
	public function createLink ($sLinkPath, $sTargetPath) {
		list($bIsSrcRemote, $aSrcMatches) = $this->isRemotePath($sTargetPath);
		return $this->execSSH('mkdir -p "$(dirname %1$s)" && ln -sf "' . $aSrcMatches[2] . '" %1$s', $sLinkPath);
	}

	/**
	 * Entoure le chemin de guillemets doubles en tenant compte des jokers '*' et '?' qui ne les supportent pas.
	 * Par exemple : '/a/b/img*jpg', donnera : '"/a/b/img"*"jpg"'.
	 * Pour rappel, '*' vaut pour 0 à n caractères, '?' vaut pour exactement 1 caractère (et non 0 à 1).
	 *
	 * @param string $sPath
	 * @return string
	 */
	public function escapePath ($sPath) {
		$sEscapedPath = preg_replace('#(\*|\?)#', '"\1"', '"' . $sPath . '"');
		$sEscapedPath = str_replace('""', '', $sEscapedPath);
		return $sEscapedPath;
	}

	public function remove ($sPath) {
		$sPath = trim($sPath);
		if (empty($sPath) || strlen($sPath) < 4) {
			throw new BadMethodCallException("Illegal path: '$sPath'");
		}
		return $this->execSSH('rm -rf %s', $sPath);
	}

	/*
cd `dirname /home/gaubry/deployment_test/T`; tar cfpz /home/gaubry/deployment_backup/`basename "/home/gaubry/deployment_test/T"`.tar.gz ./`basename "/home/gaubry/deployment_test/T"`
cd `dirname /home/gaubry/deployment_test/a3.txt`; tar cfpz /home/gaubry/deployment_backup/`basename "/home/gaubry/deployment_test/a3.txt"`.tar.gz ./`basename "/home/gaubry/deployment_test/a3.txt"`

cd /home/gaubry/t; tar -xf /home/gaubry/deployment_backup/`basename "/home/gaubry/deployment_test/T"`.tar.gz
cd /home/gaubry/t; tar -xf /home/gaubry/deployment_backup/`basename "/home/gaubry/deployment_test/a3.txt"`.tar.gz
	 */
	public function backup ($sSrcPath, $sBackupPath) {
		list($bIsSrcRemote, $aSrcMatches) = $this->isRemotePath($sSrcPath);
		list($bIsBackupRemote, $aBackupMatches) = $this->isRemotePath($sBackupPath);

		if ($aSrcMatches[1] != $aBackupMatches[1]) {
			$sTmpDir = ($bIsSrcRemote ? $aSrcMatches[1]. ':' : '') . '/tmp/' . uniqid('deployment_', true);
			$sTmpPath = $sTmpDir . '/' . pathinfo($sBackupPath, PATHINFO_BASENAME);
			return array_merge(
				$this->backup($sSrcPath, $sTmpPath),
				$this->copy($sTmpPath, $sBackupPath, true),
				$this->remove($sTmpDir));
		} else {
			$this->mkdir(pathinfo($sBackupPath, PATHINFO_DIRNAME));
			$sSrcFile = pathinfo($aSrcMatches[2], PATHINFO_BASENAME);
			$sFormat = 'cd %1$s; tar cfpz %2$s ./%3$s';
			if ($bIsSrcRemote) {
				$sSrcDir = pathinfo($aSrcMatches[2], PATHINFO_DIRNAME);
				$sFormat = 'ssh %4$s <<EOF' . "\n" . $sFormat . "\nEOF\n";
				$sCmd = sprintf($sFormat, $this->escapePath($sSrcDir), $this->escapePath($aBackupMatches[2]), $this->escapePath($sSrcFile), $aSrcMatches[1]);
			} else {
				$sSrcDir = pathinfo($sSrcPath, PATHINFO_DIRNAME);
				$sCmd = sprintf($sFormat, $this->escapePath($sSrcDir), $this->escapePath($sBackupPath), $this->escapePath($sSrcFile));
			}
			return $this->exec($sCmd);
		}
	}

	public function mkdir ($sPath, $sMode='') {
		if ($sMode !== '') {
			$sMode = "-m $sMode ";
		}
		return $this->execSSH("mkdir $sMode-p %s", $sPath);
	}

	/*
time ( \
	rsync -az --delete --delete-excluded --cvs-exclude --exclude=.cvsignore --stats -e ssh "/home/gaubry/deployment_test/src/test_gitexport1/"* "aai@aai-01:/home/aai/deployment_test/dest/test_gitexport1" & \
	rsync -az --delete --delete-excluded --cvs-exclude --exclude=.cvsignore --stats -e ssh "/home/gaubry/deployment_test/src/test_gitexport1/"* "aai@aai-02:/home/aai/deployment_test/dest/test_gitexport1" & \
	rsync -az --delete --delete-excluded --cvs-exclude --exclude=.cvsignore --stats -e ssh "/home/gaubry/deployment_test/src/test_gitexport1/"* "gaubry@dv2:/home/gaubry/deployment_test/dest/test_gitexport1" & \
	wait)

t="$(tempfile)"; ls sss 2>>$t & ls dfhdfh 2>>$t & wait; [ ! -s "$t" ] && echo ">>OK" || (cat $t; rm -f $t; exit 2)

rsync  --bwlimit=4000
	 */
	public function sync ($sSrcPath, $mDestPath, array $aExcludedPaths=array()) {
		$aPaths = (is_array($mDestPath) ? $mDestPath : array($mDestPath));

		$aAllResults = array();
		for ($i=0; $i<count($aPaths); $i++) {
			$aResult = $this->mkdir($aPaths[$i]);
			$aAllResults = array_merge($aAllResults, $aResult);
		}

		$aExcludedPaths = array_merge(self::$aDefaultRsyncExclude, $aExcludedPaths);
		for ($i=0; $i<count($aPaths); $i++) {
			$sAdditionalExclude = (count($aExcludedPaths) === 0 ? '' : '--exclude="' . implode('" --exclude="', $aExcludedPaths) . '" ');

			$aCmd = array();
			for ($j=$i; $j<count($aPaths) && $j<$i+DEPLOYMENT_RSYNC_MAX_NB_PROCESSES; $j++) {
				$aCmd[] =
					'rsync -az --delete --exclude=.cvsignore ' . $sAdditionalExclude . '--stats -e'
					. ' ssh ' . $this->escapePath($sSrcPath) . ' ' . $this->escapePath($aPaths[$j]);
			}
			$i = $j-1;
			$sCmd = implode(" & \\\n", $aCmd) . (count($aCmd) > 1 ? " & \\\nwait" : '');
			$aRawResult = $this->exec($sCmd);
			$aResult = $this->_resumeSyncResult($aRawResult);
			$aAllResults = array_merge($aAllResults, $aResult);
		}

		return $aAllResults;
	}

	private function _resumeSyncResult (array $aRawResult) {
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
			$aResult[] =
				'Number of transferred files ( / total): ' . $aStats['number of files transferred'] . ' / ' . $aStats['number of files'] . "\n"
				. 'Total transferred file size ( / total): ' . round($aStats['total transferred file size']/1024/1024) . ' / ' . round($aStats['total file size']/1024/1024) . " Mio\n";
		}
		return $aResult;
	}
}
