<?php

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
    public static function GetContentsInfo($currentDirectoryPath = 'Resource/Root/', $parentDirectoryPath = null)
    {

        //現在のディレクトリのコンテンツを取得(コンテンツの存在を担保するため)
        $currentDirectoryContents = glob($currentDirectoryPath . '*.txt');

        //子コンテンツのディレクトリリストを取得
        $childerenContentsDirectory = glob($currentDirectoryPath . '*/');


        //現在のディレクトリのコンテンツをキャッシュ
        foreach ($currentDirectoryContents as $currentDirectoryContent) {

            //pathの情報変換
            $path = str_replace('Resource/', '', $currentDirectoryPath);

            //親がいない時は確実にnullにセット
            if ($parentDirectoryPath == null) {
                $parent = null;
            } else {
                $parent = str_replace('Resource/', '', $parentDirectoryPath);
            }

            //ファイルのシステム時間を取得
            $fileSystemTime = fileatime($currentDirectoryContent);

            self::$contentDirectory[$path]=
            [
                'contentPath' => $currentDirectoryContent,
                'systemTime' => $fileSystemTime,
                'parent' => $parent
            ];

            //子ディレクトリがあるときは、再帰的に処理。
            if (!empty($childerenContentsDirectory)) {
                foreach ($childerenContentsDirectory as $childContentsDirectory) {
                    self::GetContentsInfo($childContentsDirectory, $currentDirectoryPath);
                }
            }
        }

        return self::$contentDirectory;
    }

    /**
     * コンテンツのディレクトリ構造をtxtで保存
     * @param $contentsDirectoryStructure コンテンツのディレクトリ構造を持った配列
     */
    public static function SaveContentsInfo($contentsDirectoryStructure)
    {
        $jsonText = json_encode($contentsDirectoryStructure);
        file_put_contents($_SERVER['DOCUMENT_ROOT'] . '/../Cache/ContentsDirectoryStructure.json', $jsonText);
    }

    //END ファイル構造をキャッシュする部分======================================================================================








    //キャッシュからDBに格納する部分============================================================================================

    /**
     * jsonテキストで保存されているコンテンツのディレクトリ構造を取得
     * @return $contentsDirectoryStructure jsonからデコードしたコンテンツ構造
     */
    public static function GetSavedContentsInfo()
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
     * コンテンツの差分を取得
     * @param $contents1
     * @param $contents2
     * @return $contents $contents1、$contents2ともに存在するがメタタグが違う配列
     */
    
    public static function ContentsInfoDiff($contents1, $contents2)
    {
        $contentsDiff = [];
        foreach ($contents1 as $content1Key => $content1) {

            //コンテンツ1と同じキーを持つ配列を取得
            if (array_key_exists($content1Key, $contents2)) {
                $content2 = $contents2[$content1Key];
            } else {
                continue;
            }

            //コンテンツのメタタグを比較
            $contentDiff = array_diff_assoc($content1, $content2);

            if (!empty($contentDiff)) {
                $contentsDiff[$content1Key] = $content1;
            }
        }
        return $contentsDiff;
    }
}