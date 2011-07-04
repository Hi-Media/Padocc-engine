<?php

interface Properties_Interface {

	public function getProperty ($sPropertyName);

	public function addProperty ($sPropertyName, $sValue);

	public function loadConfigIniFile ($sIniPath);

	public function loadConfigShellFile ($sConfigShellPath);
}
