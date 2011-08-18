<?php

interface Logger_IndentedInterface extends Logger_Interface {
    public function indent();
    public function unindent();
}
