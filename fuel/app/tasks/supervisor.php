<?php

namespace Himedia\Padocc;




use \Model\DeployQueue;
use \Config;
use \Log;

define("BUF_SIZ", 1024);        # max buffer size
define("FD_WRITE", 0);        # stdin
define("FD_READ", 1);        # stdout
define("FD_ERR", 2);        # stderr


class Supervisor
{

	public static $sErrorBuffer = "";
	public static $aDescriptor = array(
        0 => array("pipe", "r"),
        1 => array("pipe", "w"),
        2 => array("pipe", "w")
    );

	protected static function folderInit()
	{
 		$sLogPath = \Config::get('dee.paths.deploy_log_info');

 		if(!is_dir($sLogPath))
		if(false === @mkdir($sLogPath, 0777, true))
			   throw new \RuntimeException ("Unable to create the deploy log directory : ".$sLogPath);

	}

	protected static function logDeployInfo($sExecutionId, $sContent)
	{
		$sLogPath = \Config::get('dee.paths.deploy_log_info');
		$sDateTime = date('Y-m-d H:i:s ').substr((string)microtime(), 2, 2).'cs; ';

		if(false === @file_put_contents($sLogPath.$sExecutionId, $sDateTime.$sContent, FILE_APPEND))
			throw new \RuntimeException ("Unable to log into the file : ".$sLogPath.$sExecutionId);

		\Cli::write($sContent);
	}

	protected static function mail($sTo, $sSubject, $sMessage)
	{
		$email = \Email::forge();
		$email->from('ede@twenga.com', 'Ede');
		$email->to($sTo, 'Tony');
		$email->subject($sSubject);
		$email->body($sMessage);

		try
		{
		    $email->send();
		}
		catch(\EmailValidationFailedException $e)
		{
		    // The validation failed
		}
		catch(\EmailSendingFailedException $e)
		{
		    // The driver could not send the email
		}
	}

	protected static function getPhp()
	{
		$pPhp = \Config::get('dee.bin.php');

		if(null === $pPhp || false !== stristr($pPhp, 'auto'))
		{
			$pPhp = exec('which php');

			if(0 == strlen($pPhp))
				throw new \RuntimeException ("Unable to find PHP binary. Update your Dee config.");
		}

		if(false === is_executable($pPhp))
		{
			throw new \RuntimeException ("Unable to execute PHP binary in".$pPhp);
		}

		return $pPhp;
	}

	// TODO
	// On exception: Remettre le déploy en WAITING
	public static function run()
	{

    	Supervisor::folderInit();
    	$pPhp = Supervisor::getPhp();



		$aNextDeploy = DeployQueue::getNextToLaunch();

		if(false !== $aNextDeploy)
		{

			$iDeployQueueId 	= $aNextDeploy['DEPLOY_QUEUE_ID'];
			$iResult 			= DeployQueue::setInProgess($iDeployQueueId);

			if($iResult)
			{
				$aNextDeploy 	= DeployQueue::get($iDeployQueueId);
				$iExecutionId 	= $aNextDeploy['EXECUTION_ID'];

				Log::info("Start a new deployment : ".$iExecutionId);
				Supervisor::mail('tony.caron@twenga.com', 'Deployment in progress', "Start a new deployment : ".$iExecutionId);
				$iSuccess = Supervisor::launchDeploy($pPhp." oil r deploy_core::ede_deploy", $aNextDeploy);

				if($iSuccess)
				{
					DeployQueue::setEnd($iDeployQueueId, "END_OK");
					Supervisor::mail('tony.caron@twenga.com', 'Deployment finished', "Deployment finished");
				}
				else
				{
					DeployQueue::setEnd($iDeployQueueId,"END_ERROR");
					Supervisor::mail('tony.caron@twenga.com', 'Deployment in Error', "Error : ".Supervisor::$sErrorBuffer);
				}

			}

		}
		else
		{
			\Cli::write('Nothing to deploy');
		}

		//TODO TESTI?GG
    	//$sQuery = \DB::query('UPDATE DEE_DEPLOY_QUEUE SET STATUS = "WAITING" where DEPLOY_QUEUE_ID > 0');
    	 //$sQuery->execute();
	}

	protected static function launchDeploy($sCmd, $aDeployInfo)
	{
		// TODO GERER ROLLBACJ
		$aDeployInfo['ROLLBACK_ID']='';
		$sParam = ' --param='.base64_encode(json_encode($aDeployInfo));
		\Cli::write($sCmd.$sParam);
		$oProc = proc_open($sCmd.$sParam, Supervisor::$aDescriptor, $aPipe, null, $_ENV);

		$sBuffer = $sErrbuf = "";
		while (($sBuffer = fgets($aPipe[FD_READ], BUF_SIZ)) != null || ($sErrbuf = fgets($aPipe[FD_ERR], BUF_SIZ)) != null)
		{

			if (strlen($sBuffer))
				Supervisor::onMessage($aDeployInfo, $sBuffer);


        	if (strlen($sErrbuf))
        	{
				Supervisor::$sErrorBuffer.=$sErrbuf;
        		Supervisor::onError($aDeployInfo, $sErrbuf);
        	}


		}

		foreach ($aPipe as $pipe)
        	fclose($pipe);

        proc_close($oProc);

        return (0 == strlen(Supervisor::$sErrorBuffer));


	}

	protected static function onMessage($aDeployInfo, $sBuffer)
	{
		Supervisor::logDeployInfo($aDeployInfo['EXECUTION_ID'], $sBuffer);
	}

	protected static function onError($aDeployInfo, $sBuffer)
	{
		\Cli::error("ERR: " . $sBuffer);
		//Supervisor::mail('tony.caron@twenga.com', 'Deployment in Error', "Error : ".$aDeployInfo['EXECUTION_ID'].$sBuffer);
	}





}
