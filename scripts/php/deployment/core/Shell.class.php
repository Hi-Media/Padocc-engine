<?php

class Shell {

	/**
	 * Classe outil : pas de constructeur.
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
			echo "[Debug] (Shell) $sCmd\n";
		}
		$sFullCmd = DEPLOYMENT_SHELL_INCLUDE . '; ( ' . $sCmd . ' ) 2>&1';
		$sErrorMsg = exec($sFullCmd, $sResult, $iReturnCode);
		if ($iReturnCode !== 0) {
			throw new Exception($sErrorMsg);
		}
		return $sResult;
	}

	public static function getFileStatus ($sPath) {
		$format = '[ -d %1$s ] && echo 2 || ( [ -f %1$s ] && echo 1 || echo 0 )';
		list($bIsRemote, $aMatches) = self::isRemotePath($sPath);
		if ($bIsRemote) {
			$sCmd = 'ssh ' . $aMatches[1] . ' "' . sprintf($format, $aMatches[2]) . '"';
		} else {
			$sCmd = sprintf($format, $sPath);
		}
		$result = self::exec($sCmd);
		return (int)$result[0];
	}

	public static function isRemotePath ($sPath) {
		$result = preg_match('/^((?:[a-z0-9_-]+@)[a-z0-9_-]+):(.+)$/i', $sPath, $aMatches);
		return array($result === 1, $aMatches);
	}

	public static function copy ($sSrcPath, $sDestPath) {
		list($bIsSrcRemote, $aSrcMatches) = self::isRemotePath($sSrcPath);
		list($bIsDestRemote, $aDestMatches) = self::isRemotePath($sDestPath);

		if ($bIsSrcRemote && $bIsDestRemote && $aSrcMatches[1] == $aDestMatches[1]) {
			$sCmd = 'ssh ' . $aSrcMatches[1] . ' cp -rf "' . $aSrcMatches[2] . '" "' . $aDestMatches[2] . '"';
		} else if ($bIsSrcRemote || $bIsDestRemote) {
			$sCmd = 'scp -rp "' . $sSrcPath . '" "' . $sDestPath . '"';
		} else {
			$sCmd = 'cp -rf "' . $sSrcPath . '" "' . $sDestPath . '"';
		}
		return Shell::exec($sCmd);
	}

	public static function mkdir ($sPath) {
		$format = 'mkdir -p "%s"';
		list($bIsRemote, $aMatches) = self::isRemotePath($sPath);
		if ($bIsRemote) {
			$sCmd = 'ssh ' . $aMatches[1] . ' ' . sprintf($format, $aMatches[2]);
		} else {
			$sCmd = sprintf($format, $sPath);
		}
		return Shell::exec($sCmd);
	}

	public static function sync ($sSrcPath, $sDestPath) {
		// rsync -aqz --delete --delete-excluded --cvs-exclude -e ssh --cvs-exclude --exclude=.cvsignore
	}
}
