<?php

namespace Himedia\Padocc\Task\Base;

use Himedia\Padocc\AttributeProperties;
use Himedia\Padocc\DIContainer;
use Himedia\Padocc\Task;

/**
 * Effectue un appel cURL.
 * À inclure dans une tâche env ou target.
 * Une telle tâche est automatiquement créée par la tâche b2cswitchsymlink.
 *
 * Exemple : <http url="http://aai.twenga.com/push.php?server=${WEB_SERVERS}&amp;app=web" />
 *
 * @author Geoffroy AUBRY <gaubry@hi-media.com>, Tony CARON <tony.caron@twenga.com>
 */
class HTTP extends Task
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
     * @param \SimpleXMLElement $oTask Contenu XML de la tâche.
     * @param Project $oProject Super tâche projet.
     * @param DIContainer $oDIContainer Register de services prédéfinis (ShellInterface, ...).
     */
    public function __construct (\SimpleXMLElement $oTask, Project $oProject, DIContainer $oDIContainer)
    {
        parent::__construct($oTask, $oProject, $oDIContainer);
        $this->aAttrProperties = array(
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
     * @throws \UnexpectedValueException en cas d'attribut ou fichier manquant
     * @throws \DomainException en cas de valeur non permise
     */
    public function check ()
    {
        parent::check();
    }

    /**
     * Phase de traitements centraux de l'exécution de la tâche.
     * Elle devrait systématiquement commencer par "parent::centralExecute();".
     * Appelé par execute().
     * @see execute()
     */
    protected function centralExecute ()
    {
        parent::centralExecute();
        $this->oLogger->info('+++Call URL: ' . $this->aAttValues['url'] . '+++');


        $sTmpDir = $this->oProperties->getProperty('tmpdir').'/curl';
        $this->oShell->mkdir($sTmpDir);

        $aURLs = $this->processPath($this->aAttValues['url']);
        foreach ($aURLs as $sURL) {
            $sCmd = 'cd '.$sTmpDir.'; /usr/bin/curl --silent --retry 2 --retry-delay 2 --max-time 5 "' . $sURL . '"';
            if( isset($this->aAttValues['destdir'] )) $sCmd .= ' -O';
            $aResults = $this->oShell->exec($sCmd);
            if (count($aResults) > 0 && substr(end($aResults), 0, 7) === '[ERROR]') {
                throw new \RuntimeException(implode("\n", $aResults));
            }
        }

        if (isset($this->aAttValues['destdir'])) {
            $aDestDirs = $this->processPath($this->aAttValues['destdir']);
            foreach ($aDestDirs as $sDestDir) {
                $this->oLogger->info('Copy file(s) to: ' . $sDestDir);
                $this->oShell->copy($sTmpDir.'/*', $sDestDir, true);
            }

        }

        $this->oShell->remove($sTmpDir);

        $this->oLogger->info('------');
    }
}
