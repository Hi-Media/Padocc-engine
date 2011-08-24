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
        $this->aAttributeProperties = array(
            'src' => Task::ATTRIBUTE_SRC_PATH | Task::ATTRIBUTE_FILEJOKER | Task::ATTRIBUTE_REQUIRED,
            'destdir' => Task::ATTRIBUTE_DIR | Task::ATTRIBUTE_REQUIRED | Task::ATTRIBUTE_ALLOW_PARAMETER,
            'exclude' => Task::ATTRIBUTE_FILEJOKER,
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
        if (preg_match('#\*|\?#', $this->aAttributes['src']) === 0) {
            if ($this->oShell->getFileStatus($this->aAttributes['src']) === 2) {
                $this->aAttributes['destdir'] .= '/' . substr(strrchr($this->aAttributes['src'], '/'), 1);
                $this->aAttributes['src'] .= '/*';
            }
        }
    }

    protected function _centralExecute ()
    {
        parent::_centralExecute();
        $this->oLogger->indent();
        $sMsg = "Synchronize '" . $this->aAttributes['src'] . "' with '" . $this->aAttributes['destdir'] . "'";
        $this->oLogger->log($sMsg);
        $aExcludedPaths = (empty($this->aAttributes['exclude'])
                          ? array()
                          : explode(' ', $this->aAttributes['exclude']));
        $results = $this->oShell->sync($this->aAttributes['src'],
                                       $this->_processPath($this->aAttributes['destdir']),
                                       $aExcludedPaths);
        foreach ($results as $result) {
            $this->oLogger->log($result);
        }
        $this->oLogger->unindent();
    }

    public function backup ()
    {
        if ($this->oShell->getFileStatus($this->aAttributes['destdir']) !== 0) {
            list($bIsRemote, $aMatches) = $this->oShell->isRemotePath($this->aAttributes['destdir']);
            $sBackupPath = ($bIsRemote ? $aMatches[1]. ':' : '') . $this->sBackupPath . '/'
                . pathinfo($aMatches[2], PATHINFO_BASENAME) . '.tar.gz';
            $this->oShell->backup($this->aAttributes['destdir'], $sBackupPath);
        }
    }
}
