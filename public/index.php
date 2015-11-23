<?php

require __DIR__ . '/../vendor/autoload.php';

use Intervention\Image\ImageManager;

$app = new Silex\Application();
$app['debug'] = true;

$paths = [
    'img/airlines/logos/' => __DIR__ . '/images/'
];

$app->get('{path}' . '{width}' . '{sizedelimiter}' . '{height}' . '/' . '{name}.{extension}',
    function (Silex\Application $app, $path, $width, $height, $sizedelimiter, $name, $extension) use ($paths) {

        $sizePath = $width . $sizedelimiter . $height . '/';

        if (!file_exists(($path . $sizePath)) || !array_key_exists($path, $paths)) { //if there no folder with needed size
            return new \Symfony\Component\HttpFoundation\Response(
                sprintf('Path %s does not exist', $path . $sizePath),
                404
            );
        }

        $loadPath = $paths[$path];
        $manager = new ImageManager(array('driver' => 'gd'));
        $image = $manager->make($loadPath . $name . '.' . $extension);

        $imageAspectRatio = round($image->getWidth() / $image->getHeight(), 3);
        $resizedAspectRatio = round($width / $height, 3);

        if ( abs($imageAspectRatio - $resizedAspectRatio) > 0.01) { //if aspect ratios are different
            return new \Symfony\Component\HttpFoundation\Response(
                sprintf('Can\'t resize because of different aspect ratio', $sizePath),
                500
            );
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

$app->run();