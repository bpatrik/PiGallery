<?php

namespace piGallery\db\entities;

require_once __DIR__."/JSONParsable.php";


class AjaxError extends JSONParsable {


    const GENERAL_ERROR = 10;
    const AUTHENTICATION_FAIL = 20;
    
    /**
     * @var int
     */
    protected $code;
    
    /**
     * @var string
     */
    protected $message;



    /**
     * @param $code
     * @param $message
     */
    function __construct($code, $message)
    {
        $this->code = $code;
        $this->message = $message;
    }



    /**
     * @return int
     */
    public function getCode()
    {
        return $this->code;
    }

    /**
     * @param int $code
     */
    public function setCode($code)
    {
        $this->code = $code;
    }

    /**
     * @return string
     */
    public function getMessage()
    {
        return $this->message;
    }

    /**
     * @param string $message
     */
    public function setMessage($message)
    {
        $this->message = $message;
    }

    

    
    
} 