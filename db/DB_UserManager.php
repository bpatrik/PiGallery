<?php

namespace piGallery\db;

require_once __DIR__ ."/../config.php";
require_once __DIR__ ."/entities/Photo.php";
require_once __DIR__ ."/entities/Directory.php";
require_once __DIR__ ."/DB.php";
require_once __DIR__ ."/../model/ThumbnailManager.php";


use piGallery\db\entities\Directory;
use piGallery\db\entities\Photo;
use piGallery\db\entities\Role;
use piGallery\db\DB;
use piGallery\db\entities\User;
use piGallery\model\Helper;
use piGallery\model\ThumbnailManager;
use piGallery\Properties;
use \mysqli;
use \Exception;

/**
 * Class DB
 * @package piGallery\db
 */
class DB_UserManager {

    private static function GUID()
    {
        if (function_exists('com_create_guid') === true)
        {
            return trim(com_create_guid(), '{}');
        }

        return sprintf('%04X%04X-%04X-%04X-%04X-%04X%04X%04X', mt_rand(0, 65535), mt_rand(0, 65535), mt_rand(0, 65535), mt_rand(16384, 20479), mt_rand(32768, 49151), mt_rand(0, 65535), mt_rand(0, 65535), mt_rand(0, 65535));
    }



    /**
     * @param $user User
     * @throws \Exception
     */
    public static function register($user){
        $mysqli = DB::getDatabaseConnection();
        $stmt = $mysqli->prepare("INSERT INTO users (userName, password, passwordSalt, role) VALUES (?, ?, ?, ?)");

        if($stmt === false) {
            $error = $mysqli->error;
            $mysqli->close();
            throw new \Exception("Error: ". $error);
        }
        $userName = $user->getUserName();
        $password = $user->getPassword();
        $salt = uniqid(mt_rand(), true);
        $encrypted_password = sha1($salt.$password);
        $role = $user->getRole();

        $stmt->bind_param('sssi', $userName, $encrypted_password, $salt, $role);
        $stmt->execute();

        $stmt ->close();
        $mysqli->close();

        return "ok";
    }

    public static function logout($userId, $sessionID){
        $mysqli = DB::getDatabaseConnection();
        $stmt = $mysqli->prepare("DELETE FROM sessionids WHERE session_id = ? AND user_id = ?");

        if($stmt === false) {
            $error = $mysqli->error;
            $mysqli->close();
            throw new \Exception("Error: ". $error);
        }

        $stmt->bind_param('si', $sessionID, $userId);
        $stmt->execute();

        $stmt ->close();
        $mysqli->close();

        return "ok";
    }

    private static function clearSessionIDTable($mysqli){
        $stmt = $mysqli->prepare("DELETE FROM sessionids WHERE sessionids.validTime < NOW()");
        if($stmt === false) {
            $error = $mysqli->error;
            $mysqli->close();
            throw new \Exception("Error: ". $error);
        }
        $stmt->execute();
        $stmt->close();
    }

    public static function login($userName, $password, $rememberMe){
        $mysqli = DB::getDatabaseConnection();

        $user = null;
        $stmt = $mysqli->prepare("SELECT
                                    users.ID,
                                    users.userName,
                                    users.`password`,
                                    users.passwordSalt,
                                    users.role
                                    FROM
                                    users
                                    WHERE
                                    users.userName = ?");
        if($stmt === false) {
            $error = $mysqli->error;
            $mysqli->close();
            throw new \Exception("Error: ". $error);
        }
        $stmt->bind_param('s', $userName);
        $stmt->execute();
        $stmt->bind_result($id, $userName, $Userpassword, $passwordSalt, $role);


        while($stmt->fetch()) {
            if($Userpassword == sha1($passwordSalt.$password)) { //check if password ok
                $user = new User($userName, $password, $role);



            }
        }
        $stmt->close();

        if($user != null){
            //add session ID
            if ($rememberMe == true) {
                $Insertstmt = $mysqli->prepare("INSERT INTO sessionids (user_id, session_id, validTime) VALUES (?, ?, NOW() + INTERVAL 30 DAY)");
            }else{
                $Insertstmt = $mysqli->prepare("INSERT INTO sessionids (user_id, session_id, validTime) VALUES (?, ?, NOW() + INTERVAL 1 DAY)");
            }
            if($Insertstmt === false) {
                $error = $mysqli->error;
                $mysqli->close();
                throw new \Exception("Error: ". $error);
            }

            $GUID= DB_UserManager::GUID();
            $Insertstmt->bind_param('is', $id, $GUID);
            $Insertstmt->execute();

            $Insertstmt ->close();

            $user->setSessionID($GUID);
        }

        DB_UserManager::clearSessionIDTable($mysqli);

        $mysqli->close();


        return $user;
    }

    public static function loginWithSessionID($sessionID){
        $mysqli = DB::getDatabaseConnection();

        $user = null;
        $stmt = $mysqli->prepare("SELECT
                                    u.ID,
                                    u.userName,
                                    u.role
                                    FROM
                                    users u, sessionids s
                                    WHERE
                                    u.ID = s.user_id AND
                                    s.session_id = ? AND
                                    s.validTime > NOW()");
        if($stmt === false) {
            $error = $mysqli->error;
            $mysqli->close();
            throw new \Exception("Error: ". $error);
        }
        $stmt->bind_param('s', $sessionID);
        $stmt->execute();
        $stmt->bind_result($userID, $userName, $role);


        if($stmt->fetch()) {
                $user = new User($userName, null, $role);
                $user->setSessionID($sessionID);
                $user->setID($userID);
        }
        $stmt->close();

        $mysqli->close();

        return $user;
    }

    public static function getUsersList(){
        $mysqli = DB::getDatabaseConnection();

        $users = array();
        $stmt = $mysqli->prepare("SELECT
                                    u.ID,
                                    u.userName,
                                    u.role
                                    FROM
                                    users u ");
        if($stmt === false) {
            $error = $mysqli->error;
            $mysqli->close();
            throw new \Exception("Error: ". $error);
        }
        $stmt->execute();
        $stmt->bind_result($userID, $userName, $role);


        while($stmt->fetch()) {
            $user = new User($userName, null, $role);
            $user->setId($userID);
            array_push($users, $user);
        }
        $stmt->close();

        $mysqli->close();

        return array('users' => $users);
    }

    public static function deleteUser($id){
        $mysqli = DB::getDatabaseConnection();

        $stmt = $mysqli->prepare("DELETE FROM users WHERE users.ID = ?");
        if($stmt === false) {
            $error = $mysqli->error;
            $mysqli->close();
            throw new \Exception("Error: ". $error);
        }
        $stmt->bind_param('i', $id);
        $stmt->execute();

        $stmt->close();

        $mysqli->close();

        return "ok";
    }
} 