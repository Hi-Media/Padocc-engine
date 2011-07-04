<?php

class Numbering_Adapter implements Numbering_Interface {

	/**
	 * Compteur.
	 * @var array
	 */
	private $aCounter;

	public function __construct () {
		$this->aCounter = array(0);
	}

	public function getNextCounterValue () {
		$this->aCounter[count($this->aCounter) - 1]++;
		return implode('.', $this->aCounter);
	}

	public function addCounterDivision () {
		$this->aCounter[] = 0;
	}

	public function removeCounterDivision () {
		array_pop($this->aCounter);
	}
}
