<?php

class DatabaseConnecter extends PDO
{
    /*
    private static $user ="kohei";
    private static $password = "14142hitomigoro";
    private static $dbName = "hybridcms";
    private static $host = "localhost:3306";
    */

    private static $user ="miyokichi";
    private static $password = "14142hitomigoro";
    private static $dbName = "miyokichi_hybridcms";
    private static $host = "mysql618.db.sakura.ne.jp";

    //データベースに接続します
    function __construct()
    {
        $dsn = "mysql:host=".self::$host.";dbname=".self::$dbName.";charset=utf8";
        parent::__construct($dsn, self::$user, self::$password);
        $this->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
        $this->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }
}