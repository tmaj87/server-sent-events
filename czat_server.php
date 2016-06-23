<?php

/*
 * Nowe funkcnolaności do wprowadzenia:
 *  - komendy, np: user:abc;pass:123, kick:[hash]
 *  - zastrzeżone nazwy użytkowników
 *  - możliwość wyciszania powiadomień o wiadomościach od użytkowników
 *  - obsługa eventu zamknięcia (przez serwer) połączenia
 */

require_once '../bb/db.php';

class core {
	protected $db;
	protected $user;
	protected $czat_u = 'czat_users';
	protected $czat_m = 'czat_messages';
	
	public function __construct() {
		global $dbh;
		$this->db = $dbh;
		$this->user = hash('sha1', $_SERVER['REMOTE_ADDR'].$_SERVER['HTTP_USER_AGENT'].'9Gt:t5u$gkkrG8Oar,xCfylhIGn7lxXs');
	}
	
	public function getHash($hash) {
		return substr($hash, 0, 16);
	}
}

class controller extends core {
	public function __construct() {
		parent::__construct();
		$this->redirect();
	}
	
        private function inputFilter($variable) {
            return filter_input(INPUT_POST, "$variable", FILTER_SANITIZE_STRING);
        }
        
	private function redirect() {
		$n = $this->inputFilter('n');
		$m = $this->inputFilter('m');
		$c = $this->inputFilter('c');
		if (strlen($n) && strlen($n) < 60 && strlen($m) && strlen($m) < 200 && strlen($c) == 6) {
			// INSERT INTO czat_messages (nick, color, message, hash) VALUES ('stefan', 'ffffff', 'o jeeee', '123')
			$this->notSoFast();
			$stmt = $this->db->prepare("INSERT INTO $this->czat_m (nick, color, message, hash) VALUES (?, ?, ?, '$this->user')");
			$stmt->bindParam(1, $n, PDO::PARAM_STR, 60);
			$stmt->bindParam(2, $c, PDO::PARAM_STR, 6);
			$stmt->bindParam(3, $m, PDO::PARAM_STR, 200);
			$stmt->execute();
			exit;
		}
				
		$iam = $this->inputFilter('iam');
		if (!empty($iam)) {
			echo $this->getHash($this->user);
			exit;
		}
	}
	
	private function notSoFast() {
		$obj = $this->db->query("SELECT TIMESTAMPDIFF(SECOND, time, NOW()) diff FROM $this->czat_m WHERE hash = '$this->user' ORDER BY id DESC LIMIT 1")->fetch(PDO::FETCH_OBJ);
		if (!empty($obj) && intval($obj->diff) < 1) {
			echo 'nop';
			exit;
		}
	}
}

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
		echo 'id: '.$pointer.PHP_EOL;
		echo 'data: {"n":"tmaj","h":"admin","c":"5cb85c","m":"Witaj na Czacie, instrukcja:<br><i>Po lewej stronie znajduje się pole na <b>nicka</b> po prawej wpisz <b>wiadomość</b>,</i> miłej zabawy..!","t":"undefined"}'.PHP_EOL;
	}
	
	private function justNothing($pointer) {
		echo 'id: '.$pointer.PHP_EOL;
		echo 'data: {"n":"tmaj","h":"admin","c":"5cb85c","m":"Zzz...","t":"undefined"}'.PHP_EOL;
	}
	
	private function printMessage($messageObj) {
		echo 'id: '.$messageObj->id.PHP_EOL;
		echo 'data: {"n":"'.$messageObj->nick.'","h":"'.$this->getHash($messageObj->hash).'","c":"'.$messageObj->color.'","m":"'.$messageObj->message.'","t":"'.$messageObj->time.'"}'.PHP_EOL;
	}
	
	private function messageEvent() {
		$pointer = $this->getMyLastPost();
		if ($pointer == NULL) {
			$this->saveMyPresence();
			$this->helloMessage();
			$this->saveMyPostId(0);
		} else {
			$obj = $this->db->query("SELECT id, nick, color, message, DATE_FORMAT(time, '%H:%i:%s %d/%m/%Y') time, hash FROM $this->czat_m WHERE id > $pointer ORDER BY id ASC LIMIT 1")->fetch(PDO::FETCH_OBJ);
			if (!empty($obj)) {
				$this->speedUp();
				$this->printMessage($obj);
				$this->saveMyPostId($obj->id);
			} else {
				$this->slowDown();
				$this->justNothing($pointer);
			}
		}
	}
	
	private function saveMyPostId($postId) {
		$this->db->query("UPDATE $this->czat_u SET lastpost = $postId WHERE hash = '$this->user'");
	}
	
	private function usersEvent() {
		$str = '';
		$users = $this->db->query("SELECT id, hash FROM $this->czat_u WHERE TIMESTAMPDIFF(SECOND, lastcheckin, NOW()) < 6")->fetchAll(PDO::FETCH_OBJ);
		if (empty($users)) {
                    return;
                }
                foreach ($users as $k=>$v) {
                        $nick = $this->db->query("SELECT nick, color FROM $this->czat_m WHERE hash = '$v->hash' ORDER BY id DESC LIMIT 1")->fetch(PDO::FETCH_OBJ);
                        if (!empty($nick)) {
                                $str .= '"'.$nick->nick.'":"'.$nick->color.'",';
                        } else {
                                $str .= '"'.$this->getHash($v->hash).'":"",';
                        }
                }
                echo PHP_EOL.'event: users'.PHP_EOL;
                echo 'data: {'.substr($str, 0, -1).'}'.PHP_EOL;
	}
	
	private function checkIn() {
		$this->db->query("UPDATE $this->czat_u SET lastcheckin = NOW() WHERE hash = '$this->user'");
	}
	
	private function getMyLastPost() { // !! not so safe? !!
		return $this->db->query("SELECT lastpost FROM $this->czat_u WHERE hash = '$this->user'")->fetch(PDO::FETCH_OBJ)->lastpost;
	}
	
	private function saveMyPresence() {
		$this->db->query("INSERT INTO $this->czat_u (hash) VALUES ('$this->user')");
	}

	private function clearGarbage() {
		$time_m = 360;
		$time_u = 30;
		//dev mode:
		//$time_m = $time_u = 10;
		$this->db->query("DELETE FROM $this->czat_m WHERE TIMESTAMPDIFF(SECOND, time, NOW()) > $time_m");
		$this->db->query("DELETE FROM $this->czat_u WHERE TIMESTAMPDIFF(SECOND, lastcheckin, NOW())> $time_u");
	}

	private function finish() {
		echo PHP_EOL;
		ob_flush();
		flush();
	}
	
	private function slowDown() {
		echo 'retry: 1000'.PHP_EOL;
	}
	
	private function speedUp() {
		echo 'retry: 200'.PHP_EOL;
	}
}

$controller = new controller();
$server = new server();