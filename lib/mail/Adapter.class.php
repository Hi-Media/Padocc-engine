<?php

include_once(DEPLOYMENT_LIB_DIR . '/SimpleMail/Mail.php');
include_once(DEPLOYMENT_LIB_DIR . '/SimpleMail/AttachmentMail.php');
include_once(DEPLOYMENT_LIB_DIR . '/SimpleMail/Multipart.php');

class Mail_Adapter implements Mail_Interface {

	private function addAttachement (AttachmentMail $oMail, $sFileName) {
		if ($sFileName !== NULL && is_file($sFileName)) {
			$oAttachment = new Multipart($sFileName);
			$oMail->addAttachment($oAttachment);
			return TRUE;
		} else {
			return FALSE;
		}
	}

	/**
	 * @return bool
	 */
	public function send (
			array $aTo, $sSubject, $sMessage, $sFrom,
			$iPriority=AbstractMail::NORMAL_PRIORITY, array $aAttachments=array(),
			$sHtmlCharset=NULL
	) {
		$sFromName = '';
		$sFirstTo = array_shift($aTo);
		$oMail = new AttachmentMail($sFirstTo, $sSubject, $sFromName, $sFrom);

		foreach ($aAttachments as $file) {
			$this->addAttachement($oMail, $file);
		}

		foreach ($aTo as $sTo) {
			$oMail->addTo($sTo);
		}

		if ($sHtmlCharset === NULL) {
			$oMail->setBodyText($sMessage);
		} else {
			$oMail->setBodyHtml($sMessage, $sHtmlCharset);
		}
		$oMail->setPriority($iPriority);

		return $oMail->send();
	}
}
