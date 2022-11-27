<?php
    // https://sok-aloe.ru/
    require_once "Parser.php";

    $parser = new Parser("https://sok-aloe.ru/");

    if (!$parser->html)
    {
        unset($parser);
        echo "<b>Нечего парсить :(</b>";
        die;
    }

    $parser->init_site();
    $hrefsCss = $parser->getHrefs('img');
    $hrefsImg = $parser->getHrefs('css');
    $hrefsJs = $parser->getHrefs('js');
    show(array_merge($hrefsCss, $hrefsImg, $hrefsJs))
    // $parser->multi_parse(array_merge($hrefsCss, $hrefsImg, $hrefsJs), function($ctx, $res, $info) {
    //     $ctx->setFile($res, $info);
    // });
?>