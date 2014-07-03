<?php

namespace Himedia\Padocc\Task\Base;

use Himedia\Padocc\AttributeProperties;
use Himedia\Padocc\Task;

/**
 * Effectue un appel cURL.
 * À inclure dans une tâche env ou target.
 * Une telle tâche est automatiquement créée par la tâche b2cswitchsymlink.
 *
 * Exemple : <http url="http://aai.twenga.com/push.php?server=${WEB_SERVERS}&amp;app=web" />
 *
 * @author Geoffroy AUBRY <gaubry@hi-media.com>, Tony CARON <tony.caron@twenga.com>
 */
class HTTP extends Task
{
    /**
     * {@inheritdoc}
     */
    protected function init()
    {
        parent::init();

        $this->aAttrProperties = array(
            'url' => AttributeProperties::ALLOW_PARAMETER | AttributeProperties::REQUIRED | AttributeProperties::URL,
            'dest' => AttributeProperties::ALLOW_PARAMETER | AttributeProperties::FILE
        );
    }

    /**
     * {@inheritdoc}
     * @codeCoverageIgnore
     */
    public static function getTagName ()
    {
        return 'http';
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
        $this->getLogger()->info('+++Call URL: ' . $this->aAttValues['url'] . '+++');

        $aURLs = $this->processPath($this->aAttValues['url']);
        foreach ($aURLs as $sURL) {
            $sCmd = $this->aConfig['curl_path'] . ' ' . $this->aConfig['curl_options'] . ' "' . $sURL . '"';
            $this->oShell->exec($sCmd);
        }

        $this->getLogger()->info('------');
    }
}
