<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Http\Requests\Student\UpdateProfileRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

final class ProfileController extends Controller
{
    public function show(Request $request): View
    {
        return view('student.profile', ['student' => $request->user()]);
    }

    public function update(UpdateProfileRequest $request): RedirectResponse
    {
        $user = $request->user();
        $validated = $request->validated();

        $data = [
            'name' => $validated['name'],
            'phone' => $validated['phone'] ?? null,
        ];

        if ($request->boolean('remove_avatar')) {
            if ($user->avatar_path && Storage::disk('public')->exists($user->avatar_path)) {
                Storage::disk('public')->delete($user->avatar_path);
            }
            $data['avatar_path'] = null;
        } elseif ($request->hasFile('avatar')) {
            if ($user->avatar_path && Storage::disk('public')->exists($user->avatar_path)) {
                Storage::disk('public')->delete($user->avatar_path);
            }
            $path = $request->file('avatar')->store('avatars', 'public');
            $data['avatar_path'] = $path;
        }

        $user->update($data);

        return redirect()
            ->route('student.profile.edit')
            ->with('success', 'اطلاعات پروفایل با موفقیت به‌روزرسانی شد.');
    }
}
