<?php

/**
 * @category TwengaDeploy
 * @package Core
 * @author Geoffroy AUBRY
 */
class Task_Base_Copy extends Task
{

    /**
     * Retourne le nom du tag XML correspondant à cette tâche dans les config projet.
     *
     * @return string nom du tag XML correspondant à cette tâche dans les config projet.
     */
    public static function getTagName ()
    {
        return 'copy';
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
        $this->_aAttributeProperties = array(
            'src' => Task::ATTRIBUTE_SRC_PATH | Task::ATTRIBUTE_FILEJOKER | Task::ATTRIBUTE_REQUIRED,
            'destdir' => Task::ATTRIBUTE_DIR | Task::ATTRIBUTE_REQUIRED
        );
    }

    /**
     * Vérifie au moyen de tests basiques que la tâche peut être exécutée.
     * Lance une exception si tel n'est pas le cas.
     *
     * Comme toute les tâches sont vérifiées avant que la première ne soit exécutée,
     * doit permettre de remonter au plus tôt tout dysfonctionnement.
     * Appelé avant la méthode execute().
     *
     * @throws UnexpectedValueException en cas d'attribut ou fichier manquant
     * @throws DomainException en cas de valeur non permise
     */
    public function check ()
    {
        // TODO si *|? alors s'assurer qu'il en existe ?
        // TODO droit seulement à \w et / et ' ' ?
        parent::check();
        if (
                preg_match('#\*|\?#', $this->_aAttributes['src']) === 0
                && $this->_oShell->getPathStatus($this->_aAttributes['src']) === Shell_Interface::STATUS_DIR
        ) {
                $this->_aAttributes['destdir'] .= '/' . substr(strrchr($this->_aAttributes['src'], '/'), 1);
                $this->_aAttributes['src'] .= '/*';
        }
    }

    protected function _centralExecute ()
    {
        parent::_centralExecute();
        $this->_oLogger->indent();

        $aDestDirs = $this->_processPath($this->_aAttributes['destdir']);
        foreach ($aDestDirs as $sDestDir) {
            $this->_oShell->copy($this->_aAttributes['src'], $sDestDir);
        }

        $this->_oLogger->unindent();
    }

    public function backup ()
    {
        if ($this->_oShell->getPathStatus($this->_aAttributes['destdir']) !== Shell_Interface::STATUS_NOT_EXISTS) {
            list($bIsRemote, $aMatches) = $this->_oShell->isRemotePath($this->_aAttributes['destdir']);
            $sBackupPath = ($bIsRemote ? $aMatches[1]. ':' : '') . $this->_sBackupPath . '/'
                . pathinfo($aMatches[2], PATHINFO_BASENAME) . '.tar.gz';
            $this->_oShell->backup($this->_aAttributes['destdir'], $sBackupPath);
        }
    }
}
