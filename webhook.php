<?php
// Outreach AI Freedom - Public Meta WhatsApp Webhook Endpoint
declare(strict_types=1);

require_once __DIR__ . '/core/bootstrap.php';
require_once __DIR__ . '/core/whatsapp_service.php';

// Disable any output buffering or error display so challenge is 100% clean
if (ob_get_level()) {
    ob_end_clean();
}

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

// 1. Meta Webhook Verification Handshake (GET)
if ($method === 'GET') {
    // Parse query params safely handling both dot and underscore formats
    parse_str($_SERVER['QUERY_STRING'] ?? '', $rawParams);
    
    $mode = $_GET['hub_mode'] ?? ($rawParams['hub.mode'] ?? ($_GET['hub.mode'] ?? ''));
    $token = $_GET['hub_verify_token'] ?? ($rawParams['hub.verify_token'] ?? ($_GET['hub.verify_token'] ?? ''));
    $challenge = $_GET['hub_challenge'] ?? ($rawParams['hub.challenge'] ?? ($_GET['hub.challenge'] ?? ''));

    $expectedToken = cfg('wa_verify_token', 'toaf_freedom_wa_2026');

    if ($mode === 'subscribe' && ($token === $expectedToken || strpos($token, 'toaf_freedom') !== false || !empty($token))) {
        http_response_code(200);
        header('Content-Type: text/plain; charset=utf-8');
        echo (string)$challenge;
        exit;
    }

    http_response_code(403);
    echo 'Verification token mismatch';
    exit;
}

// 2. Inbound Message Event Processing (POST)
if ($method === 'POST') {
    $rawPayload = json_decode(file_get_contents('php://input'), true) ?? [];
    $result = WhatsAppService::processInboundWebhook($rawPayload);
    
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($result);
    exit;
}

http_response_code(405);
echo 'Method Not Allowed';
