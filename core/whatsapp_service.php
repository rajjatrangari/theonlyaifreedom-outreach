<?php
// Outreach AI Freedom - Meta WhatsApp Cloud API Service & Automated Webhook Dispatcher
declare(strict_types=1);

require_once __DIR__ . '/ai_generator.php';

class WhatsAppService {

    /**
     * Verifies WhatsApp Cloud API credentials directly against Meta Graph API.
     */
    public static function testMetaApi(string $phoneId, string $token): array {
        $phoneId = trim($phoneId);
        $token = trim($token);

        if (empty($phoneId) || empty($token)) {
            return [
                'status' => 'error',
                'message' => 'Please provide both WhatsApp Phone Number ID and System User Access Token.'
            ];
        }

        $url = "https://graph.facebook.com/v21.0/" . urlencode($phoneId) . "?fields=id,verified_name,display_phone_number,quality_rating,code_verification_status";
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Authorization: Bearer {$token}",
            "Content-Type: application/json"
        ]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlErr = curl_error($ch);
        curl_close($ch);

        if ($curlErr) {
            return [
                'status' => 'error',
                'message' => 'Connection to Meta Graph API failed: ' . $curlErr
            ];
        }

        $json = json_decode((string)$response, true);

        if ($httpCode === 200 && !empty($json['id'])) {
            return [
                'status' => 'success',
                'phone_number_id' => $json['id'],
                'verified_name' => $json['verified_name'] ?? 'The Only AI Freedom',
                'display_phone_number' => $json['display_phone_number'] ?? '',
                'quality_rating' => $json['quality_rating'] ?? 'GREEN',
                'verification_status' => $json['code_verification_status'] ?? 'VERIFIED',
                'message' => 'Meta WhatsApp Cloud API successfully connected & verified!'
            ];
        }

