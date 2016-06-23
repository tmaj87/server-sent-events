<?php

class server extends core {

    public function __construct() {
        parent::__construct();
        $this->clearGarbage();
        $this->addHeaders();
        $this->checkIn();
        $this->messageEvent();
        $this->usersEvent();
        $this->finish();
    }

    private function addHeaders() {
        header('Content-Type: text/event-stream');
        header('Cache-Control: no-cache');
    }

    private function helloMessage($pointer) {
        echo 'id: ' . $pointer . PHP_EOL;
        echo 'data: {"n":"tmaj","h":"admin","c":"5cb85c","m":"Witaj na Czacie, instrukcja:<br><i>Po lewej stronie znajduje się pole na <b>nicka</b> po prawej wpisz <b>wiadomość</b>,</i> miłej zabawy..!","t":"undefined"}' . PHP_EOL;
    }

    private function justNothing($pointer) {
        echo 'id: ' . $pointer . PHP_EOL;
        echo 'data: {"n":"tmaj","h":"admin","c":"5cb85c","m":"Zzz...","t":"undefined"}' . PHP_EOL;
    }

    private function printMessage($messageObj) {
        echo 'id: ' . $messageObj->id . PHP_EOL;
        echo 'data: {"n":"' . $messageObj->nick . '","h":"' . $this->getHash($messageObj->hash) . '","c":"' . $messageObj->color . '","m":"' . $messageObj->message . '","t":"' . $messageObj->time . '"}' . PHP_EOL;
    }

    private function messageEvent() {
        $lastPostId = $this->getMyLastPost();
        if (empty($lastPostId)) {
            $this->iAmNew();
        } else {
            $newPosts = $this->db->query("SELECT id, nick, color, message, DATE_FORMAT(time, '%H:%i:%s %d/%m/%Y') time, hash FROM $this->czat_m WHERE id > $lastPostId ORDER BY id ASC LIMIT 1")->fetch(PDO::FETCH_OBJ);
            if (!empty($newPosts)) {
                $this->speedUp();
                $this->printMessage($newPosts);
                $this->saveMyPostId($newPosts->id);
            } else {
                $this->slowDown();
                $this->justNothing($lastPostId);
            }
        }
    }

    private function iAmNew() {
        $this->saveMyPresence();
        $this->helloMessage();
        $this->saveMyPostId(0);
    }

    private function query($sql) {
        $result = $this->db->query($sql);
        if (!($result instanceof PDOStatement)) {
            return FALSE;
        }
        return $result->fetch(PDO::FETCH_OBJ);
    }

    private function saveMyPostId($postId) {
        $sql = "UPDATE $this->czat_u SET lastpost = $postId WHERE hash = '$this->user'";
        $this->db->query($sql);
    }

    private function usersEvent() {
        $str = '';
        $sql = "SELECT id, hash FROM $this->czat_u WHERE TIMESTAMPDIFF(SECOND, lastcheckin, NOW()) < 6";
        $users = $this->query($sql);
        if (empty($users)) {
            return;
        }
        foreach ($users as $k => $v) {
            $sql = "SELECT nick, color FROM $this->czat_m WHERE hash = '$v->hash' ORDER BY id DESC LIMIT 1";
            $nick = $this->db->query($sql)->fetch(PDO::FETCH_OBJ);
            if (!empty($nick)) {
                $str .= '"' . $nick->nick . '":"' . $nick->color . '",';
            } else {
                $str .= '"' . $this->getHash($v->hash) . '":"",';
            }
        }
        echo PHP_EOL . 'event: users' . PHP_EOL;
        echo 'data: {' . substr($str, 0, -1) . '}' . PHP_EOL;
    }

    private function checkIn() {
        $sql = "UPDATE $this->czat_u SET lastcheckin = NOW() WHERE hash = '$this->user'";
        $this->db->query($sql);
    }

    private function getMyLastPost() { // !! not so safe? !!
        $sql = "SELECT lastpost FROM $this->czat_u WHERE hash = '$this->user'";
        $result = $this->db->query($sql);
        if (empty($result)) {
            return false;
        }
        return $result->fetch(PDO::FETCH_OBJ)->lastpost;
    }

    private function saveMyPresence() {
        $sql = "INSERT INTO $this->czat_u (hash) VALUES ('$this->user')";
        $this->db->query($sql);
    }

    private function clearGarbage() {
        $time_m = 360;
        $time_u = 30;
        //
        //dev mode:
        $time_m = $time_u = 10;
        //
        $this->db->query("DELETE FROM $this->czat_m WHERE TIMESTAMPDIFF(SECOND, time, NOW()) > $time_m");
        $this->db->query("DELETE FROM $this->czat_u WHERE TIMESTAMPDIFF(SECOND, lastcheckin, NOW())> $time_u");
    }

    private function finish() {
        echo PHP_EOL;
        ob_flush();
        flush();
    }

    private function slowDown() {
        echo 'retry: 1000' . PHP_EOL;
    }

    private function speedUp() {
        echo 'retry: 200' . PHP_EOL;
    }

}
