<?php
//時間測定
$time_start = microtime(true);

require_once $_SERVER['DOCUMENT_ROOT'] . '/../Model/OutlineParser.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/../Model/FileDbSynchronizer.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/../Model/DatabaseConnecter.php';


echo '<pre>';


//get new and old contents information =================================================================
//コンテンツのディレクトリ構造を調べる
$contentsInfo = FileDbSynchronizer::GetContentsInfo();

//キャッシュから、古いディレクトリ構造を取得
$oldContentsInfo = FileDbSynchronizer::GetSavedContentsInfo();

//キャッシュに新しいディレクトリ構造を保存
FileDbSynchronizer::SaveContentsInfo($contentsInfo);
//END get new and old contents information =================================================================



//judge whether new and old contents info is same ======================================================

//キャッシュのディレクトリ構造と現在のディレクトリ構造が同じか調べる
//コンテンツが新規作成されたときの差分
$newContentsInfo = array_diff_key($contentsInfo, $oldContentsInfo);
//コンテンツのメタタグが編集されたときの差分
$updateContentsInfo = FileDbSynchronizer::ContentsInfoDiff($contentsInfo, $oldContentsInfo);
//コンテンツが削除されたときの差分
$deleteContentsInfo = array_diff_key($oldContentsInfo, $contentsInfo);

//END judge whether new and old contents info is same ======================================================



//get contents metatag if there is changed contents=====================================================

$newContents = [];
$updateContents = [];

if(!empty($newContentsInfo)){
    foreach ($newContentsInfo as $newContentInfoKey => $newContentInfo) {
        OutlineParser::Initialize($newContentInfo['contentPath']);
        $newContents[$newContentInfoKey] = OutlineParser::MetaParse();
        $newContents[$newContentInfoKey]['content'] = OutlineParser::ContentParse();
        $newContents[$newContentInfoKey]['parent'] = $newContentInfo['parent'];
    }
}

if(!empty($updateContentsInfo)){
    foreach ($updateContentsInfo as $updateContentInfoKey => $updateContentInfo) {
        OutlineParser::Initialize($updateContentInfo['contentPath']);
        $updateContents[$updateContentInfoKey] = OutlineParser::MetaParse();
        $updateContents[$updateContentInfoKey]['content'] = OutlineParser::ContentParse();
        $updateContents[$updateContentInfoKey]['parent'] = $updateContentInfo['parent'];
    }
}

//END get contents metatag if there is changed contents=====================================================



var_dump($contentsInfo);
echo '<br>';
var_dump($oldContentsInfo);
echo '<br>';

echo 'new=====================================<br>';
var_dump($newContents);
echo 'update==================================<br>';
var_dump($updateContents);
echo 'delete==================================<br>';
var_dump($deleteContentsInfo);



//コンテンツの差分をデータベースに反映
$pdo = new DatabaseConnecter;
$pdo->beginTransaction();
//コンテンツの新規作成があった場合
if (!empty($newContents)) {
    foreach ($newContents as $newContentPath => $newContent) {
        $sql = "INSERT INTO contents (path, title, abstract, create_at, update_at, parent, content) Value (:path, :title, :abstract, :create_at, :update_at, :parent, :content)";

        $stm = $pdo->prepare($sql);

        $stm->bindValue(":path", $newContentPath, PDO::PARAM_STR);
        $stm->bindValue(":title", $newContent["title"], PDO::PARAM_STR);
        $stm->bindValue(":abstract", $newContent["abstract"], PDO::PARAM_STR);
        $stm->bindValue(":create_at", $newContent["create_at"], PDO::PARAM_STR);
        $stm->bindValue(":update_at", $newContent["update_at"], PDO::PARAM_STR);
        $stm->bindValue(":parent", $newContent["parent"], PDO::PARAM_STR);
        $stm->bindValue(":content", $newContent["content"], PDO::PARAM_STR);

        $stm->execute();
        //echo 'dsadsafdsaf' . $newContent["path"];
    }
}


//コンテンツの編集があった場合
if (!empty($updateContents)) {
    foreach ($updateContents as $updateContentPath => $updateContent) {
        $sql = "UPDATE contents SET title=:title, abstract=:abstract, create_at=:create_at, update_at=:update_at, parent=:parent, content=:content WHERE path=:path";

        $stm = $pdo->prepare($sql);

        $stm->bindValue(":path", $updateContentPath, PDO::PARAM_STR);
        $stm->bindValue(":title", $updateContent["title"], PDO::PARAM_STR);
        $stm->bindValue(":abstract", $updateContent["abstract"], PDO::PARAM_STR);
        $stm->bindValue(":create_at", $updateContent["create_at"], PDO::PARAM_STR);
        $stm->bindValue(":update_at", $updateContent["update_at"], PDO::PARAM_STR);
        $stm->bindValue(":parent", $updateContent["parent"], PDO::PARAM_STR);
        $stm->bindValue(":content", $updateContent["content"], PDO::PARAM_STR);



        $stm->execute();
    }
}

//コンテンツの削除があった場合
if (!empty($deleteContentsInfo)) {
    foreach ($deleteContentsInfo as $deleteContentInfoPath =>$deleteContentInfo) {
        $sql = "DELETE FROM contents WHERE path=:path";

        $stm = $pdo->prepare($sql);

        $stm->bindValue(":path", $deleteContentInfoPath, PDO::PARAM_STR);

        $stm->execute();
    }
}

$pdo->commit();


echo '</pre>';
echo '処理が終わりました。';



//stop timer------------------------------------------------------------------------
$time = microtime(true) - $time_start;
echo "{$time} 秒";