<?php

include("processor.php");



class ABProcessor extends Processor {

    public function __construct() {
        parent::__construct('/users/staff/mclear/ll1/abgrammar.cfg');
    }


    protected function mkstate {
        return array('output' => '');
    }
    
    protected function output($state) {
        return $state['output'];
    }

    protected function step($state, $token) {
        $state['output'] .= $token->content;
    }

    protected function scan($str) {
        $len = strlen($str);
        $tokens = array();
        for ($i = 0; $i < $len; $i++) {
            $c = $str[$i];
            if ($c == 'A') {
                array_push($tokens, new Token('A', 'A'));
            }
            else if ($c == 'B') {
                array_push($tokens, new Token('B', 'B'));
            }
            else {
                array_push($tokens, new Token('Other', $c));
            }
            
        }

        return $tokens;
    }

}

/*
$builder = new GrammarBuilder('S', array('A', 'B', 'Other'));
$builder->add_production('S', array(new Term('A'),
                                    new Term('T', false)));
$builder->add_production('S', array(new Term(Term::EPSILON)));
$builder->add_production('T', array(new Term('B'),
                                    new Term('S', false)));
*/

$tokenizer = new ABTokenizer();
$grammar = read_grammar_from_file('/users/staff/mclear/ll1/grammar.cfg');
//$grammar->debug();

$parser = new Parser($grammar);
$consumer = new ABConsumer();
$parser->register_consumer($consumer);

$str = 'ABAB';
$tokens = $tokenizer->scan($str);
echo $parser->parse($tokens);
echo "<br/>\n";

echo "Done";
