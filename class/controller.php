<?php

class controller extends core {

    public function __construct() {
        parent::__construct();
        $this->redirect();
    }

    private function redirect() {
        $this->handleNewMessage();
        $this->handleNewUser();
    }

    private function notSoFast() {
        $obj = $this->db->query("SELECT TIMESTAMPDIFF(SECOND, time, NOW()) diff FROM $this->czat_m WHERE hash = '$this->user' ORDER BY id DESC LIMIT 1")->fetch(PDO::FETCH_OBJ);
        if (!empty($obj) && intval($obj->diff) < 1) {
            echo 'nop';
            exit;
        }
    }

    public function handleNewMessage() {
        $n = self::inputFilter('n');
        $m = self::inputFilter('m');
        $c = self::inputFilter('c');
        if (strlen($n) && strlen($n) < 60 && strlen($m) && strlen($m) < 200 && strlen($c) == 6) {
            $this->notSoFast();
            $stmt = $this->db->prepare("INSERT INTO $this->czat_m (nick, color, message, hash) VALUES (?, ?, ?, '$this->user')");
            $stmt->bindParam(1, $n, PDO::PARAM_STR, 60);
            $stmt->bindParam(2, $c, PDO::PARAM_STR, 6);
            $stmt->bindParam(3, $m, PDO::PARAM_STR, 200);
            $stmt->execute();
            exit;
        }
    }

    public function handleNewUser() {
        $iam = self::inputFilter('iam');
        if (!empty($iam)) {
            echo $this->getHash($this->user);
            exit;
        }
    }

}
