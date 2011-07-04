<?php

require_once("AbstractMail.php");

/**
 * Representation for a attachment
 *
 * @package			mail
 * @author			gustavo.gomes
 * @copyright		2006
 */
class Multipart {
	
	const DISPOSITION_ATTACHMENT = "attachment";
	const DISPOSITION_INLINE = "inline";
	
	private $content;
	
	private $disposition;
	
	public function __construct($file="",$disposition="attachment") {
		if ($file != "")
			$this->setContent($file, $disposition);
	}
	
	public function getContent() {
		return $this->content;
	}
	
	public function getDisposition() {
		return $this->disposition;
	}
	
	/**
	 * Use for $dispoition "attachment" or "inline"
	 * (f.e. example images inside a html mail
	 * 
	 * @param	file	string - nome do arquivo
	 * @param	disposition	string
	 * @return	boolean
	 */
	public function setContent($file, $disposition = "attachment") {
		$this->disposition = $disposition;
		$fileContent = $this->getFileData($file);
		if ($fileContent != "") {
			$filename = basename($file);
			$fileType = mime_content_type($file);
			$chunks = chunk_split(base64_encode($fileContent));
			
			$mailPart = "";
			if ($fileType)
				$mailPart .= "Content-type:".$fileType.";".AbstractMail::CRLF.chr(9)." name=\"".$filename."\"".AbstractMail::CRLF;
			$mailPart .= "Content-length:".filesize($file).AbstractMail::CRLF;
			$mailPart .= "Content-Transfer-Encoding: base64".AbstractMail::CRLF;
			$mailPart .= "Content-Disposition: ".$disposition.";".chr(9)."filename=\"".$filename."\"".AbstractMail::CRLF.AbstractMail::CRLF;
			$mailPart .= $chunks;
			$mailPart .= AbstractMail::CRLF.AbstractMail::CRLF;
			$this->content = $mailPart;
			return true;
		}			
		return false;
	}
	
	private function getFileData($filename) {
		if (file_exists($filename))
			return file_get_contents($filename);
		return "";
	}
}
?>
