<?php

require __DIR__ . '/../vendor/autoload.php';

interface Rule {
    public function getRoute();
    public function getSavePath();
    public function getLoadPath();
}

class ibe30 implements Rule {

    private $route;
    private $savePath;
    private $loadPath;

    /**
     * ibe30 constructor.
     * @param $route
     * @param $savePath
     * @param $loadPath
     */
    public function __construct($route, $savePath, $loadPath)
    {
        $this->route = $route;
        $this->savePath = $savePath;
        $this->loadPath = $loadPath;
    }

    /**
     * @return mixed
     */
    public function getRoute()
    {
        return $this->route;
    }

    /**
     * @return mixed
     */
    public function getSavePath()
    {
        return $this->savePath;
    }

    /**
     * @return mixed
     */
    public function getLoadPath()
    {
        return $this->loadPath;
    }

}

use Imagine\Image\Box;

$app = new Silex\Application();
$app['debug'] = true;


$rule = new ibe30(
    'img/airlines/logos/',
    'img/airlines/logos/',
    __DIR__ . '/images/'
);

$app->get($rule->getRoute() . '{width}x{height}/{name}.{extension}',
    function ($width, $height, $name, $extension) use ($rule) {

        saveImage(
            $rule,
            $width,
            $height,
            $name,
            $extension
        );

        exit(0);
    }
)
    ->assert('width', '\d{1,3}')
    ->assert('height', '\d{1,3}')
    ->assert('extension', 'png|jpg');

$app->run();



function saveImage(Rule $rule, $width, $height, $name, $extension)
{
    $sizePath = $width . 'x' . $height . '/';

    if (! file_exists(($rule->getSavePath() . $sizePath ))) {
        mkdir($rule->getSavePath() . $sizePath, 0777, true);
    }

    $imagine = new Imagine\Gd\Imagine();

    $image = $imagine->open($rule->getLoadPath() . $name . '.' . $extension);

    $currentSize = $image->getSize();

    $image = $image->thumbnail(new Box($width, $height));

    $transparentImage = createTransparentImage($imagine, new Box($width, $height));

    $beforeResizeAspectRatio = round($currentSize->getWidth() / $currentSize->getHeight(), 2);
    $resizedAspectRatio = round($width / $height);

    $x = 0;
    $y = 0;
    //move width
    if ($resizedAspectRatio > $beforeResizeAspectRatio) {
        $x = getPlacePointCoordinate($width, $image->getSize()->getWidth());
    }

    //move height
    if ($resizedAspectRatio < $beforeResizeAspectRatio) {
        $y = getPlacePointCoordinate($height, $image->getSize()->getHeight());
    }

    $image = $transparentImage->paste($image, new \Imagine\Image\Point($x, $y));
    $image->show('png');

    $image->save($rule->getSavePath() . $sizePath  . $name . '.' . $extension);
}

function getPlacePointCoordinate($transparentImageCoord, $resizedImageCoord) {

    $transparentCenter = $transparentImageCoord / 2;
    $resizedCenter = $resizedImageCoord / 2;

    return $transparentCenter - $resizedCenter;
}

/**
 * @param \Imagine\Gd\Imagine $imagine
 * @param \Imagine\Image\BoxInterface $size
 * @return \Imagine\Gd\Image
 */
function createTransparentImage(\Imagine\Gd\Imagine $imagine, Box $size)
{
    $transparentImage = $imagine->create($size);

    $im = $transparentImage->getGdResource();

    imagealphablending($im, false);
    $transparency = imagecolorallocatealpha($im, 0, 0, 0, 127);
    imagefill($im, 0, 0, $transparency);
    imagesavealpha($im, true);

    return $transparentImage;
}
