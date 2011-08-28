<?php

/**
 * Ajoute la notion d'indentation au log de messages.
 *
 * @category TwengaDeploy
 * @package Lib
 * @author Geoffroy AUBRY
 */
interface Logger_IndentedInterface extends Logger_Interface
{

    /**
     * Ajoute un niveau d'indentation pour tous les logs de messages à venir.
     *
     * @return Logger_IndentedInterface $this
     * @see log()
     */
    public function indent();

    /**
     * Retire un niveau d'indentation (s'il en reste) pour tous les logs de messages à venir.
     *
     * @return Logger_IndentedInterface $this
     * @see log()
     */
    public function unindent();
}
