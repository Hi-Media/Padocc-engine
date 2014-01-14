<?php

namespace Fuel\Tasks;


/**
 * @author Geoffroy AUBRY <gaubry@hi-media.com>
 */
class Ede_deploy
{
    /**
     * Instance de services.
     * @var DIContainer
     */
    private $oDIContainer;

    /**
     * Constructeur.
     */
    public function __construct ()
    {
        $sMyPath =  \Module::exists('deploy_core');

        require_once $sMyPath.'config/padocc-dist.php';
        $this->setAutoloader();
        $oBaseLogger = new Logger_Adapter(LoggerInterface::DEBUG);
        $oLogger = new Logger_IndentedDecorator($oBaseLogger, '   ');
        $oShell = new ShellAdapter($oLogger);

        $this->oDIContainer = new DIContainer();
        $this->oDIContainer
            ->setLogger($oLogger)
            ->setShellAdapter($oShell)
            ->setPropertiesAdapter(new PropertiesAdapter($oShell))
            ->setNumberingAdapter(new NumberingAdapter());
    }

    public function setAutoloader()
    {
        $sMyPath =  \Module::exists('deploy_core');
        set_include_path(
            get_include_path()
            . PATH_SEPARATOR . $sMyPath . ''
            . PATH_SEPARATOR . $sMyPath.'lib' . '/'
        );

        spl_autoload_register(
            function($sClass) {
                $sPath = str_ireplace('fuel\tasks\\', '', $sClass) ;
                $sPath = str_replace('_', '/', $sPath) . '.class.php';
                $iPos = strrpos($sPath, '/');
                $sPath = strtolower(substr($sPath, 0, $iPos)) . substr($sPath, $iPos);

                if(!stream_resolve_include_path($sPath))
                    return \Autoloader::load($sClass);
                else
                {
                    include_once($sPath);
                    return true;
                }

            }
        );
    }

    /**
     * Enregistre les propriétés externes dans l'instance PropertiesInterface.
     *
     * @param array $aExternalProperties tableau indexé des valeurs ordonnées des propriétés externes.
     */
    private function _setExternalProperties (Array $aExternalProperties)
    {
        var_dump($aExternalProperties);
        $oProperties = $this->oDIContainer->getPropertiesAdapter();
        foreach ($aExternalProperties as $k => $sValue) {
            //\Cli::write((array)$sValue);
            $oProperties->setProperty($k, str_replace('&#0160;', ' ', $sValue));
        }
       /* foreach ($aExternalProperties as $i => $sValue) {
            $sKey = ExternalProperty::EXTERNAL_PROPERTY_PREFIX . ($i+1);
            \Cli::write((array)$sValue);
            $oProperties->setProperty($sKey, str_replace('&#0160;', ' ', $sValue));
        }*/

    }

    /**
     * Exécute le déploiement.
     *
     * @param string $sProjectName
     * @param string $sEnvName
     * @param string $sExecutionID au format YYYYMMDDHHMMSS_xxxxx, où x est un chiffre aléatoire,
     * par exemple '20111026142342_07502'
     * @param array $aExternalProperties tableau indexé des valeurs ordonnées des propriétés externes.
     * @param string $sRollbackID identifiant de déploiement sur lequel effectuer un rollback,
     * par exemple '20111026142342_07502'
     */
    public function run ()//$sProjectName, $sEnvName, $sExecutionID, array $aExternalProperties, $sRollbackID)
    {
        new ErrorHandler(false);

        /*\Config::load('ede_deploy_config', true);
        $sDeploymentTmpDir = \Config::get('ede_deploy_config.paths.deployment_tmp_dir');   */


        // Patch temporaire pour Geoffroy
        if (!\Cli::option("param"))
        {
            echo \Cli::color("Geoffroy patched mode\r\n", 'red');

            if(1)
            {
                echo \Cli::color('Usage : php oil r deploy_core::ede_deploy name=rts environment="qa" external_property=\'{"ref":"stable","ref_common":"stable","ref_fuel_common":"stable"}\' configuration="/home/tony/projects/ede/rts.xml"'."\r\n", 'yellow');

              die;
            }

            $aParam['NAME'] = \Cli::option("name");
            $aParam['ENVIRONMENT'] = \Cli::option("environment");
            $aParam['EXECUTION_ID'] = date('YmdHis_'). str_pad(rand(0, pow(10, 5)-1), 5, '0', STR_PAD_LEFT);
            $sRollbackId = \Cli::option("rollback_id");
            $aParam['ROLLBACK_ID'] = isset( $sRollbackId ) ? $sRollbackId : '';
            $sXml = file_get_contents(\Cli::option("configuration"));
            $aParam['CONFIGURATION'] = $sXml;
            $aParam['EXTERNAL_PROPERTY'] = \Cli::option("external_property");
        }
        else
        {
            $sParam = \Cli::option("param");
            $aParam = (array)json_decode(base64_decode($sParam));
        }


        $this->oDIContainer->getPropertiesAdapter()
            ->setProperty('project_name', $aParam['NAME'])
            ->setProperty('environment_name', $aParam['ENVIRONMENT'])
            ->setProperty('execution_id', $aParam['EXECUTION_ID'])
            ->setProperty('configuration', $aParam['CONFIGURATION'])
            ->setProperty('tmpdir', $this->aConfig['dir']['tmp'] . '/deploy_' . $aParam['EXECUTION_ID'])
            ->setProperty('rollback_id', $aParam['ROLLBACK_ID']);

        $this->_setExternalProperties((array)json_decode($aParam['EXTERNAL_PROPERTY']));

        $oProject = new Project($aParam['CONFIGURATION'], $aParam['ENVIRONMENT'], $this->oDIContainer);
        $oLogger = $this->oDIContainer->getLogger();
        $oLogger->log('Check tasks:');
        $oLogger->indent();
        $oProject->setUp();
        $oLogger->unindent();
        $oLogger->log('Execute tasks:');
        $oLogger->indent();
        $oProject->execute();
        $oLogger->unindent();
    }

}
