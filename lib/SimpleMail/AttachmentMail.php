<?php

require_once("AbstractMail.php");

/**
 * Class to send emails with attachments in Text and HTML formats
 *
 * @package			mail
 * @author			gustavo.gomes
 * @copyright		2006
 */
class AttachmentMail extends AbstractMail {

	private $uidBoundary;
	
	private $delimiter;
	
	private $contentTransferEncode = "7bit";

	private $attachment = array();

	public function __construct($to, $subject, $fromName="", $fromMail="") {
		// Create a unique id boundary
		$this->uidBoundary = "_".md5(uniqid(time()));
		$this->delimiterBoundary = "--".$this->uidBoundary.self::CRLF;

		parent::__construct($to, $subject, $fromName, $fromMail);
	}

	public function setBodyHtml($html, $charset="iso-8859-1") {
		$this->contentType = "text/html";
		$this->charset = $charset;
		$this->body = $this->createMessageHeaders("text/html",$charset);
		$this->body .= "<html><head>";
		$this->body .= "<meta http-equiv=Content-Type content=\"text/html; charset=".$charset."\">";
		$this->body .= "</head><body>";
		$this->body .= nl2br($html)."";
		$this->body .= "</body></html>";
		$this->body .= self::CRLF.self::CRLF;
	}
	
	public function setHtml($html, $charset="iso-8859-1") {
		$this->contentType = "text/html";
		$this->charset = $charset;
		$this->body = $this->createMessageHeaders("text/html",$charset);
		$this->body .= nl2br($html)."".self::CRLF.self::CRLF;
	}

	public function setBodyText($text) {
		$this->contentType = "text/plain";
		$this->charset = "";
		$this->body = $this->createMessageHeaders("text/plain");
		$this->body .= $text.self::CRLF.self::CRLF;
	}
	
	protected function createMessageHeaders($contentType, $encode="") {
		$out = $this->delimiterBoundary;
		$out .= parent::createMessageHeaders($contentType, $encode);
		$out .= "Content-Transfer-Encoding: ".$this->contentTransferEncode.self::CRLF.self::CRLF;
		return $out;
	}
	
	public function addAttachment($part) {
		$this->attachment[] = $part;
	}

	public function send() {
		$this->addHeader("MIME-Version: 1.0");
		$this->addHeader("X-Mailer: Attachment Mailer ver. 1.0");
		$this->addHeader("X-Priority: ".$this->priority);
		$this->addHeader("Content-type: multipart/mixed;".self::CRLF.chr(9)." boundary=\"".$this->uidBoundary."\"".self::CRLF);
		$this->addHeader("This is a multi-part message in MIME format.");
		$headers = $this->buildHeaders();
		return mail($this->buildTo(),
					$this->subject,
					$this->body.$this->createAttachmentBlock(),
					$headers);
	}
	
	private function createAttachmentBlock() {
		$block = "";
		
		if (count($this->attachment) > 0) {
			$block .= $this->delimiterBoundary;
			for ($i = 0;$i < count($this->attachment);$i++) {
				$block .= $this->attachment[$i]->getContent();
				$block .= $this->delimiterBoundary;
			}
		}
		return $block;
	}
}
?>