@props(['target', 'course', 'max', 'label' => 'بارگذاری ویدیو', 'statusUrl' => null, 'tone' => 'edu'])
<div class="adm-uploader" data-tone="{{ $tone }}" data-course-upload data-target="{{ $target }}" data-course-id="{{ $course->id }}"
  data-start-url="{{ route('admin.uploads.start') }}" data-chunk-url="{{ route('admin.uploads.chunk') }}"
  data-complete-url="{{ route('admin.uploads.complete') }}" data-cancel-url="{{ route('admin.uploads.cancel') }}"
  @if($statusUrl) data-status-url="{{ $statusUrl }}" @endif
  data-max-mb="{{ $max }}" data-chunk-mb="{{ config('learning.chunk_mb') }}">
  @if(trim((string) $slot) !== '')<div class="adm-form">{{ $slot }}</div>@endif
  <label class="adm-drop" data-upload-drop>
    <input class="adm-drop-input" type="file" accept="video/mp4,video/quicktime,video/webm,video/x-matroska,video/x-m4v" data-upload-file>
    <span class="adm-drop-ico"><x-admin.icon name="up"/></span>
    <span class="adm-drop-chips">
      <x-admin.badge tone="ok">MP4</x-admin.badge><x-admin.badge tone="ok">MOV</x-admin.badge><x-admin.badge tone="ok">MKV</x-admin.badge><x-admin.badge tone="ok">WebM</x-admin.badge>
      <x-admin.badge tone="warn">{{ number_format((int) $max) }} MB</x-admin.badge>
    </span>
  </label>
  <div class="adm-up-file" data-upload-info hidden>
    <span class="adm-drop-ico"><x-admin.icon name="video"/></span>
    <b data-upload-name></b><x-admin.badge tone="ok"><span data-upload-size></span></x-admin.badge>
  </div>
  <div class="adm-progress" data-upload-progress hidden><div class="adm-progress-bar" data-upload-bar></div></div>
  <div class="adm-up-state" data-upload-status role="status" aria-live="polite"></div>
  <div class="adm-actions" style="margin-top:12px">
    <button type="button" class="adm-btn adm-btn-primary" data-upload-start><x-admin.icon name="up"/>{{ $label }}</button>
    {{ $actions ?? '' }}
  </div>
</div>
