<?php

class Task_Base_Link extends Task
{

    /**
     * Retourne le nom du tag XML correspondant à cette tâche dans les config projet.
     *
     * @return string nom du tag XML correspondant à cette tâche dans les config projet.
     */
    public static function getTagName ()
    {
        return 'link';
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
            'src' => Task::ATTRIBUTE_REQUIRED | Task::ATTRIBUTE_FILE | Task::ATTRIBUTE_DIR,
            'target' => Task::ATTRIBUTE_FILE | Task::ATTRIBUTE_DIR | Task::ATTRIBUTE_REQUIRED,
            'server' => Task::ATTRIBUTE_ALLOW_PARAMETER
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

        list($bIsSrcRemote, $aSrcMatches) = $this->oShell->isRemotePath($this->aAttributes['src']);
        list($bIsDestRemote, $aDestMatches) = $this->oShell->isRemotePath($this->aAttributes['target']);
        if (
            ($bIsSrcRemote && $bIsDestRemote && $aSrcMatches[1] != $aDestMatches[1])
            || ($bIsSrcRemote XOR $bIsDestRemote)
        ) {
            throw new DomainException('Servers must be equals!'
                . ' Src=' . $this->aAttributes['src'] . ' Target=' . $this->aAttributes['target']);
        }

        if ( ! empty($this->aAttributes['server']) && ($bIsSrcRemote || $bIsDestRemote)) {
            throw new DomainException('Multiple server declaration!' . ' Server=' . $this->aAttributes['server']
                . ' Src=' . $this->aAttributes['src'] . ' Target=' . $this->aAttributes['target']);
        }
    }

    protected function _centralExecute ()
    {
        parent::_centralExecute();
        $this->oLogger->indent();

        // La source doit être un lien ou ne pas exister :
        $sPath = $this->aAttributes['src'];
        if ( ! empty($this->aAttributes['server'])) {
            $sPath = $this->aAttributes['server'] . ':' . $sPath;
        }
        foreach ($this->_expandPath($sPath) as $sExpandedPath) {
            if ( ! in_array($this->oShell->getFileStatus($sExpandedPath), array(0, 11, 12))) {
                $sMsg = 'Source attribute must be a directoy symlink or a file symlink'
                      . " or not exist: '" . $sExpandedPath . "'";
                throw new RuntimeException($sMsg);
            }
        }

        $sRawTargetPath = $this->aAttributes['target'];
        if ( ! empty($this->aAttributes['server'])) {
            $sRawTargetPath = $this->aAttributes['server'] . ':' . $sRawTargetPath;
        }

        $aTargetPaths = $this->_processPath($sRawTargetPath);
        foreach ($aTargetPaths as $sTargetPath) {
            list(, $aDestMatches) = $this->oShell->isRemotePath($sTargetPath);
            list(, $aSrcMatches) = $this->oShell->isRemotePath($this->aAttributes['src']);
            $sSrc = $this->_processSimplePath($aDestMatches[1] . ':' . $aSrcMatches[2]);
            $this->oShell->createLink($sSrc, $sTargetPath);
        }
        $this->oLogger->unindent();
    }

    public function backup ()
    {
    }
}
