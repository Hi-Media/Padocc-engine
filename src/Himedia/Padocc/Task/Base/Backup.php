<?php

namespace Himedia\Padocc\Task\Base;

use Himedia\Padocc\AttributeProperties;
use Himedia\Padocc\Task;

/**
 * @author Geoffroy AUBRY <gaubry@hi-media.com>
 */
class Backup extends Task
{
    /**
     * {@inheritdoc}
     */
    protected function init()
    {
        $this->aAttrProperties = array(
            'src' => AttributeProperties::SRC_PATH | AttributeProperties::FILEJOKER | AttributeProperties::REQUIRED,
            'destfile' => AttributeProperties::FILE | AttributeProperties::REQUIRED
        );
    }

    /**
     * {@inheritdoc}
     * @codeCoverageIgnore
     */
    public static function getTagName ()
    {
        return 'backup';
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
        $this->oShell->backup($this->aAttValues['src'], $this->aAttValues['destfile']);
        $this->getLogger()->info('---');
    }
}
