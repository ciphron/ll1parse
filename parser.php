<?php

include("commmon.php");

class Consumer {

    public function on_begin($id) {}
    public function on_consume($id, $token) {return true;}
    public function on_syntax_error($id, $token, $error) {}
    public function on_end($id) {}
}


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
    private $consumer_id_counter;
    private $consumers;

    public function __construct($grammar) {
        $this->grammar = $grammar;
        $this->id_counter = 0;
        $this->consumers = array();
        $this->consumer_id_counter = 0;
    }

    public function register_consumer($consumer) {
         $this->consumers[$this->consumer_id_counter] = $consumer;
         return $this->consumer_id_counter++;
    }

    public function deregister_consumer($consumer_id) {
        unset($this->consumers[$consumer_id]);
    }

    public function parse($tokens) {
        $valid = true;
        $id = $id_counter++;
        $position = 0;
        $len = count($tokens);
        $grammar = $this->grammar;

        // notify listeners of begin with id
        $this->notify_begin($id);

        $stack = array();
        array_push($stack, $grammar->starting_nonterminal());

        while (($term = array_pop($stack)) != NULL) {

            if ($position >= $len) {
                if ($term->is_terminal ||
                        !$grammar->has_production($term->name,
                                                  Term::EPSILON)) {
                    // notify listeners of syntax error: Expected epsilon
                    $this->notify_syntax_error($id, NULL,
                                               "End of input reached");
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
                   $this->notify_consume($id, $head_token);
                   $position++;

               }
               else {
                    // Mismatch (error) - notify listeners of syntax error
                    $expected_type = $term->name;
                    $this->notify_syntax_error($id, $head_token,
                         "Token mismatch. Expected token type $expected_type");
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
                     $this->notify_syntax_error($id, $head_token,
                                                "Grammar error");
                     $valid = false;
                     break;
                }
            }
         }

         $this->notify_end($id);

         return $valid;
    }

    private function notify_begin($id) {
        foreach ($this->consumers as $consumer) {
            $consumer->on_begin($id);
        }
    }

    private function notify_consume($id, $token) {
        foreach ($this->consumers as $consumer) {
            $consumer->on_consume($id, $token);
        }
    }


    private function notify_syntax_error($id, $token, $error) {
        foreach ($this->consumers as $consumer) {
            $consumer->on_syntax_error($id, $token, $error);
        }
    }


    private function notify_end($id) {
        foreach ($this->consumers as $consumer) {
            $consumer->on_end($id);
        }
    }


}


