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
     * @var
     */
    protected $creationDate;

    /**
     * transient
     * @var ThumbnailInfo[]
     */
    protected $availableThumbnails;


    function __construct($id, $path, $fileName, $width, $height, $keywords, $creationDate, $availableThumbnails)
    {
        $this->id = $id;
        $this->path = $path;
        $this->fileName = $fileName;
        $this->width = $width;
        $this->height = $height;
        $this->keywords = $keywords;
        $this->creationDate = $creationDate;
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

    /**
     * @return mixed
     */
    public function getCreationDate()
    {
        return $this->creationDate;
    }

    /**
     * @param mixed $creationDate
     */
    public function setCreationDate($creationDate)
    {
        $this->creationDate = $creationDate;
    }

    /**
     * @return ThumbnailInfo[]
     */
    public function getAvailableThumbnails()
    {
        return $this->availableThumbnails;
    }

    /**
     * @param ThumbnailInfo[] $availableThumbnails
     */
    public function setAvailableThumbnails($availableThumbnails)
    {
        $this->availableThumbnails = $availableThumbnails;
    }






} 