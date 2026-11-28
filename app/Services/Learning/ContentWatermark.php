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
        if (! imageistruecolor($image)) {
            imagepalettetotruecolor($image);
        }

        $width = imagesx($image);
        $height = imagesy($image);
        $label = 'SHEYKHAN | '.$identity;
        $font = 4;
        $labelWidth = imagefontwidth($font) * strlen($label) + 16;
        $labelHeight = imagefontheight($font) + 8;
        $stamp = imagecreatetruecolor($labelWidth, $labelHeight);
        abort_unless($stamp !== false, 503);
        imagealphablending($stamp, false);
        imagesavealpha($stamp, true);
        imagefill($stamp, 0, 0, imagecolorallocatealpha($stamp, 0, 0, 0, 127));
        $background = imagecolorallocatealpha($stamp, 30, 51, 84, 122);
        $radius = intdiv($labelHeight, 2);
        imagefilledrectangle($stamp, $radius, 0, $labelWidth - $radius - 1, $labelHeight - 1, $background);
        imagefilledellipse($stamp, $radius, $radius, $labelHeight, $labelHeight, $background);
        imagefilledellipse($stamp, $labelWidth - $radius - 1, $radius, $labelHeight, $labelHeight, $background);
        imagestring($stamp, $font, 8, 4, $label, imagecolorallocatealpha($stamp, 30, 51, 84, 105));

        $angle = -32;
        $diagonal = imagerotate($stamp, $angle, imagecolorallocatealpha($stamp, 0, 0, 0, 127));
        abort_unless($diagonal !== false, 503);
        imagealphablending($diagonal, false);
        imagesavealpha($diagonal, true);
        imagefilter($diagonal, IMG_FILTER_GAUSSIAN_BLUR);

        $diagonalWidth = imagesx($diagonal);
        $diagonalHeight = imagesy($diagonal);
        $scale = min($width * 0.90 / $diagonalWidth, $height * 0.72 / $diagonalHeight);
        $stampWidth = max(1, (int) round($diagonalWidth * $scale));
        $stampHeight = max(1, (int) round($diagonalHeight * $scale));

        imagealphablending($image, true);
        imagesavealpha($image, true);
        imagecopyresampled(
            $image, $diagonal, intdiv($width - $stampWidth, 2), intdiv($height - $stampHeight, 2),
            0, 0, $stampWidth, $stampHeight, $diagonalWidth, $diagonalHeight,
        );

        ob_start();
        try {
            imagepng($image);
            return (string) ob_get_contents();
        } finally {
            ob_end_clean();
        }
    }
}
