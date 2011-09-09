<?php

/**
 * Collection des propriétés possibles pour un attribut de tâche.
 * Ces propriétés sont manipulées au sein de champs de bits dans la classe Task.
 *
 * @category TwengaDeploy
 * @package Core
 * @author Geoffroy AUBRY <geoffroy.aubry@twenga.com>
 * @see Task::$_aAttrProperties()
 */
class AttributeProperties
{
    /**
     * Propriété d'attribut : autorise l'utilisation des '${parameter}'.
     * @var int
     */
    const ALLOW_PARAMETER = 1;

    /**
     * Propriété d'attribut : l'attribut désigne un répertoire.
     * @var int
     */
    const DIR = 2;

    /**
     * Propriété d'attribut : autorise l'utilisation des jokers shell ? et * pour les répertoires
     * (implique AttributeProperties::DIR).
     * @var int
     */
    const DIRJOKER = 4;

    /**
     * Propriété d'attribut : l'attribut désigne un fichier.
     * @var int
     */
    const FILE = 8;

    /**
     * Propriété d'attribut : autorise l'utilisation des jokers shell ? et * pour les fichiers
     * (implique AttributeProperties::FILE).
     * @var int
     */
    const FILEJOKER = 16;

    /**
     * Propriété d'attribut : l'attribut est obligatoire.
     * @var int
     */
    const REQUIRED = 32;

    /**
     * Propriété d'attribut : l'attribut est un fichier ou un répertoire source et doit donc exister
     * (implique AttributeProperties::FILE et AttributeProperties::DIR).
     * @var int
     */
    const SRC_PATH = 64;

    /**
     * Propriété d'attribut : l'attribut est un booléen sous forme de chaîne de caractères,
     * valant soit 'true' soit 'false'.
     * @var int
     */
    const BOOLEAN = 128;

    /**
     * Propriété d'attribut : l'attribut est une URL.
     * @var int
     */
    const URL = 256;

    /**
     * Propriété d'attribut : l'attribut est un email.
     * @var int
     */
    const EMAIL = 512;

    /**
     * Propriété d'attribut : l'attribut peut être multi-valué.
     * @var int
     */
    const MULTI_VALUED = 1024;

    /**
     * Pattern regex pour scinder les différentes valeurs d'un attribut doté de la propriété MULTI_VALUED.
     * @var string
     * @see checkAttributes()
     */
    public static $sMultiValuedSeparator = '/\s*,\s*/';

    /**
     * Glue pour concaténer les différentes valeurs d'un attribut doté de la propriété MULTI_VALUED.
     * @var string
     * @see checkAttributes()
     */
    public static $sMultiValuedJoinGlue = ', ';

    /**
     * Collection de services.
     * @var ServiceContainer
     * @see _checkAttribute()
     */
    protected $_oServiceContainer;

    /**
     * Constructeur.
     *
     * @param ServiceContainer $oServiceContainer
     */
    public function __construct(ServiceContainer $oServiceContainer)
    {
        $this->_oServiceContainer = $oServiceContainer;
    }

    /**
     * Normalise les propriétés des attributs des tâches XML.
     * Par exemple si c'est un AttributeProperties::FILEJOKER, alors c'est forcément aussi
     * un AttributeProperties::FILE.
     *
     * @see aAttributeProperties
     */
    private function _normalizeAttributeProperties (array &$aProperties)
    {
        foreach ($aProperties as $sAttribute => $iProperties) {
            if (($iProperties & self::SRC_PATH) > 0) {
                $aProperties[$sAttribute] |= self::FILE | self::DIR;
            }
            if (($iProperties & self::FILEJOKER) > 0) {
                $aProperties[$sAttribute] |= self::FILE;
            }
            if (($iProperties & self::DIRJOKER) > 0) {
                $aProperties[$sAttribute] |= self::DIR;
            }
        }
    }

    /**
     * Vérifie l'absence d'attribut non permis.
     *
     * @param array $aProperties tableau associatif (nom d'attribut => (int)champ de bits de propriétés d'attribut)
     * 		Les propriétés sont une combinaisons de constantes de cette classe (ex. self::EMAIL).
     * @param array $aValues tableau associatif (nom d'attribut => (string)valeur)
     * @throws DomainException en cas d'attribut non permis
     */
    private function _checkUnknownAttributes (array $aProperties, array $aValues)
    {
        $aAvailablesAttr = array_keys($aProperties);
        $aUnknownAttributes = array_diff(array_keys($aValues), $aAvailablesAttr);
        if (count($aUnknownAttributes) > 0) {
            throw new DomainException(
                "Available attributes: " . print_r($aAvailablesAttr, true)
                . " => Unknown attribute(s): " . print_r($aUnknownAttributes, true)
            );
        }
    }

