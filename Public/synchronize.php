<?php
//時間測定
$time_start = microtime(true);

require_once $_SERVER['DOCUMENT_ROOT'] . '/../Model/FileDbSynchronizer.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/../Model/DatabaseConnecter.php';


echo '<pre>';

//コンテンツのディレクトリ構造を調べる
$contentsDirectoryStructure = FileDbSynchronizer::GetContentsDirectryStructure();


//キャッシュから、古いディレクトリ構造を取得
$oldContentsDirectoryStructure = FileDbSynchronizer::GetSavedContentsDirectryStructure();

//キャッシュに新しいディレクトリ構造を保存
FileDbSynchronizer::SaveContentsDirectoryStructure($contentsDirectoryStructure);



//キャッシュのディレクトリ構造と現在のディレクトリ構造が同じか調べる
//コンテンツが新規作成されたときの差分
$newContents = array_diff_key($contentsDirectoryStructure, $oldContentsDirectoryStructure);
//コンテンツのメタタグが編集されたときの差分
$updateContents = FileDbSynchronizer::ContentsMetatagDiff($contentsDirectoryStructure, $oldContentsDirectoryStructure);
//コンテンツが削除されたときの差分
$deleteContents = array_diff_key($oldContentsDirectoryStructure, $contentsDirectoryStructure);




var_dump($contentsDirectoryStructure);
echo '<br>';
var_dump($oldContentsDirectoryStructure);
echo '<br>';

echo 'new=====================================<br>';
var_dump($newContents);
echo 'update==================================<br>';
var_dump($updateContents);
echo 'delete==================================<br>';
var_dump($deleteContents);



//コンテンツの差分をデータベースに反映
$pdo = new DatabaseConnecter;
$pdo->beginTransaction();
//コンテンツの新規作成があった場合
if (!empty($newContents)) {
    foreach ($newContents as $newContent) {
        $sql = "INSERT INTO contents (path, title, abstract, create_at, update_at, parent) Value (:path, :title, :abstract, :create_at, :update_at, :parent)";

        $stm = $pdo->prepare($sql);

        $stm->bindValue(":path", $newContent["path"], PDO::PARAM_STR);
        $stm->bindValue(":title", $newContent["title"], PDO::PARAM_STR);
        $stm->bindValue(":abstract", $newContent["abstract"], PDO::PARAM_STR);
        $stm->bindValue(":create_at", $newContent["create_at"], PDO::PARAM_STR);
        $stm->bindValue(":update_at", $newContent["update_at"], PDO::PARAM_STR);
        $stm->bindValue(":parent", $newContent["parent"], PDO::PARAM_STR);

        $stm->execute();
        echo 'dsadsafdsaf' . $newContent["path"];
    }
}


//コンテンツの編集があった場合
if (!empty($updateContents)) {
    foreach ($updateContents as $updateContent) {
        $sql = "UPDATE contents SET title=:title, abstract=:abstract, create_at=:create_at, update_at=:update_at, parent=:parent WHERE path=:path";

        $stm = $pdo->prepare($sql);

        $stm->bindValue(":path", $updateContent["path"], PDO::PARAM_STR);
        $stm->bindValue(":title", $updateContent["title"], PDO::PARAM_STR);
        $stm->bindValue(":abstract", $updateContent["abstract"], PDO::PARAM_STR);
        $stm->bindValue(":create_at", $updateContent["create_at"], PDO::PARAM_STR);
        $stm->bindValue(":update_at", $updateContent["update_at"], PDO::PARAM_STR);
        $stm->bindValue(":parent", $updateContent["parent"], PDO::PARAM_STR);



        $stm->execute();
    }
}

//コンテンツの削除があった場合
if (!empty($deleteContents)) {
    foreach ($deleteContents as $deleteContent) {
        $sql = "DELETE FROM contents WHERE path=:path";

        $stm = $pdo->prepare($sql);

        $stm->bindValue(":path", $deleteContent["path"], PDO::PARAM_STR);

        $stm->execute();
    }
}

$pdo->commit();


echo '</pre>';
echo '処理が終わりました。';


//stop timer------------------------------------------------------------------------
$time = microtime(true) - $time_start;
echo "{$time} 秒";