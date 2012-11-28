<?
class Message
{
	public static function addAir($sMessage, $sColor='blue')
	{
		$mMessage = Session::get('message');

		$aMessage = is_array($mMessage) ? $mMessage : array();
		$aMessage[] = array("MESSAGE" => $sMessage, "COLOR" => $sColor);
		Session::set('message', $aMessage);
	}

	public static function showAir()
	{
		$mMessage = Session::get('message');

		if(is_array($mMessage))
		{
			foreach ($mMessage as $k => $aMessage):
			?>
			<div class="notification <?=$aMessage['COLOR']?> air">
			<p><?=$aMessage['MESSAGE']?></p>
			<a href="#" class="close">close</a>
			</div>
			<?
			unset($mMessage[$k]);
			endforeach;
			Session::set('message', $mMessage);
		}
	}
}