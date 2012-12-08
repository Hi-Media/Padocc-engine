<?
/**
 * Management of users
 * @category Model
 * @package Tony CARON <caron.tony@gmail.com>
 */

namespace Model;
use DB;
use Session;
class User extends \Model {

    /**
     * Add a new user
     *
     * @param string $sFirstName First name
     * @param string $sLastname Last name
     * @param string $sEmail User's email
     * @return array 
     */
    public static function add($sFirstName, $sLastname, $sEmail)
    {
        // prepare an insert statement
		$sQuery = DB::insert('EDE_USER');

		// Set the columns and vales
		$aReturn = $sQuery->set(array(
		    'FIRSTNAME' => $sFirstName,
		    'LASTNAME' => $sLastname,
		    'EMAIL' => $sEmail,
		    'DATE_INSERT' => DB::expr('NOW()')
		))->execute();

		return $aReturn[0];
    }

    /**
     * Return all users
     *
     * @return array of users
     */
    public static function listing()
    {
    	$aResult = DB::query('SELECT * FROM `EDE_USER` ORDER BY FIRSTNAME ASC')
		->execute();

		return $aResult->as_array();
    }

    /**
     * Returen if a uers is logged
     *    
     * @return bool 
     */
    public static function isLogged()
    {
        return true === $this->session->userdata('LOGGED');
    }

    /**
     * Return current logged user User_Id
     *
     * @return int 
     */
    public static function getLoggedUserId()
    {
         return Session::get('USER_ID');
    }

    /**
     * Log an user with his mail
     *
     * @param string $sEmail User's mail
     */
    public static function login($sEmail)
    {
    	Session::set('LOGGED', true);
    	Session::set('USER', $sEmail);

        $aResult = DB::query('SELECT * FROM EDE_USER WHERE EMAIL = "'.$sEmail.'"')->execute()->as_array();

        Session::set('USER_ID', $aResult[0]['USER_ID']);

        //$this->session->set_userdata('ACL', $this->getAcl($aData->USER_ID));
    }

   /* public static function update($iProjectId, $sProjectName, $sGroup)
    {
        // prepare an insert statement
		$sQuery = DB::UPDATE('EDE_PROJECT');

		// Set the columns and vales
		$sQuery->set(array(
		    'FIRSTNAME' => $sProjectName,
		    'GROUP' => $sGroup,
		    'DATE_UPDATE' => DB::expr('NOW()')
		))->where('PROJECT_ID', '=', $iProjectId)
    	->execute();
    }*/

    /**
     * Return if an user exists 
     *
     * @param string $sEmail User's mail
     */
	public static function exist($sEmail)
	{

		$aResult = DB::query('SELECT EMAIL FROM `EDE_USER` WHERE EMAIL = :sEmail ')
		->bind('sEmail', $sEmail)->execute();

		return count($aResult) ? true : false;
	}

}
