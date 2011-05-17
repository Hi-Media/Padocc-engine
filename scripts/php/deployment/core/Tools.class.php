<?php

class Tools {

	private function __construct () {}

	/**
	 * Returns narrative portion of specified docblock comment.
	 *
	 * @param string $doc_comment The docblock comment text.
	 * @return string narrative portion
	 */
	public static function getNarrativePortion ($doc_comment) {
		$block = str_replace("\r\n", "\n", $doc_comment);	// fix line-endings from windows
		$block = str_replace("\r", "\n", $block); // fix line-endings from mac os 9 and previous
		$block = preg_replace('/^\s*\/\*\*\s*$/m', '', $block);	// remove the leading comment indicator (slash-star-star)
		$block = preg_replace('/^\s*\*\/\s*$/m', '', $block);	// remove the trailing comment indicator (star-slash)
		$block = preg_replace('/^\s*\*( )?/m', '', $block);	// remove the star (and optionally one space) leading each line
		$block = "\n" . trim($block) . "\n";	// wrap with exactly one beginning and ending newline

		// find narrative and technical portions
		$pos = strpos($block, "\n@");
		if ($pos === false) {
			// apparently no technical section
			$narr = $block;
		} else {
			// there appears to be a technical section
			$narr = substr($block, 0, $pos);
		}

		return trim($narr);
	}

	/**
	 * Récursivement, transforme un objet de type stdClass et ses propriétés
	 * en tableau associatif.
	 *
	 * @param stdClass $object
	 * @return array
	 */
	public static function stdClass2Array (stdClass $object) {
		$array = (array)$object;
		foreach ($array as $key => $value) {
			if ($value instanceof stdClass) {
				$array[$key] = self::stdClass2Array($value);
			}
		}
		return $array;
	}

	/**
	 * Simplifie la trace générée par un debug_backtrace() en y éliminant le contenu des objets
	 * ainsi que le contenu des tableaux situés à plus de 3 niveaux de récursion du sommet.
	 *
	 * @param array $trace
	 * @param int $recursion
	 * @return array trace allégée
	 */
	public static function reduceDebugBackTrace (array &$trace, $recursion=0) {
		$new_trace = array();
		foreach ($trace as $key => $val) {
			if (is_array($val)) {
				if ($recursion >= 3) {
					$new_trace[$key] = 'Array(...)';
				} else {
					$new_trace[$key] = self::reduceDebugBackTrace($trace[$key], $recursion+1);
				}
			} else if (is_object($val)) {
				$new_trace[$key] = get_class($val) . ' Object...';
			} /*else if (is_string($val) && strlen($val) > 200) {
				//$trace[$key] .= '...';
				$new_trace[$key] .= '...';
			}*/
			else
				$new_trace[$key] = $val;
		}
		return $new_trace;
	}

	/**
	 * Recursive version of glob.
	 *
	 * @param string $path Directory to start with.
	 * @param mixed $patterns Pattern to glob for, or an array of patterns.
	 * @return array containing all pattern-matched files.
	 */
	public static function getFiles ($path, $patterns) {
		if ($path{strlen($path) - 1} == '/')
			$path = substr($path, 0, -1);
		$path = escapeshellcmd($path);

		// Get the list of all matching files currently in the directory.
		if ( ! is_array($patterns))
			$patterns = array($patterns);
		$files = array();
		foreach ($patterns as $pattern) {
			$files = array_merge($files, glob($path.'/'.$pattern, 0));
		}

		// Then get a list of all directories in this directory, and
		// run ourselves on the resulting array.  This is the
		// recursion step, which will not execute if there are no
		// directories.
		$paths = glob($path.'/*', GLOB_ONLYDIR|GLOB_NOSORT);
		foreach ($paths as $path) {
			$sub_files = self::getFiles($path, $patterns, 0);
			$files = array_merge($files, $sub_files);
		}

		return $files;
	}

