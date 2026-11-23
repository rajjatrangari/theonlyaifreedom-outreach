<?php
// Outreach AI Freedom - Corporate Email Dispatcher & Verification Service
declare(strict_types=1);

class EmailService {

    /**
     * Sends an email via authenticated SMTP (port 465 SSL or 587 TLS),
     * falling back to standard PHP server mail with verified Return-Path if SMTP is unconfigured.
     */
    public static function sendEmail(
        string $toEmail,
        string $subject,
        string $messageBody,
        array $config = []
    ): array {
        $to = trim($toEmail);
        if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
            return [
                'status' => 'error',
                'message' => "Invalid recipient email address: '{$to}'"
            ];
        }

        $senderEmail = trim($config['sender_email'] ?? 'rajjat@theonlyaifreedom.in');
        if (empty($senderEmail) || !filter_var($senderEmail, FILTER_VALIDATE_EMAIL)) {
            $senderEmail = 'rajjat@theonlyaifreedom.in';
        }
        $senderName = trim($config['sender_name'] ?? 'Rajjat Rangari | The Only AI Freedom');

        $smtpHost = trim($config['smtp_host'] ?? '');
        $smtpPort = (int)($config['smtp_port'] ?? 465);
        $smtpUser = trim($config['smtp_user'] ?? '');
        $smtpPass = trim($config['smtp_pass'] ?? '');

        // If authenticated SMTP is configured, attempt SMTP transmission
        if (!empty($smtpHost) && !empty($smtpUser) && !empty($smtpPass)) {
            $smtpResult = self::sendSmtp($smtpHost, $smtpPort, $smtpUser, $smtpPass, $senderName, $senderEmail, $to, $subject, $messageBody);
            if ($smtpResult['status'] === 'success') {
                return $smtpResult;
            }
            // If SMTP fails, record notice and fall through to server mail
            $smtpError = $smtpResult['message'];
        }

