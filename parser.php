<?php

include("commmon.php");
include("cfg.php");

class Consumer {

    public function on_begin($id) {}
    public function on_consume($id, $token) {return true;}
    public function on_syntax_error($id, $token, $error) {}
    public function on_end($id) {}
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
        array_push($stack, new Term($grammar->starting_nonterminal(), false));

        while (($term = array_pop($stack)) != NULL) {
            if ($position >= $len) {
                if ($term->is_terminal() ||
                        !$grammar->has_production($term->name(),
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

            if ($term->is_terminal()) {
               if ($term->name() == $head_token->type) {
                   // Correct match: consume - notify listeners of consume
                   $this->notify_consume($id, $head_token);
                   $position++;

               }
               else {
                    // Mismatch (error) - notify listeners of syntax error
                    $expected_type = $term->name();
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
                $nonterminal = $term->name();
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

         return ($valid) ? $id : -1;
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
