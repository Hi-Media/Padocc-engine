<?php

/**
 * Effectue un appel cURL.
 * À inclure dans une tâche env ou target.
 * Une telle tâche est automatiquement créée par la tâche b2cswitchsymlink.
 *
 * Exemple : <http url="http://aai.twenga.com/push.php?server=${WEB_SERVERS}&amp;app=web" />
 *
 * @category TwengaDeploy
 * @package Core
 * @author Geoffroy AUBRY <geoffroy.aubry@twenga.com>, Tony CARON <tony.caron@twenga.com>
 */
class Task_Base_HTTP extends Task
{

    /**
     * Retourne le nom du tag XML correspondant à cette tâche dans les config projet.
     *
     * @return string nom du tag XML correspondant à cette tâche dans les config projet.
     */
    public static function getTagName ()
    {
        return 'http';
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
            'url' => AttributeProperties::ALLOW_PARAMETER | AttributeProperties::REQUIRED | AttributeProperties::URL,
            'destdir' => AttributeProperties::ALLOW_PARAMETER | AttributeProperties::DIR
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
        $this->_oLogger->log('Call URL: ' . $this->_aAttributes['url']);
        $this->_oLogger->indent();


        $sTmpDir = $this->_oProperties->getProperty('tmpdir').'/curl';
        $this->_oShell->mkdir($sTmpDir);

        $aURLs = $this->_processPath($this->_aAttributes['url']);
        foreach ($aURLs as $sURL) {
            $sCmd = 'cd '.$sTmpDir.'; /usr/bin/curl --silent --retry 2 --retry-delay 2 --max-time 5 "' . $sURL . '"';
            if( isset($this->_aAttributes['destdir'] )) $sCmd .= ' -O';
            $aResults = $this->_oShell->exec($sCmd);
            if (count($aResults) > 0 && substr(end($aResults), 0, 7) === '[ERROR]') {
                throw new RuntimeException(implode("\n", $aResults));
            }
        }

        if (isset($this->_aAttributes['destdir'])) {
            $aDestDirs = $this->_processPath($this->_aAttributes['destdir']);
            foreach ($aDestDirs as $sDestDir) {
                $this->_oLogger->log('Copy file(s) to: ' . $sDestDir);
                $this->_oShell->copy($sTmpDir.'/*', $sDestDir, true);
            }

        }

        $this->_oShell->remove($sTmpDir);

        $this->_oLogger->unindent();
        $this->_oLogger->unindent();
    }
}