        // Fallback: Send using server mail() with authenticated headers & Return-Path
        $serverResult = self::sendServerMail($senderName, $senderEmail, $to, $subject, $messageBody);
        if (isset($smtpError)) {
            $serverResult['smtp_fallback_note'] = "SMTP direct attempt notice: {$smtpError}";
        }
        return $serverResult;
    }

    /**
     * Authenticated SMTP socket transmission (SSL 465 or TLS 587)
     */
    private static function sendSmtp(
        string $host,
        int $port,
        string $user,
        string $pass,
        string $fromName,
        string $fromEmail,
        string $to,
        string $subject,
        string $body
    ): array {
        $isSsl = ($port === 465);
        $protocol = $isSsl ? 'ssl://' : 'tcp://';
        $ctx = stream_context_create([
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true
            ]
        ]);

        $fp = @stream_socket_client($protocol . $host . ':' . $port, $errno, $errstr, 12, STREAM_CLIENT_CONNECT, $ctx);
        if (!$fp) {
            return [
                'status' => 'error',
                'transport' => 'smtp',
                'message' => "SMTP connection failed to {$host}:{$port} - {$errstr} ({$errno})"
            ];
        }

        stream_set_timeout($fp, 12);

        $read = function() use ($fp) {
            $out = '';
            while (($line = fgets($fp, 515)) !== false) {
                $out .= $line;
                if (isset($line[3]) && $line[3] === ' ') break;
            }
            return $out;
        };

        $cmd = function($c, $expect) use ($fp, $read) {
            if ($c !== null) fwrite($fp, $c . "\r\n");
            $resp = $read();
            $code = (int)substr($resp, 0, 3);
            if ($code !== $expect) {
                return ['ok' => false, 'code' => $code, 'response' => trim($resp)];
            }
            return ['ok' => true, 'code' => $code, 'response' => trim($resp)];
        };

        // Greeting
        $init = $cmd(null, 220);
        if (!$init['ok']) {
            fclose($fp);
            return ['status' => 'error', 'transport' => 'smtp', 'message' => "Server initial response: " . $init['response']];
        }

        $domain = substr(strrchr($fromEmail, '@'), 1) ?: 'theonlyaifreedom.in';
        $ehlo = $cmd('EHLO ' . $domain, 250);
        if (!$ehlo['ok']) {
            fclose($fp);
            return ['status' => 'error', 'transport' => 'smtp', 'message' => "EHLO failed: " . $ehlo['response']];
        }

        // StartTLS if port 587
        if ($port === 587) {
            $stls = $cmd('STARTTLS', 220);
            if ($stls['ok']) {
                stream_socket_enable_crypto($fp, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
                $cmd('EHLO ' . $domain, 250);
            }
        }

        // Auth
        $auth = $cmd('AUTH LOGIN', 334);
        if (!$auth['ok']) {
            fclose($fp);
            return ['status' => 'error', 'transport' => 'smtp', 'message' => "AUTH LOGIN not accepted: " . $auth['response']];
        }

        $authUser = $cmd(base64_encode($user), 334);
        if (!$authUser['ok']) {
            fclose($fp);
            return ['status' => 'error', 'transport' => 'smtp', 'message' => "SMTP username rejected: " . $authUser['response']];
        }

        $authPass = $cmd(base64_encode($pass), 235);
        if (!$authPass['ok']) {
            fclose($fp);
            return ['status' => 'error', 'transport' => 'smtp', 'message' => "SMTP authentication password rejected: " . $authPass['response']];
        }

        // Envelopes
        $cmd('MAIL FROM:<' . $fromEmail . '>', 250);
        $cmd('RCPT TO:<' . $to . '>', 250);

        // Data
        $dataInit = $cmd('DATA', 354);
        if (!$dataInit['ok']) {
            fclose($fp);
            return ['status' => 'error', 'transport' => 'smtp', 'message' => "DATA command rejected: " . $dataInit['response']];
        }

        $boundary = 'b_' . bin2hex(random_bytes(12));
        $encSubj = '=?UTF-8?B?' . base64_encode($subject) . '?=';
        $encName = '=?UTF-8?B?' . base64_encode($fromName) . '?=';

        $htmlBody = nl2br(htmlspecialchars($body, ENT_QUOTES, 'UTF-8'));
        $rawMsg = implode("\r\n", [
            'Date: ' . date('r'),
            "From: {$encName} <{$fromEmail}>",
            "Reply-To: {$fromEmail}",
            "To: <{$to}>",
            "Subject: {$encSubj}",
            "Message-ID: <" . bin2hex(random_bytes(16)) . "@{$domain}>",
            "MIME-Version: 1.0",
            "Content-Type: multipart/alternative; boundary=\"{$boundary}\"",
            "",
            "--{$boundary}",
            "Content-Type: text/plain; charset=UTF-8",
            "Content-Transfer-Encoding: base64",
            "",
            chunk_split(base64_encode($body)),
            "--{$boundary}",
            "Content-Type: text/html; charset=UTF-8",
            "Content-Transfer-Encoding: base64",
            "",
            chunk_split(base64_encode($htmlBody)),
            "--{$boundary}--",
            "",
            "."
        ]);

        fwrite($fp, $rawMsg . "\r\n");
        $dataEnd = $read();
        @fwrite($fp, "QUIT\r\n");
        fclose($fp);

        if ((int)substr($dataEnd, 0, 3) === 250) {
            return [
                'status' => 'success',
                'transport' => 'smtp',
                'message' => "Email sent successfully via authenticated SMTP ({$host}) to {$to}!"
            ];
        }

        return [
            'status' => 'error',
            'transport' => 'smtp',
            'message' => "SMTP transmission error: " . trim($dataEnd)
        ];
    }

    /**
     * Server local mail() transmission with full headers
     */
    private static function sendServerMail(
        string $fromName,
        string $fromEmail,
        string $to,
        string $subject,
        string $body
    ): array {
        $encSubj = '=?UTF-8?B?' . base64_encode($subject) . '?=';
        $encName = '=?UTF-8?B?' . base64_encode($fromName) . '?=';
        $boundary = 'b_' . bin2hex(random_bytes(12));

        $headers = [
            "From: {$encName} <{$fromEmail}>",
            "Reply-To: {$fromEmail}",
            "X-Mailer: The Only AI Freedom Outreach OS",
            "MIME-Version: 1.0",
            "Content-Type: multipart/alternative; boundary=\"{$boundary}\"",
        ];

        $htmlBody = nl2br(htmlspecialchars($body, ENT_QUOTES, 'UTF-8'));
        $content = "--{$boundary}\r\n" .
                   "Content-Type: text/plain; charset=UTF-8\r\n" .
                   "Content-Transfer-Encoding: base64\r\n\r\n" .
                   chunk_split(base64_encode($body)) . "\r\n" .
                   "--{$boundary}\r\n" .
                   "Content-Type: text/html; charset=UTF-8\r\n" .
                   "Content-Transfer-Encoding: base64\r\n\r\n" .
                   chunk_split(base64_encode($htmlBody)) . "\r\n" .
                   "--{$boundary}--\r\n";

        $sent = @mail($to, $encSubj, $content, implode("\r\n", $headers), "-f {$fromEmail}");

        if ($sent) {
            return [
                'status' => 'success',
                'transport' => 'server_mail',
                'message' => "Email sent successfully from {$fromEmail} to {$to}!"
            ];
        }

        return [
            'status' => 'error',
            'transport' => 'server_mail',
            'message' => "Failed to deliver email through server transport. Check server mail logs."
        ];
    }
}
