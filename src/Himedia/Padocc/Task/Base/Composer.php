<?php

namespace Himedia\Padocc\Task\Base;

use Himedia\Padocc\AttributeProperties;
use Himedia\Padocc\DIContainer;
use Himedia\Padocc\Task;

/**
 * Exécute l'outil de gestion de dépendances PHP composer.
 * À inclure dans une tâche env ou target.
 *
 * Attributs :
 * - 'dir' : répertoire d'où exécuter composer
 * - 'options' : options à transmettre à la commande 'composer install', par défaut '--no-dev'
 *
 * Exemple : <composer dir="${TMPDIR}" options="--no-dev" />
 *
 * @author Geoffroy AUBRY <gaubry@hi-media.com>
 */
class Composer extends Task
{

    /**
     * Retourne le nom du tag XML correspondant à cette tâche dans les config projet.
     *
     * @return string nom du tag XML correspondant à cette tâche dans les config projet.
     * @codeCoverageIgnore
     */
    public static function getTagName ()
    {
        return 'composer';
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
            'dir' => AttributeProperties::DIR | AttributeProperties::REQUIRED
                | AttributeProperties::ALLOW_PARAMETER,
            'options' => 0
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

        if (empty($this->aAttValues['options'])) {
            $this->aAttValues['options'] = '--no-dev';
        }
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
        $this->oLogger->info('+++');

        $sInstallCmdPattern = '%1$s install --working-dir "%2$s" %3$s';
        $sWGetCmd = 'wget -q --no-check-certificate http://getcomposer.org/installer -O - | php';
        $sCURLCmd = 'curl -sS https://getcomposer.org/installer | php';
        $sCheckCmd = 'which composer 1>/dev/null 2>&1 && echo -n 1 || echo -n 0; '
              . 'which wget     1>/dev/null 2>&1 && echo -n 1 || echo -n 0; '
              . 'which curl     1>/dev/null 2>&1 && echo 1    || echo 0';

        $aDirs = $this->processPath($this->aAttValues['dir']);
        foreach ($aDirs as $sDir) {
            list(, , $sLocalPath) = $this->oShell->isRemotePath($sDir);
            $aResult = $this->oShell->execSSH($sCheckCmd, $sDir);
            $isComposerInstalled = (substr($aResult[0], 0, 1) === '1');
            $isWGetInstalled = (substr($aResult[0], 1, 1) === '1');
            $isCURLInstalled = (substr($aResult[0], 2, 1) === '1');

            // Config:
            if ($isComposerInstalled) {
                $sComposerBin = 'composer';
                $sDownloadCmd = '';
            } elseif ($isWGetInstalled) {
                $sComposerBin = 'php composer.phar';
                $sDownloadCmd = $sWGetCmd;
            } elseif ($isCURLInstalled) {
                $sComposerBin = 'php composer.phar';
                $sDownloadCmd = $sCURLCmd;
            } else {
                $sMsg = 'Composer is not installed, but nor are both wget and curl to install it!';
                throw new \RuntimeException($sMsg);
            }

            // Optional installation:
            if (! empty($sDownloadCmd)) {
                $this->oLogger->info('Install composer:+++');
                $aResult = $this->oShell->execSSH($sDownloadCmd, $sDir);
                $this->oLogger->info(implode("\n", $aResult) . '---');
            }

            // Execution:
            $this->oLogger->info("Execute composer on '$sDir':+++");
            $sCmd = sprintf($sInstallCmdPattern, $sComposerBin, $sLocalPath, $this->aAttValues['options']);
            $aResult = $this->oShell->execSSH($sCmd, $sDir);
            $this->oLogger->info(implode("\n", $aResult) . '---');
        }

        $this->oLogger->info('---');
    }
}
