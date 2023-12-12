<?php


class Command {

    public $name;
    public $arity;
    private $handler;

    public function __construct($name, $arity, $handler) {
        $this->name = $name;
        $this->arity = $arity;
        $this->handler = $handler;
    }


    public function execute($context, $args) {
        $h = $this->handler;
        return $h($context, $args);
    }
}

class CommandDeck {

    private $context;
    private $commands;

    public function __construct($context) {
        $this->context = $context;
        $this->commands = array();
    }


    public function add($name, $arity, $handler) {
        $this->commands[$name] = new Command($name, $arity, $handler);
    }

    public function remove($name) {
        unset($this->commands[$name]);
    }

    public function has($name) {
        return isset($this->commands[$name]);
    }

    public function execute($name, $args) {
        return $this->commands[$name]->execute($this->context, $args);
    }
}