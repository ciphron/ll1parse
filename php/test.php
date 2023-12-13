<?php

include("config.php");
include("common.php");
include("processor.php");
include("languages/extmarkdown/commands.php");
include("languages/extmarkdown/extmarkdown.php");


function hello($context) {
    return "hello world";
}

$cd = new CommandDeck(array());
$cd->add('hello', 0, hello);

$proc = new EMDProcessor($cd);
$output = $proc->process('This is a test with a command: !hello.');
echo "Output:[$output]\n";