        $errorMsg = $json['error']['message'] ?? ('HTTP ' . $httpCode . ' from Meta API');
        return [
            'status' => 'error',
            'http_code' => $httpCode,
            'message' => 'Meta API Error: ' . $errorMsg,
            'details' => $json['error'] ?? null
        ];
    }

    /**
     * Sends an outbound WhatsApp text message via Meta Cloud API.
     */
    public static function sendCloudMessage(string $phoneId, string $token, string $recipientPhone, string $message): array {
        $phoneId = trim($phoneId);
        $token = trim($token);
        $cleanPhone = preg_replace('/[^\d]/', '', $recipientPhone);

        if (empty($phoneId) || empty($token)) {
            return [
                'status' => 'error',
                'message' => 'Meta Cloud API credentials not configured.'
            ];
        }

        if (empty($cleanPhone)) {
            return [
                'status' => 'error',
                'message' => 'Recipient phone number is invalid.'
            ];
        }

        $url = "https://graph.facebook.com/v21.0/" . urlencode($phoneId) . "/messages";
        $payload = [
            'messaging_product' => 'whatsapp',
            'recipient_type' => 'individual',
            'to' => $cleanPhone,
            'type' => 'text',
            'text' => [
                'preview_url' => false,
                'body' => $message
            ]
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Authorization: Bearer {$token}",
            "Content-Type: application/json"
        ]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlErr = curl_error($ch);
        curl_close($ch);

        if ($curlErr) {
            return [
                'status' => 'error',
                'message' => 'Network error dispatching WhatsApp message: ' . $curlErr
            ];
        }

        $json = json_decode((string)$response, true);

        if ($httpCode >= 200 && $httpCode < 300 && !empty($json['messages'][0]['id'])) {
            return [
                'status' => 'success',
                'wamid' => $json['messages'][0]['id'],
                'recipient' => $cleanPhone,
                'message' => 'Message successfully delivered via Meta WhatsApp Cloud API.'
            ];
        }

        $errorMsg = $json['error']['message'] ?? ('HTTP ' . $httpCode . ' from Meta API');
        $errorSubcode = $json['error']['error_subcode'] ?? 0;

        return [
            'status' => 'error',
            'http_code' => $httpCode,
            'message' => 'Meta Cloud API Error: ' . $errorMsg,
            'error_subcode' => $errorSubcode,
            'raw' => $json
        ];
    }

    /**
     * Inbound WhatsApp Webhook Processor & AI Chatbot Dispatcher.
     * STRICT REQUIREMENT: Only auto-replies to NEW/UNSAVED numbers (never existing CRM leads!).
     */
    public static function processInboundWebhook(array $payload, ?int $targetUserId = null): array {
        $entry = $payload['entry'][0] ?? null;
        if (!$entry) {
            return ['status' => 'ignored', 'reason' => 'No entry in webhook'];
        }

        $change = $entry['changes'][0] ?? null;
        $value = $change['value'] ?? null;
        $messages = $value['messages'] ?? [];

        if (empty($messages)) {
            // Status update (sent, delivered, read)
            return ['status' => 'status_update_ignored'];
        }

        $results = [];

        foreach ($messages as $msg) {
            $senderPhone = $msg['from'] ?? '';
            $msgType = $msg['type'] ?? 'text';
            $msgBody = $msgType === 'text' ? ($msg['text']['body'] ?? '') : "[{$msgType} attachment]";
            $metaMsgId = $msg['id'] ?? '';
            $cleanSender = preg_replace('/[^\d]/', '', $senderPhone);

            if (empty($cleanSender) || empty($msgBody)) continue;

            // Determine user
            $user = null;
            if ($targetUserId) {
                $user = one("SELECT * FROM users WHERE id = ?", [$targetUserId]);
            } else {
                // Find user by phone_number_id in metadata or default to primary admin
                $phoneId = $value['metadata']['phone_number_id'] ?? '';
                if ($phoneId) {
                    $channelRow = one("SELECT user_id FROM connected_channels WHERE channel_name = 'whatsapp' AND config_data LIKE ?", ['%' . $phoneId . '%']);
                    if ($channelRow) {
                        $user = one("SELECT * FROM users WHERE id = ?", [$channelRow['user_id']]);
                    }
                }
                if (!$user) {
                    $user = one("SELECT * FROM users ORDER BY id ASC LIMIT 1");
                }
            }

            if (!$user) continue;
            $userId = (int)$user['id'];

            // Fetch WhatsApp channel configuration
            $waChannel = one("SELECT * FROM connected_channels WHERE user_id = ? AND channel_name = 'whatsapp'", [$userId]);
            $waConfig = json_decode($waChannel['config_data'] ?? '{}', true) ?: [];

            // 1. Check if this phone exists in the CRM leads table
            // Match with or without country code
            $last10 = substr($cleanSender, -10);
            $existingLead = one("
                SELECT * FROM leads 
                WHERE user_id = ? AND (
                    whatsapp LIKE ? OR 
                    phone LIKE ? OR 
                    whatsapp LIKE ? OR 
                    phone LIKE ?
                )
                ORDER BY id DESC LIMIT 1
            ", [$userId, "%{$cleanSender}%", "%{$cleanSender}%", "%{$last10}%", "%{$last10}%"]);

            if ($existingLead) {
                // CASE A: EXISTING LEAD IN CRM -> DO NOT AUTO-REPLY!
                // Record inbound message into conversation timeline for human review
                q("INSERT INTO outreach_logs (lead_id, user_id, channel, touch_type, message_sent, outcome, direction, created_at)
                   VALUES (?, ?, 'whatsapp', 'inbound_client', ?, 'delivered', 'inbound', ?)", [
                    $existingLead['id'],
                    $userId,
                    $msgBody,
                    now()
                ]);

                // Update lead stage & activity
                q("UPDATE leads SET
                    pipeline_stage = CASE WHEN pipeline_stage IN ('new', 'outreach_sent', 'followup_1', 'followup_2') THEN 'responded' ELSE pipeline_stage END,
                    last_activity_at = ?,
                    last_reply_message = ?,
                    last_reply_channel = 'whatsapp',
                    updated_at = ?
                   WHERE id = ?", [
                    now(),
                    $msgBody,
                    now(),
                    $existingLead['id']
                ]);

                $results[] = [
                    'phone' => $cleanSender,
                    'action' => 'logged_inbound_existing_lead',
                    'lead_id' => $existingLead['id'],
                    'auto_reply' => false,
                    'reason' => 'Sender is already saved in CRM. Handed over to human inbox.'
                ];

            } else {
                // CASE B: NEW / UNSAVED PHONE NUMBER -> AUTO-ADD TO CRM + TRIGGER AI CHATBOT!
                $newLeadName = "Inbound Prospect (+{$cleanSender})";
                q("INSERT INTO leads (
                    user_id, business_name, decision_maker_name, phone, whatsapp, 
                    source, pipeline_stage, qualification_score, paying_capacity_tier,
                    last_activity_at, last_reply_message, last_reply_channel, 
                    tags, created_at, updated_at
                ) VALUES (
                    ?, ?, 'Inbound Contact', ?, ?,
                    'whatsapp_inbound', 'responded', 80, 'Direct Inbound',
                    ?, ?, 'whatsapp',
                    'whatsapp_inbound,new_lead', ?, ?
                )", [
                    $userId,
                    $newLeadName,
                    '+' . $cleanSender,
                    '+' . $cleanSender,
                    now(),
                    $msgBody,
                    now(),
                    now()
                ]);
                $newLeadId = last_id();

                // Log the inbound message
                q("INSERT INTO outreach_logs (lead_id, user_id, channel, touch_type, message_sent, outcome, direction, created_at)
                   VALUES (?, ?, 'whatsapp', 'inbound_new_contact', ?, 'delivered', 'inbound', ?)", [
                    $newLeadId,
                    $userId,
                    $msgBody,
                    now()
                ]);

                // Check if AI Chatbot is enabled
                $aiEnabled = !empty($waConfig['ai_chatbot_enabled']);
                $aiReplyText = null;

                if ($aiEnabled) {
                    // Generate tailored AI response
                    $aiResponse = AIGenerator::generateInboundReply($user, $msgBody, $cleanSender);
                    $aiReplyText = $aiResponse['reply'];

                    // Dispatch via WhatsApp Cloud API if configured
                    $sentOk = false;
                    if (!empty($waConfig['phone_number_id']) && !empty($waConfig['access_token'])) {
                        $sendRes = self::sendCloudMessage(
                            $waConfig['phone_number_id'],
                            $waConfig['access_token'],
                            $cleanSender,
                            $aiReplyText
                        );
                        $sentOk = ($sendRes['status'] === 'success');
                    }

                    // Log AI outbound reply in timeline
                    q("INSERT INTO outreach_logs (lead_id, user_id, channel, touch_type, message_sent, outcome, direction, created_at)
                       VALUES (?, ?, 'whatsapp', 'ai_auto_reply', ?, ?, 'outbound', ?)", [
                        $newLeadId,
                        $userId,
                        $aiReplyText,
                        $sentOk ? 'delivered' : 'generated_ready',
                        now()
                    ]);
                }

                $results[] = [
                    'phone' => $cleanSender,
                    'action' => 'created_new_lead_and_auto_replied',
                    'lead_id' => $newLeadId,
                    'auto_reply' => $aiEnabled,
                    'reply_content' => $aiReplyText
                ];
            }
        }

        return [
            'status' => 'success',
            'processed' => count($results),
            'results' => $results
        ];
    }
}
