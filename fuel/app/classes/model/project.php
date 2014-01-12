<?
/**
 * Management of projects
 * @category Model
 */

namespace Model;
use DB;

class Project extends \Model {

	/**
     * Add a new project
     *
     * @param string $sProjectName Project name
     * @param string $sProjectGroup Project group
     * @param int $iOwnerID UserId owner
     * @param int $iCreatorId UserId creator
     * @return array
     */
    public static function add($sProjectName, $sProjectGroup, $iOwnerID, $iCreatorId)
    {
        // prepare an insert statement
		$sQuery = DB::insert('EDE_PROJECT');

		// Set the columns and vales
		$aReturn = $sQuery->set(array(
		    'NAME' => $sProjectName,
		    'GROUP' => $sProjectGroup,
		    'OWNER_ID' => $iOwnerID,
		    'CREATOR_ID' => $iCreatorId,
		    'DATE_INSERT' => DB::expr('NOW()')
		))->execute();

		return $aReturn[0];
    }

    /**
     * Update a project
     *
     * @param int $iProjectId Project ident
     * @param string $sGroup Project group
     * @param int $iOwnerID UserId owner
     */
    public static function update($iProjectId, $sProjectGroup, $iOwnerId)
    {
        // prepare an insert statement
		$sQuery = DB::UPDATE('EDE_PROJECT');

		// Set the columns and vales
		$sQuery->set(array(
		    'OWNER_ID' => $iOwnerId,
		    'GROUP' => $sProjectGroup,
		    'DATE_UPDATE' => DB::expr('NOW()')
		))->where('PROJECT_ID', '=', $iProjectId)
    	->execute();
    }

    /**
     * Return if a project's name already exists
     *
     * @param string $sProjectName Project name
     * @param string $sProjectGroup Project group
     * @param int $iOwnerID UserId owner
     * @param int $iCreatorId UserId creator
     * @return bool
     */
	public static function exist($sProjectName)
	{

		$aResult = DB::query('SELECT NAME FROM `EDE_PROJECT` WHERE NAME = :sProjectName ')
		->bind('sProjectName', $sProjectName)->execute();

		return count($aResult) ? true : false;
	}


    /**
     * Return project's datas
     *
     * @param int $iProjectId optional Project Ident
     * @return array of projects data
     */
	public static function listing($iProjectId = null)
	{
		$sQuery = 'SELECT * FROM `EDE_PROJECT` ';

		if(!null == $iProjectId)
		{
			$sQuery = $sQuery.' WHERE PROJECT_ID = :iProjectId';
			return DB::query($sQuery.' ORDER BY NAME ASC')->bind('iProjectId', $iProjectId)->execute()->current();
		}

		return DB::query($sQuery.' ORDER BY NAME ASC')->bind('iProjectId', $iProjectId)->execute()->as_array();
	}

	/**
     * Return extended project's datas
     *
     * @param int $iProjectId optional Project Ident
     * @return array of extended projects data
     */
	public static function listingEx($iProjectId = null)
	{
		$sQuery = '
			SELECT *,
			du.FIRSTNAME as OWNER_FIRSTNAME, du.LASTNAME as OWNER_LASTNAME ,
			du2.FIRSTNAME as CREATOR_FIRSTNAME, du2.LASTNAME as CREATOR_LASTNAME
			FROM EDE_PROJECT dp
			JOIN EDE_USER du ON du.USER_ID = dp.OWNER_ID
			JOIN EDE_USER du2 ON du2.USER_ID = dp.CREATOR_ID
			JOIN EDE_PROJECT_CONFIGURATION dpc on dpc.PROJECT_ID = dp.PROJECT_ID AND dpc.STATUS = "ACTIVE"
			 ';

		$sOrder = ' ORDER BY dp.NAME ASC';

		if(!null == $iProjectId)
		{
			$sQuery = $sQuery.' WHERE dp.PROJECT_ID = :iProjectId';
			return DB::query($sQuery.$sOrder)->bind('iProjectId', $iProjectId)->execute()->current();
		}

		return DB::query($sQuery.$sOrder)->execute()->as_array();
	}


	public static function listingByGroup()
	{
		return DB::query('SELECT * FROM `EDE_PROJECT` ORDER BY `GROUP` ASC, NAME ASC; ')
		->execute()->as_array();
	}

	/**
     * Return all groups
     *
     * @return array of groups names
     */
	public static function listingGroup()
	{
		return DB::query('SELECT distinct(`GROUP`) FROM `EDE_PROJECT` ')
		->execute()->as_array();
	}

}
