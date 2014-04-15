<?php

namespace Himedia\Padocc\Task\Extended;

use Himedia\Padocc\AttributeProperties;
use Himedia\Padocc\DIContainer;
use Himedia\Padocc\Task;
use Himedia\Padocc\Task\Base\Project;
use Himedia\Padocc\Task\Base\Sync;

/**
 * Exporte tout ou partie du contenu d'un dépôt Git vers une ou plusieurs destinations.
 * À inclure dans une tâche env ou target.
 *
 * Exploite le script '/src/inc/cvsexport.sh'.
 * Réalise la synchronisation à l'aide d'une tâche sync avec la liste d'exclusion suivante
 * (en plus des éventuels include et exclude spécifiés dans la tâche) : '.bzr/', '.cvsignore', '.git/',
 * '.gitignore', '.svn/', 'cvslog.*', 'CVS', 'CVS.adm'.
 *
 * Attributs :
 * - 'repository'
 * - 'ref' : branche ou tag à déployer
 * - 'localrepositorydir' : lieu temporaire d'extraction du contenu qui nous intéresse du dépôt avant de l'envoyer
 * vers la destination ⇒ laisser à vide de manière générale,
 * l'outil utilisera alors le répertoire $aConfig['dir']['repositories'].
 * - 'srcsubdir' : sous-répertoire du dépôt qui nous intéresse
 * - 'destdir'
 * - 'include' : si l'on veut filtrer
 * - 'exclude' : si l'on veut filtrer
 *
 * Exemple : <gitexport repository="git@git.twenga.com:rts/rts.git" ref="${REF}"
 *     destdir="${SERVERS}:${BASEDIR}" exclude="config.* /Tests" />
 *
 * @author Geoffroy AUBRY <gaubry@hi-media.com>
 */
class GitExport extends Task
{

    /**
     * Retourne le nom du tag XML correspondant à cette tâche dans les config projet.
     *
     * @return string nom du tag XML correspondant à cette tâche dans les config projet.
     * @codeCoverageIgnore
     */
    public static function getTagName ()
    {
        return 'gitexport';
    }

    /**
     * Tâche de synchronisation sous-jacente.
     * @var Sync
     */
    private $oSyncTask;

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
            'ref' => AttributeProperties::REQUIRED | AttributeProperties::ALLOW_PARAMETER,
            'localrepositorydir' => AttributeProperties::DIR,
            'srcsubdir' => AttributeProperties::DIR,
            'destdir' => AttributeProperties::DIR | AttributeProperties::REQUIRED
                | AttributeProperties::ALLOW_PARAMETER,
            // TODO AttributeProperties::DIRJOKER abusif ici, mais à cause du multivalué :
            'include' => AttributeProperties::FILEJOKER | AttributeProperties::DIRJOKER,
            'exclude' => AttributeProperties::FILEJOKER | AttributeProperties::DIRJOKER,
        );

        // Valeur par défaut de l'attribut localrepositorydir :
        if (empty($this->aAttValues['localrepositorydir'])) {
            $this->aAttValues['localrepositorydir'] =
                $this->aConfig['dir']['repositories'] . '/git/'
                . $this->oProperties->getProperty('project_name') . '_'
                . $this->oProperties->getProperty('environment_name') . '_'
                . $this->sCounter;
        } else {
            $this->aAttValues['localrepositorydir'] =
                preg_replace('#/$#', '', $this->aAttValues['localrepositorydir']);
        }

        // Création de la tâche de synchronisation sous-jacente :
        $this->oNumbering->addCounterDivision();
        if (empty($this->aAttValues['srcsubdir'])) {
            $this->aAttValues['srcsubdir'] = '';
        } else {
            $this->aAttValues['srcsubdir'] = '/' . preg_replace('#^/|/$#', '', $this->aAttValues['srcsubdir']);
        }
        $aSyncAttributes = array(
            'src' => $this->aAttValues['localrepositorydir'] . $this->aAttValues['srcsubdir'] . '/',
            'destdir' => $this->aAttValues['destdir'],
        );
        if (! empty($this->aAttValues['include'])) {
            $aSyncAttributes['include'] = $this->aAttValues['include'];
        }
        if (! empty($this->aAttValues['exclude'])) {
            $aSyncAttributes['exclude'] = $this->aAttValues['exclude'];
        }
        $this->oSyncTask = Sync::getNewInstance($aSyncAttributes, $oProject, $oDIContainer);
        $this->oNumbering->removeCounterDivision();
    }

    /**
     * Prépare la tâche avant exécution : vérifications basiques, analyse des serveurs concernés...
     */
    public function setUp ()
    {
        parent::setUp();
        $this->oLogger->info('+++');
        $this->oSyncTask->setUp();
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

        $aRef = $this->processPath($this->aAttValues['ref']);
        $sRef = $aRef[0];

        $sMsg = "Export '$sRef' reference from '" . $this->aAttValues['repository'] . "' git repository+++";
        $this->oLogger->info($sMsg);
        $aResult = $this->oShell->exec(
            $this->aConfig['bash_path'] . ' ' . $this->aConfig['dir']['inc'] . '/gitexport.sh'
            . ' "' . $this->aAttValues['repository'] . '"'
            . ' "' . $sRef . '"'
            . ' "' . $this->aAttValues['localrepositorydir'] . '"'
        );
        $this->oLogger->info(implode("\n", $aResult) . '---');

        $this->oSyncTask->execute();
        $this->oLogger->info('---');
    }
}
