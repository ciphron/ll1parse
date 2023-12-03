<?php

include("common.php");
include("processor.php");

class ABProcessor extends Processor {

    public function __construct() {
        parent::__construct('/users/staff/mclear/ll1/abgrammar.cfg');
    }


    protected function mkstate() {
        return array('output' => '');
    }
    
    protected function output($state) {
        return $state['output'];
    }

    protected function step($state, $token) {
        $state['output'] .= $token->content;
        return $state;
    }

    protected function scan($str) {
        $len = strlen($str);
        $tokens = array();
        for ($i = 0; $i < $len; $i++) {
            $c = $str[$i];
            if ($c == 'A') {
                array_push($tokens, new Token('A', 'A'));
            }
            elseif ($c == 'B') {
                array_push($tokens, new Token('B', 'B'));
            }
            else {
                array_push($tokens, new Token('Other', $c));
            }
        }

        return $tokens;
    }

}

$proc = new ABProcessor();
$output = $proc->process('ABAB');
echo "Output:[$output]\n";
