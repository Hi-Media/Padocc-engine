<?php

namespace Himedia\Padocc\Task\Extended;

use Himedia\Padocc\AttributeProperties;
use Himedia\Padocc\DIContainer;
use Himedia\Padocc\Task;
use Himedia\Padocc\Task\Base\Project;

/**
 * Génère les fichiers de langue au format [geozoneId].php pour un projet donné.
 * À inclure dans une tâche env ou target.
 *
 * Complètement recodé par rapport à la version précédente : environ 20 fois plus rapide !
 *
 * Exemples : <buildlanguage project="rts" destdir="${SERVERS}:${BASEDIR}/languages" />
 *
 * @author Geoffroy AUBRY <gaubry@hi-media.com>
 */
class BuildLanguage extends Task
{

    /**
     * Retourne le nom du tag XML correspondant à cette tâche dans les config projet.
     *
     * @return string nom du tag XML correspondant à cette tâche dans les config projet.
     */
    public static function getTagName ()
    {
        return 'buildlanguage';
    }

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
            'project' => AttributeProperties::REQUIRED,
            'destdir' => AttributeProperties::DIR | AttributeProperties::REQUIRED
                | AttributeProperties::ALLOW_PARAMETER
        );
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

        $sLanguagesPath = tempnam(
            DEPLOYMENT_TMP_DIR,
            $this->oProperties->getProperty('execution_id') . '_languages_'
        );
        $sURL = 'https://xyz/translation_tool/build_language_files.php?project='
              . $this->aAttValues['project'];
        $this->oLogger->info('Generate language archive from web service: ' . $sURL);
        if (! copy($sURL, $sLanguagesPath)) {
            throw new \RuntimeException("Copy of '$sURL' to '$sLanguagesPath' failed!");
        }

        // Diffusion de l'archive :
        $this->oLogger->info('Send language archive to all servers+++');
        $aDestDirs = $this->processPath($this->aAttValues['destdir']);
        foreach ($aDestDirs as $sDestDir) {
            $aResult = $this->oShell->copy($sLanguagesPath, $sDestDir);
            $sResult = implode("\n", $aResult);
            if (trim($sResult) != '') {
                $this->oLogger->info($sResult);
            }
        }
        $this->oLogger->info('---');

        // Décompression des archives :
        $this->oLogger->info('Extract language files from archive on each server+++');
        $sPatternCmd = 'cd %1$s && tar -xf %1$s/"' . basename($sLanguagesPath)
                     . '" && rm -f %1$s/"' . basename($sLanguagesPath) . '"';
        foreach ($aDestDirs as $sDestDir) {
            $aResult = $this->oShell->execSSH($sPatternCmd, $sDestDir);
            $sResult = implode("\n", $aResult);
            if (trim($sResult) != '') {
                $this->oLogger->info($sResult);
            }
        }
        $this->oLogger->info('---');

        @unlink($sLanguagesPath);
        $this->oLogger->info('---');
    }
}
