<?php

namespace Himedia\Padocc\Task\Extended;

use Himedia\Padocc\AttributeProperties;
use Himedia\Padocc\Task\Base\Environment;
use Himedia\Padocc\Task\Base\HTTP;
use Himedia\Padocc\Task\Base\Link;

/**
 * Si tous les attributs booléens sont à true, alors cette tâche qui se substitue à
 * la tâche terminale SwitchSymlink effectue dans l'ordre :
 * - notification sur le téléphone des admins d'une procédure de sortie de serveurs du cluster
 * - sort du cluster les serveurs web concernés par le déploiement
 * - permute les liens symboliques
 * - redémarre Apache
 * - réinitialise les caches Smarty
 * - réintègre les serveurs web dans le cluster
 * - switch les liens symboliques des serveurs statiques
 * - ajoute une ligne dans la table SQL TWENGABUILD
 * - et enfin envoie une seconde notification sur le téléphone des admins pour indiquer la fin du processus
 *
 * Tâche adhoc pour le projet front.
 * À inclure en toute fin de tâche env ou target.
 *
 * Attributs :
 * - 'src' : laisser à vide à moins d'être bien conscient des conséquences
 * - 'target' : laisser à vide à moins d'être bien conscient des conséquences
 * - 'server' : laisser à vide à moins d'être bien conscient des conséquences
 * - 'sysopsnotifications' : envoyer ou non une notification sur le téléphone des admins
 *   (appelle le script /home/prod/twenga/tools/send_nsca_fs3.sh)
 * - 'addSQLTwBuild' : insérer une ligne dans la table SQL TWENGABUILD
 *   (appelle le script Shell /home/prod/twenga/tools/add_twengabuild)
 * - 'clusterRemoving' : retire du cluster avant restart Apache les serveurs web concernés par le déploiement
 *   (appelle le script /home/prod/twenga/tools/wwwcluster)
 * - 'clusterReintegration' : réintègre dans le cluster après restart Apache les serveurs web
 *   (appelle le script /home/prod/twenga/tools/wwwcluster)
 *
 * Exemple :
 * <b2cswitchsymlink
 *     sysopsnotifications="false"
 *     addSQLTwBuild="true"
 *     clusterRemoving="false"
 *     clusterReintegration="false"
 * />
 *
 *
 *
 * Copyright (c) 2014 HiMedia Group
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 *
 * @copyright 2014 HiMedia Group
 * @author Geoffroy Aubry <gaubry@hi-media.com>
 * @author Geoffroy Letournel <gletournel@hi-media.com>
 * @license Apache License, Version 2.0
 */
class B2CSwitchSymlink extends SwitchSymlink
{
    /**
     * Tâche de création d'appel cURL AAI sous-jacente.
     * @var HTTP
     */
    private $oHTTPTask;

    /**
     * {@inheritdoc}
     */
    protected function init()
    {
        parent::init();

        $this->aAttrProperties = array_merge(
            $this->aAttrProperties,
            array(
                'sysopsnotifications' => AttributeProperties::BOOLEAN,
                'addSQLTwBuild' => AttributeProperties::BOOLEAN,
                'clusterRemoving' => AttributeProperties::BOOLEAN,
                'clusterReintegration' => AttributeProperties::BOOLEAN
            )
        );

        $this->oNumbering->addCounterDivision();
        // Parce qu'évidemment il n'y a pas de logique commune :
        $aMappingAAI = array('qa' => 'qa', 'prod' => 'web');
        $sEnv = $this->oProperties->getProperty('environment_name');
        $sAppParameter = (isset($aMappingAAI[$sEnv]) ? $aMappingAAI[$sEnv] : $sEnv);
        $sURL = 'http://aai.twenga.com/push.php?server=${WEB_SERVERS}&amp;app=' . $sAppParameter;
        $aAttributes = array('url' => $sURL);
        $this->oHTTPTask = HTTP::getNewInstance($aAttributes, $this->oProject, $this->oDIContainer);
        $this->oNumbering->removeCounterDivision();
    }

    /**
     * {@inheritdoc}
     * @codeCoverageIgnore
     */
    public static function getTagName ()
    {
        return 'b2cswitchsymlink';
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

        $aAttrToInit = array('sysopsnotifications', 'addSQLTwBuild', 'clusterRemoving', 'clusterReintegration');
        foreach ($aAttrToInit as $sAttrName) {
            if (! isset($this->aAttValues[$sAttrName])) {
                $this->aAttValues[$sAttrName] = 'false';
            }
        }
    }