	/**
	 * array_merge_recursive does indeed merge arrays, but it converts values with duplicate
	 * keys to arrays rather than overwriting the value in the first array with the duplicate
	 * value in the second array, as array_merge does. I.e., with array_merge_recursive,
	 * this happens (documented behavior):
	 *
	 * array_merge_recursive(array('key' => 'org value'), array('key' => 'new value'));
	 *     => array('key' => array('org value', 'new value'));
	 *
	 * array_merge_recursive_distinct does not change the datatypes of the values in the arrays.
	 * Matching keys' values in the second array overwrite those in the first array, as is the
	 * case with array_merge, i.e.:
	 *
	 * array_merge_recursive_distinct(array('key' => 'org value'), array('key' => 'new value'));
	 *     => array('key' => array('new value'));
	 *
	 * EVO sur sous-tableaux indexés :
	 *   Avant :
	 *     array_merge_recursive_distinct(array('a', 'b'), array('c')) => array('c', 'b')
	 *   Maintenant :
	 *     => array('c')
	 *
	 * @param array $array1
	 * @param array $array2
	 * @return array
	 * @author Daniel <daniel (at) danielsmedegaardbuus (dot) dk>
	 * @author Gabriel Sobrinho <gabriel (dot) sobrinho (at) gmail (dot) com>
	 * @author modify by Geoffroy Aubry
	 */
	public static function array_merge_recursive_distinct (array $array1, array $array2) {
		$merged = $array1;
		foreach ($array2 as $key => &$value) {
			if (is_array($value) && isset($merged[$key]) && is_array($merged[$key])) {
				if (self::is_associative_array($merged[$key])) {
					$merged[$key] = self::array_merge_recursive_distinct($merged[$key], $value);
				} else {
					$merged[$key] = $value;
				}
			} else {
				$merged[$key] = $value;
			}
		}
		return $merged;
	}

