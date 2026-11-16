{{-- Free / paid switch with price fields (shown only when "paid"). Field names: access, price, sale_price --}}
@props(['label' => 'نوع دسترسی', 'sale' => true, 'access' => 'free'])
<x-admin.chips :label="$label" name="access" :multiple="false" :options="['free' => 'رایگان', 'paid' => 'پولی']" :checked="[$access]"/>
<div data-show-if="access=paid">
  <x-admin.input label="قیمت" name="price" type="number" min="0" step="1000" addon="تومان" placeholder="مثلاً 1800000"/>
  @if ($sale)<x-admin.input label="قیمت با تخفیف" name="sale_price" type="number" min="0" step="1000" addon="تومان" hint="خالی = بدون تخفیف"/>@endif
</div>
