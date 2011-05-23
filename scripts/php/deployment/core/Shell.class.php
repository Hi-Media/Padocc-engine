<?php

class Shell {

	private static $aFileStatus = array();

	/**
	 * Classe outil : pas d'instance.
	 */
	private function __construct () {}

	/**
	 * Exécute la commande shell spécifiée et retourne la sortie découpée par ligne dans un tableau.
	 * En cas d'erreur shell, lance une exception avec le message d'erreur.
	 *
	 * @param string $sCmd
	 * @throws Exception en cas d'erreur shell
	 * @return array Tableau indexé du flux de sortie découpé par ligne
	 */
	public static function exec ($sCmd) {
		if (DEPLOYMENT_DEBUG_MODE > 0) {
			echo "[Debug][Shell] $sCmd\n";
		}
		$sFullCmd = '( ' . $sCmd . ' ) 2>&1';
		$sErrorMsg = exec($sFullCmd, $aResult, $iReturnCode);
		if ($iReturnCode !== 0) {
			//throw new Exception($sErrorMsg);
			throw new Exception(implode("\n", $aResult));
		}
		return $aResult;
	}

	public static function execSSH ($sPatternCmd, $sParam) {
		list($bIsRemote, $aMatches) = self::isRemotePath($sParam);
		$sCmd = sprintf($sPatternCmd, self::escapePath($aMatches[2]));
		//$sCmd = vsprintf($sPatternCmd, array_map(array(self, 'escapePath'), $mParams));
		if ($bIsRemote) {
			$sCmd = 'ssh -T ' . $aMatches[1] . " <<EOF\n$sCmd\nEOF\n";
		}
		return self::exec($sCmd);
	}

