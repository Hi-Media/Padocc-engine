<?php

/**
 * Pour faciliter l'exécution des commandes shell.
 *
 * @category TwengaDeploy
 * @package Lib
 * @author Geoffroy AUBRY <geoffroy.aubry@twenga.com>
 */
interface Shell_Interface
{

    /**
     * Exécute la commande shell spécifiée et retourne la sortie découpée par ligne dans un tableau.
     * En cas d'erreur shell (code d'erreur <> 0), lance une exception incluant le message d'erreur.
     *
     * @param string $sCmd
     * @return array tableau indexé du flux de sortie shell découpé par ligne
     * @throws RuntimeException en cas d'erreur shell
     */
    public function exec ($sCmd);

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
    public function execSSH ($sPatternCmd, $sParam);

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
    public function getPathStatus ($sPath);

    /**
     * Retourne un triplet dont la 1re valeur (bool) indique si le chemin spécifié commence par
     * '[user@]servername_or_ip:', la 2e (string) est le serveur (ou chaîne vide si $sPath est local),
     * et la 3e (string) est le chemin dépourvu de l'éventuel serveur.
     *
     * @param string $sPath chemin au format [[user@]servername_or_ip:]/path
     * @return array triplet dont la 1re valeur (bool) indique si le chemin spécifié commence par
     * '[user@]servername_or_ip:', la 2e (string) est le serveur (ou chaîne vide si $sPath est local),
     * et la 3e (string) est le chemin dépourvu de l'éventuel serveur.
     * @throws DomainException si syntaxe invalide (s'il reste des paramètres non résolus par exemple)
     */
    public function isRemotePath ($sPath);

    /**
     * Copie un chemin vers un autre.
     * Les jokers '*' et '?' sont autorisés.
     * Par exemple copiera le contenu de $sSrcPath si celui-ci se termine par '/*'.
     * Si le chemin de destination n'existe pas, il sera créé.
     *
     * TODO ajouter gestion tar/gz
     *
     * @param string $sSrcPath chemin source, au format [[user@]hostname_or_ip:]/path
     * @param string $sDestPath chemin de destination, au format [[user@]hostname_or_ip:]/path
     * @param bool $bIsDestFile précise si le chemin de destination est un simple fichier ou non,
     * information nécessaire si l'on doit créer une partie de ce chemin si inexistant
     * @return array tableau indexé du flux de sortie shell découpé par ligne
     * @throws RuntimeException en cas d'erreur shell
     */
    public function copy ($sSrcPath, $sDestPath, $bIsDestFile=false);

    /**
     * Crée un lien symbolique de chemin $sLinkPath vers la cible $sTargetPath.
     *
     * @param string $sLinkPath nom du lien, au format [[user@]hostname_or_ip:]/path
     * @param string $sTargetPath cible sur laquelle faire pointer le lien, au format [[user@]hostname_or_ip:]/path
     * @return array tableau indexé du flux de sortie shell découpé par ligne
     * @throws DomainException si les chemins référencent des serveurs différents
     * @throws RuntimeException en cas d'erreur shell
     */
    public function createLink ($sLinkPath, $sTargetPath);

    /**
     * Entoure le chemin de guillemets doubles en tenant compte des jokers '*' et '?' qui ne les supportent pas.
     * Par exemple : '/a/b/img*jpg', donnera : '"/a/b/img"*"jpg"'.
     * Pour rappel, '*' vaut pour 0 à n caractères, '?' vaut pour exactement 1 caractère (et non 0 à 1).
     *
     * @param string $sPath
     * @return string
     */
    public function escapePath ($sPath);

    /**
     * Supprime le chemin spécifié, répertoire ou fichier, distant ou local.
     * S'efforce de maintenir cohérent le cache de statut de chemins rempli par getPathStatus().
     *
     * @param string $sPath chemin à supprimer, au format [[user@]hostname_or_ip:]/path
     * @return array tableau indexé du flux de sortie shell découpé par ligne
     * @throws DomainException si chemin invalide (garde-fou)
     * @throws RuntimeException en cas d'erreur shell
     * @see getPathStatus()
     */
    public function remove ($sPath);

    /**
     * Effectue un tar gzip du répertoire $sSrcPath dans $sBackupPath.
     *
     * @param string $sSrcPath au format [[user@]hostname_or_ip:]/path
     * @param string $sBackupPath au format [[user@]hostname_or_ip:]/path
     * @return array tableau indexé du flux de sortie shell découpé par ligne
     * @throws RuntimeException en cas d'erreur shell
     */
    public function backup ($sSrcPath, $sBackupPath);

    /**
     * Crée le chemin spécifié s'il n'existe pas déjà, avec les droits éventuellement transmis dans tous les cas.
     *
     * @param string $sPath chemin à créer, au format [[user@]hostname_or_ip:]/path
     * @param string $sMode droits utilisateur du chemin appliqués même si ce dernier existe déjà.
     * Par exemple '644'.
     * @return array tableau indexé du flux de sortie shell découpé par ligne
     * @throws RuntimeException en cas d'erreur shell
     */
    public function mkdir ($sPath, $sMode='');

    /**
     * Synchronise une source avec une ou plusieurs destinations.
     *
     * @param string $sSrcPath au format [[user@]hostname_or_ip:]/path
     * @param string|array $mDestPath chaque destination au format [[user@]hostname_or_ip:]/path
     * @param array $aIncludedPaths chemins à transmettre aux paramètres --include de la commande shell rsync.
     * Il précéderons les paramètres --exclude.
     * @param array $aExcludedPaths chemins à transmettre aux paramètres --exclude de la commande shell rsync
     * @return array tableau indexé du flux de sortie shell des commandes rsync exécutées,
     * découpé par ligne et analysé par _resumeSyncResult()
     * @throws RuntimeException en cas d'erreur shell
     * @throws RuntimeException car non implémenté quand plusieurs $mDestPath et $sSrcPath est distant
     */
    public function sync ($sSrcPath, $mDestPath, array $aIncludedPaths=array(), array $aExcludedPaths=array());
}
