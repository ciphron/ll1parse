<?php

include("commmon.php");

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
    private $start_nonterminal;

    public function __construct($start_nonterminal, $terminal_names, $table) {
        $this->start_nonterminal = $start_nonterminal;
        $this->terminal_names = $terminal_names;
        $this->table = $table;
    }

    public function has_terminal($name) {
        return in_array($name, $terminal_names);
    }

    public function starting_nonterminal() {
        return $this->start_nonterminal;
    }

    public function has_production($nonterminal, $head_terminal) {
        assert(in_array($nonterminal, $this->table),
               "No productions for nonterminal $nonterminal");
        $rules = $this->table[$nonterminal];
        return in_array($head_terminal, $rules);
    }


    public function get_production($nonterminal, $head_terminal) {
        assert($this->has_production($nonterminal, $head_terminal),
               "Production for head terminal $head_terminal does not exist for nonterminal $nonterminal");
        $rules = $this->table[$nonterminal];
        return $rules[$head_terminal];
    }
           
}

class GrammarBuilder {
    private $table;
    private $terminal_names;
    private $start_nonterminal;

    public function __construct($start_nonterminal, $terminal_names) {
        $this->table = array();
        $this->terminal_names = $terminal_names;
        $this->start_nonterminal = $start_nonterminal;
    }


    public function add_production($nonterminal_name, $terms) {
        assert(count($terms) == 0 || $terms[0]->is_terminal(),
              "Expected first term to be a terminal or the terms to be empty");
       $prod = new Production($nonterminal_name, $terms);

       $sel_terminal = Term::EPSILON;
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
        assert(in_array($this->start_nonterminal, $this->table),
               "No productions for starting nonterminal in grammar");
        return new Grammar($this->start_nonterminal, $this->terminal_names,
                           $this->table);
    }
}


class Parser {
    private $grammar;
    private $id_counter;

    public function __construct($grammar) {
        $this->grammar = $grammar;
        $this->id_counter = 0;
    }


    public function parse($tokens) {
        $valid = true;
        $id = $id_counter++;
        $position = 0;
        $len = count($tokens);
        $grammar = $this->grammar;

        $stack = array();
        array_push($stack, $grammar->starting_nonterminal());

        // notify listeners of begin with id
        while (($term = array_pop($stack)) != NULL) {

            if ($position >= $len) {
                if ($term->is_terminal ||
                        !$grammar->has_production($term->name,
                                                  Term::EPSILON)) {
                    // notify listeners of syntax error: Expected epsilon
                    $valid = false;
                    break;
                }
                else {
                    continue;
                }
            }

            $head_token = $tokens[$position];
            if ($term->is_terminal) {
               if ($term->name == $head_token->type) {
                   // Correct match: consume - notify listeners of consume
                   $position++;

               }
               else {
                    // Mismatch (error) - notify listeners of syntax error
                    $valid = false;
                    break;
              }
            }
            else {
                // So the term we've popped is a nonterminal
                // we need to push the terms in the right production
                // onto the stack. The right production depends on head token
                $nonterminal = $term->name;
                if ($grammar->has_production($nonterminal, $head_token->type)) {
                    // Push terms in production onto the stack
                    $prod = $grammar->get_production($nonterminal,
                                                     $head_token->type);
                    $terms = $prod->terms();
                    while (($t = array_pop($terms)) != NULL) {
                        array_push($stack, $t);
                    }

                }
                else if ($grammar->has_production($nonterminal, Term::EPSILON)) {
                    // If the nonterminal has the empty production, continue
                    continue;
                }
                else {
                     // Syntax error - notify listeners of error
                     $valid = false;
                     break;
                }
            }
            
        }
        

        return $valid;
    }
}



//echo "<br/>";
echo "Done";


