<?php
namespace Fuel\Tasks;

/**
 * Interface de log rudimentaire, pour loguer les messages à partir d'un seuil d'importance.
 *
 * @category TwengaDeploy
 * @package Lib
 * @author Geoffroy AUBRY <geoffroy.aubry@twenga.com>
 */
interface Logger_Interface
{

    /**
     * Niveau debug d'importance de message. Le plus bas.
     * @var int
     */
    const DEBUG=10;

    /**
     * Niveau info d'importance de message, > à debug.
     * @var int
     */
    const INFO=20;

    /**
     * Niveau warning d'importance de message, > à info.
     * @var int
     */
    const WARNING=30;

    /**
     * Niveau error d'importance de message, le plus haut.
     * @var int
     */
    const ERROR=40;

    /**
     * Log le message spécifié si son importance égale au moins le seuil transmis au constructeur.
     *
     * @param string $sMessage message à loguer
     * @param int $iLevel importance du message
     * @return Logger_Interface $this
     */
    public function log ($sMessage, $iLevel=self::INFO);
}
