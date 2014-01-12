<?php

namespace Himedia\Padocc\Task\Extended;

use Himedia\Padocc\AttributeProperties;
use Himedia\Padocc\DIContainer;
use Himedia\Padocc\Task;
use Himedia\Padocc\Task\Base\Project;

/**
 * Met à jour la clé SITE_LOGO des serveurs mmcache si une nouvelle VERSION des LOGO est trouvée dans la base de donnée
 * À inclure dans une tâche env ou target.
 *
 * Exemple :  <updatesitelogo host="${SERVER_CACHE_L1_ALL}" port="11700"/>
 *
 * @author Tony CARON <tony.caron@twenga.com>
 */
class B2CUpdateSiteLogo extends Task
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
     * @param \SimpleXMLElement $oTask Contenu XML de la tâche.
     * @param Project $oProject Super tâche projet.
     * @param DIContainer $oDIContainer Register de services prédéfinis (ShellInterface, ...).
     */
    public function __construct (\SimpleXMLElement $oTask, Project $oProject, DIContainer $oDIContainer)
    {
        parent::__construct($oTask, $oProject, $oDIContainer);
        $this->aAttrProperties = array(
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
        $this->oLogger->info('+++Update SITE_LOGO (DB & MMCACHE)+++');

        $this->aAttValues['host'] = $this->expandPath($this->aAttValues['host']);
        $aMemCache = $aTmp = array();

        $oDb = mysql_connect('mmas1.prod', 'twenga', 'topproducts');
        mysql_select_db('twenga', $oDb);
        if(!$oDb){
            throw new \Exception('MySQL Connection Database Error: ' . mysql_error());
        }

        foreach($this->aAttValues['host'] as $v)
        {
            $aMemCache[$v] = new \Memcache();
            if(!$aMemCache[$v]->connect($v, 11700)){
                throw new \Exception('Mmcache Connection Error: '.$v);
            }
        }

        // Try to find elements that needs to be updated
        $oQuery = mysql_query('SELECT * FROM twenga.SITE_LOGO_TM WHERE VERSION != VERSION_PROD || VERSION_PROD IS null', $oDb);
        while ($row = mysql_fetch_assoc($oQuery)) {
            $aTmp[$row["SITE_ID"]] = serialize(array(array("SITE_ID"=>(string)$row["SITE_ID"], "VERSION"=>(string)$row["VERSION"])));
        }

        $iNbElement = count($aTmp);

        // Update Mmcache servers
        if($iNbElement > 0){

            foreach($aTmp as $k=>$v){

                foreach($aMemCache as $oMmcache)
                {
                    if($k=="3487141")
                    {
                        $iIsSet = $oMmcache->set('5a171601_'.$k, $v, false, 0);
                        $this->oLogger->info("SET: ".'5a171601_'.$k.'=>'.$v);

                    if(!$iIsSet){
                        throw new \Exception('Unable to set data for SITE_ID: ' . $k);
                    }
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

        $this->oLogger->info($iNbElement.' element(s) updated!------');
    }
}
