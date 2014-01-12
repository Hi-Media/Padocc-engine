<?php

namespace Himedia\Padocc\Task\Base;

use Himedia\Padocc\AttributeProperties;
use Himedia\Padocc\DIContainer;
use Himedia\Padocc\Task;

/**
 * Renomme un fichier ou un répertoire.
 * À inclure dans une tâche env ou target.
 *
 * Exemple : <rename src="${TMPDIR}/v3" dest="${TMPDIR}/web" />
 *
 * @author Geoffroy AUBRY <gaubry@hi-media.com>
 */
class Rename extends Task
{

    /**
     * Retourne le nom du tag XML correspondant à cette tâche dans les config projet.
     *
     * @return string nom du tag XML correspondant à cette tâche dans les config projet.
     */
    public static function getTagName ()
    {
        return 'rename';
    }

    /**
     * Constructeur.
     *
     * @param \SimpleXMLElement $oTask Contenu XML de la tâche.
     * @param Project $oProject Super tâche projet.
     * @param DIContainer $oDIContainer Register de services prédéfinis (ShellInterface, ...).
     */
    public function __construct (\SimpleXMLElement $oTask, Project $oProject,
        DIContainer $oDIContainer)
    {
        parent::__construct($oTask, $oProject, $oDIContainer);
        $this->aAttrProperties = array(
            'src' => AttributeProperties::SRC_PATH | AttributeProperties::REQUIRED
                | AttributeProperties::ALLOW_PARAMETER,
            'dest' => AttributeProperties::FILE | AttributeProperties::DIR | AttributeProperties::REQUIRED
                | AttributeProperties::ALLOW_PARAMETER
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

        // Pour l'instant ne gère pas les chemins distants :
        list(, $sSrcServer, ) = $this->oShell->isRemotePath($this->aAttributes['src']);
        list(, $sDestServer, ) = $this->oShell->isRemotePath($this->aAttributes['dest']);
        if ($sSrcServer != $sDestServer) {
            throw new \DomainException('Paths must be local or on the same server!');
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
        $aSrcPath = $this->processSimplePath($this->aAttributes['src']);
        $aDestPath = $this->processSimplePath($this->aAttributes['dest']);
        $this->oLogger->info("+++Rename '$aSrcPath' to '$aDestPath'.");
        $this->oShell->execSSH("mv %s '" . $aDestPath . "'", $aSrcPath);
        $this->oLogger->info('---');
    }
}
