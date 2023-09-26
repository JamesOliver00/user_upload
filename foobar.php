<?php

function main(){
    for( $i=1 ; $i<=100 ; $i++){
        $print = "";
        if($i % 3 == 0){
            $print .= 'foo';
        }
        if($i % 5 == 0){
            $print .= 'bar';
        }
        if($i % 5 && $i % 3){
            $print = $i;
        }
        echo $print . ",";
    }
}

main();
exit();