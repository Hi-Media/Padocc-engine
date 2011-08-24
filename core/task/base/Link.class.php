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
        $this->_aAttributeProperties = array(
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

        list($bIsSrcRemote, $aSrcMatches) = $this->_oShell->isRemotePath($this->_aAttributes['src']);
        list($bIsDestRemote, $aDestMatches) = $this->_oShell->isRemotePath($this->_aAttributes['target']);
        if (
            ($bIsSrcRemote XOR $bIsDestRemote)
            || ($bIsSrcRemote && $bIsDestRemote && $aSrcMatches[1] != $aDestMatches[1])
        ) {
            $sMsg = 'Servers must be equals!' . ' Src=' . $this->_aAttributes['src']
                  . ' Target=' . $this->_aAttributes['target'];
            throw new DomainException($sMsg);
        }

        if ( ! empty($this->_aAttributes['server']) && ($bIsSrcRemote || $bIsDestRemote)) {
            $sMsg = 'Multiple server declaration!' . ' Server=' . $this->_aAttributes['server']
                  . ' Src=' . $this->_aAttributes['src'] . ' Target=' . $this->_aAttributes['target'];
            throw new DomainException($sMsg);
        }
    }

    protected function _centralExecute ()
    {
        parent::_centralExecute();
        $this->_oLogger->indent();

        // La source doit être un lien ou ne pas exister :
        $sPath = $this->_aAttributes['src'];
        if ( ! empty($this->_aAttributes['server'])) {
            $sPath = $this->_aAttributes['server'] . ':' . $sPath;
        }
        foreach ($this->_expandPath($sPath) as $sExpandedPath) {
            if ( ! in_array($this->_oShell->getFileStatus($sExpandedPath), array(0, 11, 12))) {
                $sMsg = 'Source attribute must be a directoy symlink or a file symlink'
                      . " or not exist: '" . $sExpandedPath . "'";
                throw new RuntimeException($sMsg);
            }
        }

        $sRawTargetPath = $this->_aAttributes['target'];
        if ( ! empty($this->_aAttributes['server'])) {
            $sRawTargetPath = $this->_aAttributes['server'] . ':' . $sRawTargetPath;
        }

        $aTargetPaths = $this->_processPath($sRawTargetPath);
        foreach ($aTargetPaths as $sTargetPath) {
            list(, $aDestMatches) = $this->_oShell->isRemotePath($sTargetPath);
            list(, $aSrcMatches) = $this->_oShell->isRemotePath($this->_aAttributes['src']);
            $sSrc = $this->_processSimplePath($aDestMatches[1] . ':' . $aSrcMatches[2]);
            $this->_oShell->createLink($sSrc, $sTargetPath);
        }
        $this->_oLogger->unindent();
    }

    public function backup ()
    {
    }
}
