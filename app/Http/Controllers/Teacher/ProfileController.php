<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Http\Requests\Teacher\UpdateProfileRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

final class ProfileController extends Controller
{
    public function show(Request $request): View
    {
        return view('teacher.profile', ['teacher' => $request->user()]);
    }

    public function update(UpdateProfileRequest $request): RedirectResponse
    {
        $user = $request->user();
        $validated = $request->validated();

        $previousAvatarPath = $user->avatar_path;

        if ($request->boolean('remove_avatar')) {
            $user->avatar_path = null;
        } elseif ($request->hasFile('avatar')) {
            $path = $request->file('avatar')->store('avatars', 'public');

            if (! $path) {
                return back()->withErrors(['avatar' => 'ذخیره تصویر انجام نشد؛ لطفاً دوباره تلاش کنید.'])->withInput();
            }

            $user->avatar_path = $path;
        }

        $user->name = $validated['name'];
        if (array_key_exists('phone', $validated)) {
            $user->phone = $validated['phone'];
        }
        if (array_key_exists('subject', $validated)) {
            $user->subject = $validated['subject'];
        }
        if (array_key_exists('profile_note', $validated)) {
            $user->profile_note = $validated['profile_note'];
        }
        $user->save();

        // Keep the previous image until the new profile state is safely persisted.
        if ($previousAvatarPath && $previousAvatarPath !== $user->avatar_path) {
            Storage::disk('public')->delete($previousAvatarPath);
        }

        return redirect()
            ->route('teacher.profile.edit')
            ->with('success', 'اطلاعات پروفایل و تصویر مدرس با موفقیت به‌روزرسانی شد.');
    }
}
