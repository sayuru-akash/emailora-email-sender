<?php

namespace App\Http\Controllers;

use App\Http\Requests\SettingRequest;
use App\Http\Requests\TestEmailRequest;
use App\Models\SystemSetting;
use App\Services\Email\EmailPayload;
use App\Services\Email\EmailService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class SettingController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('settings/Index', [
            'settings' => SystemSetting::pluck('value', 'key'),
            'providerStatus' => [
                'resend' => ['configured' => (bool) config('emailora.resend.api_key')],
                'brevo' => ['configured' => (bool) config('emailora.brevo.api_key')],
            ],
        ]);
    }

    public function update(SettingRequest $request): RedirectResponse
    {
        foreach ($request->validated() as $key => $value) {
            SystemSetting::updateOrCreate(['key' => $key], ['group' => str_contains($key, 'provider') || str_contains($key, 'email') ? 'email' : 'general', 'value' => $value]);
        }

        return back()->with('success', 'Settings updated.');
    }

    public function testEmail(TestEmailRequest $request, EmailService $email): RedirectResponse
    {
        $result = $email->send(new EmailPayload(
            to: $request->string('to')->toString(),
            subject: 'Emailora test email',
            html: '<p>Emailora provider test.</p>',
            text: 'Emailora provider test.',
            fromEmail: config('emailora.from_email') ?: 'no-reply@example.com',
            fromName: config('emailora.from_name') ?: 'Emailora',
            replyTo: config('emailora.reply_to'),
        ), $request->string('provider')->toString() ?: null);

        if (! $result->accepted) {
            return back()->with('error', $result->errorMessage ?: 'Provider rejected the test email.');
        }

        return back()->with('success', 'Provider accepted the test email.');
    }
}
