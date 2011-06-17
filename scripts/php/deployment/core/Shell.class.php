<?php

class Shell {

	private static $aFileStatus = array();

	/**
	 * Classe outil : pas d'instance.
	 */
	private function __construct () {
	}

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
			throw new Exception(implode("\n", $aResult), $iReturnCode);
		}
		return $aResult;
	}

	public static function execSSH ($sPatternCmd, $sParam) {
		list($bIsRemote, $aMatches) = static::isRemotePath($sParam);
		$sCmd = sprintf($sPatternCmd, static::escapePath($aMatches[2]));
		//$sCmd = vsprintf($sPatternCmd, array_map(array(self, 'escapePath'), $mParams));
		if ($bIsRemote) {
			$sCmd = 'ssh -T ' . $aMatches[1] . " <<EOF\n$sCmd\nEOF\n";
		}
		return static::exec($sCmd);
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
			$aResult = static::execSSH($sFormat, $sPath);
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
		if (preg_match('/\$\{[^}]*\}/i', $sPath) === 1) {
			throw new RuntimeException("Invalid syntax: '$sPath'.");
		}

		$result = preg_match('/^((?:[a-z0-9_.-]+@)[a-z0-9_.-]+):(.+)$/i', $sPath, $aMatches);
		if ($result !== 1) {
			$aMatches = array($sPath, '', $sPath);
		}
		return array($result === 1, $aMatches);
	}

	// TODO ajouter gestion tar/gz
	// TODO ajouter gestion destfile
	// TODO a priori, $sSrcPath est un $sSrcFilePath
	public static function copy ($sSrcPath, $sDestPath, $bIsDestFile=false) {
		if ($bIsDestFile) {
			static::mkdir(pathinfo($sDestPath, PATHINFO_DIRNAME));
		} else {
			static::mkdir($sDestPath);
		}
		list($bIsSrcRemote, $aSrcMatches) = static::isRemotePath($sSrcPath);
		list($bIsDestRemote, $aDestMatches) = static::isRemotePath($sDestPath);

		if ($aSrcMatches[1] != $aDestMatches[1]) {
			$sCmd = 'scp -rpq ' . static::escapePath($sSrcPath) . ' ' . static::escapePath($sDestPath);
			return static::exec($sCmd);
		} else {
			$sCmd = 'cp -ar %s ' . static::escapePath($aDestMatches[2]);
			return static::execSSH($sCmd, $sSrcPath);
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
	public static function escapePath ($sPath) {
		$sEscapedPath = preg_replace('#(\*|\?)#', '"\1"', '"' . $sPath . '"');
		$sEscapedPath = str_replace('""', '', $sEscapedPath);
		return $sEscapedPath;
	}

	public static function remove ($sPath) {
		return static::execSSH('rm -rf %s', $sPath);
	}

	/*
cd `dirname /home/gaubry/deployment_test/T`; tar cfpz /home/gaubry/deployment_backup/`basename "/home/gaubry/deployment_test/T"`.tar.gz ./`basename "/home/gaubry/deployment_test/T"`
cd `dirname /home/gaubry/deployment_test/a3.txt`; tar cfpz /home/gaubry/deployment_backup/`basename "/home/gaubry/deployment_test/a3.txt"`.tar.gz ./`basename "/home/gaubry/deployment_test/a3.txt"`

cd /home/gaubry/t; tar -xf /home/gaubry/deployment_backup/`basename "/home/gaubry/deployment_test/T"`.tar.gz
cd /home/gaubry/t; tar -xf /home/gaubry/deployment_backup/`basename "/home/gaubry/deployment_test/a3.txt"`.tar.gz
	 */
	public static function backup ($sSrcPath, $sBackupPath) {
		list($bIsSrcRemote, $aSrcMatches) = static::isRemotePath($sSrcPath);
		list($bIsBackupRemote, $aBackupMatches) = static::isRemotePath($sBackupPath);

		if ($aSrcMatches[1] != $aBackupMatches[1]) {
			$sTmpDir = ($bIsSrcRemote ? $aSrcMatches[1]. ':' : '') . '/tmp/' . uniqid('deployment_', true);
			$sTmpPath = $sTmpDir . '/' . pathinfo($sBackupPath, PATHINFO_BASENAME);
			return array_merge(
				static::backup($sSrcPath, $sTmpPath),
				static::copy($sTmpPath, $sBackupPath, true),
				static::remove($sTmpDir));
		} else {
			static::mkdir(pathinfo($sBackupPath, PATHINFO_DIRNAME));
			$sSrcFile = pathinfo($aSrcMatches[2], PATHINFO_BASENAME);
			$sFormat = 'cd %1$s; tar cfpz %2$s ./%3$s';
			if ($bIsSrcRemote) {
				$sSrcDir = pathinfo($aSrcMatches[2], PATHINFO_DIRNAME);
				$sFormat = 'ssh %4$s <<EOF' . "\n" . $sFormat . "\nEOF\n";
				$sCmd = sprintf($sFormat, static::escapePath($sSrcDir), static::escapePath($aBackupMatches[2]), static::escapePath($sSrcFile), $aSrcMatches[1]);
			} else {
				$sSrcDir = pathinfo($sSrcPath, PATHINFO_DIRNAME);
				$sCmd = sprintf($sFormat, static::escapePath($sSrcDir), static::escapePath($sBackupPath), static::escapePath($sSrcFile));
			}
			return static::exec($sCmd);
		}
	}

	public static function mkdir ($sPath) {
		return static::execSSH('mkdir -p %s', $sPath);
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
	public static function sync ($sSrcPath, $mDestPath) {
		$aPaths = (is_array($mDestPath) ? $mDestPath : array($mDestPath));

		$aAllResults = array();
		for ($i=0; $i<count($aPaths); $i++) {
			$aResult = static::mkdir($aPaths[$i]);
			$aAllResults = array_merge($aAllResults, $aResult);
		}

		for ($i=0; $i<count($aPaths); $i++) {
			$aCmd = array();
			for ($j=$i; $j<count($aPaths) && $j<$i+DEPLOYMENT_RSYNC_MAX_NB_PROCESSES; $j++) {
				$aCmd[] =
					'rsync -az --delete --delete-excluded --cvs-exclude --exclude=.cvsignore --stats -e'
					. ' ssh ' . static::escapePath($sSrcPath) . ' ' . static::escapePath($aPaths[$j]);
			}
			$i = $j-1;
			$sCmd = implode(" & \\\n", $aCmd) . " & \\\nwait";
			$aResult = static::exec($sCmd);
			$aAllResults = array_merge($aAllResults, $aResult);
		}

		return $aAllResults;
	}
}
