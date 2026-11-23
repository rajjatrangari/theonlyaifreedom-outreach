<?php
// Outreach AI Freedom - Internal AJAX & Automation API
declare(strict_types=1);

require_once __DIR__ . '/core/bootstrap.php';
require_once __DIR__ . '/core/scraper_engine.php';
require_once __DIR__ . '/core/ai_generator.php';
require_once __DIR__ . '/core/qualification_engine.php';

$user = current_user();
if (!$user) {
    json_response(['status' => 'error', 'message' => 'Unauthorized. Please sign in.'], 401);
}

$action = $_GET['action'] ?? '';
$input = json_decode(file_get_contents('php://input'), true) ?? $_POST;

switch ($action) {

    // 1. Scrape & Discover Qualified Leads
    case 'scrape_leads':
        $keyword = trim($input['keyword'] ?? '');
        $location = trim($input['location'] ?? '');
        $minScore = (int)($input['min_score'] ?? ($user['min_score_filter'] ?? 70));
        $limit = max(5, min(30, (int)($input['limit'] ?? 15)));

        if (empty($keyword)) {
            json_response(['status' => 'error', 'message' => 'Keyword / niche cannot be empty.'], 400);
        }

        $result = ScraperEngine::searchAndEnrich((int)$user['id'], $keyword, $location, $minScore, $limit);
        json_response($result);
        break;

    // 2. Fetch AI Outreach Sequence for a Specific Lead
    case 'get_outreach_sequence':
        $leadId = (int)($_GET['lead_id'] ?? ($input['lead_id'] ?? 0));
        $lead = one("SELECT * FROM leads WHERE id = ? AND user_id = ?", [$leadId, $user['id']]);
        if (!$lead) {
            json_response(['status' => 'error', 'message' => 'Lead not found.'], 404);
        }

        $sequence = AIGenerator::generateSequence($user, $lead);
        json_response([
            'status' => 'success',
            'lead' => $lead,
            'sequence' => $sequence,
        ]);
        break;

    // 3. Record Outreach Dispatch & Update Pipeline
    case 'record_outreach':
        $leadId = (int)($input['lead_id'] ?? 0);
        $channel = trim($input['channel'] ?? 'whatsapp');
        $touchType = trim($input['touch_type'] ?? 'initial');
        $message = trim($input['message'] ?? '');

        $lead = one("SELECT * FROM leads WHERE id = ? AND user_id = ?", [$leadId, $user['id']]);
        if (!$lead) {
            json_response(['status' => 'error', 'message' => 'Lead not found.'], 404);
        }

        // Determine next stage
        $newStage = 'outreach_sent';
        if ($touchType === 'followup_1') $newStage = 'followup_1';
        elseif ($touchType === 'followup_2') $newStage = 'followup_2';

        $nextFollowup = date('Y-m-d', strtotime('+3 days'));

        // Log outreach touchpoint
        q("INSERT INTO outreach_logs (lead_id, user_id, channel, touch_type, message_sent, outcome, created_at)
           VALUES (?, ?, ?, ?, ?, 'sent', ?)", [
            $leadId,
            $user['id'],
            $channel,
            $touchType,
            $message,
            now()
        ]);

        // Update lead status
        q("UPDATE leads SET
            pipeline_stage = ?,
            last_contacted_at = ?,
            last_channel = ?,
            next_followup_at = ?,
            updated_at = ?
           WHERE id = ? AND user_id = ?", [
            $newStage,
            now(),
            $channel,
            $nextFollowup,
            now(),
            $leadId,
            $user['id']
        ]);

        json_response([
            'status' => 'success',
            'lead_id' => $leadId,
            'stage' => $newStage,
            'next_followup' => $nextFollowup
        ]);
        break;

    // 4. Update Pipeline Stage (e.g. from Kanban drag-drop)
    case 'update_lead_stage':
        $leadId = (int)($input['lead_id'] ?? 0);
        $stage = trim($input['stage'] ?? '');

        if (!isset(PIPELINE_STAGES[$stage])) {
            json_response(['status' => 'error', 'message' => 'Invalid pipeline stage.'], 400);
        }

        $lead = one("SELECT * FROM leads WHERE id = ? AND user_id = ?", [$leadId, $user['id']]);
        if (!$lead) {
            json_response(['status' => 'error', 'message' => 'Lead not found.'], 404);
        }

        q("UPDATE leads SET pipeline_stage = ?, updated_at = ? WHERE id = ? AND user_id = ?", [
            $stage,
            now(),
            $leadId,
            $user['id']
        ]);

        json_response([
            'status' => 'success',
            'lead_id' => $leadId,
            'stage' => $stage,
            'stage_name' => PIPELINE_STAGES[$stage]['name']
        ]);
        break;

    // 5. Update Lead Details (Deal Value, Notes, Followup Date)
    case 'update_lead_details':
        $leadId = (int)($input['lead_id'] ?? 0);
        $dealValue = (float)($input['deal_value'] ?? 0.0);
        $notes = trim($input['notes'] ?? '');
        $tags = trim($input['tags'] ?? '');
        $nextFollowup = !empty($input['next_followup_at']) ? $input['next_followup_at'] : null;

        q("UPDATE leads SET
            deal_value = ?,
            notes = ?,
            tags = ?,
            next_followup_at = ?,
            updated_at = ?
           WHERE id = ? AND user_id = ?", [
            $dealValue,
            $notes,
            $tags,
            $nextFollowup,
            now(),
            $leadId,
            $user['id']
        ]);

        json_response(['status' => 'success', 'message' => 'Lead updated successfully.']);
        break;

    // 6. Delete Lead
    case 'delete_lead':
        $leadId = (int)($input['lead_id'] ?? 0);
        q("DELETE FROM leads WHERE id = ? AND user_id = ?", [$leadId, $user['id']]);
        q("DELETE FROM outreach_logs WHERE lead_id = ? AND user_id = ?", [$leadId, $user['id']]);
        json_response(['status' => 'success', 'message' => 'Lead deleted.']);
        break;

    // 7. Export Leads to CSV
    case 'export_leads_csv':
        $stageFilter = trim($_GET['stage'] ?? '');
        $sql = "SELECT business_name, website, phone, whatsapp, email, linkedin_url, instagram_url, city, category, reviews_count, rating, qualification_score, paying_capacity_tier, pipeline_stage, deal_value, created_at FROM leads WHERE user_id = ?";
        $params = [$user['id']];
        if (!empty($stageFilter) && isset(PIPELINE_STAGES[$stageFilter])) {
            $sql .= " AND pipeline_stage = ?";
            $params[] = $stageFilter;
        }
        $sql .= " ORDER BY id DESC";

        $leads = all($sql, $params);

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="outreach_leads_' . date('Ymd_His') . '.csv"');

        $out = fopen('php://output', 'w');
        fputcsv($out, [
            'Business Name', 'Website', 'Phone', 'WhatsApp', 'Email',
            'LinkedIn URL', 'Instagram URL', 'City', 'Category',
            'Reviews', 'Rating', 'Qualification Score', 'Paying Capacity Tier',
            'Pipeline Stage', 'Deal Value', 'Created At'
        ]);

        foreach ($leads as $row) {
            fputcsv($out, [
                $row['business_name'],
                $row['website'],
                $row['phone'],
                $row['whatsapp'],
                $row['email'],
                $row['linkedin_url'],
                $row['instagram_url'],
                $row['city'],
                $row['category'],
                $row['reviews_count'],
                $row['rating'],
                $row['qualification_score'],
                $row['paying_capacity_tier'],
                $row['pipeline_stage'],
                $row['deal_value'],
                $row['created_at']
            ]);
        }
        fclose($out);
        exit;

    default:
        json_response(['status' => 'error', 'message' => 'Unknown API action.'], 400);
        break;
}
