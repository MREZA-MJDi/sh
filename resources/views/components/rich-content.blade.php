@props(['value' => null, 'class' => ''])

<div {{ $attributes->class(['rich-content', $class]) }}>
    {!! app(\App\Support\RichTextSanitizer::class)->sanitize($value) !!}
</div>
