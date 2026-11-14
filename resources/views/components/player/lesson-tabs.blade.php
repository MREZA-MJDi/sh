@props(['attachments', 'comments'])
<div data-tabs>
    <div role="tablist" class="flex gap-6 border-b border-[#D9E0E4]">
        @foreach(['desc' => 'توضیحات', 'files' => 'فایل‌های جلسه', 'qa' => 'پرسش و پاسخ'] as $id => $label)
            <button type="button" role="tab" data-tab="{{ $id }}"
                    class="-mb-px border-b-2 border-transparent py-3 font-bold text-[#4A5560] aria-selected:border-[#0E7C66] aria-selected:text-[#0B6553]">
                {{ $label }}
            </button>
        @endforeach
    </div>

    <div class="py-6">
        <div role="tabpanel" data-panel="desc">
            <p data-lesson-desc class="leading-8 text-[#4A5560]"></p>
        </div>

        <div role="tabpanel" data-panel="files" hidden>
            <ul class="space-y-3">
                @foreach($attachments as $file)
                    <li class="flex items-center justify-between rounded-lg border border-[#D9E0E4] px-4 py-3">
                        <span class="font-medium text-[#16202A]">{{ $file['name'] }}</span>
                        <span class="text-sm text-[#4A5560]">{{ $file['size'] }}</span>
                    </li>
                @endforeach
            </ul>
        </div>

        <div role="tabpanel" data-panel="qa" hidden>
            <ul data-comments class="space-y-4">
                @foreach($comments as $c)
                    <li class="rounded-lg bg-[#F1F4F5] p-4">
                        <p class="text-sm font-bold text-[#16202A]">{{ $c['name'] }}</p>
                        <p class="mt-1 leading-7 text-[#4A5560]">{{ $c['text'] }}</p>
                    </li>
                @endforeach
            </ul>
            <form data-qa class="mt-6">
                <label for="question" class="block text-sm font-bold text-[#16202A]">سؤال خود را بنویسید</label>
                <textarea id="question" rows="3"
                          class="mt-2 w-full rounded-lg border border-[#B5C0C6] p-3 focus-visible:outline-2 focus-visible:outline-[#0E7C66]"></textarea>
                <x-ui.button type="submit" class="mt-2 px-5! py-2!">ارسال سؤال</x-ui.button>
            </form>
        </div>
    </div>
</div>
