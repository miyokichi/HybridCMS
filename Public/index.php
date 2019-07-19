<?php

require_once $_SERVER['DOCUMENT_ROOT'] . '/../View/html.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/../Model/DatabaseConnecter.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/../Model/OutlineParser.php';



//urlからコンテンツのパスを生成

if (array_key_exists('url', $_GET)) {
    $path = $_GET['url'];
    $urlParam = explode('/', $_GET['url']);
    $contentPath = 'Resource/' . $path . $urlParam[count($urlParam)-2] . '.txt';
} else {
    $path = 'Root/';
    $contentPath = 'Resource/Root/Root.txt';
}



//データベースからchildrenデータを取得
$pdo = new DatabaseConnecter;

$pdo->beginTransaction();

$sql = "SELECT * FROM contents WHERE path = :path";
$stm = $pdo->prepare($sql);
$stm->bindValue(":path", $path, PDO::PARAM_STR);
$stm->execute();
$contentParam = $stm->fetch(PDO::FETCH_ASSOC);

$children = array();
$sql = "SELECT * FROM contents WHERE parent = :path";
$stm = $pdo->prepare($sql);
$stm->bindValue(":path", $path, PDO::PARAM_STR);
$stm->execute();
$children = $stm->fetchAll(PDO::FETCH_ASSOC);

$pdo->commit();

//var_dump($contentParam);
//var_dump($children);

$paramArray = array_merge($contentParam, array('children' => $children));

html($paramArray);
