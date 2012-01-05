<?php

/**
 * Renomme un fichier ou un répertoire.
 * À inclure dans une tâche env ou target.
 *
 * Exemple : <rename src="${TMPDIR}/v3" dest="${TMPDIR}/web" />
 *
 * @category TwengaDeploy
 * @package Core
 * @author Geoffroy AUBRY <geoffroy.aubry@twenga.com>
 */
class Task_Base_Rename extends Task
{

    /**
     * Retourne le nom du tag XML correspondant à cette tâche dans les config projet.
     *
     * @return string nom du tag XML correspondant à cette tâche dans les config projet.
     */
    public static function getTagName ()
    {
        return 'rename';
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
            'src' => AttributeProperties::SRC_PATH | AttributeProperties::REQUIRED
                | AttributeProperties::ALLOW_PARAMETER,
            'dest' => AttributeProperties::FILE | AttributeProperties::DIR | AttributeProperties::REQUIRED
                | AttributeProperties::ALLOW_PARAMETER
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

        // Pour l'instant ne gère pas les chemins distants :
        list(, $sSrcServer, ) = $this->_oShell->isRemotePath($this->_aAttributes['src']);
        list(, $sDestServer, ) = $this->_oShell->isRemotePath($this->_aAttributes['dest']);
        if ($sSrcServer != $sDestServer) {
            throw new DomainException('Paths must be local or on the same server!');
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
        $aSrcPath = $this->_processSimplePath($this->_aAttributes['src']);
        $aDestPath = $this->_processSimplePath($this->_aAttributes['dest']);
        $this->_oLogger->log("Rename '$aSrcPath' to '$aDestPath'.");
        $this->_oShell->execSSH("mv %s '" . $aDestPath . "'", $aSrcPath);
        $this->_oLogger->unindent();
    }
}
