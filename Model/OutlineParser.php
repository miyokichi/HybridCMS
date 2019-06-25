<?php

//namespace Model\Servide;

class OutlineParser
{
    /**　一つのインデントに対応するスペースの数 */
    const INDENT_SPACE = 4;
    /** インデントにマッチする正規表現（を格納する変数）*/
    private static $indentRegexp;

    /** 結果出力用 */
    private static $output;

    /** for meta tag */
    private static $metaTag = [
        'title' => '', 
        'abstract' => '',
        'create_at' => '',
        'update_at' => ''
    ];

    //関数テーブル関連========================================================================
    /** メタタグの関数テーブル格納用 */
    private static $metaTagFunctionTable = [];

    /** ブロックタグの関数テーブル格納用 */
    private static $blockTagFunctionTable = [];

    /** インラインタグの関数テーブル格納用 */
    private static $inlineTagFunctionTable = [];

    /**
     * 関数テーブル用のキー
     * * regexp 正規表現
     * * functionNmae 処理用関数名
     */
    private static $functionTableKeys = ['regexp', 'functionName'];

    private static $metaTagFunctionTableValues = [
        'title' => ['/^title:(.*)/', 'ProcessTitle'],
        'abstract' => ['/^abstract:(.*)/', 'ProcessAbstract'],
        'create_at' => ['/^create_at:(.*)/', 'ProcessCreatAt'],
        'update_at' => ['/^update_at:(.*)/', 'ProcessUpdateAt']
    ];

    /** ブロックタグの関数テーブル用の値 */
    private static $blockTagFunctionTableValues = [
        'section' => ['/^\#(.*)/', 'ProcessSection'],
        'ul' => ['/^\*(.*)/', 'ProcessUnorderedList'],
        'tl' => ['/^\+(.*)/', 'ProcessTreeList'],
        'ol' => ['/^[\d]*\.(.*)/', 'ProcessOrderedList'],
        'table' => ['/^\|(.*)/', 'ProcessTable'],
        'img' => ['/^!\[(.+)\]\((.*)\)/','ProcessImage'],
        //paragrahタグは一番最後に判別すること（すべてにマッチする。）
        'p' => ['/(.+)/', 'ProcessParagraph']
    ];

    /** インラインタグの関数テーブルの値 */
    private static $inlineTagFunctionTableValues = [
        'a' => ['/(.*)\[(.*)\]\((.*)\)(.*)/', 'ProcessLink']
    ];


    //END 関数テーブル関連========================================================================

    /** 関数用スタック */
    private static $functionStack = [];

    /** 改行で分割した行の格納用 */
    private static $lines;
    /** 行の最大値 */
    private static $lineCount;
    /** 現在の行数 */
    private static $lineNumber;

    /** インデントレベル（絶対値） */
    private static $indentLevel;
    /** 前の行のインデントレベル（絶対値） */
    private static $previousIndentLevel;

    /** 空白行かどうか */
    private static $isEmpty;
    /** 文書末かどうか */
    private static $isFileEnd;

    /** 現在処理中の文字列 */
    private static $subject;

    /** マッチングの前処理ができているかどうか */
    //private static $isPreprocessed;

    /** ブロックタグのスタック（最後の要素は現在いる階層のタグを表す） */
    private static $blockTagNameStack = [];
    /** ブロックタグのスタックの要素数 */
    private static $stackedBlockTagNameCount;

    /** 処理中の文字列一時保存用 */
    private static $tempOutput;


    public static function Initialize($text){
        //初期処理-------------------------------------------------------------
        self::$indentRegexp = '/^[ ]{' . strval(self::INDENT_SPACE) . '}/';

        self::$metaTagFunctionTable = self::ArraysCombine(self::$functionTableKeys, self::$metaTagFunctionTableValues);
        self::$blockTagFunctionTable = self::ArraysCombine(self::$functionTableKeys, self::$blockTagFunctionTableValues);
        self::$inlineTagFunctionTable = self::ArraysCombine(self::$functionTableKeys, self::$inlineTagFunctionTableValues);
        //var_dump(self::$blockTagFunctionTable);

        self::$indentLevel = 0;
        self::$previousIndentLevel = 0;

        self::$lines = explode("\n", $text);

        self::$lineCount = count(self::$lines);
        self::$lineNumber = 0;

        self::$isEmpty = false;
        self::$isFileEnd = false;

        self::$subject = '';

        self::$stackedBlockTagNameCount = 0;

        self::$tempOutput = '';

        self::$metaTag = [
            'title' => '', 
            'abstract' => '',
            'create_at' => '',
            'update_at' => ''
        ];

        //END 初期処理-----------------------------------------------------------
    }

