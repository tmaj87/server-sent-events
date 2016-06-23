<?php

require_once '../bb/db.php';

class onlineusers {
	private $db;
	public $table = 'onlineusers';
	
	function __construct() {
		global $dbh;
		$this->db = $dbh;
		$this->check_table();
	}
		
	function __destruct() {
		$this->db->close();
	}
	
	private function check_table() {
		$result = $this->db->query("SHOW TABLES LIKE '$this->table'");
		if(!$result->num_rows) {
			if(!$this->create_table()) {
				die('error: couldn\'t create table');
			}
		}
		$result->free();
	}

	private function create_table() {
		return $this->db->query("CREATE TABLE $this->table ("
				."id INT NOT NULL AUTO_INCREMENT,"
				."PRIMARY KEY(id),"
				."time INT,"
				."ip VARCHAR(200),"
				."info VARCHAR(200),"
				."visible INT"
			.") ENGINE=MyISAM DEFAULT CHARSET=utf8");
	}
	
	private function clear() {
		$this->db->query("DELETE FROM $this->table WHERE time<UNIX_TIMESTAMP()-3*24*60*60"); // more than 3days
		// ...or if COUNT(*) is greater than 1k?
		$this->db->query("UPDATE $this->table SET visible=0 WHERE time<UNIX_TIMESTAMP()-60 AND visible=1"); // more than 1min
		return $this->db->affected_rows;
	}
	
	function get() {
		$this->clear();
		$query = "SELECT COUNT(DISTINCT ip) FROM $this->table WHERE visible=1";
		$result = $this->db->query($query);
		$row = $result->fetch_row();
		$return = $row[0];
		$result->free();
		return $return;
	}
	
	private function check_set() {
		$ip = $this->get_ip();
		$result = $this->db->query("SELECT * FROM $this->table WHERE ip LIKE '$ip' AND time>UNIX_TIMESTAMP()-10"); // less than 10sec
		$return = $result->num_rows;
		$result->free();
		return $return;
	}
	
	function set() {
		if($this->check_set()) {
			return NULL;
		}
		$stmt = $this->db->prepare("INSERT INTO $this->table VALUES ('', UNIX_TIMESTAMP(), ?, ?, 1)");
		$stmt->bind_param('ss', ip::get_ip(), getenv('HTTP_USER_AGENT'));
		$stmt->execute();
		$return = $stmt->affected_rows;
		$stmt->close();
		return (bool)$return;
	}
}

$onlineusers = new onlineusers();
$onlineusers->set();
echo $onlineusers->get();