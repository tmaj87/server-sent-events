<?php

class server extends core {

    private static $MAX_TIME_FOR_USER = 30;
    private static $MAX_TIME_FOR_MESSAGE = 360;

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

    private function helloMessage($currentId) {
        $this->saveMyPostId(1);
        echo 'id: ' . $currentId . PHP_EOL;
        echo 'data: {"n":"tmaj","h":"admin","c":"5cb85c","m":"Witaj na Czacie, instrukcja:<br><i>Po lewej stronie znajduje się pole na <b>nicka</b> po prawej wpisz <b>wiadomość</b>,</i> miłej zabawy..!","t":"undefined"}' . PHP_EOL;
    }

    private function zzzMessage($currentId) {
        echo 'id: ' . $currentId . PHP_EOL;
        echo 'data: {"n":"tmaj","h":"admin","c":"5cb85c","m":"Zzz...","t":"undefined"}' . PHP_EOL;
    }

    private function printMessage($messageObj) {
        $this->saveMyPostId($messageObj->id);
        echo 'id: ' . $messageObj->id . PHP_EOL;
        echo 'data: {"n":"' . $messageObj->nick . '","h":"' . $this->getHash($messageObj->hash) . '","c":"' . $messageObj->color . '","m":"' . $messageObj->message . '","t":"' . $messageObj->time . '"}' . PHP_EOL;
    }

    private function messageEvent() {
        $lastPostId = $this->getMyLastPost();
        if ($lastPostId < 1) {
            $this->helloMessage();
        } else {
            $sql = "SELECT id, nick, color, message, DATE_FORMAT(time, '%H:%i:%s %d/%m/%Y') time, hash FROM $this->czat_m WHERE id > $lastPostId ORDER BY id ASC LIMIT 1";
            $newPosts = $this->query();
            if (!empty($newPosts)) {
                $this->speedUp();
                $this->printMessage($newPosts);
            } else {
                $this->slowDown();
                $this->zzzMessage();
            }
        }
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

    private function usersEvent() { // Uncle Bob me
        $str = '';
        $sql = "SELECT id, hash FROM $this->czat_u WHERE TIMESTAMPDIFF(SECOND, lastcheckin, NOW()) < 6";
        $users = $this->query($sql);
        if (empty($users)) {
            return;
        }
        foreach ($users as $k => $v) {
            $sql = "SELECT nick, color FROM $this->czat_m WHERE hash = '$v->hash' ORDER BY id DESC LIMIT 1";
            $nick = $this->query($sql);
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
        return $this->query($sql)->id;
    }

    private function getMyLastPost() {
        $sql = "SELECT lastpost FROM $this->czat_u WHERE hash = '$this->user'";
        $result = $this->db->query($sql);
        if (empty($result)) {
            return 0;
        }
        return $result->fetch(PDO::FETCH_OBJ)->lastpost;
    }

    private function saveMyPresence() { // redesign this one
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
