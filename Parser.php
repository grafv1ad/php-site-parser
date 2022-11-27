<?php

function show($data) {
    echo "<pre>";
    print_r($data);
    echo "</pre>";
}

class Parser
{
    public $url;
    public $html;
    public $dirSite;
    public $siteName;
    public $siteHrefsMap = [];

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
            self::setLog(curl_error($req), __LINE__);
        }

        curl_close($req);

        if ($resCode != 200)
        {
            return false;
        }

        return $res;
    }

    private function getNameSite() {
        $name = explode('/', $this->url);
        return $name[2];
    }
    
    // сделать функцию общего использования (с css, js)
    public function getImageHrefs()
    {   
        $pattern = "/img.+src=.+\.(png|jpg|svg)/";
        preg_match_all($pattern, $this->html, $matches);
        for ($i = 0; $i < count($matches[0]); $i++)
        {
            $imgHTML = $matches[0][$i];
            preg_match_all("/[\w\d\-_\/:]+[^\.]/", $imgHTML, $m);
            $format = $m[0][3];
            $path = $m[0][2];
            $name = explode('/', $path);
            $path .= '.' . $format;
            $name = $name[count($name) - 1];
            $this->siteHrefsMap["img"][] = 
            array(
                'href' => $this->formatHref($path),
                'name' => $name,
                'format' => $format
            );
        }
    }

    private function formatHref($href) {
        if (strpos($href, "http") === false) {
            return 'https://' . $this->siteName . '/' . $href;
        }
        return $href;

    }

    public function init_site()
    {
        $this->dirSite = __DIR__ . '/html/' . $this->siteName;

        if (is_dir($this->dirSite)) return;

        $isDirSite = mkdir($this->dirSite, 0777, true);

        if ($isDirSite)
        {
            mkdir($this->dirSite . '/img');
            mkdir($this->dirSite . '/css');
            mkdir($this->dirSite . '/js');
            file_put_contents($this->dirSite . '/index.html', $this->html);
        }
    }

    static function setLog($data, $line, $dir = __DIR__ . "/log.txt") {
        file_put_contents(
            $dir, 
            "\n[" . date('Y-m-d H:i:s') .  "]\n (#" .
             $line. ") ". $data . "\n", FILE_APPEND
        );
    }
}