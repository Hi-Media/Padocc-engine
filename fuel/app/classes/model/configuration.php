<?

namespace Model;
use DB;

class Configuration extends \Model {

	private static function getActive($iProjectId)
	{
		$aResult = DB::query('SELECT * FROM DEE_PROJECT_CONFIGURATION WHERE PROJECT_ID=:iProjectId AND STATUS = "ACTIVE"')
		->bind('iProjectId', $iProjectId)->execute();

		return $aResult;
		//return count($aResult) ? true : false;
	}

    public static function getProjectConfigurations($iProjectId)
    {
        $aResult = DB::query('SELECT * FROM DEE_PROJECT_CONFIGURATION WHERE PROJECT_ID=:iProjectId')
        ->bind('iProjectId', $iProjectId)->execute()->as_array();

        return $aResult;
    }

    public static function getConfiguration($iConfigurationId)
    {
        $aResult = DB::query('SELECT * FROM DEE_PROJECT_CONFIGURATION WHERE PROJECT_CONFIGURATION_ID=:iConfigurationId')
        ->bind('iConfigurationId', $iConfigurationId)->execute()->as_array();

        return $aResult;
    }

    

	// TODO TRANSACTION
    public static function add($iProjectId, $iCreatorId, $sConfiguration)
    {
    	Configuration::checkXmlConfiguration($sConfiguration); 
        $oXml = Configuration::getXml($sConfiguration);  

        // Get Environment
        $aEnvName = array();
        $aExternalProperty = array();
        $oEnv = Configuration::getXmlEnvironment($oXml);
        $oTarget = Configuration::getXmlTarget($oXml);
        foreach($oEnv as $env)
        {
            $aEnvName[] = array('NAME' => (string)$env->attributes()->name);

            // GetExternalProperty
            $oExternalProperty = Configuration::getXmlExternalProperty($env, $oTarget);

            foreach($oExternalProperty as $property)
            {
                $aExternalProperty[(string)$env->attributes()->name][] = array('NAME' => (string)$property->attributes()->name, 'DESCRIPTION' => (string)$property->attributes()->description);
            }
        } 

    	$oActive = Configuration::getActive($iProjectId);

    	$iOldRevision = $oActive->get('REVISION');
    	$iNewRevision = $iOldRevision ? ++$iOldRevision : 1;

        // TODO
        Configuration::deletelast10($iProjectId);
    	Configuration::makeAllUnactive($iProjectId);

        // prepare an insert statement
		$sQuery = DB::insert('DEE_PROJECT_CONFIGURATION');

		// Set the columns and vales
		$sQuery->set(array(
		    'PROJECT_ID' => $iProjectId,
		    'STATUS' => "ACTIVE",
		    'CONFIGURATION' => $sConfiguration,
            'ENVIRONMENT' => json_encode($aEnvName),
            'EXTERNAL_PROPERTY' => json_encode($aExternalProperty),
		    'CREATOR_ID' => $iCreatorId,
		    'REVISION' => $iNewRevision,
		    'DATE_INSERT' => DB::expr('NOW()')
		))->execute();
    }

    public static function listing($iProjectId)
    {
        return DB::query('SELECT * FROM `DEE_PROJECT` WHERE PROJECT_ID=:iProjectId ')
        ->bind('iProjectId', $iProjectId)->execute()->as_array();
    }

    public static function makeAllUnactive($iProjectId)
    {
    	$aResult = DB::query('UPDATE DEE_PROJECT_CONFIGURATION SET STATUS="INACTIVE", DATE_UPDATE = NOW() WHERE PROJECT_ID=:iProjectId AND STATUS="ACTIVE"')
		->bind('iProjectId', $iProjectId)->execute();
    }

    // TODO
    public static function deletelast10($iProjectId)
    {

    }

    public static function xgetActive($iProjectId)
    {
		$oActive = Configuration::getActive($iProjectId);
    }


    public static function checkXmlConfiguration($sConfiguration)
    {

        $sOutput = "";
        // enable error handling
        libxml_use_internal_errors(true);
        $xdoc = new \DOMDocument;
        // load your XML document into DOMDocument object
        $xdoc->loadXML($sConfiguration);
        // validation part - add @ if you don't want to see the validation warnings
        if (!$xdoc->schemaValidate($_SERVER['DOCUMENT_ROOT'].'/xdeploy.xsd'))
        {

            $errors = libxml_get_errors();
            foreach($errors as $error)
            {
            $sOutput.= 'Line: '.$error->line.' ===> '.$error->message.'<br/>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
            }

            throw new \UnexpectedValueException($sOutput);
        }
/*
        return;
        $oXml = Configuration::getXml($sConfiguration);
    	$oEnv = Configuration::getXmlEnvironment($oXml);
        $oTarget = Configuration::getXmlTarget($oXml);

        foreach($oEnv as $env)
        {
            Configuration::getXmlExternalProperty($env, $oTarget);
        }*/
    }

    public static function getXml($sConfiguration)
    {
        try {
            @$oXml = new \SimpleXMLElement($sConfiguration);
        } catch (\Exception $oException) {
            throw new \UnexpectedValueException("Yout Configuration is not xml valid");
        }

        return $oXml;
    }

    public static function getXmlEnvironment($oXml)
    {
        $aEnv = $oXml->xpath("//env");

        if (count($aEnv) === 0) {
            throw new \UnexpectedValueException("No environment found in your configuration!");
        }

        foreach($aEnv as $k=>$env)
        {
            if(!isset($env->attributes()->name) || empty($env->attributes()->name))
                throw new \UnexpectedValueException("Your environment must have a name!");
        }

        return $aEnv;
    }

    public static function getXmlTarget($oXml)
    {
        $aTarget = $oXml->xpath("//target");
        $aReturnTarget = array();

        foreach($aTarget as $k=>$target)
        {
            if(!isset($target->attributes()->name) || empty($target->attributes()->name))
                throw new \UnexpectedValueException("Your environment must have a name!");

            $aReturnTarget[(string)$target->attributes()->name] = $target;        
        }

        return $aReturnTarget;
    }


    public static function getXmlExternalProperty($oEnv, $aTarget)
    {
        $aExternalProperty = array();

        foreach($oEnv->xpath("call") as $target)
        {   
            if(!isset($target->attributes()->target) || empty($target->attributes()->target))
                throw new \UnexpectedValueException("Your call must have a target!");
            
            $aExternalProperty = array_merge($aExternalProperty, Configuration::getXmlExternalProperty($aTarget[(string)$target->attributes()->target], $aTarget));
        }

        foreach($oEnv->xpath("externalproperty") as $property)
        {
            if(!isset($property->attributes()->name) || empty($property->attributes()->name))
                throw new \UnexpectedValueException("Your externalproperty must have a name!");

            if(!isset($property->attributes()->description) || empty($property->attributes()->description))
                throw new \UnexpectedValueException("Your externalproperty must have a description!");

            $aExternalProperty[] = $property;
        }        

        return $aExternalProperty;
    }

   
}