    /**
     * メタデータのパース部分。
     */
    public static function MetaParse()
    {

        while(self::$lineNumber < self::$lineCount){
            self::PreprocessLine(self::$lineNumber);
            
            //空白ならメタデータ部分終わり
            if (self::$isEmpty) {
                self::$lineNumber++;
                break;
            }
            //空白でないなら、正規表現マッチング
            else{

                foreach (self::$metaTagFunctionTable as $metaTagName => $functionTableRow) {

                    if (preg_match($functionTableRow['regexp'], self::$subject, $matches)){
                        $functionName = $functionTableRow['functionName'];

                        self::$functionName('begin', $matches);
                        //self::PostprocessLine($tempOutput);
                    }
                }
                
                self::$lineNumber++;
            }
        }
        return self::$metaTag;
    }

    /**
     * パーサー処理のメイン部分。
     */
    public static function ContentParse()
    {
        $output = '';

        while (self::$lineNumber < self::$lineCount) {

            $tempOutput = '';

            //次の行の場合は前処理を行う
            self::PreprocessLine(self::$lineNumber);

            //空白行の時
            if (self::$isEmpty) {
                //次の行へ
                self::$lineNumber++;
                if (self::$stackedBlockTagNameCount > 0) {
                    $functionName = self::$blockTagFunctionTable[self::$blockTagNameStack[self::$stackedBlockTagNameCount - 1]]['functionName'];
                    $output .= self::$functionName('end');
                }
                continue;
            }

            //ブロックタグの関数テーブル検索
            foreach (self::$blockTagFunctionTable as $blockTagName => $functionTableRow) {

                $tempOutput = '';

                //ブロックタグの正規表現が各行に対してマッチしていた時
                if (preg_match($functionTableRow['regexp'], self::$subject, $matches)) {
                    $functionName = $functionTableRow['functionName'];

                    if (self::$stackedBlockTagNameCount > 0) {
                        //$blockTagNameStackの最後の要素と同じなら途中過程、同じでないなら終了→初期過程
                        if ($blockTagName == self::$blockTagNameStack[self::$stackedBlockTagNameCount - 1]) {
                            $tempOutput .= self::$functionName('in', $matches);
                        } 
                        else {
                            $functionName = self::$blockTagFunctionTable[self::$blockTagNameStack[self::$stackedBlockTagNameCount - 1]]['functionName'];
                            $tempOutput .= self::$functionName('end');

                            $functionName = $functionTableRow['functionName'];
                            $tempOutput .= self::$functionName('begin', $matches);
                        }
                    }
                    //$blockTagNameStackの要素が存在しないときは、必ずbegin
                    else {
                        $tempOutput .= self::$functionName('begin', $matches);
                    }

                    break;
                }
                //ブロックタグの正規表現が各行に対してマッチしていなかった時
                else{
                    //$tempOutput = '<p>' . self::$subject . '</p>';
                    $tempOutput = self::$subject;
                }
            }


            //

            $output .= self::PostprocessLine($tempOutput);

            self::$lineNumber++;

        }

        self::$isFileEnd = true;

        //ファイル末まで行ったら$blockTagNameStackに残っているblockTagはすべて閉じる。
        while (self::$stackedBlockTagNameCount > 0) {
            $functionName = self::$blockTagFunctionTable[self::$blockTagNameStack[self::$stackedBlockTagNameCount - 1]]['functionName'];
            $output .= self::$functionName('end');
        }

        return $output;
    }

    //各行に対する処理==============================================================================

    /**
     * 行に対しての前処理をまとめたもの。（空白かどうかのチェックとインデントを数える）
     * @param $lineNumber 行番号
     */
    private static function PreprocessLine($lineNumber)
    {
        //現在の文字列の取得
        self::$subject = self::$lines[$lineNumber];

        //空白のチェック
        self::$isEmpty = self::IsEmpty(self::$subject);
        if (!self::$isEmpty) {
            //インデントを数える
            self::$previousIndentLevel = self::$indentLevel;
            self::$indentLevel = 0;
            while (preg_match(self::$indentRegexp, self::$subject)) {
                self::$subject = preg_replace(self::$indentRegexp, '', self::$subject);
                self::$indentLevel++;
            }

        }
        return;

    }

