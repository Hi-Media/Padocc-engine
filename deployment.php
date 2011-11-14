<?php

/**
 * @category TwengaDeploy
 * @package Main
 * @author Geoffroy AUBRY <geoffroy.aubry@twenga.com>
 */

// /usr/bin/php -q ~/deployment/deployment.php tests tests_gitexport v4.12.0 `date +'%Y%m%d%H%M%S'` \
//		/tmp/deployment.php.xxx.error.log
// tail -fn 500 /tmp/deployment.php.xxx.error.log
// rm -rf /home/gaubry/deployment_backup/* && rm -rf /home/gaubry/test/dest/*
// rm -rf /home/gaubry/deployment_backup/* && rm -rf /home/gaubry/deployment_test/*
// chmod +x /home/gaubry/deployment/lib/minifier/jsmin/jsmin

// Exemple de script backup préalable :
/*
servers="batch112 ..."; for server in $servers; do
    ssh $server cp -a /home/prod/twenga/tools/photo_crawler /tmp/photo_crawler.BAK; echo $server OK;
done
 */

// PHPUnit : aller dans /tests, puis : phpunit
// http://gaubry.aa.dev.twenga.local/deployment/report/index.html

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
// TODO pas de gestion robuste des erreurs qd appel direct (par AAI) de deployment.php.
//		ex: php /home/aai/deployment/deployment.php --getProjectsEnvsList
// TODO valeur par défaut pour les attributs ?
// TODO connecteur YUI : http://git.twenga.com/distribution/wtpn/blobs/master/scripts/js_minifier.php
// TODO tableau dashboard du nb de déploiement par semaine/projet/env et succès
// TODO tester getPathStatus() avec host distant

// TODO ne garder que les N dernières releases qd symlinks
// TODO Shell:remove() ne supprime du cache que $sPath et non les sous-répertoires...

/* Combine :
 * 		http://java-applets.org/jsmin-vs-yui-compressor.html
 * 		http://www.electrictoolbox.com/minify-javascript-css-yui-compressor/
 * 		http://www.bloggingdeveloper.com/post/Closure-Compiler-vs-YUI-Compressor-Comparing-the-Javascript
 * 			-Compression-Tools.aspx
 * 		http://scoop.simplyexcited.co.uk/2009/11/24/yui-compressor-vs-google-closure-compiler-for-javascript
 * 			-compression/
 *
 * 		Version C :
 * 		wget http://www.crockford.com/javascript/jsmin.c
 * 		cc jsmin.c -o jsmin

*/

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
    $aProjectsEnvsList = Deployment::getProjectsEnvsList();
    echo json_encode($aProjectsEnvsList);
} else {
    $sErrorLogFile = array_pop($argv); $argc--;
    $sExecutionID = array_pop($argv); $argc--;

    if (preg_match('/--rollback=(\d{14}_\d{5})/', $argv[$argc-1], $aMatches) === 1) {
        $sRollbackID = $aMatches[1];
        $argc--;
        array_pop($argv);
    } else {
        $sRollbackID = '';
    }

    if ($argc < 3) {
        $sMsg = 'Missing parameters! Supplied parameters: ' . print_r($argv, true)
              . ' Example: /usr/bin/php -q ~/deployment/deployment.php project1 dev 20110518121106'
              . ' /tmp/deployment.php.20110518121106.error.log';
        file_put_contents('php://stderr', $sMsg, E_USER_ERROR);
        exit(1);
    } else {
        $sProjectName = array_shift($argv);
        $sEnvName = array_shift($argv);

        new ErrorHandler(false, $sErrorLogFile);
        $oDeployment = new Deployment();
        $oDeployment->run($sProjectName, $sEnvName, $sExecutionID, $argv, $sRollbackID);
    }
}
