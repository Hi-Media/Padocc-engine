<?php

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
     * @param ServiceContainer $oServiceContainer Register de services prédéfinis (Shell_Interface, Logger_Interface, ...).
     */
    public function __construct (SimpleXMLElement $oTask, Task_Base_Project $oProject, $sBackupPath, ServiceContainer $oServiceContainer)
    {
        parent::__construct($oTask, $oProject, $sBackupPath, $oServiceContainer);
        $this->aAttributeProperties = array(
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
                preg_match('#\*|\?#', $this->aAttributes['src']) === 0
                && $this->oShell->getFileStatus($this->aAttributes['src']) === 2
        ) {
                $this->aAttributes['destdir'] .= '/' . substr(strrchr($this->aAttributes['src'], '/'), 1);
                $this->aAttributes['src'] .= '/*';
        }
    }

    public function execute ()
    {
        parent::execute();
        $this->oLogger->indent();

        $aDestDirs = $this->_processPath($this->aAttributes['destdir']);
        foreach ($aDestDirs as $sDestDir) {
            $this->oShell->copy($this->aAttributes['src'], $sDestDir);
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
