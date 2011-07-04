<?php

require_once("AbstractMail.php");

/**
 * Class to send simple emails in Text and HTML formats
 *
 * @package			mail
 * @author			gustavo.gomes
 * @copyright		2006
 */
class Mail extends AbstractMail {

	public function __construct($to, $subject, $fromName="", $fromMail="") {
		parent::__construct($to, $subject, $fromName, $fromMail);
	}

	public function setBodyHtml($html, $charset="iso-8859-1") {
		$this->contentType = "text/html";
		$this->charset = $charset;
		$this->body = "";
		$this->body .= "<html><head>";
		$this->body .= "<meta http-equiv=Content-Type content=\"text/html; charset=".$charset."\">";
		$this->body .= "</head><body>";
		$this->body .= nl2br($html)."";
		$this->body .= "</body></html>";
	}
	
	public function setHtml($html, $charset="iso-8859-1") {
		$this->contentType = "text/html";
		$this->charset = $charset;
		$this->body = nl2br($html)."";
	}

	public function setBodyText($text) {
		$this->contentType = "text/plain";
		$this->charset = "";
		$this->body = $text;
	}

	public function send() {
		$this->addHeader("MIME-Version: 1.0");
		$this->addHeader("X-Mailer: RLSP Mailer");
		$this->addHeader("X-Priority: ".$this->priority);
		$this->addHeader($this->createMessageHeaders($this->contentType, $this->charset));
		$headers = $this->buildHeaders();
		return mail($this->buildTo(),
					$this->subject,
					$this->body,
					$headers);
	}
}
?>