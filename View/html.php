<?php

function html($paramArray){
echo '<!DOCTYPE html><html>';

//head============================================================================================
echo '
    <!-- エンコードの指定 -->
    <meta charset="utf-8">

    <!-- IEで常に標準モードで表示させる -->
    <meta http-equiv="X-UA-Compatible" content="IE=edge">

    <!-- 電話番号の自動リンク化を無効 -->
    <meta name="format-detection" content="telephone=no">

    <!-- viewport(レスポンシブ用) -->
    <meta name="viewport" content="width=device-width">';

    echo '<title>' . $paramArray['title'] . '</title>';

    echo '<meta name="description" content="' . $paramArray['abstract'] .'">';

    echo'
    <!-- faviconの指定 -->
    <link rel="icon" href="favicon.ico">

    <!-- その他、必要なファイルの読み込みなど -->
    <link rel="stylesheet" type="text/css" href="/css/style.css">
    <link href="https://fonts.googleapis.com/css?family=Anton&display=swap" rel="stylesheet">';



//body========================================================================================
//start header-----------------------------------------------------------------------------
echo '
<header><div id="webSiteTitle">miyokichi.net</div></header>
';

//end header--------------------------------------------------------------------------------

//start main contents------------------------------------------------------------------------

echo '<main><article>';

echo '<h1>' . $paramArray['title'] . '</h1>';
echo '作成日時：' . $paramArray['create_at'];
echo '更新日時：' . $paramArray['update_at'];
echo '<p>' . $paramArray['abstract'] .'</p>';

echo $paramArray['html_content'];

echo '<ul>';
for($i = 0;$i < count($paramArray['children']); $i++){
    echo '<li>' .'<a href=/'. $paramArray['children'][$i]['path'] . '>'. $paramArray['children'][$i]['title'] . '</a></li>';
}
echo '</ul>';

echo '</article></main>';

//end main contents-------------------------------------------------------------------------

//start footer-----------------------------------------------------------------------------


//end footer--------------------------------------------------------------------------------
echo '</html>';

}