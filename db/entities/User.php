<?php

namespace piGallery\db\entities;

require_once __DIR__."./JSONParsable.php";

use piGallery\db\entities\SessionID;

class User extends JSONParsable {

    /**
     * @var int
     */
    protected $id;

    /**
     * @var string
     */
    protected $userName;
    /**
     * @var string
     */
    protected $password;

    /**
     * @var int
     */
    protected $role;

    /**
     * @var string
     */
    protected $sessionID;

    /**
     * @param $userName string
     * @param $password string
     * @param $role int
     */
    function __construct($userName, $password, $role)
    {
        $this->userName = $userName;
        $this->password = $password;
        $this->role = $role;
    }


    /**
     * @param string $userName
     */
    public function setUserName($userName)
    {
        $this->userName = $userName;
    }

    /**
     * @return string
     */
    public function getUserName()
    {
        return $this->userName;
    }

    /**
     * @param string $password
     */
    public function setPassword($password)
    {
        $this->password = $password;
    }

    /**
     * @return string
     */
    public function getPassword()
    {
        return $this->password;
    }

    /**
     * @param int $role
     */
    public function setRole($role)
    {
        $this->role = $role;
    }

    /**
     * @return int
     */
    public function getRole()
    {
        return $this->role;
    }

    /**
     * @param string $sessionID
     */
    public function setSessionID($sessionID)
    {
        $this->sessionID = $sessionID;
    }

    /**
     * @return string
     */
    public function getSessionID()
    {
        return $this->sessionID;
    }




} 