    /**
     * 行に対する後処理。（インラインタグの処理）
     */
    private static function PostprocessLine($tempOutput)
    {
        foreach (self::$inlineTagFunctionTable as $inlineTagName => $functionTableRow) {
            if (preg_match($functionTableRow['regexp'], $tempOutput, $matches)) {
                $functionName = $functionTableRow['functionName'];
                $tempOutput = self::$functionName($matches);
            }
        }
        return $tempOutput;
    }

    //END 各行に対する処理===================================================================================


    //Meta tag の関数テーブルの関数===========================================================================

    /**
     * タイトルを処理する。
     * @param $mode 現在の処理段階（begin, in endのどれか）
     * @param $matched 正規表現マッチ後の配列

     */
    private static function ProcessTitle($mode, $matched = '')
    {
        self::$metaTag['title'] = $matched[1];
        //$output = '<h1>' . self::$title . '</h1>';
        //return $output;
    }

    /**
     * 概要を処理する
     * @param $mode
     * @param $matched
     */
    private static function ProcessAbstract($mode, $matched = '')
    {
        self::$metaTag['abstract'] = $matched[1];
        //$output = '<p>' . self::$abstract .'</p>';
        //return $output;
    }

    /**
     * 作成日時を処理する
     * @param $mode 現在の処理段階（begin, in endのどれか）
     * @param $matched 正規表現マッチ後の配列
     */
    private static function ProcessCreatAt($mode, $matched = '')
    {
        self::$metaTag['create_at'] = $matched[1];
    }



    /**
     * 更新日時を処理する
     * @param $mode 現在の処理段階（begin, in endのどれか）
     * @param $matched 正規表現マッチ後の文字列
     */
    private static function ProcessUpdateAt($mode, $matched = '')
    {
        self::$metaTag['update_at'] = $matched[1];
    }

    //======================================================================================================


    //Blockタグの関数テーブルの関数とそれに関連する関数====================================================================

    /**
     * セクション関係を処理する。
     * @param $mode 現在の処理段階（begin, in endのどれか）
     * @param $matched 正規表現マッチ後の文字列
     * @return $output セクションタグについて処理後の文字列
     */
    private static function ProcessSection($mode, $matched = '')
    {
        $output = '';

        switch ($mode) {
            //タグ内に入った時の処理
            case 'begin':
                //スタックにタグを追加
                self::BlockTagNameStackPush('section');

                //セクションタイトルの処理
                $output .= '<section><h' . strval(self::$indentLevel + 2) . '>';
                $output .= $matched[1];
                $output .= '</h' . strval(self::$indentLevel + 2) . '><div class="sectionBody">';

                break;

            //タグ内に入っていた時の処理
            case 'in':
                //インデントがそのままの時
                if (self::$previousIndentLevel === self::$indentLevel) {

                    if (self::$stackedBlockTagNameCount > 0) {
                        //配列の最後の要素がsectionの場合、タグを閉じる
                        if (self::$blockTagNameStack[self::$stackedBlockTagNameCount - 1] == 'section') {
                            $output .= '</div></section>';
                            self::BlockTagNameStackPop();
                        }
                    }

                    //スタックにタグを追加
                    self::BlockTagNameStackPush('section');

                    //セクションタイトルの処理
                    $output .= '<section><h' . strval(self::$indentLevel + 2) . '>';
                    $output .= $matched[1];
                    $output .= '</h' . strval(self::$indentLevel + 2) . '><div class="sectionBody">';
                }

                //インデントレベルが上がった時
                else if (self::$previousIndentLevel < self::$indentLevel) {
                    self::$previousIndentLevel++;
                    $output .=self::ProcessSection('begin', $matched[1]);
                }

                //インデントレベルが下がった時
                else {
                    $output .=self::ProcessSection('end');
                    self::$previousIndentLevel--;
                    $output .=self::ProcessSection('in', $matched[1]);
                }
                break;

            //タグから抜けるときの処理
            case 'end':
                //空白行でない場合または文書末の場合はタグを閉じる
                if (self::$isFileEnd) {
                    $output .= '</div></section>';
                    self::BlockTagNameStackPop();
                }
                break;

            default:
                # code...
                break;
        }

        return $output;

    }

