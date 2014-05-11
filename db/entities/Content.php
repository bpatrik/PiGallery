<?php

namespace piGallery\db\entities;


require_once __DIR__ ."./JSONParsable.php";


class Content extends JSONParsable {

    /**
     * @var int
     */
    protected $id;

    /**
     * @var string
     */
    protected $path;

} 