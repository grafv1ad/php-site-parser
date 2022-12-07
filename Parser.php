<?php

class Parser
{
    public $url;
    public $html;
    public $siteName;
    public $domain;
    public $siteHrefsMap = array(
        "css" => [], "img" => [], "js" => []
    );
    private $dirSite;
    private $dirAssets;
    private $dirAssetsName = 'assets';

    public function __construct(string $url)
    {
        $this->url = $url;
        $this->siteName = $this->getNameSite($url);
        $this->html = $this->parse($url);
    }

    public function parse(
        string $url,
        array $options = 
        array(
            CURLOPT_RETURNTRANSFER => true
        )
    )
    {
        $req = curl_init($url);
        curl_setopt_array($req, $options);
        $res = curl_exec($req);
        $resCode = curl_getinfo($req)['http_code'];

        if (curl_errno($req)) {
            self::log(curl_error($req), __LINE__);
        }

        curl_close($req);

        if ($resCode != 200)
        {
            return false;
        }

        return $res;
    }

    private function setDynamicPathToHTML($type, $name, $format, $old_path): void
    {
        $neededPath = $this->dirAssetsName . '/' . $type . '/' . $name . '.' . $format;
        $this->html = str_replace($old_path, $neededPath, $this->html);
        file_put_contents($this->dirSite . '/index.html', $this->html);
    }

    public function setFile($res, $info)
    {
        $pathToFile = $this->dirAssets . '/' . $info['type']
         . '/' . $info['name'] . '.' . $info['format'];

         if (!isset($res['from'])) {
            $this->setDynamicPathToHTML(
                $info['type'], $info['name'],
                $info['format'], $info['path']
            );
         }

        if ($info['type'] === 'img')
        {
            $fp = fopen($pathToFile, 'wb');
            fwrite($fp, $res);
            fclose($fp);
        } else
        {
            if ($info['format'] === 'css')
            {
                $images = $this->getHrefs('img', $res);
                if (!empty($images))
                {
                    $GLOBALS['extraImages'] = [];
                    $images = array_map(function($el) {
                        $tmp = $el['href'];
                        $el['href'] = str_replace('..', 'assets', $el['href']);
                        $el['from'] = 'css';
                        $GLOBALS['extraImages'][] = array(
                            'href' => str_replace('../', '', $tmp),
                            'path' => $el['path'],
                            'name' => $el['name'],
                            'type' => $el['type'],
                            'format' => $el['format'],
                            'from' => 'css'
                        );
                        $GLOBALS['extraImages'][] = array(
                            'href' => str_replace('..', 'uploads', $tmp),
                            'path' => $el['path'],
                            'name' => $el['name'],
                            'type' => $el['type'],
                            'format' => $el['format'],
                            'from' => 'css'
                        );
                        return $el;
                    }, $images);


                    $this->siteHrefsMap['img'] = array_merge(
                        $this->siteHrefsMap['img'],
                        $images, 
                        $GLOBALS['extraImages']
                    );

                    unset($GLOBALS['extraImages']);

                    foreach ($images as $image)
                    {
                        $neededPath = '../img/' . $image['name'] . '.' . $image['format'];
                        $res = str_replace($image['path'], $neededPath, $res);
                    }
                }
            }
            file_put_contents($pathToFile, $res);
        }
    }

    public function multi_parse(array $hrefs, $callback)
    {
        $multi = curl_multi_init();
        $channels = array();
 
        foreach ($hrefs as $href) {
            $req = curl_init();
            curl_setopt($req, CURLOPT_URL, $href['href']);
            curl_setopt($req, CURLOPT_HEADER, false);
            curl_setopt($req, CURLOPT_RETURNTRANSFER, true);
        
            curl_multi_add_handle($multi, $req);
            $href['req'] = $req;
            $channels[$href['href']] = $href;
        }
 
        $active = null;
        do {
            $mrc = curl_multi_exec($multi, $active);
        } while ($mrc == CURLM_CALL_MULTI_PERFORM);
 
        while ($active && $mrc == CURLM_OK) {
            if (curl_multi_select($multi) == -1) {
                continue;
            }

            do {
                $mrc = curl_multi_exec($multi, $active);
            } while ($mrc == CURLM_CALL_MULTI_PERFORM);
        }
 
        foreach ($channels as $channel) {
            $res = curl_multi_getcontent($channel['req']);
            $resCode = curl_getinfo($channel['req'])['http_code'];
            if ($resCode !== 200) {
                self::log('Не нашёл :( ' . $channel['href'], __LINE__);
                continue;
            }
            $callback($this, $res, $channel);
        }
        curl_multi_close($multi);
    }

