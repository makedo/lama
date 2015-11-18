<?php

require __DIR__ . '/../vendor/autoload.php';

use Intervention\Image\ImageManager;

$app = new Silex\Application();
$app['debug'] = true;

$rules = [
    'img/airlines/logos/' => [
        'load' => __DIR__ . '/images/',
        'sizes' => [
            ['w' => 100, 'h' => 100],
            ['w' => 150, 'h' => 100],
        ]
    ],
];

$app->get('{path}' . '{width}' . '{sizedelimiter}' . '{height}' . '/' . '{name}.{extension}',
    function (Silex\Application $app, $path, $width, $height, $sizedelimiter, $name, $extension) use ($rules) {

        $config = '';
        if (array_key_exists($path, $rules)) {
            $config = $rules[$path];
        } else {
            // @TODO return fallback image
            return new \Symfony\Component\HttpFoundation\Response('Not found', 404);
        }

        $areSizesValid = false;
        foreach ($config['sizes'] as $size) {
            if ($size['w'] == $width && $size['h'] == $height) {
                $areSizesValid = true;
                break;
            }
        }
        if (! $areSizesValid) {
            return new \Symfony\Component\HttpFoundation\Response('Not found', 404);
        }

        $loadPath = $config['load'];
        $manager = new ImageManager(array('driver' => 'gd'));
        $image = $manager->make($loadPath . $name . '.' . $extension);

        $image->resize($width, $height);

        $sizePath = $width . $sizedelimiter . $height . '/';
        if (! file_exists(($path . $sizePath ))) {
            mkdir($path . $sizePath, 0777, true);
        }

        $savedFile = $path . $sizePath . $name . '.' . $extension;
        $image->save($savedFile);

        return $app->sendFile($savedFile);
    }
)
    ->assert('sizedelimiter', 'x')
    ->assert('path', '([a-z0-9]{0,}[\/]{1}){1,}')
    ->assert('width', '[1-9]{1}[0-9]{0,3}')
    ->assert('height', '[1-9]{1}[0-9]{0,3}')
    ->assert('extension', 'png|jpg');

$app->error(function() {
    return new \Symfony\Component\HttpFoundation\Response('Not found', 404);
});

$app->run();