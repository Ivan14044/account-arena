<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\EmailTemplate;
use Illuminate\Http\Request;

class EmailTemplateController extends Controller
{
    public function index(Request $request)
    {
        $emailTemplates = EmailTemplate::query()
            ->orderBy('id', 'desc')
            ->get();

        return view('admin.email-templates.index', compact('emailTemplates'));
    }

    public function show(EmailTemplate $emailTemplate)
    {
        $emailTemplate->load('translations');

        $translation = $emailTemplate->translations
            ->where('locale', 'ru')
            ->pluck('value', 'code')
            ->toArray();

        if (!isset($translation['title']) || !isset($translation['message'])) {
            abort(404, 'Russian translation not found for this email template.');
        }

        $subject = $translation['title'];
        $params = [
            'service_name' => 'ChatGPT',
            'amount' => '10.00 USD',
            'email' => 'john@example.com',
            'url' => url('/reset-password/example')
        ];

        if ($emailTemplate->code === 'reset_password') {
            $body = view('emails.reset-password', [
                'translation' => $translation,
                'url' => $params['url'],
                'email' => $params['email'],
            ])->render();
        } else {
            $subject = \App\Services\EmailService::renderTemplate($translation['title'], $params);
            $body = \App\Services\EmailService::renderTemplate($translation['message'], $params);
        }

        return view('emails.base', [
            'subject' => $subject,
            'body' => $body,
        ]);
    }

    public function edit(EmailTemplate $emailTemplate)
    {
        $emailTemplate->load('translations');
        $emailTemplateData = $emailTemplate->translations->groupBy('locale')->map(function ($translations) {
            return $translations->pluck('value', 'code')->toArray();
        });

        return view('admin.email-templates.edit', compact('emailTemplate', 'emailTemplateData'));
    }

    public function update(Request $request, EmailTemplate $emailTemplate)
    {
        $validated = $request->validate($this->getRules());

        $emailTemplate->update($validated);
        $emailTemplate->saveTranslation($validated);

        $route = $request->has('save')
            ? route('admin.email-templates.edit', $emailTemplate->id)
            : route('admin.email-templates.index');

        return redirect($route)->with('success', 'Email template successfully updated.');
    }

    private function getRules()
    {
        $rules = [
            'name' => 'required|string|max:255',
        ];

        foreach (config('langs') as $lang => $flag) {
            foreach(EmailTemplate::TRANSLATION_FIELDS as $field) {
                $rules[$field . '.' . $lang] = ['nullable', 'string'];
            }
        }

        return $rules;
    }
}
