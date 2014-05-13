<?php

namespace piGallery\db\entities;


class ThumbnailInfo {

    /**
     * @var int
     */
    protected $size;

    /**
     * @var bool
     */
    protected $available;

    function __construct($size, $available)
    {
        $this->size = $size;
        $this->available = $available;
    }

    /**
     * @param boolean $available
     */
    public function setAvailable($available)
    {
        $this->available = $available;
    }

    /**
     * @return boolean
     */
    public function getAvailable()
    {
        return $this->available;
    }

    /**
     * @param int $size
     */
    public function setSize($size)
    {
        $this->size = $size;
    }

    /**
     * @return int
     */
    public function getSize()
    {
        return $this->size;
    }

    function getJsonData(){
        $var = get_object_vars($this);
        foreach($var as &$value){
            if(is_object($value) && method_exists($value,'getJsonData')){
                $value = $value->getJsonData();
            }
        }
        return $var;
    }



} 