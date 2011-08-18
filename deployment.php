<?php

// /usr/bin/php -q ~/deployment/deployment.php tests tests_gitexport v4.12.0 `date +'%Y%m%d%H%M%S'` /tmp/deployment.php.xxx.error.log
// tail -fn 500 /tmp/deployment.php.xxx.error.log
// rm -rf /home/gaubry/deployment_backup/* && rm -rf /home/gaubry/test/dest/*
// rm -rf /home/gaubry/deployment_backup/* && rm -rf /home/gaubry/deployment_test/*

// PHPUnit : aller dans /tests, puis : phpunit
// http://gaubry.dev.twenga.com/deployment/report/index.html

// ln -s /home/gaubry/deployment/deployment.php /home/gaubry/supervisor/scripts/php/deployment.php
// CRON * * * * * date +\%s > /home/gaubry/cron_heartbeat.txt

// BOC : deploy/wudEPR3c
// Sur deploy-02 : mysql -u supervisor -ps53eBREw supervisor -h localhost

// alias mprodaai='mysql -u aai -pa19AI03 aai -h maai-01.twenga.com --prompt="maai-01.twenga.com:aai>"'
// alias mdevaai='mysql -u twengadevb2c -ptopprodu devaai1 -h supermicro04 --prompt="supermicro04:devaai1>"'

// cp /home/prod/backup_extranet.twenga.com/inc/config.php /home/httpd/extranet.twenga.com/inc
// cp /home/prod/backup_extranet.twenga.com/fwt/config.php /home/httpd/extranet.twenga.com/fwt

/*
chmod +r supervisor -R
chmod +r deployment -R
chmod 777 supervisor/logs -R
chmod 777 deployment/resources -R
*/

// TODO implémenter rollback
// TODO si fatal error, demander au supervisor de proposer un rollback ?
// TODO description des tâches
// TODO xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:noNamespaceSchemaLocation="biblio10.xsd"
// TODO Que doit retourner Shell_Adapter->exec() : array ou string ?
// TODO test multi rsync
// TODO Le chemin menant au fichier de configuration INI ou CFG est en absolu pour l'instant. Le passer en relatif ?
// TODO imposer des attributs desc et les remonter dans le nom des tâches ?
// TODO détester call cycliques !
// TODO archivage des logs
// TODO connecteur CVS ne gère que le trunk
// TODO remonter heartbeat dans AAI
// TODO lib cURL : see curl_setopt_array
// TODO AAI bien gérer qd second ajout (projet, env) refusé.
// TODO multi rsync n'effectue pas les mkdir en parallèle
// TODO pas de gestion robuste des erreurs qd appel direct (par AAI) de deployment.php. ex: php /home/aai/deployment/deployment.php --getProjectsEnvsList
// TODO valeur par défaut pour les attributs ?
// TODO connecteur YUI : http://git.twenga.com/distribution/wtpn/blobs/master/scripts/js_minifier.php
// TODO Task::_expandPath() => ajouter un paramètre pour signifier qu'il ne doit pas y avoir plus d'une valeur générée ?
// TODO tableau dashboard du nb de déploiement par semaine/projet/env et succès
// TODO tableau new deployment de l'état des env pour le projet en cours, ou tous les projets
// TODO surveiller l'espace disque restant à cause des dépôts cumulés
// TODO tester getFileStatus() avec host distant
// TODO Task::ATTRIBUTE_PATH ?= Task::ATTRIBUTE_FILE | Task::ATTRIBUTE_DIR
// TODO phpdoc properties insensible à la casse
// TODO ne garder que les N dernières releases qd symlinks
// TODO ACL simples AAI ? => user project env, par ex gaubry * *...

/*
 * Features :
 *  - load sh config files : master_synchro.cfg
 *  - XML project config file
 *     - de l'ordre de la minute pour ajouter un nouveau projet
 *     - accès direct à ce en quoi consiste un déploiement pour un projet et un environnement donné
 *  - handling all errors (fatal) and exceptions
 *  - task très concises et intelligentes => XML très petit
 */


include_once(__DIR__ . '/conf/config.inc.php');
include_once(DEPLOYMENT_LIB_DIR . '/ErrorHandler.class.php');
include_once(DEPLOYMENT_LIB_DIR . '/bootstrap.inc.php');

if (function_exists('xdebug_disable')) {
    xdebug_disable();
}

// On supprime le 1er paramètre correspondant au nom du script courant :
$argc--;
array_shift($argv);

if ($argc == 1 && $argv[0] === "--getProjectsEnvsList") {
    new ErrorHandler(false);
    $oDeployment = new Deployment();
    $aProjectsEnvsList = $oDeployment->getProjectsEnvsList();
    echo json_encode($aProjectsEnvsList);
} else if ($argc < 4) {
    file_put_contents('php://stderr', 'Missing parameters! Supplied parameters: ' . print_r($argv, true) . ' Example: /usr/bin/php -q ~/deployment/deployment.php project1 dev 20110518121106 /tmp/deployment.php.20110518121106.error.log', E_USER_ERROR);
    exit(1);
} else {
    $sErrorLogFile = array_pop($argv);
    $sExecutionID = array_pop($argv);
    $sProjectName = array_shift($argv);
    $sEnvName = array_shift($argv);

    new ErrorHandler(false, $sErrorLogFile);
    $oDeployment = new Deployment();
    $oDeployment->run($sProjectName, $sEnvName, $sExecutionID, $argv);
}
