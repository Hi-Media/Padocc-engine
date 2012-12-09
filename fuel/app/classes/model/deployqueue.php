<?
/**
 * Management of projects to deploy (supervisor)
 * @category Model
 * @package Tony CARON <caron.tony@gmail.com>
 */

namespace Model;
use DB;
class DeployQueue extends \Model {

    /**
     * Return project's statistics
     *
     * @param int $iProjectId Project Ident
     * @return array 
     */
    public static function statByProject($iProjectId)
    {
        $oQuery = DB::select(   
                DB::expr('MONTHNAME(DATE_START) as "MONTHNAME"'),
                DB::expr('YEAR(DATE_START) as "YEAR"'),
                DB::expr('MONTH(DATE_START) as "MONTH"'),
                DB::expr('DATE_FORMAT(DATE_START,"%Y%m") as YM'),
                DB::expr('DATE_START as "DATE"'),
                'NAME',
                'ENVIRONMENT',
                 DB::expr('count(*) as NB'))
            ->from('EDE_DEPLOY_QUEUE')
            ->join('EDE_PROJECT','INNER')->on('EDE_PROJECT.PROJECT_ID', '=', 'EDE_DEPLOY_QUEUE.PROJECT_ID')  
            ->where('EDE_DEPLOY_QUEUE.PROJECT_ID',$iProjectId)
            ->order_by('EDE_DEPLOY_QUEUE.DATE_START', 'asc')
            ->group_by(DB::expr('YEAR(DATE_START)'), DB::expr('MONTH(DATE_START)'), 'EDE_DEPLOY_QUEUE.PROJECT_ID', 'ENVIRONMENT');

        return $oQuery->execute()->as_array();
    }

    /**
     * Return projects statistics
     *
     * @param int $iProjectId Project Ident
     * @return array 
     */
    public static function stat()
    {
        $oQuery = DB::select(   
                DB::expr('MONTHNAME(DATE_START) as "MONTHNAME"'),
                DB::expr('YEAR(DATE_START) as "YEAR"'),
                DB::expr('MONTH(DATE_START) as "MONTH"'),
                DB::expr('DATE_FORMAT(DATE_START,"%Y%m") as YM'),
                DB::expr('DATE_START as "DATE"'),
                'NAME',
                'ENVIRONMENT',
                 DB::expr('count(*) as NB'))
            ->from('EDE_DEPLOY_QUEUE')
            ->join('EDE_PROJECT','INNER')->on('EDE_PROJECT.PROJECT_ID', '=', 'EDE_DEPLOY_QUEUE.PROJECT_ID')  
            ->order_by('EDE_DEPLOY_QUEUE.DATE_START', 'asc')
            ->group_by(DB::expr('YEAR(DATE_START)'), DB::expr('MONTH(DATE_START)'), 'EDE_DEPLOY_QUEUE.PROJECT_ID');

        return $oQuery->execute()->as_array();
    }

    /**
     * Return deployed projects, projects in queue
     *
     * @param int $iProjectId Project Ident
     * @param int $iLimit Sql limit
     * @return array 
     */
    public static function listing($iProjectId = NULL, $iLimit=NULL)
    {
        $oQuery = DB::select('*', 
            DB::expr('EDE_DEPLOY_QUEUE.ENVIRONMENT as "ENVIRONMENT"'),
            DB::expr('EDE_DEPLOY_QUEUE.EXTERNAL_PROPERTY as "EXTERNAL_PROPERTY"'))
            ->from('EDE_DEPLOY_QUEUE')
            ->join('EDE_PROJECT','INNER')->on('EDE_PROJECT.PROJECT_ID', '=', 'EDE_DEPLOY_QUEUE.PROJECT_ID')
            ->join('EDE_USER','INNER')->on('EDE_USER.USER_ID', '=', 'EDE_DEPLOY_QUEUE.INSTIGATOR_ID')
            ->join('EDE_PROJECT_CONFIGURATION','INNER')->on('EDE_DEPLOY_QUEUE.PROJECT_CONFIGURATION_ID', '=', 'EDE_PROJECT_CONFIGURATION.PROJECT_CONFIGURATION_ID');

        if(NULL != $iProjectId)
            $oQuery->where('EDE_DEPLOY_QUEUE.PROJECT_ID', $iProjectId);

        if(NULL != $iLimit)
             $oQuery->limit($iLimit);

          $oQuery->order_by('DATE_START','DESC');
        
        return $oQuery->execute()->as_array();
    }

