<?
/**
 * Management of the project's configuration
 * @category Model
 * @package Tony CARON <caron.tony@gmail.com>
 */

namespace Model;
use DB;

class Configuration extends \Model {
    
    /**
     * Retrieve active configuration (last revision)
     *
     * @param int $iProjectId Project ident
     * @return Logger_Interface $this
     */
	public static function getActive($iProjectId)
	{
		$aResult = DB::query('SELECT * FROM EDE_PROJECT_CONFIGURATION WHERE PROJECT_ID=:iProjectId AND STATUS = "ACTIVE"')
		->bind('iProjectId', $iProjectId)->execute();

		return $aResult;
	}

    /**
     * Retrieve all project's configurations
     *
     * @param int $iProjectId Project ident
     * @return array Configuration data
     */
    public static function getProjectConfigurations($iProjectId)
    {
        $aResult = DB::query('SELECT * FROM EDE_PROJECT_CONFIGURATION WHERE PROJECT_ID=:iProjectId')
        ->bind('iProjectId', $iProjectId)->execute()->as_array();

        return $aResult;
    }

    /**
     * Retrieve configuration's data
     *
     * @param int $iConfigurationId Configuration ident
     * @return array Configuration data
     */
    public static function getConfiguration($iConfigurationId)
    {
        $aResult = DB::query('SELECT * FROM EDE_PROJECT_CONFIGURATION WHERE PROJECT_CONFIGURATION_ID=:iConfigurationId')
        ->bind('iConfigurationId', $iConfigurationId)->execute()->as_array();

        return $aResult;
    }

    /**
     * Disable all configurations
     *
     * @param int $iProjectId Project Ident
     */
    public static function makeAllUnactive($iProjectId)
    {
        $aResult = DB::query('UPDATE EDE_PROJECT_CONFIGURATION SET STATUS="INACTIVE", DATE_UPDATE = NOW() WHERE PROJECT_ID=:iProjectId AND STATUS="ACTIVE"')
        ->bind('iProjectId', $iProjectId)->execute();
    }

    // TODO
    public static function deletelast10($iProjectId)
    {

    }

    /**
     * Add a new configuration for a project
     *
     * @param int $iProjectId Project ident
     * @param int $iCreatorId Creator ident
     * @param string Xml configuration file
     * @todo Add transaction
     * @todo Trash old revision => deletelast10
     */
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
		$sQuery = DB::insert('EDE_PROJECT_CONFIGURATION');

		// Set the columns and values
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

    
    /**
     * Check if the XML is validated by the XML Schema
     *
     * @param string $sConfiguration Xml configuration file
     * @throws UnexpectedValueException when the XML is not valided by the schema
     * @todo Rename xdeploy.xsd
     */
    protected static function checkXmlConfiguration($sConfiguration)
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
    }

    /**
     * Return an XmlElement from a string
     *
     * @param string $sConfiguration Xml configuration file
     * @throws UnexpectedValueException when the XML is not valid
     * @return SimpleXMLElement
     */
    protected static function getXml($sConfiguration)
    {
        try {
            @$oXml = new \SimpleXMLElement($sConfiguration);
        } catch (\Exception $oException) {
            throw new \UnexpectedValueException("Yout Configuration is not xml valid");
        }

        return $oXml;
    }

    /**
     * Return all Environement from a SimpleXMLElement
     *
     * @param  SimpleXMLElement $oXml
     * @throws UnexpectedValueException when no environement is found
     * @throws UnexpectedValueException when a name for an environement isn't found
     * @return array of XMLElement environment
     */
    protected static function getXmlEnvironment($oXml)
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

    /**
     * Return all Target from a SimpleXMLElement
     *
     * @param  SimpleXMLElement $oXml
     * @throws UnexpectedValueException when a name for an target isn't found
     * @return array of XMLElement target
     */
    protected static function getXmlTarget($oXml)
    {
        $aTarget = $oXml->xpath("//target");
        $aReturnTarget = array();

        foreach($aTarget as $k=>$target)
        {
            if(!isset($target->attributes()->name) || empty($target->attributes()->name))
                throw new \UnexpectedValueException("Your target must have a name!");

            $aReturnTarget[(string)$target->attributes()->name] = $target;        
        }

        return $aReturnTarget;
    }

    /**
     * Return all ExternalProperty from a environment
     *
     * @param  SimpleXMLElement $oEnv
     * @param  array of SimpleXMLElement $aTarget
     * @throws UnexpectedValueException when a name for an externalproperty isn't found
     * @throws UnexpectedValueException when a description for an externalproperty isn't found
     * @return array of XMLElement 
     */
    protected static function getXmlExternalProperty($oEnv, $aTarget)
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