    /**
     * Phase de pré-traitements de l'exécution de la tâche.
     * Elle devrait systématiquement commencer par "parent::preExecute();".
     * Appelé par execute().
     * @see execute()
     */
    protected function preExecute ()
    {
        parent::preExecute();

        $this->getLogger()->info('+++');
        if ($this->aAttValues['sysopsnotifications'] == 'true') {
            $sEnv = $this->oProperties->getProperty('environment_name');
            $sID = $this->oProperties->getProperty('execution_id');
            $this->sendSysopsNotification('MEP-activation', 2, "Deploy to $sEnv servers (#$sID) is switching...");
        }
        $this->getLogger()->info('---');
    }

    /**
     * Phase de traitements centraux de l'exécution de la tâche.
     * Elle devrait systématiquement commencer par "parent::centralExecute();".
     * Appelé par execute().
     * @see execute()
     */
    protected function centralExecute ()
    {
        $this->getLogger()->info('+++');
        if ($this->oProperties->getProperty('with_symlinks') === 'true') {
            if ($this->oProperties->getProperty(Environment::SERVERS_CONCERNED_WITH_BASE_DIR) == '') {
                $this->getLogger()->info('No release found.');
            } else {
                $this->oProperties->setProperty('with_symlinks', 'false');
                $this->checkTargets();

                // Pour chaque serveur :
                $aServers = $this->processPath('${WEB_SERVERS}');
                foreach ($aServers as $sServer) {
                    $this->getLogger()->info("Switch '$sServer' server:+++");

                    if ($this->aAttValues['clusterRemoving'] == 'true') {
                        $this->setCluster($sServer, false);
                    }

                    // Switch du lien symbolique :
                    $aAttributes = array(
                        'src' => $this->aAttValues['src'],
                        'target' => $this->aAttValues['target'],
                        'server' => $sServer
                    );
                    $oLinkTask = Link::getNewInstance($aAttributes, $this->oProject, $this->oDIContainer);
                    $oLinkTask->setUp();
                    $oLinkTask->execute();

                    $this->restartServerApache($sServer);
                    $this->clearServerSmartyCaches($sServer);
                    if ($this->aAttValues['clusterReintegration'] == 'true') {
                        $this->setCluster($sServer, true);
                    }
                    $this->getLogger()->info('---');
                }

                // Switch des symlinks
                // des éventuels serveurs de Task_Base_Environment::SERVERS_CONCERNED_WITH_BASE_DIR
                // non inclus dans ${WEB_SERVERS}, comme les schedulers par exemple...
                $aAllServers = $this->expandPath($this->aAttValues['server']);
                $aDiff = array_diff($aAllServers, $aServers);
                if (count($aDiff) > 0) {
                    $this->getLogger()->info('Switch other servers: ' . implode(', ', $aDiff) . '.+++');
                    $this->oProperties->setProperty('remaining_servers_to_switch', implode(' ', $aDiff));
                    $aAttributes = array(
                        'src' => $this->aAttValues['src'],
                        'target' => $this->aAttValues['target'],
                        'server' => '${remaining_servers_to_switch}'
                    );
                    $oLinkTask = Link::getNewInstance($aAttributes, $this->oProject, $this->oDIContainer);
                    $oLinkTask->setUp();
                    $oLinkTask->execute();
                    $this->getLogger()->info('---');
                }

                $this->oProperties->setProperty('with_symlinks', 'true');
            }
        } else {
            $this->getLogger()->info("Mode 'withsymlinks' is off: nothing to do.");
        }
        $this->getLogger()->info('---');
    }

    /**
     * Phase de post-traitements de l'exécution de la tâche.
     * Elle devrait systématiquement finir par "parent::postExecute();".
     * Appelé par execute().
     * @see execute()
     */
    protected function postExecute ()
    {
        $sEnv = $this->oProperties->getProperty('environment_name');
        $sRollbackID = $this->oProperties->getProperty('rollback_id');
        $sID = $sRollbackID !== '' ? $sRollbackID : $this->oProperties->getProperty('execution_id');
        $this->getLogger()->info('+++');

        if ($this->aAttValues['addSQLTwBuild'] == 'true') {
            $this->addSQLTwBuild($sID, $sEnv);
        }
        if ($this->aAttValues['sysopsnotifications'] == 'true') {
            $this->sendSysopsNotification('MEP-activation', 0, "Deploy to $sEnv servers (#$sID) finished.");
        }
        $this->oHTTPTask->execute();

        $this->getLogger()->info('---');
        parent::postExecute();
    }

