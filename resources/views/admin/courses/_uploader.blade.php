{{-- پارامترها: $target (teaser), $id, $maxMb --}}
<div class="uploader" data-target="{{ $target }}" data-id="{{ $id }}" data-max="{{ $maxMb }}" style="display:grid;gap:6px;margin:8px 0">
    <input type="file" class="u-file" accept="video/mp4,video/quicktime,video/webm,video/x-matroska,.mp4,.mov,.mkv,.webm,.m4v">
    <button type="button" class="u-start btn btn-primary">شروع آپلود</button>
    <progress class="u-bar" value="0" max="100" hidden style="width:100%"></progress>
    <small class="u-msg" role="status"></small>
</div>

@once
<script>
(function () {
    var CHUNK = {{ (int) config('learning.chunk_mb') }} * 1024 * 1024;
    var TOKEN = @json(csrf_token());
    var CHUNK_URL = @json(route('admin.uploads.chunk'));
    var START_URL = @json(route('admin.uploads.start'));
    var DONE_URL = @json(route('admin.uploads.complete'));

    function uuid() {
        if (window.crypto && crypto.randomUUID) return crypto.randomUUID();
        return ([1e7] + -1e3 + -4e3 + -8e3 + -1e11).replace(/[018]/g, function (c) {
            return (c ^ crypto.getRandomValues(new Uint8Array(1))[0] & 15 >> c / 4).toString(16);
        });
    }

    function sleep(ms) { return new Promise(function (r) { setTimeout(r, ms); }); }

    async function post(url, body) {
        for (var attempt = 1; attempt <= 3; attempt++) {
            try {
                var res = await fetch(url, {method: 'POST', body: body, headers: {'X-CSRF-TOKEN': TOKEN, 'Accept': 'application/json'}});
                if (res.ok) return res.json();
                if (res.status < 500 && res.status !== 419) {
                    var j = await res.json().catch(function () { return {}; });
                    var err = new Error(j.message || ('خطا ' + res.status)); err.fatal = true; throw err;
                }
            } catch (e) {
                if (e.fatal || attempt === 3) throw e;
            }
            await sleep(1000 * attempt);
        }
        throw new Error('خطای شبکه');
    }

    function init() { document.querySelectorAll('.uploader').forEach(function (box) {
        var file = box.querySelector('.u-file'), btn = box.querySelector('.u-start'),
            bar = box.querySelector('.u-bar'), msg = box.querySelector('.u-msg');

        btn.addEventListener('click', async function () {
            var f = file.files[0];
            if (!f) { msg.textContent = 'ابتدا فایل را انتخاب کن.'; return; }
            if (f.size > Number(box.dataset.max) * 1024 * 1024) { msg.textContent = 'حجم فایل بیشتر از ' + box.dataset.max + ' مگابایت است.'; return; }

            btn.disabled = true; bar.hidden = false; bar.value = 0;
            var id = null, total = Math.ceil(f.size / CHUNK);

            try {
                var start = new FormData();
                start.append('target', box.dataset.target); start.append('target_id', box.dataset.id); start.append('total', total);
                var started = await post(START_URL, start);
                id = started.upload_id;
                for (var i = 0; i < total; i++) {
                    var fd = new FormData();
                    fd.append('upload_id', id); fd.append('index', i);
                    fd.append('chunk', f.slice(i * CHUNK, (i + 1) * CHUNK), 'part');
                    await post(CHUNK_URL, fd);
                    bar.value = Math.round(((i + 1) / total) * 100);
                    msg.textContent = 'در حال آپلود… ' + bar.value + '٪';
                }
                var done = new FormData();
                done.append('upload_id', id); done.append('total', total); done.append('filename', f.name);
                await post(DONE_URL, done);
                msg.textContent = 'آپلود کامل شد؛ پردازش در صف است.';
                setTimeout(function () { location.reload(); }, 1500);
            } catch (e) {
                msg.textContent = 'خطا: ' + e.message; btn.disabled = false;
            }
        });
    }); }
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init); else init();
})();
</script>
@endonce
