<?php

/*
 * Nowe funkcnolaności do wprowadzenia:
 *  - komendy, np: user:abc;pass:123, kick:[hash]
 *  - zastrzeżone nazwy użytkowników
 *  - możliwość wyciszania powiadomień o wiadomościach od użytkowników
 *  - obsługa eventu zamknięcia (przez serwer) połączenia
 */

require_once 'header.php';

$controller = new controller();
$server = new server();
