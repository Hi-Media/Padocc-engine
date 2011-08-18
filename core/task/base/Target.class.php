<?php

class Task_Base_Target extends Task_WithProperties
{

    protected $aTasks;

    /**
     * Retourne le nom du tag XML correspondant à cette tâche dans les config projet.
     *
     * @return string nom du tag XML correspondant à cette tâche dans les config projet.
     */
    public static function getTagName ()
    {
        return 'target';
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
        $this->aAttributeProperties = array_merge($this->aAttributeProperties, array(
            'name' => Task::ATTRIBUTE_REQUIRED,
        ));

        $this->oNumbering->addCounterDivision();
        $this->aTasks = $this->getTaskInstances($oTask, $this->oProject, $sBackupPath); // et non $this->sBackupPath, pour les sous-tâches
        $this->oNumbering->removeCounterDivision();
    }

    /**
     * Retourne la liste des instances de tâches correspondant à chacune des tâches XML devant être exécutée
     * à l'intérieur du noeud XML spécifié.
     *
     * @param SimpleXMLElement $oTarget
     * @param Task_Base_Project $oProject
     * @param string $sBackupPath
     * @return array liste d'instances de type Task
     * @throws Exception si tag XML inconnu.
     * @see Task
     */
    private function getTaskInstances (SimpleXMLElement $oTarget, Task_Base_Project $oProject, $sBackupPath)
    {
        $this->oLogger->log('Initialize tasks');
        $aAvailableTasks = Tasks::getAvailableTasks();

        // Mise à plat des tâches car SimpleXML regroupe celles successives de même nom
        // dans un tableau et les autres sont hors tableau :
        $aTasks = array();
        foreach ($oTarget->children() as $sTag => $mTasks) {
            if (is_array($mTasks)) {
                foreach ($mTasks as $oTask) {
                    $aTasks[] = array($sTag, $oTask);
                }
            } else {
                $aTasks[] = array($sTag, $mTasks);
            }
        }

        // Création des instances de tâches :
        $aTaskInstances = array();
        foreach ($aTasks as $aTask) {
            list($sTag, $oTask) = $aTask;
            if ( ! isset($aAvailableTasks[$sTag])) {
                throw new UnexpectedValueException("Unkown task tag: '$sTag'!");
            } else {
                $aTaskInstances[] = new $aAvailableTasks[$sTag]($oTask, $oProject, $sBackupPath, $this->oServiceContainer);
            }
        }

        return $aTaskInstances;
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

        if ( ! empty($this->aAttributes['mailto'])) {
            $this->aAttributes['mailto'] = str_replace(array(';', ','), array(' ', ' '), trim($this->aAttributes['mailto']));
            $this->aAttributes['mailto'] = preg_replace('/\s{2,}/', ' ', $this->aAttributes['mailto']);
        }
    }

    public function setUp ()
    {
        parent::setUp();
        $this->oLogger->indent();
        foreach ($this->aTasks as $oTask) {
            $oTask->setUp();
        }
        $this->oLogger->unindent();
    }

    protected function _addMailTo ()
    {
        if ( ! empty($this->aAttributes['mailto'])) {
            $this->oLogger->log('[MAILTO] ' . $this->aAttributes['mailto']);
        }
    }

    public function execute ()
    {
        parent::execute();
        $this->oLogger->indent();
        $this->_addMailTo();
        foreach ($this->aTasks as $oTask) {
            $oTask->backup();
            $oTask->execute();
        }
        $this->oLogger->unindent();
    }

    public function backup ()
    {
    }
}
