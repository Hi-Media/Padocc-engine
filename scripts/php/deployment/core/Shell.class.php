<?php

class Shell {

	/**
	 * Classe outil : pas de constructeur.
	 */
	private function __construct () {}

	/**
	 * Exécute la/les commandes spécifiées au sein d'un SSH sur le serveur de prod.
	 * Le canal d'erreur est redirigé sur le canal d'affichage, et le moindre affichage sera considéré comme une erreur.
	 *
	 * Une exception est levée s'il y a une erreur.
	 * La commande shell complète et le message d'erreur shell seront concaténés au message d'erreur spécifié.
	 *
	 * @param string $ssh_cmd
	 * @param string $error_message
	 */
	/*public static function execNoResult ($shell_cmd, $error_message) {
		$cmd = DEPLOYMENT_SHELL_INCLUDE . '; ' . $shell_cmd . ' 2>&1';
		$result = shell_exec($cmd);
		if ($result != '') {
			throw new Exception("$error_message >> Command: $cmd >> Error: $result");
		}
	}*/

	/*public static function exec ($shell_cmd) {
		$cmd = DEPLOYMENT_SHELL_INCLUDE . '; "' . $shell_cmd . '" 2>&1';
		$result = shell_exec($cmd);
		$lines = preg_split('/\n/', $result, -1, PREG_SPLIT_NO_EMPTY);
		return $lines;
	}*/
	public static function exec ($shell_cmd) {
		$cmd = DEPLOYMENT_SHELL_INCLUDE . '; ( ' . $shell_cmd . ' ) 2>&1';
		$error_msg = exec($cmd, $result, $return_code);
		if ($return_code !== 0) {
			throw new Exception($error_msg);
		}
		return $result;
	}
}