    /**
     * Add a new deploy in the queue
     *
     * @param int $iProjectId Project Ident
     * @param int $iProjectConfigurationId Selected configuration (xml)
     * @param string $sEnvironment Selected environment
     * @param array $aExternalProperty User property (like branch name)
     * @param int $iInstigatorId UserId
     * @return array 
     */
	public static function add($iProjectId, $iProjectConfigurationId, $sEnvironment, $aExternalProperty, $iInstigatorId)
    {

        $sQuery = DB::insert('EDE_DEPLOY_QUEUE');

        // Set the columns and vales
        $aReturn = $sQuery->set(array(
            'PROJECT_ID' => $iProjectId,
            'PROJECT_CONFIGURATION_ID' => $iProjectConfigurationId,
            'ENVIRONMENT' => $sEnvironment,
            'EXTERNAL_PROPERTY' => json_encode($aExternalProperty),
            'INSTIGATOR_ID' => $iInstigatorId,
            'DATE_INSERT' => DB::expr('NOW()')
        ))->execute();

        return $aReturn[0]; 
    }

    /**
     * Return if a deploy is currently in progress for a given project
     *
     * @param int $iProjectId Project Ident
     * @return bool 
     */
    public static function isInProgress($iProjectId)
    {
        $aResult = DB::query('SELECT PROJECT_ID FROM `EDE_DEPLOY_QUEUE` WHERE PROJECT_ID = :iProjectId AND (STATUS = "WAITING" OR STATUS = "IN_PROGRESS") ')
        ->bind('iProjectId', $iProjectId)->execute();

        return count($aResult) ? true : false;
    }

    /**
     * Find the next project in queue to deploy
     *
     * @return mixed 
     */
    public static function getNextToLaunch()
    {
        $sQuery = DB::query('   SELECT dpq.DEPLOY_QUEUE_ID FROM EDE_DEPLOY_QUEUE dpq
                                JOIN EDE_PROJECT_CONFIGURATION dpc USING(PROJECT_CONFIGURATION_ID)
                                JOIN EDE_PROJECT dp ON dpc.PROJECT_ID = dp.PROJECT_ID
                                WHERE dpq.STATUS = "WAITING" ORDER BY dpq.DATE_INSERT ASC LIMIT 1');
        $aResult = $sQuery->execute()->current();

        return count($aResult) ? $aResult : false;
    }

    /**
     * Tag a queued project as "In PROGRESS"
     *
     * @param int $iDeployQueueId Queue Ident
     * @return bool 
     */
    public static function setInProgess($iDeployQueueId)
    {
        $sExecutionId = date('YmdHis_'). str_pad(rand(0, pow(10, 5)-1), 5, '0', STR_PAD_LEFT);

        $sQuery = DB::query('UPDATE EDE_DEPLOY_QUEUE SET STATUS = "IN_PROGRESS", EXECUTION_ID="'.$sExecutionId.'", DATE_START=NOW() WHERE STATUS = "WAITING" AND DEPLOY_QUEUE_ID="'.$iDeployQueueId.'"');
        $iRowUpdated = $sQuery->execute();

        return !(0 == $iRowUpdated);
    }

    /**
     * Report the final status to an finished deployment
     * (END_OK, END_WARNING, END_ERROR)
     *
     * @param int $iDeployQueueId Queue Ident
     * @param int $iDeployQueueId Queue Ident
     * @return bool 
     */
    public static function setEnd($iDeployQueueId, $sEndStatus)
    {

        $sQuery = DB::query('UPDATE EDE_DEPLOY_QUEUE SET STATUS = "'.$sEndStatus.'", DATE_END=NOW() WHERE STATUS = "IN_PROGRESS" AND DEPLOY_QUEUE_ID="'.$iDeployQueueId.'"');
        $iRowUpdated = $sQuery->execute();

        return !(0 == $iRowUpdated);
    }

    /**
     * Return an queued entry
     *
     * @param int $iDeployQueueId Queue Ident
     * @return mixed 
     */
    public static function get($iDeployQueueId)
    {
        // TODO TEST
        //$iDeployQueueId = 21;
        $sQuery = DB::query('   SELECT dpc.PROJECT_ID, dpc.CONFIGURATION, dpq.ENVIRONMENT, dpq.EXECUTION_ID, dpq.EXTERNAL_PROPERTY, dp.NAME FROM EDE_DEPLOY_QUEUE dpq
                                JOIN EDE_PROJECT_CONFIGURATION dpc USING(PROJECT_CONFIGURATION_ID)
                                JOIN EDE_PROJECT dp ON dpc.PROJECT_ID = dp.PROJECT_ID
                                WHERE dpq.DEPLOY_QUEUE_ID = "'.$iDeployQueueId.'"');
        $aResult = $sQuery->execute()->current();

        return count($aResult) ? $aResult : false;
    }



}
  
