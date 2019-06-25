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



//ファイルからコンテンツデータを取得
$content = file_get_contents($contentPath);
OutlineParser::Initialize($content);
$metaTag = OutlineParser::MetaParse();
$htmlContent = OutlineParser::ContentParse();

//データベースからchildrenデータを取得
$pdo = new DatabaseConnecter;

$pdo->beginTransaction();

$sql = "SELECT * FROM contents WHERE parent = :path";
$stm = $pdo->prepare($sql);
$stm->bindValue(":path", $path, PDO::PARAM_STR);
$stm->execute();
$childrenContents = $stm->fetchAll(PDO::FETCH_ASSOC);


$pdo->commit();

//var_dump($childrenContent);

$paramArray = [
    'title' => $metaTag['title'],
    'abstract' => $metaTag['abstract'],
    'create_at' => $metaTag['create_at'],
    'update_at' => $metaTag['update_at'],
    'children' => $childrenContents,
    'html_content' => $htmlContent
];

html($paramArray);
