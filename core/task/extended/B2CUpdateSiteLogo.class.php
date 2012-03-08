<?php

/**
 * Met à jour la clé SITE_LOGO des serveurs mmcache si une nouvelle VERSION des LOGO est trouvée dans la base de donnée 
 * À inclure dans une tâche env ou target.
 *
 * Exemple :  <updatesitelogo host="${SERVER_CACHE_L1_ALL}" port="11700"/>
 *
 * @category TwengaDeploy
 * @package Core
 * @author Tony CARON <tony.caron@twenga.com>
 */
class Task_Extended_B2CUpdateSiteLogo extends Task
{

    /**
     * Retourne le nom du tag XML correspondant à cette tâche dans les config projet.
     *
     * @return string nom du tag XML correspondant à cette tâche dans les config projet.
     */
    public static function getTagName ()
    {
        return 'updatesitelogo';
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
            'host' => AttributeProperties::ALLOW_PARAMETER | AttributeProperties::REQUIRED | AttributeProperties::DIR,
            'port' => AttributeProperties::ALLOW_PARAMETER | AttributeProperties::REQUIRED | AttributeProperties::DIR
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
        $this->_oLogger->log('Update SITE_LOGO (DB & MMCACHE)');
        $this->_oLogger->indent();

        $this->_aAttributes['host'] = $this->_expandPath($this->_aAttributes['host']);
        $aMemCache = $aTmp = array();

        $oDb = mysql_connect('mmas1.prod', 'twenga', 'topproducts');
        mysql_select_db('twenga', $oDb);
        if(!$oDb){
            throw new Exception('MySQL Connection Database Error: ' . mysql_error());
        }

        foreach($this->_aAttributes['host'] as $v)
        {
            $aMemCache[$v] = new Memcache();
            if(!$aMemCache[$v]->connect($v, 11700)){
                throw new Exception('Mmcache Connection Error: '.$v);
            }
        }

        // Try to find elements that needs to be updated
        $oQuery = mysql_query('SELECT * FROM twenga.SITE_LOGO_TM WHERE VERSION != VERSION_PROD || VERSION_PROD IS NULL', $oDb);
        while ($row = mysql_fetch_assoc($oQuery)) {
            $aTmp[$row["SITE_ID"]] = serialize(array(array("SITE_ID"=>(int)$row["SITE_ID"], "VERSION"=>(int)$row["VERSION"])));
        }

        $iNbElement = count($aTmp);

        // Update Mmcache servers
        if($iNbElement > 0){
            
            foreach($aTmp as $k=>$v){

                foreach($aMemCache as $oMmcache)
                {
                    $iIsSet = $oMmcache->set('SITE_LOGO_'.$k, $v, false, 0);
                    if(!$iIsSet){
                        throw new Exception('Unable to set data for SITE_ID: ' . $k);
                    }
                }
            }
            $oQuery = mysql_query('UPDATE SITE_LOGO_TM SET VERSION_PROD = VERSION WHERE SITE_ID IN ('.implode(',', array_keys($aTmp)).')' , $oDb);
        }

        foreach($aMemCache as $oMmcache)
        {
            $oMmcache->close();
        }
        
        mysql_close($oDb);

        $this->_oLogger->log($iNbElement.' element(s) updated!');

        $this->_oLogger->unindent();
        $this->_oLogger->unindent();
    }
}
