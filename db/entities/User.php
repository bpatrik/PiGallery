<?php

namespace piGallery\db\entities;

require_once __DIR__."/JSONParsable.php";
require_once __DIR__."/PathRestriction.php";


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
     * @var string
     */
    protected $passwordSalt;

    /**
     * @var int
     */
    protected $role;

    /**
     * @var string
     */
    protected $sessionID;

    /**
     * @var PathRestriction
     */
    protected $pathRestriction;

    /**
     * @param string $userName
     * @param string $password
     * @param int $role
     * @param PathRestriction $pathRestriction
     */
    function __construct($userName, $password, $role, $pathRestriction = null)
    {
        $this->userName = $userName;
        $this->password = $password;
        $this->role = $role;
        $this->pathRestriction = $pathRestriction;
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

    /**
     * @return string
     */
    public function getPasswordSalt()
    {
        return $this->passwordSalt;
    }

    /**
     * @param string $passwordSalt
     */
    public function setPasswordSalt($passwordSalt)
    {
        $this->passwordSalt = $passwordSalt;
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param int $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * @return PathRestriction
     */
    public function getPathRestriction()
    {
        return $this->pathRestriction;
    }

    /**
     * @param PathRestriction $pathRestriction
     */
    public function setPathRestriction($pathRestriction)
    {
        $this->pathRestriction = $pathRestriction;
    }

 


} 