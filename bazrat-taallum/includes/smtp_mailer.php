<?php
/**
 * إرسال بريد HTML عبر SMTP (587 + STARTTLS أو 465 SSL).
 */

function smtp_mail_encode_subject(string $subject): string {
    return '=?UTF-8?B?' . base64_encode($subject) . '?=';
}

/**
 * @return array{ok:bool,error:string}
 */
function smtp_send_html(string $to, string $subject, string $htmlBody, string $fromEmail, string $fromName = ''): array {
    $host = trim(env_value('SMTP_HOST', get_system_setting('smtp_host', '')));
    if ($host === '') {
        return ['ok' => false, 'error' => 'لم يُضبط خادم SMTP في لوحة التحكم ← الإعدادات'];
    }

    $port = (int)env_value('SMTP_PORT', get_system_setting('smtp_port', '587'));
    if ($port <= 0) {
        $port = 587;
    }
    $user = env_value('SMTP_USER', get_system_setting('smtp_user', ''));
    $pass = env_value('SMTP_PASSWORD', get_system_setting('smtp_password', ''));
    $enc = strtolower(trim(env_value('SMTP_ENCRYPTION', get_system_setting('smtp_encryption', 'tls'))));

    $fromName = $fromName !== '' ? $fromName : env_value('MAIL_FROM_NAME', get_system_setting('mail_from_name', platform_display_name()));
    $fromEmail = $fromEmail !== '' ? $fromEmail : env_value('MAIL_FROM_EMAIL', get_system_setting('mail_from_email', 'noreply@localhost'));
    if (!filter_var($fromEmail, FILTER_VALIDATE_EMAIL)) {
        return ['ok' => false, 'error' => 'عنوان المرسل (mail_from_email) غير صالح'];
    }
    if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
        return ['ok' => false, 'error' => 'عنوان المستلم غير صالح'];
    }

    $subjectEnc = smtp_mail_encode_subject($subject);
    $boundary = 'bnd_' . bin2hex(random_bytes(8));

    $plain = trim(html_entity_decode(strip_tags(str_replace(['<br>', '<br/>', '<br />'], "\n", $htmlBody)), ENT_QUOTES, 'UTF-8'));

    $mimeHeaders = [];
    $mimeHeaders[] = 'MIME-Version: 1.0';
    $mimeHeaders[] = 'Content-Type: multipart/alternative; boundary="' . $boundary . '"';
    $mimeHeaders[] = 'From: ' . smtp_mail_format_address($fromEmail, $fromName);

    $body = "--$boundary\r\n";
    $body .= "Content-Type: text/plain; charset=UTF-8\r\nContent-Transfer-Encoding: base64\r\n\r\n";
    $body .= chunk_split(base64_encode($plain));
    $body .= "--$boundary\r\n";
    $body .= "Content-Type: text/html; charset=UTF-8\r\nContent-Transfer-Encoding: base64\r\n\r\n";
    $body .= chunk_split(base64_encode($htmlBody));
    $body .= "--$boundary--\r\n";

    $socket = null;
    try {
        if ($enc === 'ssl') {
            $ctx = stream_context_create([
                'ssl' => [
                    'verify_peer' => get_system_setting('smtp_verify_peer', '1') === '1',
                    'verify_peer_name' => get_system_setting('smtp_verify_peer', '1') === '1',
                ],
            ]);
            $socket = @stream_socket_client(
                'ssl://' . $host . ':' . $port,
                $errno,
                $errstr,
                30,
                STREAM_CLIENT_CONNECT,
                $ctx
            );
            if (!$socket) {
                return ['ok' => false, 'error' => "SSL ($host:$port): $errstr ($errno)"];
            }
            smtp_expect($socket, [220]);
            smtp_cmd($socket, 'EHLO ' . smtp_ehlo_hostname(), [250]);
            if ($user !== '') {
                smtp_auth_login($socket, $user, $pass);
            }
        } else {
            $socket = @stream_socket_client("tcp://$host:$port", $errno, $errstr, 30);
            if (!$socket) {
                return ['ok' => false, 'error' => "TCP ($host:$port): $errstr ($errno)"];
            }
            smtp_expect($socket, [220]);
            smtp_cmd($socket, 'EHLO ' . smtp_ehlo_hostname(), [250]);
            $useTls = ($enc === 'tls' || $port === 587);
            if ($useTls) {
                smtp_cmd($socket, 'STARTTLS', [220]);
                $cryptoOk = @stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
                if (!$cryptoOk) {
                    fclose($socket);
                    return ['ok' => false, 'error' => 'فشل TLS (STARTTLS)'];
                }
                smtp_cmd($socket, 'EHLO ' . smtp_ehlo_hostname(), [250]);
            }
            if ($user !== '') {
                smtp_auth_login($socket, $user, $pass);
            }
        }

        smtp_cmd($socket, 'MAIL FROM:<' . $fromEmail . '>', [250]);
        smtp_cmd($socket, 'RCPT TO:<' . $to . '>', [250, 251]);
        smtp_cmd($socket, 'DATA', [354]);
        $data = 'Subject: ' . $subjectEnc . "\r\n" . implode("\r\n", $mimeHeaders) . "\r\n\r\n" . $body;
        $data = preg_replace('/\r\n\./', "\r\n..", $data);
        fwrite($socket, $data . "\r\n.\r\n");
        smtp_expect($socket, [250]);
        fwrite($socket, "QUIT\r\n");
        fclose($socket);
        $socket = null;
        return ['ok' => true, 'error' => ''];
    } catch (Throwable $e) {
        if ($socket !== null && is_resource($socket)) {
            @fclose($socket);
        }
        return ['ok' => false, 'error' => $e->getMessage()];
    }
}

function smtp_ehlo_hostname(): string {
    $h = parse_url(APP_URL, PHP_URL_HOST);
    return ($h && $h !== '') ? $h : 'localhost';
}

function smtp_mail_format_address(string $email, string $name): string {
    if ($name === '') {
        return '<' . $email . '>';
    }
    return '=?UTF-8?B?' . base64_encode($name) . '?= <' . $email . '>';
}

function smtp_read_multiline($socket): string {
    $data = '';
    while (!feof($socket)) {
        $line = fgets($socket, 8192);
        if ($line === false) {
            break;
        }
        $data .= $line;
        if (preg_match('/^[0-9]{3} /', $line)) {
            break;
        }
    }
    return $data;
}

function smtp_expect($socket, array $codes): void {
    $resp = smtp_read_multiline($socket);
    $code = (int)substr($resp, 0, 3);
    if (!in_array($code, $codes, true)) {
        throw new RuntimeException(trim($resp));
    }
}

function smtp_cmd($socket, string $cmd, array $expectCodes): void {
    fwrite($socket, $cmd . "\r\n");
    smtp_expect($socket, $expectCodes);
}

function smtp_auth_login($socket, string $user, string $pass): void {
    smtp_cmd($socket, 'AUTH LOGIN', [334]);
    fwrite($socket, base64_encode($user) . "\r\n");
    smtp_expect($socket, [334]);
    fwrite($socket, base64_encode($pass) . "\r\n");
    smtp_expect($socket, [235]);
}
