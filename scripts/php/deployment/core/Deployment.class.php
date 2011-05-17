<?php

include_once(DEPLOYMENT_CORE_DIR . '/Tools.class.php');

class Deployment {

	public function __construct ($sProjectName, $sEnvName) {
		$oEnv = $this->_getEnvironment($sProjectName, $sEnvName);
		/*die;

		$aProject = Tools::simpleXMLToArray($oSXE, false, false, false);
		print_r($aProject);

		$oSelectedEnv = NULL;
		foreach ($aProject['@children'] as $aEnv) {
			if (isset($aEnv['@attributes']['name']) && $aEnv['@attributes']['name'] === $sEnvName) {
				$oSelectedEnv = $oEnv;
				break;
			}
		}*/

		$aTasks = Task::getTaskInstances($oEnv);
		print_r($aTasks);
	}

	private function _getEnvironment ($sProjectName, $sEnvName) {
		$sProjectFilename = DEPLOYMENT_PROJECTS_DIR . '/' . $sProjectName . '.xml';
		if ( ! file_exists($sProjectFilename)) {
			throw new Exception("Project definition not found: $sProjectFilename!");
		}

		$oSXE = new SimpleXMLElement($sProjectFilename, NULL, true);
		if ((string)$oSXE['name'] !== $sProjectName) {
			throw new Exception("Project's attribute name ('" . $oSXE['name'] . "') must be eqal to project filename ('$sProjectName').");
		}

		$oSelectedEnv = NULL;
		foreach ($oSXE->children() as $sTag => $oEnv) {
			if ((string)$sTag === 'env' && (string)$oEnv['name'] === $sEnvName) {
				$oSelectedEnv = $oEnv;
				break;
			}
		}
		if ($oSelectedEnv === NULL) {
			throw new Exception("Environment '$sEnvName' not found in project '$sProjectFilename'!");
		}

		return $oSelectedEnv;
	}
}


		/*$sxe = new SimpleXMLElement($result['body']);
		var_dump($sxe);

		foreach ($sxe->children() as $key => $value) {
			echo "$key => $value\n";
		}

		$failure = $sxe->xpath('/AdCourierAPIResponse/Failed/Message');
		if (count($failure) > 0) {
			echo "Failure: " . (string)$failure[0];
		} else {
			echo "Success";
		}
		echo "\n";*/

/*
function getFormColumnsFromXML ($xml_file) {
	$xml = new SimpleXMLElement($xml_file, NULL, true);
	$form_columns = array();
	$searched_types = array_flip(array('advcheckbox', 'date_text', 'select', 'text', 'textarea'));
	$skipped_types = array_flip(array('button', 'debut_div', 'debut_fieldset', 'description', 'file', 'fin_div', 'fin_fieldset', 'hidden',
		'image', 'load_xml', 'password', 'reset', 'script', 'submit', 'tiret', 'titre', 'titre2'));
	$unknown_types = array();
	foreach ($xml->element as $element) {
		if (isset($element['report']) && $element['report'] == 'false')
			continue;

		$new_column = false;
		$type = (string)$element['type'];
		$name = strtoupper((string)$element['nom']);
		$group = (isset($element['group']) ? (string)$element['group'] : '*');

		if ($group === 'can' || $group === 'can,cli')
			continue;

		if (isset($searched_types[$type])) {
			if ($type == 'select' && isset($element['type_select']) && $element['type_select'] == 'multiple')
				$type = 'select_multiple';
			$join_table = NULL;
			$new_column = true;
		} else if (preg_match('/^select_(.+)$/', $type, $matches) === 1) {
			if (isset($element['type_select']) && $element['type_select'] == 'multiple')
				$type = 'select_multiple_over_join';
			else
				$type = 'select_over_join';
			$join_table = $matches[1];
			$new_column = true;
		} else if ($type == 'advmultiselect') {
			$type = 'select_multiple';
			$join_table = NULL;
			$new_column = true;
		} else if (
				$type == 'hidden' && (
					(isset($element['report']) && $element['report'] == 'true')
					|| preg_match('/^id_(.+)$/', (string)$element['nom'], $matches) === 1
				)
		) {
			$join_table = NULL;
			$new_column = true;
		} else if ( ! isset($skipped_types[$type])) {
			$unknown_types[$type] = true;
		}

		if (
				$new_column && (
					! isset($form_columns[$name]) || (
						$form_columns[$name]['group'] != '*'
						&& ! in_array('rec', explode(',', $form_columns[$name]['group']))
					)
				)
		) {
			if (isset($form_columns[$name]))
				throw new PrestadevRuntimeException("Fichier '$xml_file'."
					. " \$form_columns[$name] déjà affecté ! 1re valeur : " . print_r($form_columns[$name], true)
					. " Seconde valeur : " . print_r($element, true));
			$form_columns[$name] = array(
				'name' => $name,
				'type' => $type,
				'label_id' => (string)$element['lib'],
				'liste_id' => (isset($element['id']) ? (string)$element['id'] : NULL),
				'join_table' => $join_table,
				'group' => $group,
				'filter' => (isset($element['filtre']) ? (string)$element['filtre'] : ''),
			);
		}
	}
//	echo 'Unknown types: ' . print_r(array_keys($unknown_types), true);
	if (count($unknown_types) > 0) {
//		Debug::print_r(array_keys($unknown_types), "Unknown types of $xml_file: ");
		throw new PrestadevRuntimeException("Unknown types in $xml_file: " . print_r(array_keys($unknown_types), true));
	}
//	Debug::print_r($form_columns);
	return $form_columns;
}*/