<?php

namespace Himedia\Padocc\Task\Base;

use GAubry\Shell\PathStatus;
use Himedia\Padocc\AttributeProperties;
use Himedia\Padocc\DIContainer;
use Himedia\Padocc\Task;

/**
 * Crée un lien symbolique.
 * À inclure dans une tâche env ou target.
 *
 * Attributs :
 * - 'src'
 * - 'target' : la cible n'a pas forcément besoin d'exister, ce qui créera un lien cassé.
 *   Ceci peut-être intéressant lorsque l'on prépare un déploiement dans un fichier temporaire
 *   avant d'effectuer la synchronisation finale vers tous les serveurs (cf. l'exemple ci-dessous).
 * - 'server' : à utiliser si l'on doit créer des mêmes liens sur plusieurs serveurs
 *
 * Exemple : <link src="${TMPDIR}/inc/config.php" target="../../../config/www.twenga/config.php" />
 *
 * @author Geoffroy AUBRY <gaubry@hi-media.com>
 */
class Link extends Task
{

    /**
     * Retourne le nom du tag XML correspondant à cette tâche dans les config projet.
     *
     * @return string nom du tag XML correspondant à cette tâche dans les config projet.
     */
    public static function getTagName ()
    {
        return 'link';
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
            'src' => AttributeProperties::REQUIRED | AttributeProperties::FILE | AttributeProperties::DIR
                | AttributeProperties::ALLOW_PARAMETER,
            'target' => AttributeProperties::FILE | AttributeProperties::DIR | AttributeProperties::REQUIRED
                | AttributeProperties::ALLOW_PARAMETER,
            'server' => AttributeProperties::ALLOW_PARAMETER
        );
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
        parent::check();

        list($bIsSrcRemote, $sSrcServer, ) = $this->oShell->isRemotePath($this->aAttributes['src']);
        list($bIsDestRemote, $sDestServer, ) = $this->oShell->isRemotePath($this->aAttributes['target']);
        if (
            ($bIsSrcRemote XOR $bIsDestRemote)
            || ($bIsSrcRemote && $bIsDestRemote && $sSrcServer != $sDestServer)
        ) {
            $sMsg = 'Servers must be equals!' . ' Src=' . $this->aAttributes['src']
                  . ' Target=' . $this->aAttributes['target'];
            throw new \DomainException($sMsg);
        }

        if (! empty($this->aAttributes['server']) && ($bIsSrcRemote || $bIsDestRemote)) {
            $sMsg = 'Multiple server declaration!' . ' Server=' . $this->aAttributes['server']
                  . ' Src=' . $this->aAttributes['src'] . ' Target=' . $this->aAttributes['target'];
            throw new \DomainException($sMsg);
        }

        // Valeur par défaut :
        if (! isset($this->aAttributes['server'])) {
            $this->aAttributes['server'] = '';
        }
    }

    /**
     * Phase de traitements centraux de l'exécution de la tâche.
     * Elle devrait systématiquement commencer par "parent::centralExecute();".
     * Appelé par _execute().
     * @see execute()
     */
    protected function centralExecute ()
    {
        parent::centralExecute();
        $this->oLogger->info('+++');

        // La source doit être un lien symbolique ou ne pas exister :
        $sPath = $this->aAttributes['src'];
        if (! empty($this->aAttributes['server'])) {
            $sPath = $this->aAttributes['server'] . ':' . $sPath;
        }
        $aValidSources = array(
            PathStatus::STATUS_NOT_EXISTS,
            PathStatus::STATUS_SYMLINKED_FILE,
            PathStatus::STATUS_SYMLINKED_DIR,
            PathStatus::STATUS_BROKEN_SYMLINK
        );
        foreach ($this->expandPath($sPath) as $sExpandedPath) {
            if (! in_array($this->oShell->getPathStatus($sExpandedPath), $aValidSources)) {
                $sMsg = "Source attribute must be a symlink or not exist: '" . $sExpandedPath . "'";
                throw new \RuntimeException($sMsg);
            }
        }

        $sRawTargetPath = $this->aAttributes['target'];
        if (! empty($this->aAttributes['server'])) {
            $sRawTargetPath = $this->aAttributes['server'] . ':' . $sRawTargetPath;
        }
        $this->oLogger->info("Create symlink from '$sPath' to '$sRawTargetPath'.+++");

        $aTargetPaths = $this->processPath($sRawTargetPath);
        foreach ($aTargetPaths as $sTargetPath) {
            list(, $sDestServer, ) = $this->oShell->isRemotePath($sTargetPath);
            if (! empty($sDestServer)) {
                $sDestServer .= ':';
            }
            list(, , $sSrcRealPath) = $this->oShell->isRemotePath($this->aAttributes['src']);
            $sSrc = $this->processSimplePath($sDestServer . $sSrcRealPath);
            $this->oShell->createLink($sSrc, $sTargetPath);
        }
        $this->oLogger->info('------');
    }
}
