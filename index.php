<?php
    include "utils.php";
    require_once "Parser.php";

    $parser = new Parser("https:/ВАШ_САЙТ/.ru/");

    if (!$parser->html)
    {
        unset($parser);
        echo "<b>Нечего парсить :(</b>";
        die;
    }

    $parser->init_site();
    $hrefsCss = $parser->getHrefs('css');
    $hrefsImg = $parser->getHrefs('img');
    $hrefsJs = $parser->getHrefs('js');
    $parser->siteHrefsMap['img'] = array_merge(
        $parser->siteHrefsMap['img'], $hrefsImg
    );
    $parser->multi_parse($hrefsCss, function($ctx, $res, $info) {
        $ctx->setFile($res, $info);
    });
    $parser->multi_parse($parser->siteHrefsMap['img'], function($ctx, $res, $info) {
        $ctx->setFile($res, $info);
    });
    $parser->multi_parse($hrefsJs, function($ctx, $res, $info) {
        $ctx->setFile($res, $info);
    });

    echo "Парсер отработал!";
?>