{{-- Search box (+ extra filters in the slot). Works with data-filter wrapper, see filter.js --}}
@props(['placeholder' => 'جستجو…'])
<div class="adm-toolbar">
  <label class="adm-search-field"><x-admin.icon name="search"/><input class="adm-input" type="search" data-filter-q placeholder="{{ $placeholder }}" aria-label="{{ $placeholder }}"></label>
  {{ $slot }}
</div>
