<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\EmailTemplate;
use App\Models\User;
use App\Services\EmailService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;

class EmailTemplateController extends Controller
{
    public function index(Request $request)
    {
        $emailTemplates = EmailTemplate::query()
            ->orderBy('id', 'desc')
            ->get();

        return view('admin.email-templates.index', compact('emailTemplates'));
    }

    public function create()
    {
        return view('admin.email-templates.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate($this->getRules(true));

        // ВАЖНО: Санитизация данных для защиты от XSS
        $validated = $this->sanitizeTemplateData($validated);

        $emailTemplate = EmailTemplate::create([
            'code' => $validated['code'],
            'name' => $validated['name'],
        ]);

        $emailTemplate->saveTranslation($validated);

        $route = $request->has('save')
            ? route('admin.email-templates.edit', $emailTemplate->id)
            : route('admin.email-templates.index');

        return redirect($route)->with('success', 'Email template successfully created.');
    }

    public function show(EmailTemplate $emailTemplate)
    {
        $emailTemplate->load('translations');

        // Try to get Russian translation, fallback to English, then any available
        $translation = $emailTemplate->translations
            ->where('locale', 'ru')
            ->pluck('value', 'code')
            ->toArray();

        if (empty($translation) || !isset($translation['title']) || !isset($translation['message'])) {
            $translation = $emailTemplate->translations
                ->where('locale', 'en')
                ->pluck('value', 'code')
                ->toArray();
        }

        if (empty($translation) || !isset($translation['title']) || !isset($translation['message'])) {
            abort(404, 'Email template translation not found.');
        }

        // Prepare test parameters based on template code
        $params = $this->getPreviewParams($emailTemplate->code);

        // Render subject and body
        $subject = EmailService::renderTemplate($translation['title'], $params);
        
        if ($emailTemplate->code === 'reset_password') {
            $body = view('emails.reset-password', [
                'translation' => $translation,
                'url' => $params['url'],
                'email' => $params['email'],
            ])->render();
        } else {
            $body = \App\Services\EmailService::renderTemplate($translation['message'], $params);
        }

        return view('emails.base', [
            'subject' => $subject,
            'body' => $body,
        ]);
    }

    /**
     * Get preview parameters for email template based on template code
     */
    private function getPreviewParams(string $templateCode): array
    {
        $baseParams = [
            'email' => 'john@example.com',
            'url' => url('/reset-password/example-token'),
        ];

        switch ($templateCode) {
            case 'payment_confirmation':
                return array_merge($baseParams, [
                    'amount' => '10.00 USD',
                ]);

            case 'product_purchase_confirmation':
                return array_merge($baseParams, [
                    'products_count' => '3',
                    'total_amount' => '25.50 USD',
                ]);

            case 'guest_purchase_confirmation':
                return array_merge($baseParams, [
                    'products_count' => '2',
                    'total_amount' => '15.99 USD',
                    'guest_email' => 'guest@example.com',
                ]);

            case 'reset_password':
                return $baseParams;

            default:
                // Generic parameters for custom templates
                return array_merge($baseParams, [
                    'amount' => '10.00 USD',
                    'products_count' => '1',
                    'total_amount' => '10.00 USD',
                    'guest_email' => 'guest@example.com',
                ]);
        }
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

        // ВАЖНО: Санитизация данных для защиты от XSS
        $validated = $this->sanitizeTemplateData($validated);

        $emailTemplate->update($validated);
        $emailTemplate->saveTranslation($validated);

        $route = $request->has('save')
            ? route('admin.email-templates.edit', $emailTemplate->id)
            : route('admin.email-templates.index');

        return redirect($route)->with('success', 'Email template successfully updated.');
    }

    /**
     * Санитизация данных шаблона для защиты от XSS
     */
    private function sanitizeTemplateData(array $data): array
    {
        foreach (config('langs') as $lang => $flag) {
            foreach(\App\Models\EmailTemplate::TRANSLATION_FIELDS as $field) {
                $key = $field . '.' . $lang;
                if (isset($data[$key]) && is_string($data[$key])) {
                    // Разрешаем базовые теги форматирования для писем
                    $allowedTags = '<b><strong><i><em><u><br><p><ul><ol><li><a><div><span><h1><h2><h3><h4><h5><h6><table><thead><tbody><tr><th><td>';
                    $data[$key] = strip_tags($data[$key], $allowedTags);
                    
                    // Удаляем опасные атрибуты
                    $data[$key] = preg_replace('/on\w+\s*=\s*["\'][^"\']*["\']/i', '', $data[$key]);
                    $data[$key] = preg_replace('/javascript:/i', '', $data[$key]);
                }
            }
        }
        return $data;
    }

    /**
     * Send test email for template
     */
    public function sendTest(Request $request, EmailTemplate $emailTemplate)
    {
        $request->validate([
            'test_email' => 'required|email',
        ]);

        $emailTemplate->load('translations');

        // Get translation (try user locale, then default, then any)
        $locale = $request->input('locale', 'en');
        $translation = $emailTemplate->translations
            ->where('locale', $locale)
            ->pluck('value', 'code')
            ->toArray();

        if (empty($translation) || !isset($translation['title']) || !isset($translation['message'])) {
            $translation = $emailTemplate->translations
                ->where('locale', 'en')
                ->pluck('value', 'code')
                ->toArray();
        }

        if (empty($translation) || !isset($translation['title'], $translation['message'])) {
            return back()->with('error', 'Email template translation not found.');
        }

        // Prepare test parameters
        $params = $this->getPreviewParams($emailTemplate->code);
        $params['email'] = $request->input('test_email');

        // Render subject and body
        $subject = EmailService::renderTemplate($translation['title'], $params);
        
        if ($emailTemplate->code === 'reset_password') {
            $body = view('emails.reset-password', [
                'translation' => $translation,
                'url' => $params['url'],
                'email' => $params['email'],
            ])->render();
        } else {
            $body = EmailService::renderTemplate($translation['message'], $params);
        }

        try {
            EmailService::configureMailFromOptions();
            
            Mail::send('emails.base', [
                'subject' => $subject,
                'body' => $body,
            ], function ($message) use ($request, $subject) {
                $message->to($request->input('test_email'))->subject($subject);
            });

            return back()->with('success', 'Test email sent successfully to ' . $request->input('test_email'));
        } catch (TransportExceptionInterface $e) {
            $errorMessage = $e->getMessage();
            
            // Provide more helpful error messages
            if (strpos($errorMessage, '535') !== false || strpos($errorMessage, 'authentication') !== false || strpos($errorMessage, 'Authentication') !== false) {
                $errorMessage = 'SMTP authentication failed. Please check: Username and password are correct; Username might need to be full email address or just username (depends on SMTP server); Password might need to be an app-specific password (for Gmail, etc.); Encryption setting matches your SMTP server (tls/ssl); Port matches encryption (587 for TLS, 465 for SSL).';
            } elseif (strpos($errorMessage, 'Connection') !== false || strpos($errorMessage, 'connection') !== false) {
                $errorMessage = 'Cannot connect to SMTP server. Please check host and port settings.';
            }
            
            Log::error('Test email sending failed', [
                'error' => $e->getMessage(),
                'template_id' => $emailTemplate->id,
                'test_email' => $request->input('test_email'),
            ]);
            
            return back()->with('error', $errorMessage);
        } catch (\Exception $e) {
            $errorMessage = $e->getMessage();
            
            // Check if it's an authentication error even if not TransportExceptionInterface
            if (strpos($errorMessage, '535') !== false || strpos($errorMessage, 'authentication') !== false || strpos($errorMessage, 'Authentication') !== false) {
                $errorMessage = 'SMTP authentication failed. Please check your username and password in SMTP settings.';
            }
            
            Log::error('Test email sending failed', [
                'error' => $e->getMessage(),
                'template_id' => $emailTemplate->id,
                'test_email' => $request->input('test_email'),
            ]);
            
            return back()->with('error', 'Failed to send test email: ' . $errorMessage);
        }
    }

    private function getRules($isCreate = false)
    {
        $rules = [
            'name' => 'required|string|max:255',
        ];

        if ($isCreate) {
            $rules['code'] = 'required|string|max:255|unique:email_templates,code';
        }

        foreach (config('langs') as $lang => $flag) {
            foreach(EmailTemplate::TRANSLATION_FIELDS as $field) {
                $rules[$field . '.' . $lang] = ['nullable', 'string'];
            }
        }

        return $rules;
    }
}
