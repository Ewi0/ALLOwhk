<?php
class Database {
    private $mysqli;

    public function __construct() {
        $this->mysqli = new mysqli("localhost", "root", "", "pbase");
        if ($this->mysqli->connect_error) {
            die("Ошибка подключения к БД: " . $this->mysqli->connect_error);
        }
    }

    public function getConnection() {
        return $this->mysqli;
    }
}