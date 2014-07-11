<?php

namespace Himedia\Padocc\Task\Base;

use Himedia\Padocc\AttributeProperties;
use Himedia\Padocc\Task;

/**
 * Définit une propriété externe qu'il sera obligatoire de fournir lors de tout déploiement.
 * Cette propriété est par la suite réutilisable dans les attributs possédant le flag ALLOW_PARAMETER.
 * À inclure dans une tâche env ou target.
 *
 * Exemple : <externalproperty name="ref" description="Branch or tag to deploy" />
 *
 * @author Geoffroy AUBRY <gaubry@hi-media.com>
 */
class ExternalProperty extends Task
{
    /**
     * Préfixe de propriété externe, c.-à-d. fournie par l'utilisateur.
     * @var string
     * @see centralExecute()
     */
    const EXTERNAL_PROPERTY_PREFIX = 'external_property_';

    /**
     * {@inheritdoc}
     */
    protected function init()
    {
        parent::init();

        $this->aAttrProperties = array(
            'name' => AttributeProperties::REQUIRED,
            'description' => AttributeProperties::REQUIRED
        );
    }

    /**
     * {@inheritdoc}
     * @codeCoverageIgnore
     */
    public static function getTagName ()
    {
        return 'externalproperty';
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
        try {
            $sValue = $this->oProperties->getProperty(self::EXTERNAL_PROPERTY_PREFIX . $this->aAttValues['name']);
        } catch (\UnexpectedValueException $oException) {
            $sMsg = "Property '" . $this->aAttValues['name'] . "' undefined!";
            throw new \UnexpectedValueException($sMsg, 1, $oException);
        }
        $sMsg = "Set external property '" . $this->aAttValues['name'] . "' (description: '"
              . $this->aAttValues['description'] . "') to '$sValue'.";
        $this->getLogger()->info($sMsg);
        $this->oProperties->setProperty($this->aAttValues['name'], $sValue);
        $this->getLogger()->info('---');
    }
}
