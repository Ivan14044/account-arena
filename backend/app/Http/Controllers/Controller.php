<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Http\Request;

class Controller extends BaseController
{
    use AuthorizesRequests, ValidatesRequests;

    protected function getApiUser(Request $request)
    {
        $token = $request->bearerToken();
        if (!$token) {
            return false;
        }

        $accessToken = \Laravel\Sanctum\PersonalAccessToken::findToken($token);
        if (!$accessToken) {
            return false;
        }

        return $accessToken->tokenable;
    }

    /**
     * Sanitize HTML content to prevent XSS
     * Uses a whitelist of allowed tags and removes dangerous attributes
     */
    protected function sanitizeHtml(?string $html): ?string
    {
        if (empty($html)) {
            return $html;
        }

        // Allowed tags for CKEditor content
        $allowedTags = '<p><b><i><strong><em><a><ul><ol><li><br><h1><h2><h3><h4><h5><h6><img><span><div><table><thead><tbody><tr><th><td><figure><blockquote><hr><u>';
        
        // Strip all except allowed tags
        $clean = strip_tags($html, $allowedTags);

        // Remove dangerous attributes like onclick, onmouseover, etc.
        $clean = preg_replace('/on\w+\s*=/i', 'data-removed=', $clean);
        
        // Remove javascript: links
        $clean = preg_replace('/href\s*=\s*"\s*javascript:/i', 'href="#', $clean);
        
        // Remove <script> if somehow escaped strip_tags
        $clean = preg_replace('/<script\b[^>]*>(.*?)<\/script>/is', "", $clean);

        return $clean;
    }
}
