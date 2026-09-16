<?php
/**
 * تسجيل دخول عبر فيسبوك (OAuth 2).
 * اضبط معرّف التطبيق والسر في لوحة التحكم ← الإعدادات.
 */

function facebook_oauth_callback_url(): string {
    return rtrim(APP_URL, '/') . '/auth/facebook-callback.php';
}

function facebook_app_configured(): bool {
    return trim(get_system_setting('facebook_app_id', '')) !== ''
        && trim(get_system_setting('facebook_app_secret', '')) !== '';
}

function facebook_oauth_start_url(): string {
    $appId = trim(get_system_setting('facebook_app_id', ''));
    $redirect = rawurlencode(facebook_oauth_callback_url());
    $state = bin2hex(random_bytes(16));
    $_SESSION['oauth_fb_state'] = $state;
    $scope = rawurlencode('email,public_profile');
    $v = 'v19.0';
    return "https://www.facebook.com/{$v}/dialog/oauth?client_id={$appId}&redirect_uri={$redirect}&state={$state}&scope={$scope}&response_type=code";
}

function facebook_http_get_json(string $url): ?array {
    $body = '';
    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 25);
        $body = curl_exec($ch);
        curl_close($ch);
    } else {
        $ctx = stream_context_create(['http' => ['timeout' => 25]]);
        $body = @file_get_contents($url, false, $ctx);
    }
    if ($body === false || $body === '') {
        return null;
    }
    $j = json_decode($body, true);
    return is_array($j) ? $j : null;
}

/**
 * @return array{access_token?:string,error?:string}|null
 */
function facebook_exchange_code(string $code): ?array {
    $appId = trim(get_system_setting('facebook_app_id', ''));
    $secret = trim(get_system_setting('facebook_app_secret', ''));
    $redirect = facebook_oauth_callback_url();
    $v = 'v19.0';
    $q = http_build_query([
        'client_id' => $appId,
        'redirect_uri' => $redirect,
        'client_secret' => $secret,
        'code' => $code,
    ]);
    $url = "https://graph.facebook.com/{$v}/oauth/access_token?{$q}";
    return facebook_http_get_json($url);
}

/**
 * @return array{id?:string,name?:string,email?:string}|null
 */
function facebook_graph_me(string $accessToken): ?array {
    $v = 'v19.0';
    $q = http_build_query([
        'fields' => 'id,name,email',
        'access_token' => $accessToken,
    ]);
    $url = "https://graph.facebook.com/{$v}/me?{$q}";
    return facebook_http_get_json($url);
}
