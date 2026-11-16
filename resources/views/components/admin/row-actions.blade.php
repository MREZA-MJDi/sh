{{-- Edit / delete buttons for one row. `row` is serialised into data-fill → modal.js fills the form by field name. --}}
@props(['edit' => null, 'del' => null, 'row' => [], 'name' => '', 'url' => null, 'editTitle' => 'ویرایش'])
<div class="adm-row-actions">
  {{ $slot }}
  @if ($edit)<x-admin.btn variant="soft" size="sm" icon="edit" :data-modal-open="$edit" :data-title="$editTitle" :data-fill="json_encode($row, JSON_UNESCAPED_UNICODE)" :data-action="$url" data-method="PUT">ویرایش</x-admin.btn>@endif
  @if ($del)<x-admin.btn variant="bad" size="sm" icon="trash" :data-modal-open="$del" :data-name="$name" :data-action="$url" aria-label="حذف"/>@endif
</div>
