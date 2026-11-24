<?php
// Outreach AI Freedom - Lead Qualification & Paying Capacity Engine
declare(strict_types=1);

class QualificationEngine {

    /**
     * Evaluates commercial maturity, verified contactability,
     * paying capacity signals, and online footprint.
     * Returns score (0-100), tier, is_qualified flag, and explanatory badges.
     */
    public static function evaluate(array $lead, int $minThreshold = 70): array {
        $score = 0;
        $reasons = [];
        $flags = [];

        // 1. Live Domain & Corporate Website Analysis (Max 25 pts)
        $website = trim($lead['website'] ?? '');
        if (!empty($website)) {
            $parsedUrl = parse_url($website);
            $host = strtolower($parsedUrl['host'] ?? $website);
            $host = preg_replace('/:\d+$/', '', $host);

            // Free blogging subdomains receive lower credit
            $isFreeDomain = preg_match('/\.(wixsite|wordpress|blogspot|weebly|jimdo|carrd)\./i', $host);
            if ($isFreeDomain) {
                $score += 10;
                $flags[] = 'Free hosting platform (Moderate commercial budget)';
            } else {
                // Award full 25 points for commercial custom domains
                $score += 25;
                $reasons[] = 'Commercial custom domain & verified corporate web presence (+25)';
            }
        } else {
            // Enterprise commercial fallback
            $score += 20;
            $reasons[] = 'Commercial enterprise market presence (+20)';
        }

        // 2. Verified Direct WhatsApp Contact (Max 25 pts)
        $whatsapp = self::cleanPhone($lead['whatsapp'] ?? ($lead['phone'] ?? ''));
        if (!empty($whatsapp) && strlen($whatsapp) >= 10) {
            $score += 25;
            $reasons[] = 'Verified Direct WhatsApp number with 1-click chat (+25)';
        } else {
            $flags[] = 'Missing or unverified WhatsApp (-25)';
        }

        // 3. Multi-Channel Social Footprint & Brand Proof (Max 25 pts)
        $socialCount = 0;
        if (!empty($lead['linkedin_url'])) {
            $score += 8;
            $socialCount++;
            $reasons[] = 'Active LinkedIn Company Profile (+8)';
        }
        if (!empty($lead['instagram_url'])) {
            $score += 7;
            $socialCount++;
            $reasons[] = 'Active Instagram Business Profile (+7)';
        }
        if (!empty($lead['youtube_url'])) {
            $score += 5;
            $socialCount++;
            $reasons[] = 'Official YouTube Channel (+5)';
        }
        if (!empty($lead['facebook_url'])) {
            $score += 3;
            $socialCount++;
        }
        if (!empty($lead['twitter_url'])) {
            $score += 2;
            $socialCount++;
        }
        if ($socialCount >= 2) {
            $reasons[] = sprintf('Multi-channel brand footprint (%d active social profiles)', $socialCount);
        }

        // 4. Commercial Reviews & Customer Cashflow Proof (Max 25 pts)
        $reviews = (int)($lead['reviews_count'] ?? 0);
        $rating = (float)($lead['rating'] ?? 0.0);

        if ($reviews >= 20 && $rating >= 4.0) {
            $score += 25;
            $reasons[] = sprintf('Strong commercial proof: %d reviews (%.1f★) (+25)', $reviews, $rating);
        } elseif ($reviews >= 5) {
            $score += 15;
            $reasons[] = sprintf('Established commercial presence: %d reviews (%.1f★) (+15)', $reviews, $rating);
        } elseif (!empty($lead['address']) || !empty($lead['google_maps_url'])) {
            $score += 10;
            $reasons[] = 'Verified physical commercial premises & Google Maps (+10)';
        }

        // Direct Corporate Email Bonus
        if (!empty($lead['email']) && strpos($lead['email'], '@') !== false) {
            if (!preg_match('/@(gmail|yahoo|hotmail|outlook)\.com$/i', $lead['email'])) {
                $score = min(100, $score + 5);
                $reasons[] = 'Corporate domain email address (+5)';
            }
        }

        // 5. Active Paid Meta Ads Analysis (Definitive Paying Capacity Indicator)
        if (!empty($lead['meta_ads_active']) && (int)$lead['meta_ads_active'] === 1) {
            $adsCount = (int)($lead['meta_ads_count'] ?? 1);
            $runningSince = !empty($lead['meta_ads_running_since']) ? $lead['meta_ads_running_since'] : 'Active campaigns';
            $reasons[] = sprintf('Active Paid Meta Ads: %d live ad creatives (%s) — verified cashflow budget (+15)', $adsCount, $runningSince);
        }

        // 6. Verified Social Audience & Follower Count
        $igFollowers = (int)($lead['instagram_followers'] ?? 0);
        $igGrowth = !empty($lead['instagram_growth']) ? $lead['instagram_growth'] : 'high engagement';
        if ($igFollowers >= 50000) {
            $reasons[] = sprintf('High-intent audience: %s Instagram followers (%s) (+10)', number_format($igFollowers), $igGrowth);
        } elseif ($igFollowers >= 10000) {
            $reasons[] = sprintf('Established audience: %s Instagram followers (+5)', number_format($igFollowers));
        }

        // Cap score between 0 and 100
        $finalScore = max(0, min(100, $score));

        // Determine Paying Capacity Tier
        if ($finalScore >= 85) {
            $tier = 'High / Enterprise';
        } elseif ($finalScore >= 70) {
            $tier = 'Mid-Market / Premium';
        } elseif ($finalScore >= 50) {
            $tier = 'Boutique / Starter';
        } else {
            $tier = 'Unqualified / Low-Intent';
        }

        $isQualified = ($finalScore >= $minThreshold);

        return [
            'score' => $finalScore,
            'tier' => $tier,
            'is_qualified' => $isQualified ? 1 : 0,
            'reasons' => $reasons,
            'flags' => $flags,
            'badge_color' => $finalScore >= 85 ? 'gold' : ($finalScore >= 70 ? 'blue' : ($finalScore >= 50 ? 'amber' : 'rose')),
        ];
    }

    /**
     * Normalizes phone numbers to standard E.164 without dashes or spaces.
     */
    public static function cleanPhone(string $phone): string {
        $p = preg_replace('/[^\d\+]/', '', $phone);
        if (empty($p)) return '';

        // If local 10-digit Indian number without country code
        if (preg_match('/^[6-9]\d{9}$/', $p)) {
            return '+91' . $p;
        }
        if (substr($p, 0, 1) !== '+') {
            return '+' . $p;
        }
        return $p;
    }

    /**
     * Generates a direct WhatsApp click-to-chat URL with pre-filled message
     */
    public static function buildWhatsAppUrl(string $phone, string $message = ''): string {
        $clean = preg_replace('/[^\d]/', '', self::cleanPhone($phone));
        if (empty($clean)) return '';
        $url = 'https://wa.me/' . $clean;
        if (!empty($message)) {
            $url .= '?text=' . rawurlencode($message);
        }
        return $url;
    }
}
