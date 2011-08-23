<?php

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
     * @param string $sBackupPath répertoire hôte pour le backup de la tâche.
     * @param ServiceContainer $oServiceContainer Register de services prédéfinis (Shell_Interface, Logger_Interface, ...).
     */
    public function __construct (SimpleXMLElement $oTask, Task_Base_Project $oProject, $sBackupPath, ServiceContainer $oServiceContainer)
    {
        parent::__construct($oTask, $oProject, $sBackupPath, $oServiceContainer);
        $this->aAttributeProperties = array(
            'project' => Task::ATTRIBUTE_REQUIRED,
            'destdir' => Task::ATTRIBUTE_DIR | Task::ATTRIBUTE_REQUIRED | Task::ATTRIBUTE_ALLOW_PARAMETER
        );
    }

    protected function _centralExecute ()
    {
        parent::_centralExecute();
        $this->oLogger->indent();

        $this->oLogger->log('Generate language archive');
        $sLanguagesPath = tempnam('/tmp', $this->oProperties->getProperty('execution_id') . '_languages_');
        $fh = fopen($sLanguagesPath, 'w');
        $aCurlParameters = array(
            'url' => 'https://admin.twenga.com/translation_tool/build_language_files.php?project=rts',
            'login' => 'gaubry',
            'password' => 'jR7nN0',
            'user_agent' => Curl::$USER_AGENTS['FireFox3'],
            'referer' => 'http://aai.twenga.com',
            'file' => $fh,
            'timeout' => 120,
            'return_header' => 0,
        );
        $result = Curl::disguiseCurl($aCurlParameters);
        fclose($fh);

        if ( ! empty($result['curl_error'])) {
            // Selon les configuration serveur, il se peut que le retour de cURL soit mal interprété.
            // Du coup on vérifie si c'est vrai en testant l'archive :
            if (preg_match('/^transfer closed with \d+ bytes remaining to read$/i', $result['curl_error']) === 1) {
                $this->oLogger->log('Test language archive');
                $this->oShell->exec('tar -tf "' . $sLanguagesPath . '"');
            } else {
                @unlink($sLanguagesPath);
                throw new RuntimeException($result['curl_error']);;
            }

        } else if ($result['http_code'] < 200 || $result['http_code'] >= 300) {
            @unlink($sLanguagesPath);
            throw new RuntimeException(
                'Return HTTP code: ' . $result['http_code']
                . '. Last URL: ' . $result['last_url']
                . '. Body: ' . $result['body']
            );
        }
        //$sLanguagesPath = '/home/gaubry/languages.tar.gz';

        // Diffusion de l'archive :
        $this->oLogger->log('Send language archive to servers');
        $aDestDirs = $this->_processPath($this->aAttributes['destdir']);
        foreach ($aDestDirs as $sDestDir) {
            $aResult = $this->oShell->copy($sLanguagesPath, $sDestDir);
            $sResult = implode("\n", $aResult);
            if (trim($sResult) != '') {
                $this->oLogger->log($sResult);
            }
        }

        // Décompression des archives :
        $this->oLogger->log('Extract language files from archive on servers');
        $sPatternCmd = 'cd %1$s && tar -xf %1$s/"' . basename($sLanguagesPath) . '" && rm -f %1$s/"' . basename($sLanguagesPath) . '"';
        foreach ($aDestDirs as $sDestDir) {
            $aResult = $this->oShell->execSSH($sPatternCmd, $sDestDir);
            $sResult = implode("\n", $aResult);
            if (trim($sResult) != '') {
                $this->oLogger->log($sResult);
            }
        }

        @unlink($sLanguagesPath);
        $this->oLogger->unindent();
    }

    public function backup ()
    {
    }
}