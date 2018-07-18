<?php

include_once dirname(__DIR__) . '/vendor/autoload.php';

$config = new \Bavix\Config\Config(__DIR__ . '/data');

$files = \Bavix\SDK\FileLoader::extensions();

foreach ($files as $file) {
    var_dump($config->get($file)->description);
}

$slice = $config->get($files[0]);
$slice->description = 'Simple';

// if not exists -> make file
$config->save('simple', $slice);

var_dump($slice->asArray());

\Bavix\Helpers\File::remove(__DIR__ . '/data/simple.' . $files[0]);
