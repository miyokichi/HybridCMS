<?php

require_once 'OutlineParser.php';
//require_once 'DatabaseConnecter.php';

class FileDbSynchronizer
{
    private static $contentDirectory = [];
    private static $resourceDirectory = 'Resource/Root/';
    //private static $latestSystemUpdateTime = 0;


    //ファイル構造をキャッシュする部分=========================================================================================

    /**
     * 再帰的にコンテンツのディレクトリ構造を取得、配列化
     * @param $currentDirectoryPath 親のディレクトリのパス
     * @return $tempContentDirectory 最終的なディレクトリ構造（配列）
     */
    public static function GetContentsDirectryStructure($currentDirectoryPath = 'Resource/Root/', $parentDirectoryPath = null)
    {

        //現在のディレクトリのコンテンツを取得(コンテンツのぞんざいを担保するため)
        $currentDirectoryContents = glob($currentDirectoryPath . '*.txt');

        //子コンテンツのディレクトリリストを取得
        $childerenContentsDirectory = glob($currentDirectoryPath . '*/');
        //var_dump($currentDirectoryPath);


        //現在のディレクトリのコンテンツをキャッシュ
        foreach ($currentDirectoryContents as $currentDirectoryContent) {
            $metaTag = [
                'path' => '',
                'title' => '',
                'abstract' => '',
                'create_at' => '',
                'update_at' => '',
            ];

            //コンテンツの情報を取得
            /*
            $htmlContent = '';
            */
            $content = file_get_contents($currentDirectoryContent);
            if ($content) {
                OutlineParser::Initialize($content);
                $metaTag = OutlineParser::MetaParse();
            }
            

            //pathの情報変換
            $path = str_replace('Resource/', '', $currentDirectoryPath);

            //親がいない時は確実にnullにセット
            if($parentDirectoryPath == null){
                $parent = null;
            }else{
                $parent = str_replace('Resource/', '', $parentDirectoryPath);
            }
            
            self::$contentDirectory[$path]=
                [
                    'path' => $path,
                    'title' => $metaTag['title'],
                    'abstract' => $metaTag['abstract'],
                    'create_at' => str_replace('/', '-', $metaTag['create_at']),
                    'update_at' => str_replace('/', '-', $metaTag['update_at']),
                    'parent' => $parent
                ];
        

            //子ディレクトリがあるときは、再帰的に処理。
            if (!empty($childerenContentsDirectory)) {
                foreach ($childerenContentsDirectory as $childContentsDirectory) {
                    self::GetContentsDirectryStructure($childContentsDirectory, $currentDirectoryPath);
                }
            }
        }

        return self::$contentDirectory;
    }

    /**
     * コンテンツのディレクトリ構造をtxtで保存
     * @param $contentsDirectoryStructure コンテンツのディレクトリ構造を持った配列
     */
    public static function SaveContentsDirectoryStructure($contentsDirectoryStructure)
    {
        $jsonText = json_encode($contentsDirectoryStructure);
        file_put_contents($_SERVER['DOCUMENT_ROOT'] . '/../Cache/ContentsDirectoryStructure.json', $jsonText);
    }

    //END ファイル構造をキャッシュする部分======================================================================================








    //キャッシュからDBに格納する部分============================================================================================

    /**
     * キャッシュと現在のディレクトリ構造の差分からデータベースへコンテンツ情報を反映
     * @param $contentsDiff キャッシュと現在のコンテンツのディレクトリ構造の差分
     */
    /*
    public static function SyncronizeCacheToDatabase($contentsDiff)
    {
        $pdo = new DatabaseConnecter;

        $pdo->beginTransaction();

        foreach ($contentsDiff as $content) {
            $sql = "INSERT contents (path, title, abstract, create_at, update_at) Value (:path, :title, :abstract, :create_at, :update_at)";

            $stm = $pdo->prepare($sql);

            $stm->bindValue(":path", $content["path"], PDO::PARAM_STR);
            $stm->bindValue(":title", $content["title"], PDO::PARAM_STR);
            $stm->bindValue(":abstract", $content["abstract"], PDO::PARAM_STR);
            $stm->bindValue(":create_at", $content["create_at"], PDO::PARAM_STR);
            $stm->bindValue(":update_at", $content["update_at"], PDO::PARAM_STR);

            $stm->execute();
        }

        $pdo->commit();

    }
    */

    /**
     * jsonテキストで保存されているコンテンツのディレクトリ構造を取得
     * @return $contentsDirectoryStructure jsonからデコードしたコンテンツ構造
     */
    public static function GetSavedContentsDirectryStructure()
    {
        if (file_exists($_SERVER['DOCUMENT_ROOT'] . '/../Cache/ContentsDirectoryStructure.json')) {
            $jsonText = file_get_contents($_SERVER['DOCUMENT_ROOT'] . '/../Cache/ContentsDirectoryStructure.json');
            $contentsDirectoryStructure = json_decode($jsonText, $assoc = true);
        } else {
            $contentsDirectoryStructure = [];
        }
        return $contentsDirectoryStructure;
    }


    //END キャッシュからDBに格納する部分========================================================================================

    /**
     * 
     * @param $contents1
     * @param $contents2
     * @return $contents $contents1、$contents2ともに存在するがメタタグが違う配列
     */
    
    public static function ContentsMetatagDiff($contents1, $contents2)
    {
        $contentsDiff = [];
        foreach ($contents1 as $content1Key => $content1) {

            //コンテンツ1と同じキーを持つ配列を取得
            if (array_key_exists($content1Key, $contents2)) {
                $content2 = $contents2[$content1Key];
            }else{
                continue;
            }

            //コンテンツのメタタグを比較
            $contentDiff = array_diff_assoc($content1, $content2);
            //var_dump($contentDiff);
            //echo 'fefdsafds';

            if(!empty($contentDiff)){
                $contentsDiff[$content1Key] = $content1;
            }
        }
        return $contentsDiff;
    }
    
}
