<?php

namespace piGallery\db\entities;

require_once __DIR__ ."./Content.php";


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
     * @var int
     */
    protected $photoCount;

    /**
     * @param $id
     * @param $path
     * @param $directoryName
     * @param $lastModification
     * @param $photoCount
     */
    function __construct($id, $path, $directoryName, $lastModification, $samplePhotos, $photoCount)
    {
        $this->id = $id;
        $this->path = $path;
        $this->directoryName = $directoryName;
        $this->lastModification = $lastModification;
        $this->samplePhotos = $samplePhotos;
        $this->photoCount = $photoCount;
    }


} 