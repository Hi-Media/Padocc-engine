<?php

namespace Himedia\Padocc\Task\Base;

use GAubry\Shell\PathStatus;
use Himedia\Padocc\AttributeProperties;
use Himedia\Padocc\Task;

/**
 * Permet de copier un fichier ou un répertoire dans un autre.
 * À inclure dans une tâche env ou target.
 *
 * Exemple : <copy src="/path/to/src" dest="${SERVERS}:/path/to/dest" />
 *
 * @author Geoffroy AUBRY <gaubry@hi-media.com>
 */
class Copy extends Task
{
    /**
     * {@inheritdoc}
     */
    protected function init()
    {
        parent::init();

        $this->aAttrProperties = array(
            'src' => AttributeProperties::SRC_PATH | AttributeProperties::FILEJOKER | AttributeProperties::REQUIRED,
            'destdir' => AttributeProperties::DIR | AttributeProperties::REQUIRED
        );
    }

    /**
     * {@inheritdoc}
     * @codeCoverageIgnore
     */
    public static function getTagName ()
    {
        return 'copy';
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
        // TODO si *|? alors s'assurer qu'il en existe ?
        // TODO droit seulement à \w et / et ' ' ?
        parent::check();

        // Suppression de l'éventuel slash terminal :
        $this->aAttValues['src'] = preg_replace('#/$#', '', $this->aAttValues['src']);

        if (preg_match('/\*|\?/', $this->aAttValues['src']) === 0
            && $this->oShell->getPathStatus($this->aAttValues['src']) === PathStatus::STATUS_DIR
        ) {
            $this->aAttValues['destdir'] .= '/' . substr(strrchr($this->aAttValues['src'], '/'), 1);
            $this->aAttValues['src'] .= '/*';
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
        $this->getLogger()->info('+++');

        $aSrcPath = $this->processSimplePath($this->aAttValues['src']);
        $aDestDirs = $this->processPath($this->aAttValues['destdir']);
        foreach ($aDestDirs as $sDestDir) {
            $this->oShell->copy($aSrcPath, $sDestDir);
        }

        $this->getLogger()->info('---');
    }
}
