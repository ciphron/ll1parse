<?php

include("parser.php");

class AbstractConsumer extends Consumer {

    private $map;      

    public function __construct() {
        $this->map = array();
    }

    protected function mkstate() {
        // not implemented
        return NULL;
    }

    protected function output($state) {
        // not implemented
    }

    protected function step($state, $token) {
        // not implemented
    }

    public function on_begin($id) {
        $this->map[$id] = $this->mkstate();
    }

    public function on_consume($id, $token) {
        $state = $this->map[$id];
        $this->map[$id] = $this->step($state, $token);
    }

    public function on_syntax_error($id, $token, $error) {
        // handle errors
    }

    public function on_end($id) {
        
    }

    public function output_for_id($id) {
        return $this->output($this->map[$id]);
    }
}

class Processor extends AbstractConsumer {
      private $parser;

      public function __construct($grammar_filename) {
          parent::__construct();
          $grammar = read_grammar_from_file($grammar_filename);
          $this->parser = new Parser($grammar);
          $this->parser->register_consumer($this);
      }


      public function process($str) {
          $tokens = $this->scan($str);
          if ($tokens) {
              $id = $this->parser->parse($tokens);
              return $this->output_for_id($id);
          }
          return NULL;
      }

      // Methods to be overriden by subclasses

      protected function mkstate() {return NULL;}
      protected function output($state) {return NULL;}
      protected function step($state, $token) {}
      protected function scan($str) {return NULL;}
}
