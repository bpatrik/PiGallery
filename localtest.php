<?php
header('Content-type: application/json; charset=utf-8');


$jsonp = json_encode("ok");

if(isset($_GET['callback'])){
    echo $_GET['callback'] . '(' . $jsonp . ')';
}else{
    echo $jsonp;
}
