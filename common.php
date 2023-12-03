<?php

class Token {
    public $type;
    public $content;

    public function __construct($type, $content=NULL) {
        $this->type = $type;
        $this->content = $content;
    }

    public function type() {
        return $this->type;
    }
}

