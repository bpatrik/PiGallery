<?php

namespace piGallery\db\entities;

require_once __DIR__ ."/Content.php";


class Directory extends Content {

    /**
     * @var string
     */
    protected $directoryName;

    /**
     * @var int
     */
    protected $lastModification;

    /**
     * @var Photo[]
     */
    protected $samplePhotos;


    /**
     * @param $id
     * @param $path
     * @param $directoryName
     * @param $lastModification
     * @param $samplePhotos
     */
    function __construct($id, $path, $directoryName, $lastModification, $samplePhotos)
    {
        $this->id = $id;
        $this->path = $path;
        $this->directoryName = $directoryName;
        $this->lastModification = $lastModification;
        $this->samplePhotos = $samplePhotos;
    }

    /**
     * @return string
     */
    public function getDirectoryName()
    {
        return $this->directoryName;
    }

    /**
     * @param string $directoryName
     */
    public function setDirectoryName($directoryName)
    {
        $this->directoryName = $directoryName;
    }

    /**
     * @return int
     */
    public function getLastModification()
    {
        return $this->lastModification;
    }

    /**
     * @param int $lastModification
     */
    public function setLastModification($lastModification)
    {
        $this->lastModification = $lastModification;
    }

    /**
     * @return Photo[]
     */
    public function getSamplePhotos()
    {
        return $this->samplePhotos;
    }

    /**
     * @param Photo[] $samplePhotos
     */
    public function setSamplePhotos($samplePhotos)
    {
        $this->samplePhotos = $samplePhotos;
    }



    public function toUTF8(){
        $this->path = utf8_encode($this->path);
        $this->directoryName = utf8_encode($this->directoryName);
        if($this->samplePhotos != null){
            foreach($this->samplePhotos as $photo){
                $photo->toUTF8();
            }
        }
    }



} 