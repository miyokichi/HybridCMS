<?php
//start timer--------------------------------------------------------------------------------
$time_start = microtime(true);
//-------------------------------------------------------------------------------------------


// エラーを出力する
ini_set('display_errors', "On");



echo '<pre>';
require_once $_SERVER['DOCUMENT_ROOT'] . '/../Model/FileDbSynchronizer.php';


$contentsDirectoryStructure = FileDbSynchronizer::GetContentsDirectryStructure('Resource/Root/');
FileDbSynchronizer::SaveContentsDirectoryStructure($contentsDirectoryStructure);
//$oldContentsDirectoryStructure = FileDbSynchronizer::GetSavedContentsDirectryStructure();

//$diff = FileDbSynchronizer::DiffKeyDirectoryStructure($contentsDirectoryStructure, $oldContentsDirectoryStructure);
//var_dump($diff);


//差分を求める



//コンテンツの差分をデータベースに反映
/*
$contents = FileDbSynchronizer::GetSavedContentsDirectryStructure();
FileDbSynchronizer::SyncronizeCacheToDatabase($contents);
*/


echo '</pre>';



//stop timer------------------------------------------------------------------------
$time = microtime(true) - $time_start;
echo "{$time} 秒";