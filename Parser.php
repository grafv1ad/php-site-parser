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

    private function getNameSite(): string {
        $name = explode('/', $this->url);
        return $name[2];
    }
    
    public function getHrefs(string $type, string $html = ''): array
    {   
        $html = empty($html) ? $this->html : $html;
        $format = ($type === 'img') ? '(png|jpg|svg)' : $type;
        $pattern = "/[_\w\d\.\/-]+\.$format/";
        preg_match_all($pattern, $html, $matches);
        if (empty($matches[0])) return array();

        $arHrefs = [];

        for ($i = 0; $i < count($matches[0]); $i++)
        {
            $findedHref = $matches[0][$i];
            $formatFile = array_pop(preg_split("/[\/\.]/", $findedHref));
            $name = explode('/', $findedHref);
            $name = $name[count($name) - 1];
            $posFormat = strpos($name, ".$formatFile");
            $name = substr_replace($name, '', $posFormat, strlen($formatFile) + 1);
            $href = $this->formatHref($findedHref);
            $arHrefs[$name] = array(
                'href' => $href,
                'path' => $findedHref,
                'name' => $name,
                'format' => $formatFile
            );
        }
        
        return $this->siteHrefsMap[$type] = $arHrefs;
    }

    private function formatHref($href): string {
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