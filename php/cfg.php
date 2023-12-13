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
        if (count($terms) == 1 && $terms[0]->name() == Term::EPSILON) {
            $this->terms = array();
        }
        else {
            $this->terms = $terms;
        }
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
    private $start_nonterminal;

    public function __construct($start_nonterminal, $terminal_names, $table) {
        $this->start_nonterminal = $start_nonterminal;
        $this->terminal_names = $terminal_names;
        $this->table = $table;
    }

    public function has_terminal($name) {
        return isset($terminal_names[$name]);
    }

    public function starting_nonterminal() {
        return $this->start_nonterminal;
    }

    public function has_production($nonterminal, $head_terminal) {
        assert(isset($this->table[$nonterminal]),
               "No productions for nonterminal $nonterminal");
        $rules = $this->table[$nonterminal];
        return isset($rules[$head_terminal]);
    }


    public function get_production($nonterminal, $head_terminal) {
        assert($this->has_production($nonterminal, $head_terminal),
               "Production for head terminal $head_terminal does not exist for nonterminal $nonterminal");
        $rules = $this->table[$nonterminal];
        return $rules[$head_terminal];
    }


    public function debug() {

        echo "Begin Grammar Debug:<br/>\n";
        echo "---------------------<br/>\n";
             echo "---------------------<br/>\n";
        echo "End Grammar Debug:<br/>\n";
    }
           
}

class GrammarBuilder {
    private $table;
    private $terminal_names;
    private $start_nonterminal;

    public function __construct($start_nonterminal, $terminal_names) {
        $this->table = array();
        $this->terminal_names = $terminal_names;
        if (!in_array(Term::EPSILON, $this->terminal_names)) {
            array_push($this->terminal_names, Term::EPSILON);
        }
        $this->start_nonterminal = $start_nonterminal;
    }


    public function add_production($nonterminal_name, $terms) {
       assert(count($terms) == 0 || $terms[0]->is_terminal(),
              "Expected first term to be a terminal or the terms to be empty");
       $prod = new Production($nonterminal_name, $terms);
       $sel_terminal = Term::EPSILON;
       if (count($terms) > 0) {
           $sel_terminal = $terms[0]->name();
           //assert($isset($this->terminal_names[$sel_terminal]), "Head terminal in production is not a valid terminal");
       }

       $rules = array();
       if (isset($this->table[$nonterminal_name])) {
           $rules = $this->table[$nonterminal_name];
       }

       assert(!isset($rules[$sel_terminal]), "Production already exists with same head terminal / empty");

       $rules[$sel_terminal] = $prod;
       $this->table[$nonterminal_name] = $rules;
    }


    public function get_grammar() {
        assert(isset($this->table[$this->start_nonterminal]),
               "No productions for starting nonterminal in grammar");
        return new Grammar($this->start_nonterminal, $this->terminal_names,
                           $this->table);
    }

}


function read_grammar_from_file($fullpath) {

    if (!file_exists($fullpath)) {
        throw new NotFoundException();
    }  

    $file_contents = file_get_contents($fullpath);
    $lines = explode("\n", $file_contents);

    $terminals = array();
    foreach ($lines as $line) {
        $piece = $line;
        while (preg_match('/^[^#]*#(\w+)(.*)$/m', $piece, $matches)) {
            $match = $matches[1];
            if (!in_array($match, $terminals)) {
                array_push($terminals, $match);
            }
            $piece = $matches[2];
        }
    }

    $builder = new GrammarBuilder('S', $terminals);

    foreach ($lines as $line) {
        $parts = explode("->", $line);
        assert(count($parts) == 2, "Invalid production specified");
        if (count($parts) != 2) {
            continue;
        }
        $parts[0] = trim($parts[0]);
        $parts[1] = trim($parts[1]);
        $nonterminal = $parts[0];
        $terms = array();
        $piece = $parts[1];
        while (preg_match('/^\s*((#(\w+))|(<(\w+)>))+(.*)$/', $piece, $matches)) {
            $is_terminal = isset($matches[3]) &&
                           strlen(trim($matches[3])) > 0;
            $name = ($is_terminal) ? $matches[3] : $matches[5];
            array_push($terms, new Term($name, $is_terminal));
            $piece = $matches[count($matches) - 1];
        }
        $builder->add_production($parts[0], $terms);
    }

    return $builder->get_grammar();

}

