<?php
$result = createRange(10);
while($result->valid()) {
    sleep(1);
    $tmp = $result->current();
    $result->next();
    $result->send($tmp);
}


function createRange($number) {
    $a = 1;
    $b = 1;
    echo "$a\n";
    echo "$b\n";
    for ($i=0; $i<$number; $i++) {
        $r = $a + $b; 
        echo $r . "\n";
        yield $r; 
        $a = $b; 
        $b = (yield 1); 
    }   
}
