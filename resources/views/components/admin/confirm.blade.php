{{-- Delete confirmation. Trigger: data-modal-open="id" data-action="/admin/x/1" data-name="نام مورد" --}}
@props(['id', 'title' => 'حذف', 'text' => 'این مورد حذف می‌شود و قابل بازگشت نیست.', 'submit' => 'بله، حذف شود', 'method' => 'DELETE', 'demo' => false])
<x-admin.modal :id="$id" :title="$title" size="sm" :method="$method" :submit="$submit" :demo="$demo" danger plain>
  <p class="adm-confirm"><x-admin.icon name="alert"/><span><b data-modal-name></b><br>{{ $text }}</span></p>
</x-admin.modal>
