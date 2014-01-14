<?php

namespace Himedia\Padocc\Task\Extended;

use Himedia\Padocc\AttributeProperties;
use Himedia\Padocc\DIContainer;
use Himedia\Padocc\Task;
use Himedia\Padocc\Task\Base\Project;

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
     * Retourne le nom du tag XML correspondant à cette tâche dans les config projet.
     *
     * @return string nom du tag XML correspondant à cette tâche dans les config projet.
     */
    public static function getTagName ()
    {
        return 'cvsexport';
    }

    /**
     * Tâche de synchronisation sous-jacente.
     * @var Task\Base\Sync
     */
    private $_oSyncTask;

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
        $this->_oSyncTask = Task\Base\Sync::getNewInstance(
            array(
                'src' => $this->aAttValues['srcdir'] . '/' . $this->aAttValues['module'] . '/',
                'destdir' => $this->aAttValues['destdir']
            ),
            $oProject,
            $oDIContainer
        );
        $this->oNumbering->removeCounterDivision();
    }

    /**
     * Prépare la tâche avant exécution : vérifications basiques, analyse des serveurs concernés...
     */
    public function setUp ()
    {
        parent::setUp();
        $this->oLogger->info('+++');
        try {
            $this->_oSyncTask->setUp();
        } catch (\UnexpectedValueException $oException) {
            if ($oException->getMessage() !== "File or directory '" . $this->aAttValues['srcdir']
                                            . '/' . $this->aAttValues['module'] . '/' . "' not found!") {
                throw $oException;
            }
        }

        $this->oLogger->info('---');
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
        $this->oLogger->info('+++');

        $this->oLogger->info("Export from '" . $this->aAttValues['repository'] . "' CVS repository+++");
        $aResult = $this->oShell->exec(
            $this->aConfig['bash_path'] . ' ' . $this->aConfig['dir']['inc'] . '/cvsexport.sh'
            . ' "' . $this->aAttValues['repository'] . '"'
            . ' "' . $this->aAttValues['module'] . '"'
            . ' "' . $this->aAttValues['srcdir'] . '"'
        );
        $this->oLogger->info(implode("\n", $aResult) . '---');

        $this->_oSyncTask->execute();
        $this->oLogger->info('---');
    }
}
