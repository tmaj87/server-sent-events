<?php

class core {

    protected $db;
    protected $user;
    protected $czat_u = 'czat_users';
    protected $czat_m = 'czat_messages';

    public function __construct() {
        global $dbh;
        $this->db = $dbh;
        $this->user = hash('sha1', $_SERVER['REMOTE_ADDR'] . $_SERVER['HTTP_USER_AGENT'] . '9Gt:t5u$gkkrG8Oar,xCfylhIGn7lxXs');
    }

    public function getHash($hash) {
        return substr($hash, 0, 16);
    }

}