    /**
     * Vérifie au moyen de tests basiques que les valeurs des attributs sont conformes à leurs propriétés.
     * Lance une exception si tel n'est pas le cas.
     *
     * @param array &$aProperties tableau associatif (nom d'attribut => (int)champ de bits de propriétés d'attribut)
     * 		Les propriétés sont une combinaisons de constantes de cette classe (ex. self::EMAIL).
     * 		Passé par référence car potentiellement modifié par _normalizeAttributeProperties().
     * @param array &$aValues tableau associatif (nom d'attribut => (string)valeur)
     * 		Passé par référence car potentiellement modifié par _formatAttribute().
     * @throws UnexpectedValueException en cas d'attribut ou fichier manquant
     * @throws DomainException en cas d'attribut non permis
     */
    public function checkAttributes (array &$aProperties, array &$aValues)
    {
        $this->_normalizeAttributeProperties($aProperties);
        $this->_checkUnknownAttributes($aProperties, $aValues);

        foreach ($aProperties as $sName => $iProperties) {
            if (isset($aValues[$sName])) {
                if (($iProperties & self::MULTI_VALUED) > 0) {
                    $aSplittedValues = preg_split(
                        self::$sMultiValuedSeparator,
                        $aValues[$sName],
                        -1,
                        PREG_SPLIT_NO_EMPTY
                    );
                } else {
                    $aSplittedValues = array($aValues[$sName]);
                }

                foreach ($aSplittedValues as $i => $sSplittedValue) {
                    $aSplittedValues[$i] = $this->_formatAttribute($iProperties, $sSplittedValue);
                    $this->_checkAttribute($sName, $iProperties, $sSplittedValue);
                }
                $aValues[$sName] = implode(self::$sMultiValuedJoinGlue, $aSplittedValues);

            } else {
                $this->_checkAttribute($sName, $iProperties, '');
            }
        }
    }

    /**
     * Formate la valeur d'un attribut au regard de ses propriétés.
     *
     * @param int $iProperties champ de bits de propriétés d'attribut,
     * 		combinaisons de constantes de cette classe (ex. self::EMAIL).
     * @param string $sValue valeur de l'attribut
     * @return string valeur potentiellement formatée de l'attribut au regard de ses propriétés.
     */
    private function _formatAttribute ($iProperties, $sValue)
    {
        if ( ! empty($sValue)) {
            if (($iProperties & self::DIR) > 0 || ($iProperties & self::FILE) > 0) {
                $sValue = str_replace('\\', '/', $sValue);
            }
        }
        return $sValue;
    }

    /**
     * Vérifie au moyen de tests basiques que la valeur de l'attribut spécifié est conforme à ses propriétés.
     * Lance une exception si tel n'est pas le cas.
     *
     * @param string $sName nom d'attribut
     * @param int $iProperties champ de bits de propriétés d'attribut,
     * 		combinaisons de constantes de cette classe (ex. self::EMAIL).
     * @param string $sValue valeur de l'attribut
     * @throws UnexpectedValueException en cas d'attribut ou fichier manquant
     * @throws DomainException en cas de valeur non permise
     */
    private function _checkAttribute ($sName, $iProperties, $sValue)
    {
        if (empty($sValue) && ($iProperties & self::REQUIRED) > 0) {
            throw new UnexpectedValueException("'$sName' attribute is required!");

        } else if ( ! empty($sValue)) {
            if (($iProperties & self::BOOLEAN) > 0 && ! in_array($sValue, array('true', 'false'))) {
                $sMsg = "Value of '$sName' attribute is restricted to 'true' or 'false'. Value: '$sValue'!";
                throw new DomainException($sMsg);
            }

            if (($iProperties & self::URL) > 0 && preg_match('#^http://#i', $sValue) === 0 ) {
                throw new DomainException("Bad URL: '" . $sValue . "'");
            }

            if (($iProperties & self::EMAIL) > 0
                && preg_match(
                    '#^[[:alnum:]]([-_.]?[[:alnum:]_?])*@[[:alnum:]]([-.]?[[:alnum:]])+\.([a-z]{2,6})$#',
                    $sValue
                ) === 0
            ) {
                throw new DomainException("Email invalid: '" . $sValue . "'");
            }

            if (preg_match('#[*?].*/#', $sValue) !== 0 && ($iProperties & self::DIRJOKER) === 0) {
                $sMsg = "'*' and '?' jokers are not authorized for directory in '$sName' attribute!";
                throw new DomainException($sMsg);
            }

            if (preg_match('#[*?](.*[^/])?$#', $sValue) !== 0
                && ($iProperties & self::FILEJOKER) === 0
                && ($iProperties & self::URL) === 0
            ) {
                $sMsg = "'*' and '?' jokers are not authorized for filename in '$sName' attribute!";
                throw new DomainException($sMsg);
            }

            if (preg_match('#\$\{[^}]*\}#', $sValue) !== 0 && ($iProperties & self::ALLOW_PARAMETER) === 0) {
                $sMsg = "Parameters are not allowed in '$sName' attribute! Value: '$sValue'";
                throw new DomainException($sMsg);
            }

            // Vérification de présence de la source si chemin sans joker ni paramètre :
            if (
                    ($iProperties & self::SRC_PATH) > 0
                    && preg_match('#\*|\?|\$\{[^}]*\}#', $sValue) === 0
                    && $this->_oServiceContainer->getShellAdapter()->getPathStatus($sValue)
                        === Shell_PathStatus::STATUS_NOT_EXISTS
            ) {
                throw new UnexpectedValueException("File or directory '$sValue' not found!");
            }
        }
    }
}
