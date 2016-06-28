<?php

class core {

    protected $user;
    protected $czat_u = 'czat_users';
    protected $czat_m = 'czat_messages';
    private $seed = '9Gt:t5u$gkkrG8Oar,xCfylhIGn7lxXs';

    public function __construct() {
        $this->user = hash('sha1', $_SERVER['REMOTE_ADDR'] . $_SERVER['HTTP_USER_AGENT'] . $this->seed);
    }

    public function getHash($hash) {
        return substr($hash, 0, 16);
    }

    public function inputFilter($name) {
        return filter_input(INPUT_POST, "$name", FILTER_SANITIZE_STRING);
    }

}
