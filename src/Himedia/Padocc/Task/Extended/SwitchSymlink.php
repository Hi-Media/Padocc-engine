<?php

namespace Himedia\Padocc\Task\Extended;

use GAubry\Shell\PathStatus;
use Himedia\Padocc\AttributeProperties;
use Himedia\Padocc\DIContainer;
use Himedia\Padocc\Task\Base\Environment;
use Himedia\Padocc\Task\Base\Link;
use Himedia\Padocc\Task\Base\Project;

/**
 * Permute les liens symboliques de la dernière release vers la nouvelle à la fin du déploiement.
 * Tâche ajoutée par défaut en tant que dernière tâche de l'environnement, si et seulement si aucune
 * tâche SwitchSymlink ou fille (comme B2CSwitchSymlink) n'est spécifiée dans le XML,
 * et si l'attribut withsymlinks de la tâche env vaut true. À inclure en toute fin de tâche env ou target.
 *
 * Attributs :
 * - 'src' : laisser à vide à moins d'être bien conscient des conséquences
 * - 'target' : laisser à vide à moins d'être bien conscient des conséquences
 * - 'server' : laisser à vide à moins d'être bien conscient des conséquences
 *
 * Exemple : <switchsymlink />
 *
 * @category TwengaDeploy
 * @author Geoffroy AUBRY <gaubry@hi-media.com>
 */
class SwitchSymlink extends Link
{

    /**
     * Retourne le nom du tag XML correspondant à cette tâche dans les config projet.
     *
     * @return string nom du tag XML correspondant à cette tâche dans les config projet.
     */
    public static function getTagName ()
    {
        return 'switchsymlink';
    }

    /**
     * Compteur d'instances de la classe.
     * @var int
     * @see getNbInstances()
     */
    private static $iNbInstances = 0;

    /**
     * Accesseur au compteur d'instances de la classe.
     *
     * @return int nombre d'instances de la classe.
     * @see $iNbInstances
     */
    public static function getNbInstances ()
    {
        return self::$iNbInstances;
    }

    /**
     * Constructeur.
     *
     * @param \SimpleXMLElement $oTask Contenu XML de la tâche.
     * @param Project $oProject Super tâche projet.
     * @param DIContainer $oDIContainer Register de services prédéfinis (ShellInterface, ...).
     */
    public function __construct (\SimpleXMLElement $oTask, Project $oProject, DIContainer $oDIContainer)
    {
        parent::__construct($oTask, $oProject, $oDIContainer);
        $this->aAttrProperties = array(
            'src' => AttributeProperties::FILE | AttributeProperties::DIR | AttributeProperties::ALLOW_PARAMETER,
            'target' => AttributeProperties::FILE | AttributeProperties::DIR | AttributeProperties::ALLOW_PARAMETER,
            'server' => AttributeProperties::ALLOW_PARAMETER
        );
        self::$iNbInstances++;
    }

    /**
     * Vérifie au moyen de tests basiques que la tâche peut être exécutée.
     * Lance une exception si tel n'est pas le cas.
     *
     * Comme toute les tâches sont vérifiées avant que la première ne soit exécutée,
     * doit permettre de remonter au plus tôt tout dysfonctionnement.
     * Appelé avant la méthode execute().
     *
     * @throws \UnexpectedValueException en cas d'attribut ou fichier manquant
     * @throws \DomainException en cas de valeur non permise
     */
    public function check ()
    {
        if (
            ! isset($this->aAttValues['src'])
            && ! isset($this->aAttValues['target'])
            && ! isset($this->aAttValues['server'])
        ) {
            $sBaseSymLink = $this->oProperties->getProperty('basedir');
            $sRollbackID = $this->oProperties->getProperty('rollback_id');
            if ($sRollbackID !== '') {
                $this->oLogger->info("Rollback to '$sRollbackID' requested.");
                $sID = $sRollbackID;
            } else {
                $sID = $this->oProperties->getProperty('execution_id');
            }
            $sReleaseSymLink = $sBaseSymLink . $this->aConfig['symlink_releases_dir_suffix'] . '/' . $sID;

            $this->aAttValues['src'] = $sBaseSymLink;
            $this->aAttValues['target'] = $sReleaseSymLink;
            $this->aAttValues['server'] = '${' . Environment::SERVERS_CONCERNED_WITH_BASE_DIR . '}';
        }

        parent::check();
    }

    /**
     * Phase de traitements centraux de l'exécution de la tâche.
     * Elle devrait systématiquement commencer par "parent::centralExecute();".
     * Appelé par execute().
     * @see execute()
     */
    protected function centralExecute ()
    {
        $this->oLogger->info('+++');
        if ($this->oProperties->getProperty('with_symlinks') === 'true') {
            if ($this->oProperties->getProperty(Environment::SERVERS_CONCERNED_WITH_BASE_DIR) == '') {
                $this->oLogger->info('No release found.');
            } else {
                $this->oProperties->setProperty('with_symlinks', 'false');
                $this->checkTargets();
                $this->oLogger->info('---');
                parent::centralExecute();
                $this->oLogger->info('+++');
                $this->oProperties->setProperty('with_symlinks', 'true');
            }
        } else {
            $this->oLogger->info("Mode 'withsymlinks' is off: nothing to do.");
        }
        $this->oLogger->info('---');
    }

    /**
     * Vérifie que chaque répertoire cible des liens existe.
     * Notamment nécessaire en cas de rollback.
     *
     * @throws \RuntimeException si l'un des répertoires cible des liens n'existe pas
     */
    protected function checkTargets ()
    {
        $this->oLogger->info('Check that all symlinks targets exists.+++');

        $aValidStatus = array(
            PathStatus::STATUS_DIR,
            PathStatus::STATUS_SYMLINKED_DIR
        );

        $sPath = $this->aAttValues['target'];
        $aServers = $this->expandPath($this->aAttValues['server']);
        $aPathStatusResult = $this->oShell->getParallelSSHPathStatus($sPath, $aServers);
        foreach ($aServers as $sServer) {
            $sExpandedPath = $sServer . ':' . $sPath;
            if (! in_array($aPathStatusResult[$sServer], $aValidStatus)) {
                $sMsg = "Target attribute must be a directory or a symlink to a directory: '" . $sExpandedPath . "'";
                throw new \RuntimeException($sMsg);
            }
        }

        $this->oLogger->info('---');
    }
}
