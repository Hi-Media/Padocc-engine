<?php

interface Properties_Interface {

	public function getProperty ($sPropertyName);

	public function loadConfigIniFile ($sIniPath);

	public function loadConfigShellFile ($sConfigShellPath);
}
