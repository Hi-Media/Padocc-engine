<?


namespace Fuel\Tasks;



/**
 * Robot example task
 *
 * Ruthlessly stolen from the beareded Canadian sexy symbol:
 *
 *		Derek Allard: http://derekallard.com/
 *
 * @package		Fuel
 * @version		1.0
 * @author		Phil Sturgeon
 */

use \Model\DeployQueue;
use \Config;
use \Log;




class Test
{

	public static function run()
	{
		for($i=0; $i<2; $i++)
		{
			\Cli::write($i);
			sleep(1);
		}
		file_put_contents('php://stderr', "fuck", E_USER_ERROR);
		exit(22);
		$oException = new \ErrorException("toto", 10, 20);
		throw $oException;
		throw new \RuntimeException ("Unable to create the deploy log directory : ", 30, 2);
	}
}