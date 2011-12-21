<?php

/**
 * @category TwengaDeploy
 * @package Core
 * @author Geoffroy AUBRY <geoffroy.aubry@twenga.com>
 */
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
     * @param ServiceContainer $oServiceContainer Register de services prédéfinis (Shell_Interface, ...).
     */
    public function __construct (SimpleXMLElement $oTask, Task_Base_Project $oProject,
        ServiceContainer $oServiceContainer)
    {
        parent::__construct($oTask, $oProject, $oServiceContainer);
        $this->_aAttrProperties = array(
            'src' => AttributeProperties::REQUIRED | AttributeProperties::FILE | AttributeProperties::DIR
                | AttributeProperties::ALLOW_PARAMETER,
            'target' => AttributeProperties::FILE | AttributeProperties::DIR | AttributeProperties::REQUIRED
                | AttributeProperties::ALLOW_PARAMETER,
            'server' => AttributeProperties::ALLOW_PARAMETER
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

        list($bIsSrcRemote, $sSrcServer, ) = $this->_oShell->isRemotePath($this->_aAttributes['src']);
        list($bIsDestRemote, $sDestServer, ) = $this->_oShell->isRemotePath($this->_aAttributes['target']);
        if (
            ($bIsSrcRemote XOR $bIsDestRemote)
            || ($bIsSrcRemote && $bIsDestRemote && $sSrcServer != $sDestServer)
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

        // Valeur par défaut :
        if ( ! isset($this->_aAttributes['server'])) {
            $this->_aAttributes['server'] = '';
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

        // La source doit être un lien symbolique ou ne pas exister :
        $sPath = $this->_aAttributes['src'];
        if ( ! empty($this->_aAttributes['server'])) {
            $sPath = $this->_aAttributes['server'] . ':' . $sPath;
        }
        $aValidSources = array(
            Shell_PathStatus::STATUS_NOT_EXISTS,
            Shell_PathStatus::STATUS_SYMLINKED_FILE,
            Shell_PathStatus::STATUS_SYMLINKED_DIR,
            Shell_PathStatus::STATUS_BROKEN_SYMLINK
        );
        foreach ($this->_expandPath($sPath) as $sExpandedPath) {
            if ( ! in_array($this->_oShell->getPathStatus($sExpandedPath), $aValidSources)) {
                $sMsg = "Source attribute must be a symlink or not exist: '" . $sExpandedPath . "'";
                throw new RuntimeException($sMsg);
            }
        }

        $sRawTargetPath = $this->_aAttributes['target'];
        if ( ! empty($this->_aAttributes['server'])) {
            $sRawTargetPath = $this->_aAttributes['server'] . ':' . $sRawTargetPath;
        }
        $this->_oLogger->log("Create symlink from '$sPath' to '$sRawTargetPath'.");

        $this->_oLogger->indent();
        $aTargetPaths = $this->_processPath($sRawTargetPath);
        foreach ($aTargetPaths as $sTargetPath) {
            list(, $sDestServer, ) = $this->_oShell->isRemotePath($sTargetPath);
            if ( ! empty($sDestServer)) {
                $sDestServer .= ':';
            }
            list(, , $sSrcRealPath) = $this->_oShell->isRemotePath($this->_aAttributes['src']);
            $sSrc = $this->_processSimplePath($sDestServer . $sSrcRealPath);
            $this->_oShell->createLink($sSrc, $sTargetPath);
        }
        $this->_oLogger->unindent();
        $this->_oLogger->unindent();
    }
}
