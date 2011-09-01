<?php

/**
 * @category TwengaDeploy
 * @package Core
 * @author Geoffroy AUBRY <geoffroy.aubry@twenga.com>
 */
class Task_Base_HTTP extends Task
{

    /**
     * Retourne le nom du tag XML correspondant à cette tâche dans les config projet.
     *
     * @return string nom du tag XML correspondant à cette tâche dans les config projet.
     */
    public static function getTagName ()
    {
        return 'http';
    }

    /**
     * Constructeur.
     *
     * @param SimpleXMLElement $oTask Contenu XML de la tâche.
     * @param Task_Base_Project $oProject Super tâche projet.
     * @param string $sBackupPath répertoire hôte pour le backup de la tâche.
     * @param ServiceContainer $oServiceContainer Register de services prédéfinis (Shell_Interface, ...).
     */
    public function __construct (SimpleXMLElement $oTask, Task_Base_Project $oProject, $sBackupPath,
        ServiceContainer $oServiceContainer)
    {
        parent::__construct($oTask, $oProject, $sBackupPath, $oServiceContainer);
        $this->_aAttrProperties = array(
            'url' => AttributeProperties::ALLOW_PARAMETER | AttributeProperties::REQUIRED | AttributeProperties::URL
        );
    }

    protected function _centralExecute ()
    {
        parent::_centralExecute();
        $this->_oLogger->indent();

        $aURLs = $this->_processPath($this->_aAttributes['url']);
        foreach ($aURLs as $sURL) {
            $aResults = $this->_oShell->exec('curl --silent --retry 2 --retry-delay 2 --max-time 5 "' . $sURL . '"');
            if (count($aResults) > 0 && substr(end($aResults), 0, 7) === '[ERROR]') {
                throw new RuntimeException(implode("\n", $aResults));
            }
        }

        $this->_oLogger->unindent();
    }

    public function backup ()
    {
    }
}
