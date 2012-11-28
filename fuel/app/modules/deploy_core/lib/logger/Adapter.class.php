<?php

/**
 * Classe de log rudimentaire, pour loguer les messages à partir d'un seuil d'importance.
 *
 * @category TwengaDeploy
 * @package Lib
 * @author Geoffroy AUBRY <geoffroy.aubry@twenga.com>
 */
class Logger_Adapter implements Logger_Interface
{

    /**
     * Seuil d'importance à partir duquel accepter de loguer un message.
     * @var int
     * @see log()
     */
    private $_iMinMsgLevel;

    /**
     * Constructeur.
     *
     * @param int $iMinMsgLevel Seuil d'importance à partir duquel accepter de loguer un message.
     */
    public function __construct ($iMinMsgLevel)
    {
        $this->_iMinMsgLevel = $iMinMsgLevel;
    }

    /**
     * Log le message spécifié si son importance égale au moins le seuil transmis au constructeur.
     *
     * @param string $sMessage message à loguer
     * @param int $iLevel importance du message
     * @return Logger_Interface $this
     */
    public function log ($sMessage, $iLevel=self::INFO)
    {
        if ($iLevel >= $this->_iMinMsgLevel) {
            echo str_replace("\n", '\\\n', rtrim($sMessage)) . "\n";
        }
        return $this;
    }
}