	/**
	 * Retourne true ssi le tableau spécifié est associatif.
	 * Retourne false si le tableau est vide.
	 * http://stackoverflow.com/questions/173400/php-arrays-a-good-way-to-check-if-an-array-is-associative-or-sequential
	 *
	 * @param array $a
	 * @return bool true ssi le tableau est associatif
	 */
	public static function is_associative_array (array $a) {
		foreach (array_keys($a) as $key) {
			if ( ! is_int($key)) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Converts a simpleXML element into an array. Preserves attributes and everything.
	 * You can choose to get your elements either flattened, or stored in a custom index that
	 * you define.
	 * For example, for a given element
	 * <field name="someName" type="someType"/>
	 * if you choose to flatten attributes, you would get:
	 * $array['field']['name'] = 'someName';
	 * $array['field']['type'] = 'someType';
	 * If you choose not to flatten, you get:
	 * $array['field']['@attributes']['name'] = 'someName';
	 * _____________________________________
	 * Repeating fields are stored in indexed arrays. so for a markup such as:
	 * <parent>
	 * <child>a</child>
	 * <child>b</child>
	 * <child>c</child>
	 * </parent>
	 * you array would be:
	 * $array['parent']['child'][0] = 'a';
	 * $array['parent']['child'][1] = 'b';
	 * ...And so on.
	 *
	 * @param simpleXMLElement $xml the XML to convert
	 * @param boolean $flattenValues Choose wether to flatten values
	 *   or to set them under a particular index. Defaults to true;
	 * @param boolean $flattenAttributes Choose wether to flatten attributes
	 *   or to set them under a particular index. Defaults to true;
	 * @param boolean $flattenChildren Choose wether to flatten children
	 *   or to set them under a particular index. Defaults to true;
	 * @param string $valueKey index for values, in case $flattenValues was set to
	 *   false. Defaults to "@value"
	 * @param string $attributesKey index for attributes, in case $flattenAttributes was set to
	 *   false. Defaults to "@attributes"
	 * @param string $childrenKey index for children, in case $flattenChildren was set to
	 *   false. Defaults to "@children"
	 * @return array the resulting array.
	 * @author xananax@yelostudio.com
	 * @author modify by Geoffroy Aubry
	 */
	public static function simpleXMLToArray(
			$xml,
			$flattenValues=true,
			$flattenAttributes=true,
			$flattenChildren=true,
			$valueKey='@value',
			$attributesKey='@attributes',
			$childrenKey='@children'
	){
		$return = array();
		if(!($xml instanceof SimpleXMLElement)){return $return;}
		$name = $xml->getName();
		$_value = trim((string)$xml);
		if(strlen($_value)==0){$_value = null;};

		if($_value!==null){
			if(!$flattenValues){$return[$valueKey] = $_value;}
			else{$return = $_value;}
		}

		$children = array();
		$first = true;
		foreach($xml->children() as $elementName => $child){
			$value = self::simpleXMLToArray($child, $flattenValues, $flattenAttributes, $flattenChildren, $valueKey, $attributesKey, $childrenKey);
			if(isset($children[$elementName])){
				if($first){
					$temp = $children[$elementName];
					unset($children[$elementName]);
					$children[$elementName][] = $temp;
					$first=false;
				}
				$children[$elementName][] = $value;
			} else {
				$children[$elementName] = $value;
			}
		}
		if(count($children)>0){
			if(!$flattenChildren){$return[$childrenKey] = $children;}
			else{$return = array_merge($return,$children);}
		}

		$attributes = array();
		foreach($xml->attributes() as $name=>$value){
			$attributes[$name] = trim($value);
		}
		if(count($attributes)>0){
			if(!$flattenAttributes){
				$return[$attributesKey] = $attributes;
			} else {
				if ( ! is_array($return)) {
					$return = array($return);
				}
				$return = array_merge($return, $attributes);
			}
		}

		return $return;
	}

	/**
	 * Génère une chaîne de caractères pseudo-aléatoire.
	 *
	 * @param string $type 'alnum' pour [a-zA-Z0-9]+, 'numeric' pour [0-9]+, 'nozero' pour [1-9]+, 'unique' pour un md5(uniqid(mt_rand()))
	 * @param int $length nombre de caractères à générer.
	 *    Si $type vaut 'unique', la chaîne retournée contiendra 32 caractères.
	 * @return string chaîne de caractères pseudo-aléatoire
	 */
	public static function makeRandomString ($type='alnum', $length=8) {
		switch ($type) {
			case 'alnum'	:
			case 'numeric'	:
			case 'nozero'	:
				switch ($type) {
					case 'alnum':	$pool = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ'; break;
					case 'numeric':	$pool = '0123456789'; break;
					case 'nozero':	$pool = '123456789'; break;
				}

				$str = '';
				for ($i=0; $i < $length; $i++) {
					$str .= substr($pool, mt_rand(0, strlen($pool) -1), 1);
				}

				return $str;
				break;

			case 'unique' :
				return md5(uniqid(mt_rand()));
				break;
		}
	}

	/**
	 * Normalise la chaîne spécifiée, permettant de s'en servir par exemple comme non de fichier.
	 *
	 * @param string $str chaîne UTF-8
	 * @return string chaîne normalisée
	 */
	public static function normalizeString ($str) {
		$a = 'ÀÁÂÃÄÅÆÇÈÉÊËÌÍÎÏÐÑÒÓÔÕÖØÙÚÛÜÝÞßàáâãäåæçèéêëìíîïðñòóôõöøùúûýýþÿŔŕ ';
		$b = 'AAAAAAACEEEEIIIIDNOOOOOOUUUUYbsaaaaaaaceeeeiiiidnoooooouuuyybyRr_';
		$result = utf8_decode($str);
		$result = strtr($result, utf8_decode($a), $b);
		$result = preg_replace('/__+/', '_', $result);
		$result = trim($result, '_');
		return utf8_encode($result);
	}

	/**
	 * Retourne le nombre de caractères de la chaîne spécifiée, multi-byte ou non, sans avoir recours à l'extension PHP mbstring.
	 *
	 * @param string $text
	 * @return int le nombre de caractères de la chaîne spécifiée
	 */
	public static function mbStrLen ($text) {
		$dummy = array();
		return preg_match_all("/.{1}/us", $text, $dummy);
	}

	/**
	 * Complète la chaîne spécifiée jusqu'à une taille donnée.
	 * Émule str_pad(), en la faisant fonctionner correctement avec les chaînes multi-byte.
	 * N'a pas recours à l'extension PHP mbstring.
	 *
	 * @param string $text
	 * @param int $pad_length taille voulue
	 * @param string $pad_string chaîne pour compléter
	 * @param int $pad_type type de remplissage
	 * @return string la chaîne fournie complétée jusqu'à la taille spécifiée
	 * @see str_pad()
	 */
	public static function mbStrPad ($text, $pad_length, $pad_string=' ', $pad_type=STR_PAD_RIGHT) {
		$diff = strlen($text) - self::mbStrLen($text);
		return str_pad($text, $pad_length+$diff, $pad_string, $pad_type);
	}
}