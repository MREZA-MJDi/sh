<?php

namespace App\Services\Learning;

use App\Models\User;

final class ContentWatermark
{
    public static function identity(User $user): string
    {
        return (string) ($user->phone ?: $user->email ?: 'Account #'.$user->getKey());
    }

    public static function markImage(string $png, string $identity): string
    {
        abort_unless(extension_loaded('gd'), 503);
        $image = @imagecreatefromstring($png);
        abort_unless($image !== false, 422);

        $width = imagesx($image);
        $height = imagesy($image);
        $label = 'SHEYKHAN | '.$identity;
        $font = 4;
        $lineWidth = imagefontwidth($font) * strlen($label);
        $ink = imagecolorallocatealpha($image, 28, 43, 75, 76);
        $light = imagecolorallocatealpha($image, 255, 255, 255, 70);
        $badge = imagecolorallocate($image, 19, 38, 66);
        $white = imagecolorallocate($image, 255, 255, 255);
        imagealphablending($image, true);
        imagesavealpha($image, true);

        for ($y = 72, $row = 0; $y < $height; $y += 150, $row++) {
            for ($x = 24 + ($row % 2) * 72; $x < $width; $x += max(270, $lineWidth + 72)) {
                imagestring($image, $font, $x + 1, $y + 1, $label, $light);
                imagestring($image, $font, $x, $y, $label, $ink);
            }
        }

        imagefilledrectangle($image, 0, 0, min($width - 1, $lineWidth + 15), min($height - 1, 24), $badge);
        imagestring($image, $font, 7, 5, $label, $white);

        ob_start();
        try {
            imagepng($image);
            return (string) ob_get_contents();
        } finally {
            ob_end_clean();
        }
    }
}