    /**
     * ulの処理
     * @param $mode 現在の処理段階（begin, in outのどれか）
     * @param $matched 正規表現マッチ後の文字列
     * @return $output セクションタグについて処理後の文字列
     */
    private static function ProcessUnorderedList($mode, $matched = '')
    {
        $output = '';

        switch ($mode) {
            case 'begin':
                self::BlockTagNameStackPush('ul');
                $output .= '<ul><li>' . $matched[1] . '</li>';
                break;

            case 'in':
                //インデントがそのままの時
                if (self::$previousIndentLevel === self::$indentLevel) {
                    $output .= '<li>' . $matched[1] . '</li>';
                }

                //インデントレベルが上がった時
                else if (self::$previousIndentLevel < self::$indentLevel) {
                    while(self::$previousIndentLevel < self::$indentLevel - 1){
                        self::BlockTagNameStackPush('ul');
                        $output .= '<ul>';
                        self::$previousIndentLevel++;
                    }
                    $output .=self::ProcessUnorderedList('begin', $matched);
                }

                //インデントレベルが下がった時
                else {
                    while(self::$previousIndentLevel > self::$indentLevel){
                        $output .= '</ul>';
                        self::BlockTagNameStackPop();
                        self::$previousIndentLevel--;
                    }
                    $output .=self::ProcessUnorderedList('in', $matched);
                }
                break;
                
            case 'end':
                while(self::$blockTagNameStack[self::$stackedBlockTagNameCount - 1] == 'ul'){
                    $output .= '</ul>';
                    self::BlockTagNameStackPop();
                }
                break;
            
            default:
                # code...
                break;
        }

        return $output;
    }

    /**
     * tl(tree list)の処理
     * @param $mode 現在の処理段階（begin, in outのどれか）
     * @param $matched 正規表現マッチ後の文字列
     * @return $output セクションタグについて処理後の文字列
     */
    //未完成
    private static function ProcessTreeList($mode, $matched = '')
    {
        $output = '';

        switch ($mode) {
            case 'begin':
                self::BlockTagNameStackPush('tl');
                $output .= '<ul class="Tree"><li>' . $matched[1] . '</li>';
                break;

            case 'in':
                //インデントがそのままの時
                if (self::$previousIndentLevel === self::$indentLevel) {
                    $output .= '<li>' . $matched[1] . '</li>';
                }

                //インデントレベルが上がった時
                else if (self::$previousIndentLevel < self::$indentLevel) {
                    while(self::$previousIndentLevel < self::$indentLevel - 1){
                        self::BlockTagNameStackPush('tl');
                        $output .= '<ul class="Tree">';
                        self::$previousIndentLevel++;
                    }
                    $output .=self::ProcessUnorderedList('begin', $matched[1]);
                }

                //インデントレベルが下がった時
                else {
                    while(self::$previousIndentLevel > self::$indentLevel){
                        $output .=self::ProcessUnorderedList('end');
                        self::$previousIndentLevel--;
                    }
                    $output .=self::ProcessUnorderedList('in', $matched[1]);
                }
                break;
                
            case 'end':
                $output .= '</ul>';
                self::BlockTagNameStackPop();
                break;
            
            default:
                # code...
                break;
        }

        return $output;
    }