    /**
     * Envoie une notification sur le téléphone des admins.
     *
     * @param string $sService catégorie
     * @param int $iStatus 0 ok, 1 warning, 2 critical
     * @param string $sMessage
     */
    private function sendSysopsNotification ($sService, $iStatus, $sMessage)
    {
        $this->getLogger()->info("Send notification to Sysops: '$sMessage'+++");
        $sCmd = "/home/prod/twenga/tools/send_nsca_fs3.sh $sService $iStatus \"$sMessage\"";
        $this->oShell->execSSH($sCmd, 'fs3:foo');
        $this->getLogger()->info('---');
    }

    /**
     * Insère le Twenga build number dans la table SQL centralisée 'TWENGABUILD'.
     *
     * @param string $sID Twenga build number
     * @param string $sEnv Environnement
     * @throws \DomainException quand environnement non capturé
     */
    private function addSQLTwBuild ($sID, $sEnv)
    {
        $aTypes = array('qa' => 'Q', 'bct' => 'B', 'internal' => 'I', 'preprod' => 'X', 'prod' => 'P');
        if (! isset($aTypes[$sEnv])) {
            throw new \DomainException("Environment not handled: '$sEnv'!");
        }
        $this->getLogger()->info("Add Twenga build number $sID into 'TWENGABUILD' SQL table.+++");
        $sCmd = "/home/prod/twenga/tools/add_twengabuild $sID " . $aTypes[$sEnv];
        $this->oShell->execSSH($sCmd, 'fs3:foo');
        $this->getLogger()->info('---');
    }

    /**
     * Redémarre le serveur Apache du serveur spécifié.
     *
     * @param string $sServer au format [user@]servername_or_ip
     */
    private function restartServerApache ($sServer)
    {
        $this->getLogger()->info("Restart Apache webserver '$sServer'.+++");
        $sToExec = $this->processSimplePath($sServer . ':/root/apache_restart');
        $aResult = $this->oShell->execSSH('sudo %s', $sToExec);
        $this->getLogger()->info(implode("\n", $aResult) . '---');
    }

    /**
     * Réinitialise les caches Smarty du serveur spécifié.
     *
     * @param string $sServer au format [user@]servername_or_ip
     */
    private function clearServerSmartyCaches ($sServer)
    {
        $this->getLogger()->info("Clear Smarty caches of server '$sServer':+++");

        $sCmd = "/home/prod/twenga/tools/clear_cache $sServer smarty";
        if (strcasecmp(strrchr($sServer, '.'), '.us1') === 0) {
            $sCmd = 'export FORCE_TWENGA_DC=US && ' . $sCmd . ' && export FORCE_TWENGA_DC=\'\'';
        }
        $aResult = $this->oShell->execSSH($sCmd, 'fs3:foo');
        $this->getLogger()->info(strip_tags(implode("\n", $aResult)) . '---');
    }

    /**
     * Sors ou réintègre le serveur spécifié du cluster.
     *
     * @param string $sServer au format [user@]servername_or_ip
     * @param bool $bStatus true pour réintégrer, false pour sortir.
     * @throws \Exception
     * @throws \RuntimeException
     */
    private function setCluster ($sServer, $bStatus)
    {
        $aMsgs = ($bStatus ? array('Reintegrate', 'into', '-e') : array('Remove', 'from', '-d'));

        if (preg_match('/wwwtest/i', $sServer) !== 1) {
            $this->getLogger()->info($aMsgs[0] . " '$sServer' server $aMsgs[1] the cluster.+++");
            $sCmd = "/home/prod/twenga/tools/wwwcluster -s $sServer $aMsgs[2]";
            try {
                $aResult = $this->oShell->exec($sCmd);
                $sResult = implode("\n", $aResult);
                if ($sResult != '') {
                    $this->getLogger()->info($sResult);
                }
            } catch (\RuntimeException $oException) {
                if ($oException->getCode() == 2) {
                    $sResult = '[WARNING] ' . $oException->getMessage();
                    $this->getLogger()->warning($sResult);
                } else {
                    throw $oException;
                }
            }
            $this->getLogger()->info('---');
        } else {
            $this->getLogger()->info(" '$sServer' server is not handled by the cluster.");
        }
    }

    /**
     * Prépare la tâche avant exécution : vérifications basiques, analyse des serveurs concernés...
     */
    public function setUp ()
    {
        parent::setUp();
        $this->getLogger()->info('+++');
        $this->oHTTPTask->setUp();
        $this->getLogger()->info('---');
    }
}
