<?php

namespace piGallery\db\entities;

require_once __DIR__."/JSONParsable.php";


class PathRestriction extends JSONParsable {

    /**
     * @var string
     */
    protected $path;
    
    /**
     * @var bool
     */
    protected $recursive;

    /**
     * @var
     */
    protected $validTime;


    /**
     * @param string $path
     * @param bool $recursive
     * @param $validTime
     */
    function __construct($path, $recursive, $validTime)
    {
        $this->path = $path;
        $this->recursive = $recursive;
        $this->validTime = $validTime;
    }

    /**
     * @return string
     */
    public function getPath()
    {
        return $this->path;
    }

    /**
     * @param string $path
     */
    public function setPath($path)
    {
        $this->path = $path;
    }

    /**
     * @return boolean
     */
    public function isRecursive()
    {
        return $this->recursive;
    }

    /**
     * @param boolean $recursive
     */
    public function setRecursive($recursive)
    {
        $this->recursive = $recursive;
    }


    
    
} 