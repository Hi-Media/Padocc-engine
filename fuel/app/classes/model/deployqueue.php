<?

namespace Model;
use DB;
class DeployQueue extends \Model {

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
            ->from('DEE_DEPLOY_QUEUE')
            ->join('DEE_PROJECT','INNER')->on('DEE_PROJECT.PROJECT_ID', '=', 'DEE_DEPLOY_QUEUE.PROJECT_ID')  
            ->where('DEE_DEPLOY_QUEUE.PROJECT_ID',$iProjectId)
            ->order_by('DEE_DEPLOY_QUEUE.DATE_START', 'asc')
            ->group_by(DB::expr('YEAR(DATE_START)'), DB::expr('MONTH(DATE_START)'), 'DEE_DEPLOY_QUEUE.PROJECT_ID', 'ENVIRONMENT');

        return $oQuery->execute()->as_array();
    }

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
            ->from('DEE_DEPLOY_QUEUE')
            ->join('DEE_PROJECT','INNER')->on('DEE_PROJECT.PROJECT_ID', '=', 'DEE_DEPLOY_QUEUE.PROJECT_ID')  
            ->order_by('DEE_DEPLOY_QUEUE.DATE_START', 'asc')
            ->group_by(DB::expr('YEAR(DATE_START)'), DB::expr('MONTH(DATE_START)'), 'DEE_DEPLOY_QUEUE.PROJECT_ID');



        return $oQuery->execute()->as_array();
    }

    public static function listing($iProjectId = NULL, $iLimit=NULL)
    {
        $oQuery = DB::select('*', 
            DB::expr('DEE_DEPLOY_QUEUE.ENVIRONMENT as "ENVIRONMENT"'),
            DB::expr('DEE_DEPLOY_QUEUE.EXTERNAL_PROPERTY as "EXTERNAL_PROPERTY"'))
            ->from('DEE_DEPLOY_QUEUE')
            ->join('DEE_PROJECT','INNER')->on('DEE_PROJECT.PROJECT_ID', '=', 'DEE_DEPLOY_QUEUE.PROJECT_ID')
            ->join('DEE_USER','INNER')->on('DEE_USER.USER_ID', '=', 'DEE_DEPLOY_QUEUE.INSTIGATOR_ID')
            ->join('DEE_PROJECT_CONFIGURATION','INNER')->on('DEE_DEPLOY_QUEUE.PROJECT_CONFIGURATION_ID', '=', 'DEE_PROJECT_CONFIGURATION.PROJECT_CONFIGURATION_ID');

        if(NULL != $iProjectId)
            $oQuery->where('DEE_DEPLOY_QUEUE.PROJECT_ID', $iProjectId);

        if(NULL != $iLimit)
             $oQuery->limit($iLimit);

          $oQuery->order_by('DATE_START','DESC');
        
        return $oQuery->execute()->as_array();
    }

	public static function add($iProjectId, $iProjectConfigurationId, $sEnvironment, $aExternalProperty, $iInstigatorId)
    {

        $sQuery = DB::insert('DEE_DEPLOY_QUEUE');

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

    public static function isInProgress($iProjectId)
    {
        $aResult = DB::query('SELECT PROJECT_ID FROM `DEE_DEPLOY_QUEUE` WHERE PROJECT_ID = :iProjectId AND (STATUS = "WAITING" OR STATUS = "IN_PROGRESS") ')
        ->bind('iProjectId', $iProjectId)->execute();

        return count($aResult) ? true : false;
    }

    public static function getNextToLaunch()
    {
        $sQuery = DB::query('   SELECT dpq.DEPLOY_QUEUE_ID FROM DEE_DEPLOY_QUEUE dpq
                                JOIN DEE_PROJECT_CONFIGURATION dpc USING(PROJECT_CONFIGURATION_ID)
                                JOIN DEE_PROJECT dp ON dpc.PROJECT_ID = dp.PROJECT_ID
                                WHERE dpq.STATUS = "WAITING" ORDER BY dpq.DATE_INSERT ASC LIMIT 1');
        $aResult = $sQuery->execute()->current();

        return count($aResult) ? $aResult : false;
    }

    public static function setInProgess($iDeployQueueId)
    {
        $sExecutionId = date('YmdHis_'). str_pad(rand(0, pow(10, 5)-1), 5, '0', STR_PAD_LEFT);

        $sQuery = DB::query('UPDATE DEE_DEPLOY_QUEUE SET STATUS = "IN_PROGRESS", EXECUTION_ID="'.$sExecutionId.'", DATE_START=NOW() WHERE STATUS = "WAITING" AND DEPLOY_QUEUE_ID="'.$iDeployQueueId.'"');
        $iRowUpdated = $sQuery->execute();

        return !(0 == $iRowUpdated);
    }

    public static function setEnd($iDeployQueueId, $sEndStatus)
    {

        $sQuery = DB::query('UPDATE DEE_DEPLOY_QUEUE SET STATUS = "'.$sEndStatus.'", DATE_END=NOW() WHERE STATUS = "IN_PROGRESS" AND DEPLOY_QUEUE_ID="'.$iDeployQueueId.'"');
        $iRowUpdated = $sQuery->execute();

        return !(0 == $iRowUpdated);
    }

    public static function get($iDeployQueueId)
    {
        // TODO TEST
        //$iDeployQueueId = 21;
        $sQuery = DB::query('   SELECT dpc.PROJECT_ID, dpc.CONFIGURATION, dpq.ENVIRONMENT, dpq.EXECUTION_ID, dpq.EXTERNAL_PROPERTY, dp.NAME FROM DEE_DEPLOY_QUEUE dpq
                                JOIN DEE_PROJECT_CONFIGURATION dpc USING(PROJECT_CONFIGURATION_ID)
                                JOIN DEE_PROJECT dp ON dpc.PROJECT_ID = dp.PROJECT_ID
                                WHERE dpq.DEPLOY_QUEUE_ID = "'.$iDeployQueueId.'"');
        $aResult = $sQuery->execute()->current();

        return count($aResult) ? $aResult : false;
    }



}
  
