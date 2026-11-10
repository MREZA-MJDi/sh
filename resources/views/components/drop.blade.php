@props(['name' => 'files', 'accept' => 'image/*,.pdf,.doc,.docx'])
<label class="drop" data-drop>
  <input type="file" name="{{ $name }}[]" accept="{{ $accept }}" multiple hidden>
  <x-icon name="upload"/><b>فایل را بکشید و رها کنید یا کلیک کنید</b>
  <span>عکس، PDF یا Word · حداکثر ۱۰ مگابایت برای هر فایل</span>
  <ul class="files" data-files></ul>
</label>
