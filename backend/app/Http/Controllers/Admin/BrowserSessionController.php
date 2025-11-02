<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class BrowserSessionController extends Controller
{
    private function apiGet(string $path, array $orderedParams = [], int $timeout = 60)
    {
        try {
            $url = $this->buildApiUrl($path, $orderedParams);

            return Http::timeout($timeout)->get($url);
        } catch (\Throwable $e) {
            return null;
        }
    }

    private function apiPost(string $path, array $payload = [], int $timeout = 60)
    {
        try {
            return Http::timeout($timeout)->post($this->buildApiUrl($path), $payload);
        } catch (\Throwable $e) {
            return null;
        }
    }

    private function parseStartResponse($response): array
    {
        if (!$response || !$response->ok()) {
            return [null, null, null];
        }
        $data = $response->json() ?: [];
        return [
            $data['pid'] ?? null,
            $data['url'] ?? null,
            $data['port'] ?? null,
        ];
    }

    private function startRemoteSession(?string $profile = null): array
    {
        $ordered = [
            'app' => 'https://chatgpt.com',
            'title' => 'SubCloudy',
            'profile' => $profile ?: null,
            'kiosk' => 0,
        ];
        return $this->startRemoteSessionOrdered($ordered);
    }

    private function startRemoteSessionOrdered(array $orderedParams): array
    {
        $response = $this->apiGet('/new', $orderedParams);
        [$pid, $url, $port] = $this->parseStartResponse($response);

        if ($pid !== null || $url !== null || $port !== null) {
            Cache::forget('browser_sessions.active_count');
        }

        return [$pid, $url, $port];
    }

    private function formatSessionSummary($pid, $url, $port): string
    {
        $parts = [];
        if ($pid) {
            $parts[] = 'PID ' . e($pid);
        }
        if ($port) {
            $parts[] = 'Port ' . e($port);
        }
        if ($url) {
            $parts[] = 'URL <a href="' . e($url) . '" target="_blank" rel="noopener">' . e($url) . '</a>';
        }
        return empty($parts) ? 'Session started' : ('Session started: ' . implode(', ', $parts));
    }

    public function index()
    {
        return view('admin.browser-sessions.index', [
            'dataUrl' => route('admin.browser-sessions.data'),
        ]);
    }

    public function data()
    {
        $response = $this->apiGet('/list', [], 5);
        if ($response && $response->ok()) {
            $raw = $response->json('sessions') ?? [];
            $sessions = [];

            // Preload users keyed by session_pid for quick lookup
            $usersBySessionPid = User::whereNotNull('session_pid')
                ->get(['id', 'name', 'email', 'session_pid'])
                ->keyBy('session_pid');

            foreach ($raw as $s) {
                $sessionPid = isset($s['xpra_pid']) ? (string)$s['xpra_pid'] : null;

                $matchedUser = null;
                if ($sessionPid && $usersBySessionPid->has($sessionPid)) {
                    $matchedUser = $usersBySessionPid->get($sessionPid);
                }

                $uptimeSec = $s['uptime_sec'] ?? null;
                $sessions[] = array_merge($s, [
                    'user' => $matchedUser ? [
                        'id' => $matchedUser->id,
                        'name' => $matchedUser->name,
                        'email' => $matchedUser->email,
                        'edit_url' => route('admin.users.edit', $matchedUser->id),
                    ] : null,
                    'uptime_human' => $this->formatUptimeHuman($uptimeSec),
                ]);
            }

            return response()->json([
                'sessions' => $sessions,
            ]);
        }

        return response()->json(['sessions' => []]);
    }

    private function formatUptimeHuman($seconds): ?string
    {
        if ($seconds === null) return null;
        $seconds = (int)$seconds;
        if ($seconds < 0) $seconds = 0;

        $days = intdiv($seconds, 86400);
        $seconds %= 86400;
        $hours = intdiv($seconds, 3600);
        $seconds %= 3600;
        $minutes = intdiv($seconds, 60);
        $seconds %= 60;

        $parts = [];
        if ($days > 0) {
            $parts[] = $days . ' ' . ($days === 1 ? 'day' : 'days');
        }
        if ($hours > 0) {
            $parts[] = $hours . ' ' . ($hours === 1 ? 'hour' : 'hours');
        }
        if ($minutes > 0) {
            $parts[] = $minutes . ' ' . ($minutes === 1 ? 'minute' : 'minutes');
        }
        if ($seconds > 0 || empty($parts)) {
            $parts[] = $seconds . ' ' . ($seconds === 1 ? 'second' : 'seconds');
        }

        return implode(' ', $parts);
    }

    public function start()
    {
        [$pid, $url, $port] = $this->startRemoteSession();
        if ($pid === null && $url === null && $port === null) {
            return back()->with('error', 'Failed to start session');
        }

        return back()->with('success', $this->formatSessionSummary($pid, $url, $port));
    }

    public function startJson(Request $request)
    {
        $data = $request->validate([
            'app' => ['nullable', 'string', 'max:2048'],
            'title' => ['nullable', 'string', 'max:120'],
            'profile' => ['nullable', 'string', 'max:100'],
            'kiosk' => ['nullable'],
        ]);

        // 2) Нормализация
        $app = $this->normalizeUrl($data['app'] ?? null) ?? 'https://chatgpt.com';
        $title = isset($data['title']) ? trim((string)$data['title']) : 'SubCloudy';
        $profile = isset($data['profile']) ? trim((string)$data['profile']) : null;

        // kiosk → строго 0/1
        $kiosk = $data['kiosk'] ?? 0;
        $kiosk = filter_var($kiosk, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
        $kiosk = is_null($kiosk) ? 0 : (int)$kiosk;

        // 3) Собираем УПОРЯДОЧЕННЫЕ параметры для API
        $ordered = [
            'app' => $app,
            'title' => $title,
            'profile' => $profile ?: null,
            'kiosk' => $kiosk,
        ];

        // 4) Стартуем сессию (порядок ключей сохранится)
        [$pid, $url, $port] = $this->startRemoteSessionOrdered($ordered);

        if ($pid === null && $url === null && $port === null) {
            return response()->json(['ok' => false, 'error' => 'Failed to start session'], 500);
        }

        return response()->json(['ok' => true, 'pid' => $pid, 'url' => $url, 'port' => $port]);
    }


    public function stopByPid(Request $request)
    {
        $validated = $request->validate([
            'pid' => ['required', 'integer', 'min:1'],
        ]);
        $pid = (int)$validated['pid'];

        $response = $this->apiPost('/stop', ['pid' => $pid]);
        if (!$response || !$response->ok()) {
            $body = $response ? $response->body() : 'no response';
            return back()->with('error', 'Failed to stop by PID: ' . $body);
        }

        Cache::forget('browser_sessions.active_count');
        return back()->with('success', 'Stopped session by PID ' . e($pid));
    }

    public function stopAll(Request $request)
    {
        $clean = $request->boolean('clean');

        $response = $this->apiPost('/stop_all', ['clean' => $clean], 15);
        if (!$response || !$response->ok()) {
            return back()->with('error', 'Failed to stop all');
        }

        $json = $response->json();
        $summary = $json['summary'] ?? [];
        $stopped = $summary['xpra_stopped'] ?? null;
        $cleaned = $summary['profiles_cleaned'] ?? null;
        $msg = 'All sessions stopped';
        if ($clean) {
            $msg .= ' and profiles cleaned';
        }
        $details = [];
        if ($stopped !== null) {
            $details[] = 'stopped: ' . e($stopped);
        }
        if ($clean && $cleaned !== null) {
            $details[] = 'profiles cleaned: ' . e($cleaned);
        }
        if (!empty($details)) {
            $msg .= ' (' . implode(', ', $details) . ')';
        }

        Cache::forget('browser_sessions.active_count');
        return back()->with('success', $msg);
    }

    private function buildApiUrl(string $path, array $orderedParams = []): string
    {
        $base = rtrim(config('services.browser_api.url', ''), '/');
        $path = '/' . ltrim($path, '/');

        $parts = [];
        foreach ($orderedParams as $k => $v) {
            if ($v === null) continue;
            $parts[] = rawurlencode((string)$k) . '=' . rawurlencode((string)$v);
        }

        $qs = $parts ? ('?' . implode('&', $parts)) : '';

        return $base . $path . $qs;
    }

    private function normalizeUrl(?string $url): ?string
    {
        if (!$url) return null;
        $url = trim($url);
        if ($url === '') return null;

        if (!Str::startsWith($url, ['http://', 'https://'])) {
            $url = 'https://' . ltrim($url, '/');
        }
        return filter_var($url, FILTER_VALIDATE_URL) ? $url : null;
    }
}



