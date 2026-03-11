<?php

function qa_test($name,$ok){

    if($ok){
        echo "[PASS] $name\n";
    }else{
        echo "[FAIL] $name\n";
    }

}