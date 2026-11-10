<?php

namespace App\Models\Concerns;

use App\Support\RichTextSanitizer;

trait SanitizesRichText
{
    protected static function bootSanitizesRichText(): void
    {
        static::saving(function ($model): void {
            $sanitizer = app(RichTextSanitizer::class);
            foreach (['body', 'instructions', 'feedback', 'description'] as $attribute) {
                if (array_key_exists($attribute, $model->getAttributes()) && $model->getAttribute($attribute) !== null) {
                    $model->setAttribute($attribute, $sanitizer->sanitize($model->getAttribute($attribute)));
                }
            }
        });
    }
}