    /**
     * Tableの処理
     */
    private static function ProcessTable($mode, $matched ='')
    {
        $output = '';

        switch ($mode) {
            case 'begin':
                self::$tempOutput = '';
                self::BlockTagNameStackPush('table');
                $output .= '<table>';
                //キャプションが来るとき
                if(preg_match('/^\[(.*)\]$/', $matched[1], $matches)){
                    $output .= '<caption>'. $matches[1] . '</caption>';
                }
                else{
                    //theadとtbodyの区切り（---が複数回）であるとき
                    if(preg_match('/^(-{3,}\|{1,2})*$/', $matched[1])){
                        //何もしない
                    }
                    else{
                        if(preg_match('/(.*)\|$/', $matched[1], $matches)){
                            $tableData = explode('|', $matches[1]);
                            $tableDataCount = count($tableData);
                            $tableDataNumber = 0;
                            self::$tempOutput .= '<tr>';
                            while($tableDataNumber < $tableDataCount) {
                                //次のデータがへ空白ならth、でないならtd
                                if($tableData[$tableDataNumber + 1] == ''){
                                    self::$tempOutput .= '<th>' . $tableData[$tableDataNumber] . '</th>';
                                    $tableDataNumber = $tableDataNumber + 2;
                                }else {
                                    self::$tempOutput .= '<td>' . $tableData[$tableDataNumber] . '</td>';
                                    $tableDataNumber = $tableDataNumber + 1;
                                }
                                //最後のデータの時
                                if($tableDataNumber == $tableDataCount - 1){
                                    self::$tempOutput .= '<td>' . $tableData[$tableDataNumber] . '</td>';
                                    break;
                                }
                            }
                            self::$tempOutput .= '</tr>';
                        }
                    }
                }
                break;

            case 'in':

                //theadとtbodyの区切り（---が複数回）であるとき
                if(preg_match('/^(-{3,}\|{1,2})*$/', $matched[1])){
                    self::$tempOutput = str_replace(array('<td>', '</td>'), array('<th>', '</th>'), self::$tempOutput);
                    $output .= '<thead>' . self::$tempOutput . '</thead>';
                    self::$tempOutput = '';
                }
                else{
                    if(preg_match('/(.*)\|$/', $matched[1], $matches)){
                        $tableData = explode('|', $matches[1]);
                        $tableDataCount = count($tableData);
                        $tableDataNumber = 0;
                        self::$tempOutput .= '<tr>';
                        while($tableDataNumber < $tableDataCount) {
                            //次のデータがへ空白ならth、でないならtd
                            if($tableData[$tableDataNumber + 1] == ''){
                                self::$tempOutput .= '<th>' . $tableData[$tableDataNumber] . '</th>';
                                $tableDataNumber = $tableDataNumber + 2;
                            }else {
                                self::$tempOutput .= '<td>' . $tableData[$tableDataNumber] . '</td>';
                                $tableDataNumber = $tableDataNumber + 1;
                            }
                            //最後のデータの時
                            if($tableDataNumber == $tableDataCount - 1){
                                self::$tempOutput .= '<td>' . $tableData[$tableDataNumber] . '</td>';
                                break;
                            }
                        }
                        self::$tempOutput .= '</tr>';
                    }
                }
                break;
                
            case 'end':
                $output .= '<tbody>' . self::$tempOutput . '</tbody></table>'; 
                self::BlockTagNameStackPop();
                break;
            
            default:
                break;
        }

        return $output;
    }


    /**
     * パラグラフの処理
     */
    private static function ProcessParagraph($mode, $matched = '')
    {
        return '<p>' . $matched[1] .'</p>';
    }


    /**
     * 画像のタグの処理
     */
    private static function ProcessImage($mode, $matched = '')
    {
        return '<figre><img src=' . $matched[2] . ' alt=' . $matched[1] . '>' . '<figcaption>' . $matched[1] . '</figcaption></figre>';
    }

    //END Block関数テーブルの関数とそれに関連する関数================================================================

    //inlineタグの関数テーブルの関数================================================================================

    /**
     * aタグの処理
     * @param $matched 正規表現マッチ後の文字列
     * @return  aタグ処理後の文字列
     */
    private static function ProcessLinK($matched){
        return $matched[1] . '<a href=' . $matched[3] . '>' . $matched[2] . '</a>' . $matched[4];
    }


    //END inlineタグの関数テーブルの関数============================================================================


    /**
     * 配列内の配列に対してキーを設定する。
     * @param $keys 配列のキーとなる配列。
     * @param $values 複数の配列を持つ配列。
     * @return $associativeArrays 配列内の配列に対してキーを割り当てた配列。
     */
    private static function ArraysCombine($keys, $values)
    {
        $associativeArrays = [];

        foreach ($values as $key => $value) {

            $associativeArray = array_combine($keys, $value);

            $associativeArrays[$key] = $associativeArray;
        }

        return $associativeArrays;
    }

    /**
     * 空白かどうかを判別する
     * @param $subject 判別する文字列
     * @return bool $result 判別結果
     */
    private static function IsEmpty($subject)
    {
        if (preg_match('/^[\s]*$/', $subject)) {
            $result = true;
        } else {
            $result = false;
        }
        return $result;
    }

    /**
     * $blockTagNameStackからpopし、要素数更新
     */
    private static function BlockTagNameStackPop()
    {
        array_pop(self::$blockTagNameStack);
        self::$stackedBlockTagNameCount = count(self::$blockTagNameStack);
    }

    /**
     * $blockTagNameStackにpushし、要素数更新
     * @param $element 追加する要素
     */
    private static function BlockTagNameStackPush($element)
    {
        self::$stackedBlockTagNameCount = array_push(self::$blockTagNameStack, $element);
    }

}
