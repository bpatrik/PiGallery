<?php

namespace piGallery\db\entities;


class JSONParsable {


    function getJsonData(){
        $var = get_object_vars($this);
        foreach($var as &$value){
            if(is_object($value) && method_exists($value,'getJsonData')){
                $value = $value->getJsonData();
            }else  if(is_array($value)){
                $JSON_array = array();
                foreach ($value as $row) {
                    if(is_object($row) && method_exists($row,'getJsonData')){
                        $row = $row->getJsonData();
                    }
                    $JSON_array[] =  $row;
                }
                $value = $JSON_array;
            }
        }
        return $var;
    }

} 