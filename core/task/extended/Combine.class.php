<?php

/**
 * @category TwengaDeploy
 * @package Core
 * @author Geoffroy AUBRY <geoffroy.aubry@twenga.com>
 */
class Task_Extended_Combine extends Task
{

    /**
     * Retourne le nom du tag XML correspondant à cette tâche dans les config projet.
     *
     * @return string nom du tag XML correspondant à cette tâche dans les config projet.
     */
    public static function getTagName ()
    {
        return 'combine';
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
            'server' => 0,
            'cssbasedir' => AttributeProperties::DIR,
            'cssfile' => AttributeProperties::FILEJOKER,
            'jsbasedir' => AttributeProperties::DIR,
            'jsfile' => AttributeProperties::FILEJOKER,
            'destfile' => AttributeProperties::FILE
        );
    }

    protected function _centralExecute ()
    {
        parent::_centralExecute();
        $this->_oLogger->indent();

        $this->_oLogger->log('Combine');

        $sSrcPath = $this->_aAttributes['server'] . ':' . $this->_aAttributes['cssbasedir']
                  . '/' . $this->_aAttributes['cssfile'];
        $this->_oShell->copy($sSrcPath, DEPLOYMENT_TMP_PATH);

        $this->_oLogger->unindent();
    }
}
