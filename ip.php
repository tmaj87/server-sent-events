<?php

class ip {
    	public static function get_ip() {
		return getenv('REMOTE_ADDR');
//		return getenv('HTTP_CLIENT_IP')?:
//		getenv('HTTP_X_FORWARDED_FOR')?:
//		getenv('HTTP_X_FORWARDED')?:
//		getenv('HTTP_FORWARDED_FOR')?:
//		getenv('HTTP_FORWARDED')?:
//		getenv('REMOTE_ADDR');
	}
}
