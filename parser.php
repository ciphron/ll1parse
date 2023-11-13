<?php


class Term {
    public const EPSILON = "EPSILON";

    private $name;
    private $is_terminal;


    public function __construct($name, $is_terminal=true) {
        $this->name = $name;
        $this->is_terminal = $is_terminal;
    }

    public function name() {
        return $this->name;
    }

    public function is_terminal() {
        return $this->is_terminal;
    }
}

class Production {

    private $nonterminal_name;
    private $terms;

    public function __construct($nonterminal_name, $terms=array()) {
        $this->nonterminal_name = $nonterminal_name;
        $this->terms = $terms;
    }


    public function nonterminal_name() {
        return $this->nonterminal_name;
    }

    public function terms() {
        return $this->terms;
    }

    public function is_empty() {
        return count($this->terms) == 0;
    }
}

class Grammar {
    private $table;
    private $terminal_names;

    public function __construct($terminal_names, $table) {
        $this->terminal_names = $terminal_names;
        $this->table = $table;
    }

    public function has_terminal($name) {
        return in_array($name, $terminal_names);
    }
}

class GrammarBuilder {
    private $table;
    private $terminal_names;

    public function __construct($terminal_names) {
        $this->table = array();
        $this->terminal_names = $terminal_names;
    }


    public function add_production($nonterminal_name, $terms) {
        assert(count($terms) == 0 || $terms[0]->is_terminal(),
              "Expected first term to be a terminal or the terms to be empty");
       $prod = new Production($nonterminal_name, $terms);

       $sel_terminal = Term.EPSILON;
       if (count($terms) > 0) {
           $sel_terminal = $terms[0]->name();
           assert(in_array($sel_terminal, $this->terminal_names), "Head terminal in production is not a valid terminal");
       }

       $rules = array();
       if (in_array($nonterminal_name, $this->table)) {
           $rules = $this->table[$nonterminal_name];
       }
       else {
           $this->table[$nonterminal_name] = $rules;
       }
       
       assert(!in_array($sel_terminal, $rules), "Production already exists with same head terminal / empty");

       $rules[$sel_terminal] = prod;
    }


    public function get_grammar() {
        return new Grammar($terminal_names, $table);
    }
}




//echo "<br/>";
echo "Done";


