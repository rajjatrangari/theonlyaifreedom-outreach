<?php
// Outreach AI Freedom - AI Copywriting & Multi-Stage Follow-up Sequencer
declare(strict_types=1);

class AIGenerator {

    /**
     * Generates a complete, tailored outreach sequence combining the lead's
     * profile signals and the user's core offering.
     */
    public static function generateSequence(array $user, array $lead, string $tone = 'consultative'): array {
        $leadName = trim($lead['business_name']);
        $leadNiche = !empty($lead['category']) ? $lead['category'] : 'your industry';
        $leadCity = !empty($lead['city']) ? $lead['city'] : 'your area';
        $leadReviews = (int)($lead['reviews_count'] ?? 0);
        $leadRating = (float)($lead['rating'] ?? 4.8);

        $senderName = !empty($user['sender_name']) ? $user['sender_name'] : (!empty($user['name']) ? $user['name'] : 'Our Team');
        $senderTitle = !empty($user['sender_title']) ? $user['sender_title'] : 'Founder';
        $myBusiness = !empty($user['business_name']) ? $user['business_name'] : 'The Only AI Freedom';
        $myOffering = !empty($user['business_offering']) ? $user['business_offering'] : 'high-converting AI automation & client acquisition systems';
        $myUsp = !empty($user['usp_hook']) ? $user['usp_hook'] : 'We implement a custom lead engine with guaranteed results in 7 days.';
        $pricing = !empty($user['business_pricing']) ? $user['business_pricing'] : 'custom growth packages';

        // Custom observation hook
        $observation = "I came across {$leadName} while researching leading {$leadNiche} businesses in {$leadCity}";
        if ($leadReviews > 10) {
            $observation .= " and was really impressed by your {$leadReviews}+ positive reviews ({$leadRating}★)";
        }

        // WhatsApp Sequences
        $waInitial = "Hey team {$leadName}! 👋\n\n"
            . "{$observation}.\n\n"
            . "I'm {$senderName} from {$myBusiness}. We specialize in {$myOffering}.\n\n"
            . "Quick question: Are you currently taking on new high-budget clients in {$leadCity} this month?\n\n"
            . "{$myUsp}\n\n"
            . "Mind if I share a quick 2-minute video showing the exact framework we set up for similar businesses? No pitch, just pure value!";

        $waFollowup1 = "Hey team {$leadName}! Hope you're having a productive week.\n\n"
            . "Just following up on my note from earlier. We recently helped a fellow {$leadNiche} brand scale their direct inquiry pipeline by 3.4x without ad spend.\n\n"
            . "Would you be open to checking out a quick breakdown of how it works for {$leadName}?";

        $waFollowup2 = "Quick bump here {$leadName} — wanted to make sure this didn't get buried in your messages!\n\n"
            . "Could you point me to whoever oversees growth and client acquisition at your office?";

        $waFollowup3 = "Hey {$leadName}, I assume you're completely booked with clients right now (which is awesome!).\n\n"
            . "I won't follow up again so I don't clutter your inbox. If you ever want to explore how our {$myOffering} can automate your pipeline, feel free to ping me anytime.\n\n"
            . "Wishing you and the team massive success!";

        // LinkedIn DM Sequence
        $liInitial = "Hi team {$leadName},\n\n"
            . "I noticed your impressive presence in {$leadCity} for {$leadNiche}. Huge congratulations on your strong track record!\n\n"
            . "I'm {$senderName}, {$senderTitle} at {$myBusiness}. {$myUsp}\n\n"
            . "We've developed a turnkey system that brings qualified, high-ticket prospects directly into your pipeline.\n\n"
            . "Would you be opposed to seeing a 90-second preview of what this looks like for {$leadName}?";

        // Email Sequence
        $emailSubject = "Quick idea for {$leadName} ({$leadCity})";
        $emailBody = "Hi team {$leadName},\n\n"
            . "{$observation}.\n\n"
            . "I'm reaching out because at {$myBusiness}, we work specifically with premium {$leadNiche} brands to {$myOffering}.\n\n"
            . "{$myUsp}\n\n"
            . "We put together a high-level case study on how we add 15-25 qualified high-paying client discussions every month on autopilot.\n\n"
            . "Do you have 5 minutes this Thursday or Friday for a quick chat, or would you prefer I send over the PDF summary first?\n\n"
            . "Best regards,\n"
            . "{$senderName}\n"
            . "{$senderTitle} | {$myBusiness}\n"
            . (!empty($user['sender_phone']) ? "Direct / WhatsApp: {$user['sender_phone']}\n" : "")
            . (!empty($user['sender_email']) ? "Email: {$user['sender_email']}\n" : "");

        // Instagram DM
        $igInitial = "Hey {$leadName}! Love what you guys are doing in {$leadCity} ✨\n\n"
            . "Quick question — are you looking to onboard more qualified high-ticket clients this quarter?\n\n"
            . "We help top {$leadNiche} brands automate client booking with {$myOffering}.\n\n"
            . "Drop a 'YES' and I'll send over a free 2-min breakdown!";

        return [
            'lead_id' => $lead['id'] ?? null,
            'business_name' => $leadName,
            'whatsapp' => [
                'initial' => $waInitial,
                'followup_1' => $waFollowup1,
                'followup_2' => $waFollowup2,
                'followup_3' => $waFollowup3,
            ],
            'linkedin' => [
                'initial' => $liInitial,
                'followup_1' => "Following up on my message regarding client acquisition for {$leadName}. Would love to connect briefly if you have a moment!",
            ],
            'email' => [
                'subject' => $emailSubject,
                'body' => $emailBody,
                'followup_1_subject' => "Re: Quick idea for {$leadName}",
                'followup_1_body' => "Hi {$leadName},\n\nWanted to check if you had a chance to read my previous email regarding our {$myOffering}?\n\nHappy to share the case study whenever convenient.\n\nBest,\n{$senderName}",
            ],
            'instagram' => [
                'initial' => $igInitial,
            ],
        ];
    }
}
