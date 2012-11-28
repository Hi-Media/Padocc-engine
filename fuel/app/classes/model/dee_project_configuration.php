<?

namespace Model;

class Dee_project_configuration extends \Model {

 	public static function getActive(int $iProjectId)
    {
    	$sQuery = DB::query('SELECT * FROM `DEE_PROJECT_CONFIGURATION` WHERE PROJECT_ID = :iProjectId ');
    	return $sQuery->execute();
    }
    public static function add(string $sProjectName, string $sGroup, int $iOwnerID)
    {
        // prepare an insert statement
		$sQuery = DB::insert('DEE_PROJECT');

		// Set the columns and vales
		$sQuery->set(array(
		    'NAME' => $sProjectName,
		    'GROUP' => $sGroup,
		    'OWNER_ID' => $iOwnerID,
		    'DATE_INSERT' => 'NOW()'
		));
    }

    public static function update(int $iProjectId, $sProjectName, string $sGroup)
    {
        // prepare an insert statement
		$sQuery = DB::UPDATE('DEE_PROJECT');

		// Set the columns and vales
		$sQuery->set(array(
		    'NAME' => $sProjectName,
		    'GROUP' => $sGroup,
		    'DATE_UPDATE' => 'NOW()'
		))->where('PROJECT_ID', '=', $iProjectId)
    	->execute();
    }

	public static function exist(string $sProjectName)
	{
		$sQuery = DB::query('SELECT NAME FROM `DEE_PROJECT` WHERE NAME = :sProjectName ');
		$aResult = $sQuery->execute();

		return count($aResult);
	}

}
  `PROJECT_CONFIGURATION_ID` int(11) NOT NULL AUTO_INCREMENT,
  `PROJECT_ID` int(11) NOT NULL,
  `REVISION` int(11) NOT NULL,
  `STATUS` ENUM('ACTIVE', 'NOT_ACTIVE')  DEFAULT 'ACTIVE' NULL,
  `DATE_INSERT` timestamp NULL DEFAULT NULL,
  `DATE_UPDATE` timestamp NULL DEFAULT NULL,
