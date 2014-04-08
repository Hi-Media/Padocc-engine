<?php

namespace Himedia\Padocc\Task\Base;

use GAubry\Shell\PathStatus;
use Himedia\Padocc\AttributeProperties;
use Himedia\Padocc\DIContainer;
use Himedia\Padocc\Shell_PathStatus;
use Himedia\Padocc\Task;

/**
 * Synchronise efficacement (rsync Shell) et avec suppression le contenu d'un répertoire à l'intérieur d'un autre.
 * À inclure dans une tâche env ou target.
 *
 * Attributs :
 * - 'src'
 * - 'destdir'
 * - 'include'
 * - 'exclude' : à noter que systématiquement sont exclus '.bzr/', '.cvsignore', '.git/', '.gitignore',
 *   '.svn/', 'cvslog.*', 'CVS' et 'CVS.adm'
 *
 * Exemples :
 * <sync src="${TMPDIR}/" destdir="${WEB_SERVERS}:${BASEDIR}" exclude="v3/css v3/js v4/css v4/js" />
 * <sync src="prod@fs3:/home/prod/twenga_files/merchant_logos/"
 *     destdir="${TMPDIR}/img/sites" include="*.png" exclude="*" />
 *
 * @author Geoffroy AUBRY <gaubry@hi-media.com>
 */
class Sync extends Task
{

    /**
     * Retourne le nom du tag XML correspondant à cette tâche dans les config projet.
     *
     * @return string nom du tag XML correspondant à cette tâche dans les config projet.
     */
    public static function getTagName ()
    {
        return 'sync';
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
            'src' => AttributeProperties::SRC_PATH | AttributeProperties::FILEJOKER | AttributeProperties::REQUIRED
                | AttributeProperties::ALLOW_PARAMETER,
            'destdir' => AttributeProperties::DIR | AttributeProperties::REQUIRED
                | AttributeProperties::ALLOW_PARAMETER,
            // TODO AttributeProperties::DIRJOKER abusif ici, mais à cause du multivalué :
            'include' => AttributeProperties::FILEJOKER | AttributeProperties::DIRJOKER,
            // TODO AttributeProperties::DIRJOKER abusif ici, mais à cause du multivalué :
            'exclude' => AttributeProperties::FILEJOKER | AttributeProperties::DIRJOKER,
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

        if (
                preg_match('#\*|\?|/$#', $this->aAttValues['src']) === 0
                && $this->oShell->getPathStatus($this->aAttValues['src']) === PathStatus::STATUS_DIR
        ) {
            $this->aAttValues['destdir'] .= '/' . substr(strrchr($this->aAttValues['src'], '/'), 1);
            $this->aAttValues['src'] .= '/';
        }
    }

    /**
     * Phase de traitements centraux de l'exécution de la tâche.
     * Elle devrait systématiquement commencer par "parent::centralExecute();".
     * Appelé par execute().
     * @see execute()
     */
    protected function centralExecute ()
    {
        parent::centralExecute();
        $sMsg = "+++Synchronize '" . $this->aAttValues['src'] . "' with '" . $this->aAttValues['destdir'] . "'+++";
        $this->oLogger->info($sMsg);

        // include / exclude :
        $aIncludedPaths = (empty($this->aAttValues['include'])
                          ? array()
                          : explode(' ', $this->aAttValues['include']));
        $aExcludedPaths = (empty($this->aAttValues['exclude'])
                          ? array()
                          : explode(' ', $this->aAttValues['exclude']));

        list($bIsDestRemote, $sDestServer, $sDestRawPath) = $this->oShell->isRemotePath($this->aAttValues['destdir']);
        $sDestPath = ($bIsDestRemote ? '[]:' . $sDestRawPath : $sDestRawPath);
        foreach ($this->processPath($sDestPath) as $sDestRealPath) {
            $aResults = $this->oShell->sync(
                $this->processSimplePath($this->aAttValues['src']),
                $this->processSimplePath($sDestRealPath),
                $this->processPath($sDestServer),
                $aIncludedPaths,
                $aExcludedPaths
            );
            foreach ($aResults as $sResult) {
                $this->oLogger->info($sResult);
            }
        }
        $this->oLogger->info('------');
    }
}
