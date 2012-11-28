<?

namespace Model;
use DB;

class Project extends \Model {

    public static function add($sProjectName, $sGroup, $iOwnerID, $iCreatorId)
    {
        // prepare an insert statement
		$sQuery = DB::insert('DEE_PROJECT');

		// Set the columns and vales
		$aReturn = $sQuery->set(array(
		    'NAME' => $sProjectName,
		    'GROUP' => $sGroup,
		    'OWNER_ID' => $iOwnerID,
		    'CREATOR_ID' => $iCreatorId,
		    'DATE_INSERT' => DB::expr('NOW()')
		))->execute();

		return $aReturn[0];
    }

    public static function update($iProjectId, $sProjectGroup, $iOwnerId)
    {
        // prepare an insert statement
		$sQuery = DB::UPDATE('DEE_PROJECT');

		// Set the columns and vales
		$sQuery->set(array(
		    'OWNER_ID' => $iOwnerId,
		    'GROUP' => $sProjectGroup,
		    'DATE_UPDATE' => DB::expr('NOW()')
		))->where('PROJECT_ID', '=', $iProjectId)
    	->execute();
    }

	public static function exist($sProjectName)
	{

		$aResult = DB::query('SELECT NAME FROM `DEE_PROJECT` WHERE NAME = :sProjectName ')
		->bind('sProjectName', $sProjectName)->execute();

		return count($aResult) ? true : false;
	}

	public static function listing($iProjectId = NULL)
	{
		$sQuery = 'SELECT * FROM `DEE_PROJECT` ';

		if(!NULL == $iProjectId)
		{
			$sQuery = $sQuery.' WHERE PROJECT_ID = :iProjectId';
			return DB::query($sQuery.' ORDER BY NAME ASC')->bind('iProjectId', $iProjectId)->execute()->current();
		}

		return DB::query($sQuery.' ORDER BY NAME ASC')->bind('iProjectId', $iProjectId)->execute()->as_array();
	}

	public static function listingEx($iProjectId = NULL)
	{
		$sQuery = '
			SELECT *, 
			du.FIRSTNAME as OWNER_FIRSTNAME, du.LASTNAME as OWNER_LASTNAME , 
			du2.FIRSTNAME as CREATOR_FIRSTNAME, du2.LASTNAME as CREATOR_LASTNAME  
			FROM DEE_PROJECT dp
			JOIN DEE_USER du ON du.USER_ID = dp.OWNER_ID
			JOIN DEE_USER du2 ON du2.USER_ID = dp.CREATOR_ID
			JOIN DEE_PROJECT_CONFIGURATION dpc on dpc.PROJECT_ID = dp.PROJECT_ID AND dpc.STATUS = "ACTIVE"
			 ';

		$sOrder = ' ORDER BY dp.NAME ASC';

		if(!NULL == $iProjectId)
		{
			$sQuery = $sQuery.' WHERE dp.PROJECT_ID = :iProjectId';
			return DB::query($sQuery.$sOrder)->bind('iProjectId', $iProjectId)->execute()->current();
		}

		return DB::query($sQuery.$sOrder)->execute()->as_array();
	}

	public static function listingByGroup()
	{
		return DB::query('SELECT * FROM `DEE_PROJECT` ORDER BY `GROUP` ASC, NAME ASC; ')
		->execute()->as_array();
	}

	public static function listingGroup()
	{
		return DB::query('SELECT distinct(`GROUP`) FROM `DEE_PROJECT` ')
		->execute()->as_array();
	}

}
