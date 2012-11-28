<?

namespace Model;
use DB;
use Session;
class User extends \Model {

    public static function add($sFirstName, $sLastname, $sEmail)
    {
        // prepare an insert statement
		$sQuery = DB::insert('DEE_USER');

		// Set the columns and vales
		$aReturn = $sQuery->set(array(
		    'FIRSTNAME' => $sFirstName,
		    'LASTNAME' => $sLastname,
		    'EMAIL' => $sEmail,
		    'DATE_INSERT' => DB::expr('NOW()')
		))->execute();

		return $aReturn[0];
    }

    public static function listing()
    {
    	$aResult = DB::query('SELECT * FROM `DEE_USER` ORDER BY FIRSTNAME ASC')
		->execute();

		return $aResult->as_array();
    }

    public static function isLogged()
    {
        return true === $this->session->userdata('LOGGED');
    }


    public static function getLoggedUserId()
    {
         return Session::get('USER_ID');
    }

    public static function login($sEmail)
    {
    	Session::set('LOGGED', true);
    	Session::set('USER', $sEmail);

        $aResult = DB::query('SELECT * FROM DEE_USER WHERE EMAIL = "'.$sEmail.'"')->execute()->as_array();

        Session::set('USER_ID', $aResult[0]['USER_ID']);

        //$this->session->set_userdata('ACL', $this->getAcl($aData->USER_ID));
    }

   /* public static function update($iProjectId, $sProjectName, $sGroup)
    {
        // prepare an insert statement
		$sQuery = DB::UPDATE('DEE_PROJECT');

		// Set the columns and vales
		$sQuery->set(array(
		    'FIRSTNAME' => $sProjectName,
		    'GROUP' => $sGroup,
		    'DATE_UPDATE' => DB::expr('NOW()')
		))->where('PROJECT_ID', '=', $iProjectId)
    	->execute();
    }*/

	public static function exist($sEmail)
	{

		$aResult = DB::query('SELECT EMAIL FROM `DEE_USER` WHERE EMAIL = :sEmail ')
		->bind('sEmail', $sEmail)->execute();

		return count($aResult) ? true : false;
	}

}
