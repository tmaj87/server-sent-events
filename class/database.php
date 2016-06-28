<?php

class database {

    private $handle;
    private $dsn = 'mysql:dbname=czat;host=localhost';
    private $user = 'root';
    private $password = '';

    function __construct() {
        try {
            $this->handle = new PDO(self::$dsn, self::$user, self::$password);
        } catch (PDOException $e) {
            trigger_error($e->getMessage(), E_USER_ERROR);
        }
    }

    function query($sql) {
        $result = $this->db->query($sql);
        if (!($result instanceof PDOStatement)) {
            trigger_error('could not query database for...', E_USER_ERROR);
        }
        return $result->fetch(PDO::FETCH_OBJ);
    }
}
