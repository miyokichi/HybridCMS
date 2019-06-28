<?php
function html($paramArray)
{
?>
<!DOCTYPE html>

<html>
<head>

    <!-- エンコードの指定 -->
    <meta charset="utf-8">

    <!-- IEで常に標準モードで表示させる -->
    <meta http-equiv="X-UA-Compatible" content="IE=edge">

    <!-- 電話番号の自動リンク化を無効 -->
    <meta name="format-detection" content="telephone=no">

    <!-- viewport(レスポンシブ用) -->
    <meta name="viewport" content="width=device-width">

    <?php
    echo '<title>' . $paramArray['title'] . '</title>';
    echo '<meta name="description" content="' . $paramArray['abstract'] .'">'; ?>

    <!-- faviconの指定 -->
    <link rel="icon" href="favicon.ico">

    <!-- その他、必要なファイルの読み込みなど -->
    <link rel="stylesheet" type="text/css" href="/css/style.css">
    <link href="https://fonts.googleapis.com/css?family=Anton&display=swap" rel="stylesheet">
    <script type="text/javascript" src="/js/IndexGenerator.js"></script>
</head>

<body>
<header><div id="webSiteTitle">miyokichi.net</div></header>


<main>
<article>

<?php
    echo '<div id="meta-content">';
    echo '<h1>' . $paramArray['title'] . '</h1>';
    echo '<p>作成日時：' . $paramArray['create_at'] . '   ';
    echo '更新日時：' . $paramArray['update_at'] . '</p>';
    echo '<p>' . $paramArray['abstract'] .'</p>';
    echo '</div>';

    echo '<div id="index"><p>目次</p></div>';

    echo '<div id="main-content">';
    echo $paramArray['html_content'];
    echo '</div>'; ?>


<?php
    
    echo '<div id=children-contents-list>';
    for ($i = 0;$i < count($paramArray['children']); $i++) {
        echo '<a href=/'. $paramArray['children'][$i]['path'] . ' class="children-contents-list-item">'. $paramArray['children'][$i]['title'] . '</a>';
    }
    echo '</div>'; ?>

</article>
</main>

</html>

<?php
}
