<?php

interface Numbering_Interface
{

    public function getNextCounterValue ();

    public function addCounterDivision ();

    public function removeCounterDivision ();
}
