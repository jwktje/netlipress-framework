<?php

namespace Netlipress;

use Intervention\Image\ImageManager;

class ImageResizer
{
    private $filePath;
    private $fileName;
    private $resizedDir;
    private $quality = 80;

    public function __construct($filePath)
    {
        $this->filePath = APP_ROOT . PUBLIC_DIR . $filePath;
        $this->fileName = pathinfo($filePath)['filename'];
        $this->fileExt = pathinfo($filePath)['extension'];
        $this->resizedDir = APP_ROOT . PUBLIC_DIR . '/uploads/resized/';

        if (!is_dir($this->resizedDir)) {
            mkdir($this->resizedDir);
        }
    }

    public function resizeImage($new_width, $new_height)
    {
        //Create new file path
        $resizedFilePath = $this->resizedDir . $this->fileName . '-' . $new_width . 'x' . $new_height . '.' . $this->fileExt;
        //Check if it's already resized
        if (file_exists($resizedFilePath)) {
            $this->outputImage($resizedFilePath);
        } else {
            $manager = new ImageManager();
            $img = $manager->make($this->filePath);
            $img->fit($new_width, $new_height);
            $img->save($resizedFilePath, $this->quality, $this->fileExt);
            $this->outputImage($resizedFilePath);
        }
    }

    private function outputImage($path)
    {
        $mime = getimagesize($path);
        header('Content-Type: ' . $mime['mime']);
        if ($mime['mime'] == 'image/png') {
            imagepng(imagecreatefrompng($path));
        }
        if ($mime['mime'] == 'image/jpg' || $mime['mime'] == 'image/jpeg' || $mime['mime'] == 'image/pjpeg') {
            imagejpeg(imagecreatefromjpeg($path));
        }
    }
}
