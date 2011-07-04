<?php

interface Mail_Interface {

	/**
	 * @return bool
	 */
	public function send (
			array $aTo, $sSubject, $sMessage, $sFrom,
			$iPriority=AbstractMail::NORMAL_PRIORITY, array $aAttachments=array(),
			$sHtmlCharset=NULL
	);
}
