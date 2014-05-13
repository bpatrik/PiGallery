<?php
namespace piGallery\db\entities;

require_once __DIR__ ."/Content.php";
/**
 * Class Photo
 * @package piGallery
 */
class Photo extends Content {

    /**
     * @var string
     */
    protected $fileName;

    /**
     * @var int
     */
    protected $width;

    /**
     * @var int
     */
    protected $height;

    /**
     * @var string[]
     */
    protected $keywords;

    /**
     * transient
     * @var ThumbnailInfo[]
     */
    protected $availableThumbnails;

    function __construct($id, $path, $fileName, $width, $height, $keywords, $availableThumbnails)
    {
        $this->id = $id;
        $this->path = $path;
        $this->fileName = $fileName;
        $this->width = $width;
        $this->height = $height;
        $this->keywords = $keywords;
        $this->availableThumbnails = $availableThumbnails;
    }


    /**
     * @param string $fileName
     */
    public function setFileName($fileName)
    {
        $this->fileName = $fileName;
    }

    /**
     * @return string
     */
    public function getFileName()
    {
        return $this->fileName;
    }

    /**
     * @param int $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param \string[] $keywords
     */
    public function setKeywords($keywords)
    {
        $this->keywords = $keywords;
    }

    /**
     * @return \string[]
     */
    public function getKeywords()
    {
        return $this->keywords;
    }

    /**
     * @param string $path
     */
    public function setPath($path)
    {
        $this->path = $path;
    }

    /**
     * @return string
     */
    public function getPath()
    {
        return $this->path;
    }

    /**
     * @param int $height
     */
    public function setHeight($height)
    {
        $this->height = $height;
    }

    /**
     * @return int
     */
    public function getHeight()
    {
        return $this->height;
    }

    /**
     * @param int $width
     */
    public function setWidth($width)
    {
        $this->width = $width;
    }

    /**
     * @return int
     */
    public function getWidth()
    {
        return $this->width;
    }




} 