<?php

    require_once __DIR__ . '/utils.php';
    require_once __DIR__ . '/' . "Parser.php";


    if (!isset($_GET['site_url'])) {
        echo "Пропишите GET-параметр в адресную строку <b>?site_url=ВАШ_САЙТ</b>";
        die;
    }

    $parser = new Parser($_GET['site_url']);

    if (!$parser->html) {
        unset($parser);
        echo "<b>Нечего парсить :(</b>";
        die;
    }

    $parser->init_site();
    $parser->siteHrefsMap['css'] = $hrefsCss = $parser->getHrefs('css');
    $parser->siteHrefsMap['js'] = $hrefsJs = $parser->getHrefs('js');
    $hrefsImg = $parser->getHrefs('img');
    $hrefsImg = $parser->siteHrefsMap['img'] = array_merge(
        $parser->siteHrefsMap['img'], $hrefsImg
    );

    $parser->multi_parse($hrefsCss, function($ctx, $res, $info) {
        $ctx->setFile($res, $info);
    });
    $parser->multi_parse($hrefsImg, function($ctx, $res, $info) {
        $ctx->setFile($res, $info);
    });
    $parser->multi_parse($hrefsJs, function($ctx, $res, $info) {
        $ctx->setFile($res, $info);
    });

?>

<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Parser</title>
    <style>
        * {
            font-family: sans-serif;
        }
        .container-item {
            margin: 10px 0;
            box-shadow: 3px 3px 5px silver;
            padding: 5px;
        }
        .item {
            display: block;
            margin: 8px 0;
            color: #305583;
        }
        .icon {
            width: 32px;
            margin: 10px;
        }
    </style>
</head>
<body>
    <h1>Parser v.0.2</h1>
    <h3><?=$parser->siteName?></h3>
    <div class="container">
        <div class="container-item">
            <img src="./icons/icon-css.png" alt="icon-css" class="icon" />
            <?php foreach ($parser->siteHrefsMap['css'] as $image): ?>
                <a class="item" href="<?=$image['href']?>" target="_blank"><?=$image['name']?></a>
            <?php endforeach; ?>
        </div>
        <div class="container-item">
            <img src="./icons/icon-js.png" alt="icon-js" class="icon" />
            <?php foreach ($parser->siteHrefsMap['js'] as $image): ?>
                <a class="item" href="<?=$image['href']?>" target="_blank"><?=$image['name']?></a>
            <?php endforeach; ?>
        </div>
        <div class="container-item">
            <img src="./icons/icon-img.png" alt="icon-img" class="icon" />
            <?php foreach ($parser->siteHrefsMap['img'] as $image): ?>
                <a class="item" href="<?=$image['href']?>" target="_blank"><?=$image['name']?></a>
            <?php endforeach; ?>
        </div>
    </div>
</body>
</html>