<?php

class Task_Base_Sync extends Task
{

    /**
     * Retourne le nom du tag XML correspondant à cette tâche dans les config projet.
     *
     * @return string nom du tag XML correspondant à cette tâche dans les config projet.
     */
    public static function getTagName ()
    {
        return 'sync';
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
            'destdir' => Task::ATTRIBUTE_DIR | Task::ATTRIBUTE_REQUIRED | Task::ATTRIBUTE_ALLOW_PARAMETER,
            // TODO Task::ATTRIBUTE_DIRJOKER abusif ici, mais à cause du multivalué :
            'exclude' => Task::ATTRIBUTE_FILEJOKER | Task::ATTRIBUTE_DIRJOKER,
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
        parent::check();
        if (preg_match('#\*|\?#', $this->_aAttributes['src']) === 0) {
            if ($this->_oShell->getFileStatus($this->_aAttributes['src']) === 2) {
                $this->_aAttributes['destdir'] .= '/' . substr(strrchr($this->_aAttributes['src'], '/'), 1);
                $this->_aAttributes['src'] .= '/*';
            }
        }
    }

    protected function _centralExecute ()
    {
        parent::_centralExecute();
        $this->_oLogger->indent();
        $sMsg = "Synchronize '" . $this->_aAttributes['src'] . "' with '" . $this->_aAttributes['destdir'] . "'";
        $this->_oLogger->log($sMsg);
        $this->_oLogger->indent();
        $aExcludedPaths = (empty($this->_aAttributes['exclude'])
                          ? array()
                          : explode(' ', $this->_aAttributes['exclude']));
        $results = $this->_oShell->sync(
            $this->_aAttributes['src'],
            $this->_processPath($this->_aAttributes['destdir']),
            $aExcludedPaths
        );
        foreach ($results as $result) {
            $this->_oLogger->log($result);
        }
        $this->_oLogger->unindent();
        $this->_oLogger->unindent();
    }

    public function backup ()
    {
        if ($this->_oShell->getFileStatus($this->_aAttributes['destdir']) !== 0) {
            list($bIsRemote, $aMatches) = $this->_oShell->isRemotePath($this->_aAttributes['destdir']);
            $sBackupPath = ($bIsRemote ? $aMatches[1]. ':' : '') . $this->_sBackupPath . '/'
                . pathinfo($aMatches[2], PATHINFO_BASENAME) . '.tar.gz';
            $this->_oShell->backup($this->_aAttributes['destdir'], $sBackupPath);
        }
    }
}
