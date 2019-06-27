<?php

class DatabaseConnecter extends PDO
{

    private static $user ="username";
    private static $password = "password";
    private static $dbName = "dbname";
    private static $host = "host";

    //データベースに接続します
    function __construct()
    {
        $dsn = "mysql:host=".self::$host.";dbname=".self::$dbName.";charset=utf8";
        parent::__construct($dsn, self::$user, self::$password);
        $this->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
        $this->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }
}