<?php

declare(strict_types=1);

namespace App;

use Imagine\Gd\Imagine;
use Imagine\Image\Box;

final class ImageOptimizer
{
    private const MAX_HEIGHT = 150;
    private const MAX_WIDTH  = 200;

    private Imagine $imagine;

    public function __construct()
    {
        $this->imagine = new Imagine();
    }

    public function resize(string $filename): void
    {
        [$iWidth, $iHeight] = getimagesize($filename);

        $height = self::MAX_HEIGHT;
        $ratio  = $iWidth / $iHeight;
        $width  = self::MAX_WIDTH;

        if ($width / $height > $ratio) {
            $width = $height * $ratio;
        } else {
            $height = $width / $ratio;
        }
        
        $image = $this->imagine->open($filename);
        
        $image->resize(new Box($width, $height))->save($filename);
    }
}
