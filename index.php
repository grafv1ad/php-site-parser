<?php

    require_once __DIR__ . '/utils.php';
    require_once __DIR__ . '/Parser.php';

    $ROOT = getRoot();

    if (!isset($_GET['site_url'])) {
        echo 'Пропишите GET-параметр в адресную строку <b>?site_url=ВАШ_САЙТ</b>';
        die;
    }

    $parser = new Parser($_GET['site_url']);

    if (!$parser->html) {
        unset($parser);
        echo '
              <h1 style="font-family: sans-serif;">
                <b style="color: #e03c3c;">Error:</b> Nothing to parse ¯\_(ツ)_/¯
              </h1>
              ';
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
    <link rel="stylesheet" href="<?= $ROOT ?>/assets/style.css">
</head>
<body>
    <h1 class="title">Parser v0.4</h1>
    <a href="<?= $ROOT ?>/<?= Parser::dirResultsName ?>/<?= $parser->siteName ?>" target="_blank" class="website-link"><?= $parser->siteName ?></a>
    <div class="container">
        <div class="container__item">
            <div class="container__item-header">
                <img src="<?= $ROOT ?>/assets/icons/icon-css.png" alt="icon-css" class="icon" />
                <span>Styles</span>
            </div>
            <div class="container__item-list">
                <?php foreach ($parser->siteHrefsMap['css'] as $style): ?>
                    <a class="item" href="<?= $style['href'] ?>" target="_blank">
                        <?= $style['name'] . '.' . $style['format'] ?>
                    </a>
                <?php endforeach ?>
            </div>
        </div>
        <div class="container__item">
            <div class="container__item-header">
                <img src="<?= $ROOT ?>/assets/icons/icon-js.png" alt="icon-js" class="icon" />
                <span>Scripts</span>
            </div>
            <div class="container__item-list">
                <?php foreach ($parser->siteHrefsMap['js'] as $script): ?>
                    <a class="item" href="<?= $script['href'] ?>" target="_blank">
                        <?= $script['name'] . '.' . $script['format'] ?>
                    </a>
                <?php endforeach ?>
            </div>
        </div>
        <div class="container__item">
            <div class="container__item-header">
                <img src="<?= $ROOT ?>/assets/icons/icon-img.png" alt="icon-img" class="icon" />
                <span>Images</span>
            </div>
            <div class="container__item-list">
                <?php foreach ($parser->siteHrefsMap['img'] as $image): ?>
                    <a class="item" href="<?= $image['href'] ?>" target="_blank">
                        <?= $image['name'] . '.' . $image['format'] ?>
                    </a>
                <?php endforeach ?>
            </div>
        </div>
    </div>
</body>
</html>