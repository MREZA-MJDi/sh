@props(['faqs'])
<section class="mx-auto max-w-4xl px-5 py-20">
    <x-ui.section-title title="سؤالات متداول" />
    @foreach($faqs as $faq)
        <x-ui.accordion-item :title="$faq['q']" name="faq">{{ $faq['a'] }}</x-ui.accordion-item>
    @endforeach
</section>
