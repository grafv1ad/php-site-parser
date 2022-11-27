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
    $parser->getImageHrefs();
    show($parser->siteHrefsMap);
?>