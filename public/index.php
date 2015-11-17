<?php

require __DIR__ . '/../vendor/autoload.php';

use Intervention\Image\ImageManager;

$app = new Silex\Application();
$app['debug'] = true;

$rules = [
    'img/airlines/logos/' => __DIR__ . '/images/',
    'rule/images/'        => __DIR__ . '/images/',
    'rule/images/shot/'   => __DIR__ . '/images/',
];

$app->get('{path}' . '{width}' . '{sizedelimiter}' . '{height}' . '/' . '{name}.{extension}',
    function (Silex\Application $app, $path, $width, $height, $sizedelimiter, $name, $extension) use ($rules) {

        //find configuration by path.
        // User can have in configuration /{path}/ or {path} or /{path} or {path}/
        $loadPath = '';
        if (array_key_exists($path, $rules)) {
            $loadPath = $rules[$path];
        } else {
            // @TODO return fallback image
            return new \Symfony\Component\HttpFoundation\Response('Not found', 404);
        }

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
    ->assert('width', '[1-9]{1}[0-9]{1,3}')
    ->assert('height', '[1-9]{1}[0-9]{1,3}')
    ->assert('extension', 'png|jpg');

$app->error(function() {
    return new \Symfony\Component\HttpFoundation\Response('Not found', 404);
});

$app->run();

