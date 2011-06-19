<?php

interface IShell {

	/**
	 * Exécute la commande shell spécifiée et retourne la sortie découpée par ligne dans un tableau.
	 * En cas d'erreur shell, lance une exception avec le message d'erreur.
	 *
	 * @param string $sCmd
	 * @throws Exception en cas d'erreur shell
	 * @return array Tableau indexé du flux de sortie découpé par ligne
	 */
	public function exec ($sCmd);

	public function execSSH ($sPatternCmd, $sParam);

	/**
	 * Retourne 0 si le chemin spécifié n'existe pas, 1 si c'est un fichier 'classique', 2 si c'est un répertoire.
	 * Passe par ssh au besoin.
	 *
	 * @param string $sPath chemin à tester
	 * @return int 0 si le chemin spécifié n'existe pas, 1 si c'est un fichier, 2 si c'est un répertoire.
	 */
	public function getFileStatus ($sPath);

	/**
	 * Retourne un couple dont la 1re valeur indique si oui ou non le chemin spécifié commence par '[user@]servername_or_ip:'
	 * et la 2nde est un tableau indexé contenant le chemin initial, le serveur et le chemin dépourvu du serveur.
	 *
	 * @param string $sPath
	 * @return array
	 */
	public function isRemotePath ($sPath);

	// TODO ajouter gestion tar/gz
	// TODO ajouter gestion destfile
	// TODO a priori, $sSrcPath est un $sSrcFilePath
	public function copy ($sSrcPath, $sDestPath, $bIsDestFile=false);

	/**
	 * Entoure le chemin de guillemets doubles en tenant compte des jokers '*' et '?' qui ne les supportent pas.
	 * Par exemple : '/a/b/img*jpg', donnera : '"/a/b/img"*"jpg"'.
	 * Pour rappel, '*' vaut pour 0 à n caractères, '?' vaut pour exactement 1 caractère (et non 0 à 1).
	 *
	 * @param string $sPath
	 * @return string
	 */
	public function escapePath ($sPath);

	public function remove ($sPath);
	public function backup ($sSrcPath, $sBackupPath);
	public function mkdir ($sPath);
	public function sync ($sSrcPath, $mDestPath);
}