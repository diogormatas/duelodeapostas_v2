<?php

if(!function_exists('test')){

function test($name,$ok){

    if($ok){
        echo "[PASS] $name\n";
    }else{
        echo "[FAIL] $name\n";
    }

}

}