	/**
	 * Retourne 0 si le chemin spécifié n'existe pas, 1 si c'est un fichier 'classique', 2 si c'est un répertoire.
	 * Passe par ssh au besoin.
	 *
	 * @param string $sPath chemin à tester
	 * @return int 0 si le chemin spécifié n'existe pas, 1 si c'est un fichier, 2 si c'est un répertoire.
	 */
	public static function getFileStatus ($sPath) {
		if (isset(self::$aFileStatus[$sPath])) {
			$iStatus = self::$aFileStatus[$sPath];
		} else {
			$sFormat = '[ -d %1$s ] && echo 2 || ( [ -f %1$s ] && echo 1 || echo 0 )';
			$aResult = self::execSSH($sFormat, $sPath);
			$iStatus = (int)$aResult[0];
			if ($iStatus !== 0) {
				self::$aFileStatus[$sPath] = $iStatus;
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
	public static function isRemotePath ($sPath) {
		$result = preg_match('/^((?:[a-z0-9_-]+@)[a-z0-9_.-]+):(.+)$/i', $sPath, $aMatches);
		if ($result !== 1) {
			$aMatches = array($sPath, '', $sPath);
		}
		return array($result === 1, $aMatches);
	}

	// TODO ajouter gestion tar/gz
	public static function copy ($sSrcPath, $sDestPath, $bIsDestFile=false) {
		if ($bIsDestFile) {
			self::mkdir(pathinfo($sDestPath, PATHINFO_DIRNAME));
		} else {
			self::mkdir($sDestPath);
		}
		list($bIsSrcRemote, $aSrcMatches) = self::isRemotePath($sSrcPath);
		list($bIsDestRemote, $aDestMatches) = self::isRemotePath($sDestPath);

		if ($aSrcMatches[1] != $aDestMatches[1]) {
			$sCmd = 'scp -rpq ' . self::escapePath($sSrcPath) . ' ' . self::escapePath($sDestPath);
			return self::exec($sCmd);
		} else {
			$sCmd = 'cp -ar %s ' . self::escapePath($aDestMatches[2]);
			return self::execSSH($sCmd, $sSrcPath);
		}
	}

	/**
	 * Entoure le chemin de guillemets doubles en tenant compte des jokers '*' et '?' qui ne les supportent pas.
	 * Par exemple : '/a/b/img*jpg', donnera : '"/a/b/img"*"jpg"'.
	 * Pour rappel, '*' vaut pour 0 à n caractères, '?' vaut pour exactement 1 caractère (et non 0 à 1).
	 *
	 * @param string $sPath
	 * @return string
	 */
	private static function escapePath ($sPath) {
		$sEscapedPath = preg_replace('#(\*|\?)#', '"\1"', '"' . $sPath . '"');
		$sEscapedPath = str_replace('""', '', $sEscapedPath);
		return $sEscapedPath;
	}

	public static function remove ($sPath) {
		return self::execSSH('rm -rf %s', $sPath);
	}

	/*
cd `dirname /home/gaubry/deployment_test/T`; tar cfpz /home/gaubry/deployment_backup/`basename "/home/gaubry/deployment_test/T"`.tar.gz ./`basename "/home/gaubry/deployment_test/T"`
cd `dirname /home/gaubry/deployment_test/a3.txt`; tar cfpz /home/gaubry/deployment_backup/`basename "/home/gaubry/deployment_test/a3.txt"`.tar.gz ./`basename "/home/gaubry/deployment_test/a3.txt"`

cd /home/gaubry/t; tar -xf /home/gaubry/deployment_backup/`basename "/home/gaubry/deployment_test/T"`.tar.gz
cd /home/gaubry/t; tar -xf /home/gaubry/deployment_backup/`basename "/home/gaubry/deployment_test/a3.txt"`.tar.gz
	 */
	public static function backup ($sSrcPath, $sBackupPath) {
		list($bIsSrcRemote, $aSrcMatches) = self::isRemotePath($sSrcPath);
		list($bIsBackupRemote, $aBackupMatches) = self::isRemotePath($sBackupPath);

		if ($aSrcMatches[1] != $aBackupMatches[1]) {
			$sTmpDir = ($bIsSrcRemote ? $aSrcMatches[1]. ':' : '') . '/tmp/' . uniqid('deployment_', true);
			$sTmpPath = $sTmpDir . '/' . pathinfo($sBackupPath, PATHINFO_BASENAME);
			return array_merge(
				self::backup($sSrcPath, $sTmpPath),
				self::copy($sTmpPath, $sBackupPath, true),
				self::remove($sTmpDir));
		} else {
			self::mkdir(pathinfo($sBackupPath, PATHINFO_DIRNAME));
			$sSrcFile = pathinfo($aSrcMatches[2], PATHINFO_BASENAME);
			$sFormat = 'cd %1$s; tar cfpz %2$s ./%3$s';
			if ($bIsSrcRemote) {
				$sSrcDir = pathinfo($aSrcMatches[2], PATHINFO_DIRNAME);
				$sFormat = 'ssh %4$s <<EOF' . "\n" . $sFormat . "\nEOF\n";
				$sCmd = sprintf($sFormat, self::escapePath($sSrcDir), self::escapePath($aBackupMatches[2]), self::escapePath($sSrcFile), $aSrcMatches[1]);
			} else {
				$sSrcDir = pathinfo($sSrcPath, PATHINFO_DIRNAME);
				$sCmd = sprintf($sFormat, self::escapePath($sSrcDir), self::escapePath($sBackupPath), self::escapePath($sSrcFile));
			}
			return self::exec($sCmd);
		}
	}

	public static function mkdir ($sPath) {
		return self::execSSH('mkdir -p %s', $sPath);
	}

	public static function sync ($sSrcPath, $sDestPath) {
		self::mkdir($sDestPath);
		$sCVSExclude = '--cvs-exclude --exclude=.cvsignore';
		// rsync -aqz --delete --delete-excluded -e ssh --cvs-exclude --exclude=.cvsignore --stats
		// rsync -az --delete --delete-excluded --cvs-exclude --exclude=.cvsignore --stats "/home/gaubry/test/src/[EXT] Phing 2.4.5" "gaubry@dv2:/home/gaubry/rsync_test"
		// rsync -az --delete --delete-excluded --cvs-exclude --exclude=.cvsignore --stats "/home/gaubry/test/src/merchant_logos" "gaubry@dv2:/home/gaubry/rsync_test"
		$sCmd = 'rsync -az --delete --delete-excluded ' . $sCVSExclude . ' --stats -e ssh ' . self::escapePath($sSrcPath) . ' ' . self::escapePath($sDestPath);
		return self::exec($sCmd);
	}
}
