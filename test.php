<?php

include("common.php");
include("processor.php");
include("languages/extmarkdown/extmarkdown.php");

$proc = new ABProcessor();
$output = $proc->process('ABABAB');
echo "Output:[$output]\n";

