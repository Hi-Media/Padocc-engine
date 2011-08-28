<?php

/**
 * @category TwengaDeploy
 * @package Core
 * @author Geoffroy AUBRY
 */
class Task_Base_Backup extends Task
{

    /**
     * Retourne le nom du tag XML correspondant à cette tâche dans les config projet.
     *
     * @return string nom du tag XML correspondant à cette tâche dans les config projet.
     */
    public static function getTagName ()
    {
        return 'backup';
    }

    /**
     * Constructeur.
     *
     * @param SimpleXMLElement $oTask Contenu XML de la tâche.
     * @param Task_Base_Project $oProject Super tâche projet.
     * @param string $sBackupPath répertoire hôte pour le backup de la tâche.
     * @param ServiceContainer $oServiceContainer Register de services prédéfinis (Shell_Interface ...).
     */
    public function __construct (SimpleXMLElement $oTask, Task_Base_Project $oProject, $sBackupPath,
        ServiceContainer $oServiceContainer)
    {
        parent::__construct($oTask, $oProject, $sBackupPath, $oServiceContainer);
        $this->_aAttributeProperties = array(
            'src' => Task::ATTRIBUTE_SRC_PATH | Task::ATTRIBUTE_FILEJOKER | Task::ATTRIBUTE_REQUIRED,
            'destfile' => Task::ATTRIBUTE_FILE | Task::ATTRIBUTE_REQUIRED
        );
    }

    protected function _centralExecute ()
    {
        parent::_centralExecute();
        $this->_oLogger->indent();
        $this->_oShell->backup($this->_aAttributes['src'], $this->_aAttributes['destfile']);
        $this->_oLogger->unindent();
    }

    public function backup ()
    {
        if ($this->_oShell->getPathStatus($this->_aAttributes['destfile']) !== Shell_Interface::STATUS_NOT_EXISTS) {
            list($bIsRemote, $aMatches) = $this->_oShell->isRemotePath($this->_aAttributes['destfile']);
            $sBackupPath = ($bIsRemote ? $aMatches[1]. ':' : '')
                         . $this->_sBackupPath . '/' . pathinfo($aMatches[2], PATHINFO_BASENAME);
            $this->_oShell->copy($this->_aAttributes['destfile'], $sBackupPath, true);
        }
    }
}
