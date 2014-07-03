<?php

namespace Himedia\Padocc\Task\Extended;

use Himedia\Padocc\AttributeProperties;
use Himedia\Padocc\Task;

/**
 * Exporte tout ou partie du contenu d'un dépôt CVS vers une ou plusieurs destinations.
 * À inclure dans une tâche env ou target.
 *
 * Exploite le script '/src/inc/cvsexport.sh'.
 * Réalise la synchronisation à l'aide d'une tâche ''sync''.
 *
 * Attributs :
 * - 'repository'
 * - 'module' : sous-répertoire du dépôt qui nous intéresse
 * - 'src' : lieu temporaire d'extraction du contenu qui nous intéresse du dépôt avant de l'envoyer
 *   vers la destination => laisser à vide de manière générale,
 *   l'outil utilisera alors le répertoire $aConfig['dir']['repositories']
 * - 'dest'
 *
 * Exemple :
 * <cvsexport repository=":extssh:gaubry@fs1.twenga.com:/home/cvsroot"
 *     module="twengaweb/common" destdir="${SERVERS}:${COMMONDIR}" />
 *
 * @author Geoffroy AUBRY <gaubry@hi-media.com>
 */
class CVSExport extends Task
{
    /**
     * Tâche de synchronisation sous-jacente.
     * @var Task\Base\Sync
     */
    private $oSyncTask;

    /**
     * {@inheritdoc}
     */
    protected function init()
    {
        parent::init();

        $this->aAttrProperties = array(
            'repository' => AttributeProperties::FILE | AttributeProperties::REQUIRED,
            'module' => AttributeProperties::DIR | AttributeProperties::REQUIRED,
            'srcdir' => AttributeProperties::DIR,
            'destdir' => AttributeProperties::DIR | AttributeProperties::REQUIRED
                | AttributeProperties::ALLOW_PARAMETER
        );

        if (empty($this->aAttValues['srcdir'])) {
            $this->aAttValues['srcdir'] =
                $this->aConfig['dir']['repositories'] . '/cvs/'
                . $this->oProperties->getProperty('project_name') . '_'
                . $this->oProperties->getProperty('environment_name') . '_'
                . $this->sCounter;
        } else {
            $this->aAttValues['srcdir'] =
                preg_replace('#/$#', '', $this->aAttValues['srcdir']);
        }

        // Création de la tâche de synchronisation sous-jacente :
        $this->oNumbering->addCounterDivision();
        $this->oSyncTask = Task\Base\Sync::getNewInstance(
            array(
                'src' => $this->aAttValues['srcdir'] . '/' . $this->aAttValues['module'] . '/',
                'destdir' => $this->aAttValues['destdir']
            ),
            $this->oProject,
            $this->oDIContainer
        );
        $this->oNumbering->removeCounterDivision();
    }

    /**
     * {@inheritdoc}
     * @codeCoverageIgnore
     */
    public static function getTagName ()
    {
        return 'cvsexport';
    }

    /**
     * Prépare la tâche avant exécution : vérifications basiques, analyse des serveurs concernés...
     */
    public function setUp ()
    {
        parent::setUp();
        $this->getLogger()->info('+++');
        try {
            $this->oSyncTask->setUp();
        } catch (\UnexpectedValueException $oException) {
            if ($oException->getMessage() !== "File or directory '" . $this->aAttValues['srcdir']
                                            . '/' . $this->aAttValues['module'] . '/' . "' not found!") {
                throw $oException;
            }
        }

        $this->getLogger()->info('---');
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

        $this->getLogger()->info("Export from '" . $this->aAttValues['repository'] . "' CVS repository+++");
        $aResult = $this->oShell->exec(
            $this->aConfig['bash_path'] . ' ' . $this->aConfig['dir']['inc'] . '/cvsexport.sh'
            . ' "' . $this->aAttValues['repository'] . '"'
            . ' "' . $this->aAttValues['module'] . '"'
            . ' "' . $this->aAttValues['srcdir'] . '"'
        );
        $this->getLogger()->info(implode("\n", $aResult) . '---');

        $this->oSyncTask->execute();
        $this->getLogger()->info('---');
    }
}
