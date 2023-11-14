<?php

include("commmon.php");

class Tokenizer {
    private $types;

    public function __construct($types) {    
        $this->types = $types;
    }

    public function scan($str) {
        return array();
    }
}
