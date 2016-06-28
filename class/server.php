<?php

class server extends core {

    private static $MAX_TIME_FOR_USER = 30;
    private static $MAX_TIME_FOR_MESSAGE = 360;

    public function __construct() {
        $this->clearGarbage();
        $this->addHeaders();
        $this->checkIn();
        $this->handleMessages();
        $this->printAllUsers();
        $this->finish();
    }

    private function addHeaders() {
        header('Content-Type: text/event-stream');
        header('Cache-Control: no-cache');
    }

    private function helloMessage($currentId) {
        $this->saveMyPostId(1);
        echo 'id: ' . $currentId . PHP_EOL;
        echo 'data: {"n":"tmaj","h":"admin","c":"5cb85c","m":"Witaj na Czacie, instrukcja:<br><i>Po lewej stronie znajduje się pole na <b>nicka</b> po prawej wpisz <b>wiadomość</b>,</i> miłej zabawy..!","t":"undefined"}' . PHP_EOL;
    }

    private function printMessage($messageObj) {
        $this->saveMyPostId($messageObj->id);
        echo 'id: ' . $messageObj->id . PHP_EOL;
        echo 'data: {"n":"' . $messageObj->nick . '","h":"' . $this->getHash($messageObj->hash) . '","c":"' . $messageObj->color . '","m":"' . $messageObj->message . '","t":"' . $messageObj->time . '"}' . PHP_EOL;
    }

    private function handleMessages() {
        $lastPostId = $this->getMyLastPost();
        if ($lastPostId < 1) {
            $this->helloMessage();
        } else {
            $this->checkForNews($lastPostId);
        }
    }

    public function checkForNews($id) {
        $sql = "SELECT id, nick, color, message, DATE_FORMAT(time, '%H:%i:%s %d/%m/%Y') time, hash FROM $this->czat_m WHERE id > $id ORDER BY id ASC LIMIT 1";
        $message = $this->db->query($sql);
        if (!empty($message)) {
            $this->speedUp();
            $this->printMessage($message);
        } else {
            $this->slowDown();
        }
    }

    private function saveMyPostId($postId) {
        $sql = "UPDATE $this->czat_u SET lastpost = $postId WHERE hash = '$this->user'";
        $this->db->query($sql);
    }

    private function printAllUsers() {
        $str = '';
        $sql = "SELECT id, hash FROM $this->czat_u WHERE TIMESTAMPDIFF(SECOND, lastcheckin, NOW()) < 6";
        $users = $this->db->query($sql);
        if (empty($users)) {
            return;
        }
        
        foreach ($users as $k => $v) {
            $sql = "SELECT nick, color FROM $this->czat_m WHERE hash = '$v->hash' ORDER BY id DESC LIMIT 1";
            $nick = $this->db->query($sql);
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
        if ($this->getUserId()) {
            $this->saveMyPresence();
        }
        $sql = "UPDATE $this->czat_u SET lastcheckin = NOW() WHERE hash = '$this->user'";
        $this->db->query($sql);
    }

    private function getUserId() {
        $sql = "SELECT id FROM $this->czat_u WHERE hash = '$this->user'";
        return $this->db->query($sql)->id;
    }

    private function getMyLastPost() {
        $sql = "SELECT lastpost FROM $this->czat_u WHERE hash = '$this->user'";
        return $this->db->query($sql)->lastpost;
    }

    private function saveMyPresence() {
        $sql = "INSERT INTO $this->czat_u (hash) VALUES ('$this->user')";
        $this->db->query($sql);
    }

    private function clearGarbage() {
        $sql = "DELETE FROM $this->czat_m WHERE TIMESTAMPDIFF(SECOND, time, NOW()) > " + self::$MAX_TIME_FOR_USER;
        $this->db->query($sql);
        $sql = "DELETE FROM $this->czat_u WHERE TIMESTAMPDIFF(SECOND, lastcheckin, NOW()) > " + self::$MAX_TIME_FOR_MESSAGE;
        $this->db->query($sql);
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
