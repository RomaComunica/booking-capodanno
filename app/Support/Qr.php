<?php
namespace App\Support;

use chillerlan\QRCode\{QRCode, QROptions};

class Qr
{
    public static function dataUri(string $text, int $size = 240): string
    {
        $scale = max(1, (int)round($size / 25));
        $options = new QROptions([
            'outputType'  => QRCode::OUTPUT_IMAGE_PNG,
            'scale'       => $scale,
            'imageBase64' => true,
        ]);

        return (new QRCode($options))->render($text);
    }
}
