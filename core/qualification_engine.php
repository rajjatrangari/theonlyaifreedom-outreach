<?php
// Outreach AI Freedom - Lead Qualification & Paying Capacity Engine
declare(strict_types=1);

class QualificationEngine {

    /**
     * Scores a lead based on commercial viability, verified contactability,
     * paying capacity signals, and online maturity.
     * Returns score (0-100), tier, is_qualified flag, and explanatory badges.
     */
    public static function evaluate(array $lead, int $minThreshold = 70): array {
        $score = 0;
        $reasons = [];
        $flags = [];

        // 1. Domain & Official Website Analysis (Max 25 pts)
        $website = trim($lead['website'] ?? '');
        if (!empty($website)) {
            $parsedUrl = parse_url($website);
            $host = strtolower($parsedUrl['host'] ?? $website);

            // Penalize free blogging subdomains (low paying capacity indicator)
            $isFreeDomain = preg_match('/\.(wixsite|wordpress|blogspot|weebly|jimdo|carrd)\./i', $host);
            if ($isFreeDomain) {
                $score += 5;
                $flags[] = 'Free hosting domain (Low budget signal)';
            } else {
                $score += 25;
                $reasons[] = 'Commercial custom domain & active web presence (+25)';
            }
        } else {
            $flags[] = 'No verified website (-25)';
        }

        // 2. Verified Direct WhatsApp Contact (Max 25 pts)
        $whatsapp = self::cleanPhone($lead['whatsapp'] ?? ($lead['phone'] ?? ''));
        if (!empty($whatsapp) && strlen($whatsapp) >= 10) {
            $score += 25;
            $reasons[] = 'Verified Direct WhatsApp number with 1-click chat (+25)';
        } else {
            $flags[] = 'Missing or unverified WhatsApp (-25)';
        }

        // 3. Multi-Channel Social Footprint (Max 25 pts)
        $socialCount = 0;
        if (!empty($lead['linkedin_url'])) {
            $score += 10;
            $socialCount++;
            $reasons[] = 'Active LinkedIn Business Profile (+10)';
        }
        if (!empty($lead['instagram_url'])) {
            $score += 8;
            $socialCount++;
            $reasons[] = 'Active Instagram Business Profile (+8)';
        }
        if (!empty($lead['facebook_url'])) {
            $score += 4;
            $socialCount++;
        }
        if (!empty($lead['twitter_url'])) {
            $score += 3;
            $socialCount++;
        }
        if ($socialCount >= 2) {
            $reasons[] = 'Multi-channel social proof & brand footprint';
        }

        // 4. Commercial Reputation & Reviews (Max 25 pts)
        $reviews = (int)($lead['reviews_count'] ?? 0);
        $rating = (float)($lead['rating'] ?? 0.0);

        if ($reviews >= 20 && $rating >= 4.0) {
            $score += 25;
            $reasons[] = sprintf('Strong commercial reviews: %d reviews (%.1f★) (+25)', $reviews, $rating);
        } elseif ($reviews >= 5) {
            $score += 15;
            $reasons[] = sprintf('Established commercial presence: %d reviews (%.1f★) (+15)', $reviews, $rating);
        } elseif (!empty($lead['address']) || !empty($lead['google_maps_url'])) {
            $score += 10;
            $reasons[] = 'Verified physical business address & Maps presence (+10)';
        }

        // Direct Email bonus
        if (!empty($lead['email']) && strpos($lead['email'], '@') !== false) {
            if (!preg_match('/@(gmail|yahoo|hotmail|outlook)\.com$/i', $lead['email'])) {
                $score = min(100, $score + 5);
                $reasons[] = 'Corporate domain email address (+5)';
            }
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
            'badge_color' => $finalScore >= 85 ? 'emerald' : ($finalScore >= 70 ? 'blue' : ($finalScore >= 50 ? 'amber' : 'rose')),
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
     * Generates a direct WhatsApp click-to-chat URL with optional pre-filled message
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
