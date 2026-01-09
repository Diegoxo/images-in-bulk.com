<?php
/**
 * Global Security Headers
 * Applying modern HTTP security headers to protect against common attacks.
 */

function applySecurityHeaders()
{
    // 1. Protection against Clickjacking (X-Frame-Options)
    // Deny embedding this site in an iframe
    header('X-Frame-Options: DENY');

    // 2. Protection against MIME type sniffing (X-Content-Type-Options)
    // Prevents the browser from "guessing" the file type (e.g. executing an image as a script)
    header('X-Content-Type-Options: nosniff');

    // 3. XSS Protection (X-XSS-Protection)
    // Although deprecated in some modern browsers, it is still useful for older ones.
    header('X-XSS-Protection: 1; mode=block');

    // 4. Content Security Policy (CSP)
    // Controls which resources (scripts, styles, images) can be loaded.
    // 'unsafe-inline' and 'unsafe-eval' are allowed strictly for current functionality compatibility,
    // but ideally should be removed in future refinements.
    // We allow connection to OpenAI and Google/Stripe APIs.
    header("Content-Security-Policy: default-src 'self' https:; script-src 'self' 'unsafe-inline' 'unsafe-eval' https://js.stripe.com https://cdnjs.cloudflare.com; style-src 'self' 'unsafe-inline' https://fonts.googleapis.com; font-src 'self' https://fonts.gstatic.com data:; img-src 'self' data: https: blob:; connect-src 'self' https://api.openai.com https://api.stripe.com data:;");

    // 5. Referrer Policy
    // Controls how much information is sent in the Referer header when navigating away.
    header('Referrer-Policy: strict-origin-when-cross-origin');

    // 6. Strict Transport Security (HSTS)
    // Forces HTTPS (Only if not in local dev environment)
    if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
        header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
    }
}

// Automatically apply when included
applySecurityHeaders();
?>