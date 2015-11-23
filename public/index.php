<?php

require __DIR__ . '/../vendor/autoload.php';

use Intervention\Image\ImageManager;

$app = new Silex\Application();
$app['debug'] = true;

$paths = [
    'img/airlines/logos/' => __DIR__ . '/images'
];

$app->get('{path}' . '{width}' . '{sizedelimiter}' . '{height}' . '/' . '{name}.{extension}',
    function (Silex\Application $app, $path, $width, $height, $sizedelimiter, $name, $extension) use ($paths) {


        $loadPath = '';
        if (array_key_exists($path, $paths)) { //if there no path in valid paths
            $loadPath = $paths[$path];
        } else {
            // @TODO return fallback image
            return new \Symfony\Component\HttpFoundation\Response('Not found', 404);
        }

        $sizePath = $width . $sizedelimiter . $height . '/';
        if (! file_exists(($path . $sizePath ))) { //if there no folder with needed size
            return new \Symfony\Component\HttpFoundation\Response('Not found', 404);
        }

        $manager = new ImageManager(array('driver' => 'gd'));
        $image = $manager->make($loadPath . $name . '.' . $extension);

        if (($image->getWidth() - $image->getHeight()) > 0) { //if image is not square
            return new \Symfony\Component\HttpFoundation\Response('Not found', 404);
        }

        $image->resize($width, $height);

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