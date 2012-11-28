<?php
namespace Twenga;
class Ssh
{
	private $oSshRessource;

	public function __construct($sHost, $sLogin, $sPassword)
	{
		$aCallbacks = array('disconnect' => array($this, 'disconnect'));

		$this->oSshRessource = ssh2_connect($sHost, 22, $this->getSshMethod(), $aCallbacks);
		if ($this->oSshRessource === false)
			throw new \Exception('Connection failed');

        $iTest = ssh2_auth_password($this->oSshRessource,$sLogin, $sPassword);
        if ($iTest === false)
            throw new \Exception('Wrong Login/Password to '.$sHost);

	}

	private function getSshMethod()
	{
		return array(
			'kex' => 'diffie-hellman-group1-sha1,diffie-hellman-group14-sha1,'
			.'diffie-hellman-group-exchange-sha1',
			'client_to_server' => array(
			'crypt' => '3des-cbc',
			'comp' => 'none'),
			'hostkey'=>'ssh-rsa',
			'server_to_client' => array(
			'crypt' => 'aes256-cbc,aes192-cbc,aes128-cbc',
			'comp' => 'none'));
	}

	private function disconnect($sReason, $sMessage, $sLanguage)
	{
		 printf("Server disconnected with reason code [%d] and message: %s\n",
         $sReason, $sMessage);
	}

    public function sendFile($sSource, $sDest, $sChmod=0644)
    {
        ssh2_scp_send($this->oSshRessource , $sSource, $sDest, 0644);
    }

	public function exec($sCmd, $sShell="xterm")
	{
		$sReturn = "";

		$sStream = ssh2_exec($this->oSshRessource, $sCmd, $sShell);
		stream_set_blocking($sStream, true);

		while($o=fgets($sStream))
		{
			$sReturn.=$o;

		}

		return substr($sReturn, 0, strlen($sReturn)-1);
	}


	public function multiExec($mCmd, $sShell='sterm', $sReturn='array')
	{
		$aLines = array();

		if(is_array($mCmd))
		{
			$mCmd = implode(' && ', $mCmd);
		}


		$mCmd .= ' | cat';

		$sStream = ssh2_exec($this->oSshRessource, $mCmd, $sShell, array(), -1, -1 );
		stream_set_blocking($sStream, true);

		if($sReturn=='string')
		{
			$sOutput = '';
			while($o=fgets($sStream))
			{
				$sOutput .= $o;
			}
			return $sOutput;
		}
		else
		{
			$aLines = array();
			while($o=fgets($sStream))
			{
				$aLines[] = $o;
			}
		}

		return $aLines;

	}

	public function longExec($sCmd, $sShell="xterm")
	{
		$sReturn = "";
		$sStream = ssh2_exec($this->oSshRessource, $sCmd, $sShell, array(), -1, -1 );
		stream_set_blocking($sStream, true);


		$iTimeStart = microtime(true);
		$aTime = array();
		$i=0;

		$sOutput = '';
		$aLines = array();
		while(true)
		{
			$aLines[] = fgets($sStream);

			$aTime[] = microtime(true) - $iTimeStart;

			//fwrite(" ");

			if(feof($sStream))
			{
				break;
			}
		}

		var_dump($sCmd, $aLines, $aTime);
		die;

		//var_dump($sCmd, $aLines);
		//die;
		return $aLines;
	}

	public static function format2web($sText)
	{
		$sText = htmlentities($sText, ENT_QUOTES, 'UTF-8');
		$sText = preg_replace('!'.chr(27).'\[1;30m!', '</span><span class="shell_internal">', $sText);
		$sText = preg_replace('!'.chr(27).'\[1;31m!', '</span><span class="shell_error">', $sText);
		$sText = preg_replace('!'.chr(27).'\[0;32m!', '</span><span class="shell_ok">', $sText);
		$sText = preg_replace('!'.chr(27).'\[0;33m!', '</span><span class="shell_warning">', $sText);
		$sText = preg_replace('!'.chr(27).'\[1;33m!', '</span><span class="shell_question">', $sText);
		$sText = preg_replace('!'.chr(27).'\[4;33m!', '</span><span class="shell_warning_underlined">', $sText);
		$sText = preg_replace('!'.chr(27).'\[1;34m!', '</span><span class="shell_redmine">', $sText);
		$sText = preg_replace('!'.chr(27).'\[0;36m!', '</span><span class="shell_title">', $sText);
		$sText = preg_replace('!'.chr(27).'\[1;36m!', '</span><span class="shell_title_header">', $sText);
		$sText = preg_replace('!'.chr(27).'\[0;37m!', '</span><span class="shell_detail">', $sText);
		$sText = preg_replace('!'.chr(27).'\[1;37m!', '</span><span class="shell_info">', $sText);
		return $sText;
	}
}
?>
