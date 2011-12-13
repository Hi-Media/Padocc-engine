<?php

/**
 * @category TwengaDeploy
 * @package Core
 * @author Geoffroy AUBRY <geoffroy.aubry@twenga.com>
 */
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
     * @param ServiceContainer $oServiceContainer Register de services prédéfinis (Shell_Interface, ...).
     */
    public function __construct (SimpleXMLElement $oTask, Task_Base_Project $oProject,
        ServiceContainer $oServiceContainer)
    {
        parent::__construct($oTask, $oProject, $oServiceContainer);
        $this->_aAttrProperties = array(
            'src' => AttributeProperties::SRC_PATH | AttributeProperties::FILEJOKER | AttributeProperties::REQUIRED
                | AttributeProperties::ALLOW_PARAMETER,
            'destdir' => AttributeProperties::DIR | AttributeProperties::REQUIRED
                | AttributeProperties::ALLOW_PARAMETER,
            // TODO AttributeProperties::DIRJOKER abusif ici, mais à cause du multivalué :
            'include' => AttributeProperties::FILEJOKER | AttributeProperties::DIRJOKER,
            // TODO AttributeProperties::DIRJOKER abusif ici, mais à cause du multivalué :
            'exclude' => AttributeProperties::FILEJOKER | AttributeProperties::DIRJOKER,
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
        if (
                preg_match('#\*|\?|/$#', $this->_aAttributes['src']) === 0
                && $this->_oShell->getPathStatus($this->_aAttributes['src']) === Shell_PathStatus::STATUS_DIR
        ) {
            $this->_aAttributes['destdir'] .= '/' . substr(strrchr($this->_aAttributes['src'], '/'), 1);
            $this->_aAttributes['src'] .= '/';
        }
    }

    /**
     * Phase de traitements centraux de l'exécution de la tâche.
     * Elle devrait systématiquement commencer par "parent::_centralExecute();".
     * Appelé par _execute().
     * @see execute()
     */
    protected function _centralExecute ()
    {
        parent::_centralExecute();
        $this->_oLogger->indent();
        $sMsg = "Synchronize '" . $this->_aAttributes['src'] . "' with '" . $this->_aAttributes['destdir'] . "'";
        $this->_oLogger->log($sMsg);
        $this->_oLogger->indent();

        // include / exclude :
        $aIncludedPaths = (empty($this->_aAttributes['include'])
                          ? array()
                          : explode(' ', $this->_aAttributes['include']));
        $aExcludedPaths = (empty($this->_aAttributes['exclude'])
                          ? array()
                          : explode(' ', $this->_aAttributes['exclude']));

        list($bIsDestRemote, $sDestServer, $sDestRawPath) =
            $this->_oShell->isRemotePath($this->_aAttributes['destdir']);
        $sDestPath = ($bIsDestRemote ? '[]:' . $sDestRawPath : $sDestRawPath);
        foreach ($this->_processPath($sDestPath) as $sDestRealPath) {
            $aResults = $this->_oShell->sync(
                $this->_processSimplePath($this->_aAttributes['src']),
                $this->_processSimplePath($sDestRealPath),
                $this->_processPath($sDestServer),
                $aIncludedPaths,
                $aExcludedPaths
            );
            foreach ($aResults as $sResult) {
                $this->_oLogger->log($sResult);
            }
        }
        $this->_oLogger->unindent();
        $this->_oLogger->unindent();
    }
}