    private function getNameSite(): string {
        preg_match("/http(s|):\/\/.+\.\w+/", $this->url, $matches);

        if (empty($matches)) {
            echo "<b>Введите корректный адрес сайта<b>";
            die;
        }

        $arSplitedURL = explode('/', $this->url);
        $name = $arSplitedURL[2];

        if (isset($arSplitedURL[3])) {
            for ($i = 3; $i < count($arSplitedURL); $i++) {
                $name .= '/' . $arSplitedURL[$i];
            }
        }

        $this->domain = $arSplitedURL[2];

        return $name;
    }
    
    public function getHrefs(string $type, string $html = ''): array
    {   
        $html = empty($html) ? $this->html : $html;
        $format = ($type === 'img') ? '(png|jpg|svg)' : $type;
        $pattern = "/[:_\w\d\.\/-]+\.$format/";
        preg_match_all($pattern, $html, $matches);
        if (empty($matches[0])) return array();

        $arHrefs = [];

        for ($i = 0; $i < count($matches[0]); $i++)
        {
            $finderHref = $matches[0][$i];
            $arSplitedHref = preg_split("/[\/\.]/", $finderHref);
            $formatFile = array_pop($arSplitedHref);
            $name = explode('/',  $finderHref);
            $name = $name[count($name) - 1];
            $posFormat = strpos($name, ".$formatFile");
            $name = substr_replace($name, '', $posFormat, strlen($formatFile) + 1);
            $href = $this->formatHref($finderHref);
            $arHrefs[] = array(
                'href' => $href,
                'path' => $finderHref,
                'name' => $name,
                'type' => $type,
                'format' => $formatFile
            );
        }

        return $this->getUniqArray($arHrefs, 'path');
    }

    public function getUniqArray($array, $value): array
    {
        $uniqPath = [];
        $GLOBALS['repeatIndex'] = [];
        for ($i = 0; $i < count($array); $i++)
        {
            if (in_array($array[$i][$value], $uniqPath)) {
                $GLOBALS['repeatIndex'][$i] = 1;
                continue;
            }
            $uniqPath[] = $array[$i][$value];
        }

        $uniqArray = array_filter($array, function ($_, $key) {
            if (isset($GLOBALS['repeatIndex'][$key])) {
                return $GLOBALS['repeatIndex'][$key] !== 1;
            }
            return $_;
        }, ARRAY_FILTER_USE_BOTH);

        unset($GLOBALS['repeatIndex']);

        return $uniqArray;
    }

    private function formatHref($href): string {
        if (strpos($href, "http") === false) {
            return 'https://' . $this->domain . '/' . $href;
        }
        return $href;
    }

    public function init_site()
    {
        $this->dirSite = __DIR__ . '/results/' . $this->siteName;
        $this->dirAssets =  $this->dirSite . '/' . $this->dirAssetsName;

        if (!is_dir($this->dirSite)) {
            mkdir($this->dirSite, 0777, true);
        }
        if (!is_dir($this->dirAssets)) {
            mkdir($this->dirAssets, 0777, true);
        }
        if (!is_dir($this->dirAssets . '/img')) {
            mkdir($this->dirAssets . '/img');
        }
        if (!is_dir($this->dirAssets . '/css')) {
            mkdir($this->dirAssets . '/css');
        }
        if (!is_dir($this->dirAssets . '/js')) {
            mkdir($this->dirAssets . '/js');
        }
        file_put_contents($this->dirSite . '/index.html', $this->html);
    }

    static function log($data, $line, $dir = __DIR__ . "/log.txt") {
        file_put_contents(
            $dir, 
            "\n[" . date('Y-m-d H:i:s') .  "]\n (#" .
             $line. ") ". $data . "\n", FILE_APPEND
        );
    }
}

?>