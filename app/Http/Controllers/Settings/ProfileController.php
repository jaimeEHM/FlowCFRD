<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\ProfileDeleteRequest;
use App\Http\Requests\Settings\ProfileUpdateRequest;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class ProfileController extends Controller
{
    /**
     * Show the user's profile settings page.
     */
    public function edit(Request $request): Response
    {
        return Inertia::render('settings/Profile', [
            'mustVerifyEmail' => $request->user() instanceof MustVerifyEmail,
            'status' => $request->session()->get('status'),
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $user = $request->user();

        $avatarFile = $request->file('avatar');
        $shouldRemoveAvatar = (bool) ($validated['avatar_remove'] ?? false);

        unset($validated['avatar'], $validated['avatar_remove']);

        $user->fill($validated);

        if ($avatarFile instanceof UploadedFile) {
            $user->avatar = $this->toDataUrl($avatarFile);
        } elseif ($shouldRemoveAvatar) {
            $user->avatar = null;
            $user->avatar_position_x = 0;
            $user->avatar_position_y = 0;
        }

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        $user->save();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Profile updated.')]);

        return to_route('profile.edit');
    }

    private function toDataUrl(UploadedFile $file): string
    {
        $mime = $file->getMimeType() ?: 'application/octet-stream';
        $contents = file_get_contents($file->getRealPath());

        return 'data:'.$mime.';base64,'.base64_encode($contents ?: '');
    }

    /**
     * Delete the user's profile.
     */
    public function destroy(ProfileDeleteRequest $request): RedirectResponse
    {
        $user = $request->user();

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}
