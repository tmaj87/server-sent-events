<?php

/*
 * Nowe funkcnolaności do wprowadzenia:
 *  - komendy, np: user:abc;pass:123, kick:[hash]
 *  - zastrzeżone nazwy użytkowników
 *  - możliwość wyciszania powiadomień o wiadomościach od użytkowników
 *  - obsługa eventu zamknięcia (przez serwer) połączenia
 */

require_once 'db.php';

require_once 'class/core.php';
require_once 'class/controller.php';
require_once 'class/server.php';


$controller = new controller();
$server = new server();
