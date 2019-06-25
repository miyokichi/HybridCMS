<?php
require_once 'OutlineParser.php';

class Content
{
    public const ROOT_DIRECTORY = '/Resource/Root/';
    public const FILE_EXTENSION = '.txt';
    private $directoryPath;
    private $contentFileName;

    private $htmlContent;
    public $metaTag;

    public function __construct($directoryPath = '')
    {
        
        $this->contentFileName = '';
        $this->htmlContent = '';
        $this->metaTag = ['title' => '','abstract'=>'', 'createAt'=>'', 'updateAt'=>''];
        $this->directoryPath = $directoryPath;
        if ($directoryPath == '') {
            $this->contentFileName = 'Root';
        } else {
            $pathParamaters = explode('/', $directoryPath);
            $this->contentFileName = $pathParamaters[count($pathParamaters) - 2];
        }
    }


    public function GetTitle()
    {
        return $this->metaTag['title'];
    }

    public function GetCreatAt()
    {
        return $this->metaTag['creatAt'];
    }

    public function GetUpdateAt()
    {
        return $this->metaTag['updateAt'];
    }

    public function GetHtmlContent()
    {
        return $this->htmlContent;
    }

    
    public function MetaParse()
    {
        $content = file_get_contents($this->directoryPath . $this->contentFileName . self::FILE_EXTENSION);
        if ($content) {
            OutlineParser::Initialize($content);
            $this->metaTag = OutlineParser::MetaParse();
        }
    }

    public function ContentParse()
    {
        $content = file_get_contents($this->directoryPath . $this->contentFileName . self::FILE_EXTENSION);
        if ($content) {
            OutlineParser::Initialize($content);
            $this->htmlContent = OutlineParser::ContentParse();
        }
    }

    public function AllParse()
    {
        $content = file_get_contents($this->directoryPath . $this->contentFileName . self::FILE_EXTENSION);
        if ($content) {
            OutlineParser::Initialize($content);
            $this->metaTag = OutlineParser::MetaParse();
            $this->htmlContent = OutlineParser::ContentParse();
        }
    }

    public function GetChildrenContents()
    {
        $children = [];
        $childrenDirectoryPath = glob($this->directoryPath . '*/');

        foreach ($childrenDirectoryPath as $childDirectorypath) {
            //childの相対パスを取得
            $url = $this->GetRelativePath($this->directoryPath, $childDirectorypath);
            
            //childのtitleを取得
            $childContent = new Content($this->directoryPath . $url);
            $childContent->MetaParse();
            $childTitle = $childContent->GetTitle();
            //var_dump($childContent);

            //childの情報を格納
            array_push($children, ['title' => $childTitle, 'url' => $url]);
        }
        return $children;
    }

    private function GetRelativePath($from, $to)
    {
        // some compatibility fixes for Windows paths
        $from = is_dir($from) ? rtrim($from, '\/') . '/' : $from;
        $to   = is_dir($to)   ? rtrim($to, '\/') . '/'   : $to;
        $from = str_replace('\\', '/', $from);
        $to   = str_replace('\\', '/', $to);

        $from     = explode('/', $from);
        $to       = explode('/', $to);
        $relPath  = $to;

        foreach ($from as $depth => $dir) {
            // find first non-matching dir
            if ($dir === $to[$depth]) {
                // ignore this directory
                array_shift($relPath);
            } else {
                // get number of remaining dirs to $from
                $remaining = count($from) - $depth;
                if ($remaining > 1) {
                    // add traversals up to first matching dir
                    $padLength = (count($relPath) + $remaining - 1) * -1;
                    $relPath = array_pad($relPath, $padLength, '..');
                    break;
                } else {
                    $relPath[0] = './' . $relPath[0];
                }
            }
        }
        return implode('/', $relPath);
    }
}
