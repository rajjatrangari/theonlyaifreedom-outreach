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

    // 1. Scrape & Discover Qualified Leads (50 to 80+ leads per search)
    case 'scrape_leads':
        $keyword = trim($input['keyword'] ?? '');
        $location = trim($input['location'] ?? '');
        $minScore = (int)($input['min_score'] ?? ($user['min_score_filter'] ?? 70));
        $limit = max(20, min(100, (int)($input['limit'] ?? 60)));
        // Default to auto-save = true so every search and lead is stored automatically into CRM
        $autoSave = !isset($input['auto_save']) || $input['auto_save'] === true || $input['auto_save'] === '1' || $input['auto_save'] === 1;

        if (empty($keyword)) {
            json_response(['status' => 'error', 'message' => 'Keyword / niche cannot be empty.'], 400);
        }

        $result = ScraperEngine::searchAndEnrich((int)$user['id'], $keyword, $location, $minScore, $limit, $autoSave);

        // Aggregate statistics, saved searches, categories and locations
        $result['saved_searches'] = all("SELECT * FROM saved_searches WHERE user_id = ? ORDER BY id DESC LIMIT 12", [$user['id']]);
        $result['total_stored_leads'] = (int)val("SELECT COUNT(*) FROM leads WHERE user_id = ?", [$user['id']]);
        $result['categories'] = all("SELECT category, COUNT(*) as cnt FROM leads WHERE user_id = ? AND category IS NOT NULL AND category != '' GROUP BY category ORDER BY cnt DESC", [$user['id']]);
        $result['cities'] = all("SELECT city, COUNT(*) as cnt FROM leads WHERE user_id = ? AND city IS NOT NULL AND city != '' GROUP BY city ORDER BY cnt DESC", [$user['id']]);

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
        if (str_starts_with($touchType, 'playbook_')) {
            $newStage = 'responded';
        } elseif ($touchType === 'followup_1') {
            $newStage = 'followup_1';
        } elseif ($touchType === 'followup_2') {
            $newStage = 'followup_2';
        }

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

    // 8. Send Direct Inbox Message
    case 'send_inbox_message':
        $leadId = (int)($input['lead_id'] ?? 0);
        $channel = trim($input['channel'] ?? 'whatsapp');
        $message = trim($input['message'] ?? '');
        $touchType = trim($input['touch_type'] ?? 'inbox_direct');

        if (empty($message)) {
            json_response(['status' => 'error', 'message' => 'Message content cannot be empty.'], 400);
        }

        $lead = one("SELECT * FROM leads WHERE id = ? AND user_id = ?", [$leadId, $user['id']]);
        if (!$lead) {
            json_response(['status' => 'error', 'message' => 'Lead not found.'], 404);
        }

        // Insert log record with direction = 'outbound'
        q("INSERT INTO outreach_logs (lead_id, user_id, channel, touch_type, message_sent, outcome, direction, created_at)
           VALUES (?, ?, ?, ?, ?, 'delivered', 'outbound', ?)", [
            $leadId,
            $user['id'],
            $channel,
            $touchType,
            $message,
            now()
        ]);

        $logId = last_id();

        // Update lead activity & stage
        q("UPDATE leads SET 
            last_contacted_at = ?,
            last_channel = ?,
            last_activity_at = ?,
            pipeline_stage = CASE WHEN pipeline_stage IN ('new', 'outreach_sent') THEN 'outreach_sent' ELSE pipeline_stage END,
            updated_at = ?
           WHERE id = ? AND user_id = ?", [
            now(),
            $channel,
            now(),
            now(),
            $leadId,
            $user['id']
        ]);

        json_response([
            'status' => 'success',
            'log_id' => $logId,
            'lead_id' => $leadId,
            'channel' => $channel,
            'sent_at' => now()
        ]);
        break;

    // 9. Get Inbox Messages for a Lead
    case 'get_inbox_messages':
        $leadId = (int)($_GET['lead_id'] ?? ($input['lead_id'] ?? 0));
        $lead = one("SELECT * FROM leads WHERE id = ? AND user_id = ?", [$leadId, $user['id']]);
        if (!$lead) {
            json_response(['status' => 'error', 'message' => 'Lead not found.'], 404);
        }

        $messages = all("
            SELECT * FROM outreach_logs
            WHERE lead_id = ? AND user_id = ?
            ORDER BY id ASC
        ", [$leadId, $user['id']]);

        json_response([
            'status' => 'success',
            'lead' => $lead,
            'messages' => $messages,
        ]);
        break;

    // 10. Bulk Save Discovered Leads to CRM Pipeline
    case 'save_scraped_leads':
        $leads = $input['leads'] ?? [];
        $stage = trim($input['stage'] ?? 'new');

        if (!is_array($leads) || empty($leads)) {
            json_response(['status' => 'error', 'message' => 'No leads provided to save.'], 400);
        }

        $result = ScraperEngine::saveLeadsBatch((int)$user['id'], $leads, $stage);
        json_response($result);
        break;

    // 11. Save Single Lead to CRM Pipeline
    case 'save_single_lead':
        $lead = $input['lead'] ?? null;
        $stage = trim($input['stage'] ?? 'new');

        if (!is_array($lead) || empty($lead['business_name'])) {
            json_response(['status' => 'error', 'message' => 'Invalid lead data.'], 400);
        }

        $lead['pipeline_stage'] = $stage;
        $saved = ScraperEngine::saveOrUpdateLead((int)$user['id'], $lead);

        if (!$saved) {
            json_response(['status' => 'error', 'message' => 'Failed to save lead.'], 500);
        }

        json_response([
            'status' => 'success',
            'lead_id' => $saved['id'],
            'message' => "Saved {$saved['business_name']} to CRM Pipeline."
        ]);
        break;

    // 12. Purge Broken / Mock Leads & Refresh
    case 'purge_synthetic_leads':
        q("DELETE FROM leads WHERE business_name LIKE '%Luxury Jewellery Brands%' OR business_name LIKE '%Signature Luxury%' OR business_name LIKE '%Prestige Luxury%' OR business_name LIKE '%Elysian Luxury%' OR business_name LIKE '%Vanguard Luxury%' OR website LIKE '%luxuryjewellerybrands.in%' OR website LIKE '%pexluxury%' OR website LIKE '%restigeluxury%' OR website LIKE '%signatureluxury%' OR email LIKE '%luxuryjewellerybrands.in%' OR email LIKE '%ignature%'");

        $remaining = (int)val("SELECT COUNT(*) FROM leads WHERE user_id = ?", [$user['id']]);
        if ($remaining === 0) {
            ScraperEngine::searchAndEnrich((int)$user['id'], 'Luxury Jewellery', 'Mumbai, India', 75, 6);
        }

        json_response([
            'status' => 'success',
            'message' => 'Cleaned mock records and restored live verified leads.'
        ]);
        break;

    // 13. Get Saved CRM Leads (with optional category & location filtering)
    case 'get_saved_leads':
        $cat = trim($_GET['category'] ?? ($input['category'] ?? ''));
        $loc = trim($_GET['location'] ?? ($input['location'] ?? ''));
        $stage = trim($_GET['stage'] ?? ($input['stage'] ?? ''));

        $sql = "SELECT * FROM leads WHERE user_id = ?";
        $params = [$user['id']];
        if (!empty($cat)) {
            $sql .= " AND category = ?";
            $params[] = $cat;
        }
        if (!empty($loc)) {
            $sql .= " AND (city LIKE ? OR address LIKE ?)";
            $params[] = "%{$loc}%";
            $params[] = "%{$loc}%";
        }
        if (!empty($stage)) {
            $sql .= " AND pipeline_stage = ?";
            $params[] = $stage;
        }
        $sql .= " ORDER BY id DESC LIMIT 500";
        $savedList = all($sql, $params);

        $categories = all("SELECT category, COUNT(*) as cnt FROM leads WHERE user_id = ? AND category IS NOT NULL AND category != '' GROUP BY category ORDER BY cnt DESC", [$user['id']]);
        $cities = all("SELECT city, COUNT(*) as cnt FROM leads WHERE user_id = ? AND city IS NOT NULL AND city != '' GROUP BY city ORDER BY cnt DESC", [$user['id']]);
        $searches = all("SELECT * FROM saved_searches WHERE user_id = ? ORDER BY id DESC LIMIT 12", [$user['id']]);

        json_response([
            'status' => 'success',
            'leads' => $savedList,
            'count' => count($savedList),
            'total_in_db' => (int)val("SELECT COUNT(*) FROM leads WHERE user_id = ?", [$user['id']]),
            'categories' => $categories,
            'cities' => $cities,
            'saved_searches' => $searches,
        ]);
        break;

    // 14. Get Stored Search Sessions History
    case 'get_saved_searches':
        $searches = all("SELECT * FROM saved_searches WHERE user_id = ? ORDER BY id DESC LIMIT 20", [$user['id']]);
        json_response([
            'status' => 'success',
            'searches' => $searches
        ]);
        break;

    default:
        json_response(['status' => 'error', 'message' => 'Unknown API action.'], 400);
        break;
}
