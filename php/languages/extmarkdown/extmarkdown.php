<?php

const TOKEN_IDENT = 'IDENT';
const TOKEN_TEXT = 'TEXT';
const TOKEN_EXCLAIM = 'EXCLAIM';
const TOKEN_LBRACE = 'LBRACE';
const TOKEN_RBRACE = 'RBRACE';

class EMDState {
    private const STAGE_OPEN = 0;
    private const STAGE_NAME = 1;
    private const STAGE_ARG = 2;
    private const STAGE_END_ARG = 3;

    private $in_command;
    private $stage;
    private $output;
    private $cmd_name;
    private $cmd_args;
    private $cmd_deck;

    public function __construct($cmd_deck) {
        $this->stage = STAGE_OPEN;
        $this->in_command = false;
        $this->output = '';
        $this->cmd_deck = $cmd_deck;
    }


    public function step($token) {
        switch ($this->stage) {
            case STAGE_OPEN:
                if ($this->in_command) {
                    if ($token->type == TOKEN_LBRACE) {
                        // this signals a new argument
                        $this->stage = STAGE_ARG;
                        break;
                    }

                    // otherwise the command has finished being entered so
                    // it is time to execute it

                    $this->output .= $this->cmd_deck->execute($this->cmd_name, $this->cmd_args);


                    $this->in_command = false;
                }

                // Check if token is ! i.e. a new command
                if ($token->type == TOKEN_EXCLAIM) {
                    // begin new command
                    $this->in_command = true;
                    $this->cmd_name = '';
                    $this->stage = STAGE_NAME;
                }
                else {
                    $this->output .= $token->content;
                }
                break;
            case STAGE_NAME:
                $this->cmd_name = $token->content;
                $this->cmd_args = array();
                $this->stage = STAGE_OPEN;
                break;
            case STAGE_ARG:
                array_push($this->cmd_args, $token->content);
                $this->stage = STAGE_END_ARG;
                break;
            case STAGE_END_ARG:
                $this->stage = STAGE_OPEN;
                break;
        }
    }

    public function get_output() {
        if ($this->stage == STAGE_OPEN && $this->in_command) {
            $this->output .= $this->cmd_deck->execute($this->cmd_name, $this->cmd_args);
            $this->in_command = false;
        }
        
        return $this->output;
    }
}


class EMDProcessor extends Processor {
    private $cmd_deck;

    public function __construct($cmd_deck) {
        parent::__construct(LL1_LIB_PATH . '/languages/extmarkdown/emdgrammar.cfg');
        $this->cmd_deck = $cmd_deck;
    }


    protected function mkstate() {
        return new EMDState($this->cmd_deck);
    }
    
    protected function output($state) {
        return $state->get_output();
    }

    protected function step($state, $token) {
        $state->step($token);
        return $state;
    }

    protected function scan($str) {
        $len = strlen($str);
        $tokens = array();
        $index = 0;
        $prev = '';
        $text = '';

        while ($index < $len) {

            $c = $str[$index];
            
            if ($prev == '\\') {
                $prev = '';
                $text .= $c;
                $index++;
                continue;
            }

            if ($c == '!') {
                if (strlen($text) > 0) {
                    array_push($tokens, new Token(TOKEN_TEXT, $text));
                    $text = '';
                }
                
                array_push($tokens, new Token(TOKEN_EXCLAIM, $c));
                if (preg_match('/!(\w+)/s', substr($str, $index), $matches)) {
                    $token = new Token(TOKEN_IDENT, $matches[1]);
                    array_push($tokens, $token);
                    $index += strlen($matches[0]);
                }
                else {
                    $index++;
                }
            }
            elseif ($c == '\{') {
                if (strlen($text) > 0) {
                    array_push($tokens, new Token(TOKEN_TEXT, $text));
                    $text = '';
                }

                array_push($tokens, new Token(TOKEN_LBRACE, $c));
                $index++;
            }
            elseif ($c == '\}') {
                if (strlen($text) > 0) {
                    array_push($tokens, new Token(TOKEN_TEXT, $text));
                    $text = '';
                }

                array_push($tokens, new Token(TOKEN_RBRACE, $c));
                $index++;
            }
            elseif ($c == '\\') {
                $index++;
            }
            else {
                $text .= $c;
                $index++;
            }

            $prev = $c;
        }

        if (strlen($text) > 0) {
            array_push($tokens, new Token(TOKEN_TEXT, $text));
        }

        return $tokens;
    }

}

