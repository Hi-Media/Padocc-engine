<?php

namespace Himedia\Padocc\Task\Base;

use Himedia\Padocc\AttributeProperties;
use Himedia\Padocc\Task;

/**
 * Définit une propriété interne, réutilisable dans les attributs possédant le flag ALLOW_PARAMETER.
 * À inclure dans une tâche env ou target.
 *
 * Exemple : <property name="servers" value="${SERVER_ADMIN} ${SERVER_SCHED_DEPLOY}" />
 *
 * @author Geoffroy AUBRY <gaubry@hi-media.com>
 */
class Property extends Task
{
    /**
     * {@inheritdoc}
     */
    protected function init()
    {
        parent::init();

        $this->aAttrProperties = array(
            'name' => AttributeProperties::REQUIRED,
            'value' => AttributeProperties::REQUIRED | AttributeProperties::ALLOW_PARAMETER
        );
    }

    /**
     * {@inheritdoc}
     * @codeCoverageIgnore
     */
    public static function getTagName()
    {
        return 'property';
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
        $sMsg = "+++Set internal property '" . $this->aAttValues['name'] . "' to '"
              . $this->aAttValues['value'] . "'.---";
        $this->getLogger()->info($sMsg);
        $this->oProperties->setProperty($this->aAttValues['name'], $this->aAttValues['value']);
    }
}
