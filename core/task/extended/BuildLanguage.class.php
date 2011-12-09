<?php

/**
 * @category TwengaDeploy
 * @package Core
 * @author Geoffroy AUBRY <geoffroy.aubry@twenga.com>
 */
class Task_Extended_BuildLanguage extends Task
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
     * @param SimpleXMLElement $oTask Contenu XML de la tâche.
     * @param Task_Base_Project $oProject Super tâche projet.
     * @param ServiceContainer $oServiceContainer Register de services prédéfinis (Shell_Interface, ...).
     */
    public function __construct (SimpleXMLElement $oTask, Task_Base_Project $oProject,
        ServiceContainer $oServiceContainer)
    {
        parent::__construct($oTask, $oProject, $oServiceContainer);
        $this->_aAttrProperties = array(
            'project' => AttributeProperties::REQUIRED,
            'destdir' => AttributeProperties::DIR | AttributeProperties::REQUIRED
                | AttributeProperties::ALLOW_PARAMETER
        );
    }

    /**
     * Phase de traitements centraux de l'exécution de la tâche.
     * Elle devrait systématiquement commencer par "parent::_centralExecute();".
     * Appelé par _execute().
     * @see execute()
     */
    protected function _centralExecute ()
    {
        parent::_centralExecute();
        $this->_oLogger->indent();

        $sLanguagesPath = tempnam(
            DEPLOYMENT_TMP_DIR,
            $this->_oProperties->getProperty('execution_id') . '_languages_'
        );
        $sURL = 'https://%s:%s@'
              . 'admin.twenga.com/translation_tool/build_language_files.php?project='
              . $this->_aAttributes['project'];
        $this->_oLogger->log('Generate language archive from web service: ' . $sURL);
        $sURL = sprintf($sURL, DEPLOYMENT_LANGUAGE_WS_LOGIN, DEPLOYMENT_LANGUAGE_WS_PASSWORD);
        if ( ! copy($sURL, $sLanguagesPath)) {
            throw new RuntimeException("Copy of '$sURL' to '$sLanguagesPath' failed!");
        }

        // Diffusion de l'archive :
        $this->_oLogger->log('Send language archive to all servers');
        $this->_oLogger->indent();
        $aDestDirs = $this->_processPath($this->_aAttributes['destdir']);
        foreach ($aDestDirs as $sDestDir) {
            $aResult = $this->_oShell->copy($sLanguagesPath, $sDestDir);
            $sResult = implode("\n", $aResult);
            if (trim($sResult) != '') {
                $this->_oLogger->log($sResult);
            }
        }
        $this->_oLogger->unindent();

        // Décompression des archives :
        $this->_oLogger->log('Extract language files from archive on each server');
        $this->_oLogger->indent();
        $sPatternCmd = 'cd %1$s && tar -xf %1$s/"' . basename($sLanguagesPath)
                     . '" && rm -f %1$s/"' . basename($sLanguagesPath) . '"';
        foreach ($aDestDirs as $sDestDir) {
            $aResult = $this->_oShell->execSSH($sPatternCmd, $sDestDir);
            $sResult = implode("\n", $aResult);
            if (trim($sResult) != '') {
                $this->_oLogger->log($sResult);
            }
        }
        $this->_oLogger->unindent();

        @unlink($sLanguagesPath);
        $this->_oLogger->unindent();
    }
}
