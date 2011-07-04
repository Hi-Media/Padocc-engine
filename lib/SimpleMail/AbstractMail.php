<?php
/**
 * Abstract class used for email sender implementation classes
 *
 * @package			mail
 * @author			gustavo.gomes
 * @copyright		2006
 */
abstract class AbstractMail {
	
	const CRLF = "\n";
	
	const HIGH_PRIORITY = 2;
	const NORMAL_PRIORITY = 3;
	const LOW_PRIORITY = 4;
	
	protected $to = array();
	protected $fromName;
	protected $fromMail;
	protected $subject;
	
	protected $contentType;
	protected $charset;
	
	protected $priority = 2;
	
	protected $headers = array();
	protected $body;

	public function __construct($to, $subject, $fromName="", $fromMail="") {
		$this->to[] = $to;
		$this->subject = $subject;
		$this->fromName = $fromName;
		$this->fromMail = $fromMail;
		$this->init();
	}
	
	private function init() {
		if ($this->fromName != "" && $this->fromMail != "") {
			$this->addHeader("From: ".$this->fromName." <".$this->fromMail.">");
			$this->addHeader("Reply-To: ".$this->fromName." <".$this->fromMail.">");
		} else if ($this->fromName == "" && $this->fromMail != "") {
			$this->addHeader("From: ".$this->fromMail);
			$this->addHeader("Reply-To: ".$this->fromMail);
		}
	}
	
	public function getPriority() {
		return $this->priority;
	}
	
	public function setPriority($priority) {
		$this->priority = $priority;
	}
	
	public function getContentType() {
		return $this->contentType;
	}
	
	public function getCharset() {
		return $this->charset;
	}
	
	public function addTo($mail) {
		$this->to[] = $mail;
	}

	public function addCC($mail) {
		$this->addHeader("CC:".$mail);
	}

	public function addBCC($mail) {
		$this->addHeader("BCC:".$mail);
	}

	public function addHeader($header) {
		$this->headers[] = $header;
	}
	
	protected function buildTo() {
		return implode(", ",$this->to);
	}
	
	protected function buildHeaders() {
		$headers = "";
		if (count($this->headers) > 0) {
			for ($i = 0;$i < count($this->headers)-1;$i++)
				$headers .= $this->headers[$i].self::CRLF;
			$headers .= $this->headers[$i];
		}
		return $headers;
	}
	
	protected function createMessageHeaders($contentType, $encode="") {
		$out = "";
		if ($encode != "")
			$out .= "Content-type:".$contentType."; charset=".$encode;
		else
			$out .= "Content-type:".$contentType.";";
		return $out;
	}

	public static function validateAddress($mailadresse) {
		if (!preg_match("/[a-z0-9_-]+(\.[a-z0-9_-]+)*@([0-9a-z][0-9a-z-]*[0-9a-z]\.)+([a-z]{2,4})/i",$mailadresse))
			return false;
		return true;
	}
	
	public function resetHeaders() {
		$this->headers = array();
		$this->init();
	}
	
	public abstract function setBodyHtml($html, $charset="iso-8859-1");

	public abstract function setHtml($html, $charset="iso-8859-1");

	public abstract function setBodyText($text);
	
	public abstract function send();
	
}
?>