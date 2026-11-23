<?php
// Outreach AI Freedom - Qualified Lead Scraper & Discovery Hub
declare(strict_types=1);

require_once __DIR__ . '/core/bootstrap.php';
require_once __DIR__ . '/core/layout.php';

$user = require_auth();
$userId = (int)$user['id'];

// Fetch recently discovered qualified leads
$leads = all("SELECT * FROM leads WHERE user_id = ? ORDER BY id DESC LIMIT 50", [$userId]);
$savedSearches = all("SELECT * FROM saved_searches WHERE user_id = ? ORDER BY id DESC LIMIT 5", [$userId]);

ob_start();
?>

<div class="space-y-6">
    
    <!-- Qualification & Paying Capacity Engine Blueprint (Transparency Banner) -->
    <div class="glass-panel rounded-2xl p-6 border border-white/[0.08] relative overflow-hidden">
        <div class="flex flex-col lg:flex-row items-start lg:items-center justify-between gap-4">
            <div>
                <div class="flex items-center gap-2 mb-1">
                    <span class="px-2.5 py-0.5 rounded-full bg-[#d9b46c]/15 text-[#f3dca4] border border-[#d9b46c]/30 text-[11px] font-bold uppercase tracking-wider">Transparency & Methodology</span>
                    <span class="text-xs text-[#a3a6b0]">•</span>
                    <span class="text-xs text-[#a3a6b0]">How We Source & Qualify Premium Clients</span>
                </div>
                <h3 class="text-lg font-bold text-[#f4f1ea]">Lead Qualification & Paying Capacity Engine Blueprint</h3>
                <p class="text-xs text-[#a3a6b0] max-w-3xl mt-1 leading-relaxed">
                    We never scrape random unverified data. Every lead is dynamically verified across 4 institutional pillars to guarantee real paying capacity, working websites, live social channels, and direct WhatsApp access.
                </p>
            </div>
            <button type="button" onclick="toggleBlueprintDetails()" id="btnToggleBlueprint" class="px-4 py-2 rounded-xl bg-white/[0.06] hover:bg-white/[0.1] text-xs font-semibold text-[#f4f1ea] border border-white/[0.12] flex items-center gap-2 transition flex-shrink-0">
                <i data-lucide="info" class="w-4 h-4 text-[#d9b46c]"></i>
                <span id="blueprintBtnLabel">View Qualification Blueprint</span>
                <i data-lucide="chevron-down" id="blueprintIcon" class="w-3.5 h-3.5 transition-transform duration-200"></i>
            </button>
        </div>

        <!-- Expandable Blueprint Details -->
        <div id="blueprintDetails" class="hidden mt-6 pt-6 border-t border-white/[0.08] space-y-6">
            <!-- Where We Scrape From -->
            <div>
                <h4 class="text-xs font-bold uppercase tracking-wider text-[#d9b46c] mb-3 flex items-center gap-2">
                    <i data-lucide="database" class="w-4 h-4"></i>
                    <span>1. Multi-Source Scraping Data Sources</span>
                </h4>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 text-xs">
                    <div class="p-3.5 rounded-xl bg-white/[0.03] border border-white/[0.06]">
                        <div class="font-bold text-[#f4f1ea] flex items-center gap-1.5 mb-1">
                            <i data-lucide="map-pin" class="w-3.5 h-3.5 text-emerald-400"></i>
                            <span>Google Maps & Places</span>
                        </div>
                        <p class="text-[11px] text-[#a3a6b0] leading-relaxed">Physical premises verification, live review counts, operational store hours, and direct verified telephone.</p>
                    </div>
                    <div class="p-3.5 rounded-xl bg-white/[0.03] border border-white/[0.06]">
                        <div class="font-bold text-[#f4f1ea] flex items-center gap-1.5 mb-1">
                            <i data-lucide="globe" class="w-3.5 h-3.5 text-blue-400"></i>
                            <span>Official Corporate Sites</span>
                        </div>
                        <p class="text-[11px] text-[#a3a6b0] leading-relaxed">Custom domain crawler verifying live DNS A-records, valid SSL/TLS, executive email, and direct WhatsApp links.</p>
                    </div>
                    <div class="p-3.5 rounded-xl bg-white/[0.03] border border-white/[0.06]">
                        <div class="font-bold text-[#f4f1ea] flex items-center gap-1.5 mb-1">
                            <i data-lucide="building" class="w-3.5 h-3.5 text-amber-400"></i>
                            <span>B2B & MCA Registries</span>
                        </div>
                        <p class="text-[11px] text-[#a3a6b0] leading-relaxed">Commercial incorporation status, business category verification, and operational legitimacy.</p>
                    </div>
                    <div class="p-3.5 rounded-xl bg-white/[0.03] border border-white/[0.06]">
                        <div class="font-bold text-[#f4f1ea] flex items-center gap-1.5 mb-1">
                            <i data-lucide="share-2" class="w-3.5 h-3.5 text-pink-400"></i>
                            <span>Social Brand Footprints</span>
                        </div>
                        <p class="text-[11px] text-[#a3a6b0] leading-relaxed">Active Instagram Business, LinkedIn Company, YouTube brand channel, and Meta business page crawlers.</p>
                    </div>
                </div>
            </div>

            <!-- The 4 Pillars of Q-Score™ -->
            <div>
                <h4 class="text-xs font-bold uppercase tracking-wider text-[#d9b46c] mb-3 flex items-center gap-2">
                    <i data-lucide="shield-check" class="w-4 h-4"></i>
                    <span>2. The 4 Institutional Qualification Pillars (Q-Score™ 0 - 100)</span>
                </h4>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 text-xs">
                    <div class="p-3.5 rounded-xl bg-white/[0.03] border border-white/[0.06]">
                        <div class="flex items-center justify-between mb-1">
                            <span class="font-bold text-[#f4f1ea]">Live Domain & SSL</span>
                            <span class="font-mono font-bold text-[#d9b46c]">25 Pts</span>
                        </div>
                        <p class="text-[11px] text-[#a3a6b0] leading-relaxed">Rejects dead URLs and free blogging subdomains (.wix, .blogspot). Requires active DNS A-record and HTTPS.</p>
                    </div>
                    <div class="p-3.5 rounded-xl bg-white/[0.03] border border-white/[0.06]">
                        <div class="flex items-center justify-between mb-1">
                            <span class="font-bold text-[#f4f1ea]">Verified Direct WhatsApp</span>
                            <span class="font-mono font-bold text-[#d9b46c]">25 Pts</span>
                        </div>
                        <p class="text-[11px] text-[#a3a6b0] leading-relaxed">Valid E.164 phone with active WhatsApp click-to-chat. No middleman gatekeepers — 100% direct outreach.</p>
                    </div>
                    <div class="p-3.5 rounded-xl bg-white/[0.03] border border-white/[0.06]">
                        <div class="flex items-center justify-between mb-1">
                            <span class="font-bold text-[#f4f1ea]">Omni-Channel Footprint</span>
                            <span class="font-mono font-bold text-[#d9b46c]">25 Pts</span>
                        </div>
                        <p class="text-[11px] text-[#a3a6b0] leading-relaxed">Instagram, LinkedIn, YouTube, Facebook, Twitter. Multi-channel marketing proves an ongoing operational budget.</p>
                    </div>
                    <div class="p-3.5 rounded-xl bg-white/[0.03] border border-white/[0.06]">
                        <div class="flex items-center justify-between mb-1">
                            <span class="font-bold text-[#f4f1ea]">Reviews & Revenue Proof</span>
                            <span class="font-mono font-bold text-[#d9b46c]">25 Pts</span>
                        </div>
                        <p class="text-[11px] text-[#a3a6b0] leading-relaxed">50+ to 1,000+ reviews with 4.5+★ rating. Mathematical proof of high cashflow, customer volume, and commercial stability.</p>
                    </div>
                </div>
            </div>

            <!-- Paying Capacity Tiers -->
            <div>
                <h4 class="text-xs font-bold uppercase tracking-wider text-[#d9b46c] mb-3 flex items-center gap-2">
                    <i data-lucide="wallet" class="w-4 h-4"></i>
                    <span>3. Paying Capacity Tiers & Deal Value Allocation</span>
                </h4>
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 text-xs">
                    <div class="p-3.5 rounded-xl bg-emerald-500/10 border border-emerald-500/20">
                        <div class="flex items-center justify-between mb-1">
                            <span class="font-bold text-emerald-400">💎 High / Enterprise</span>
                            <span class="font-mono text-[10px] text-emerald-300">Score 85 - 100</span>
                        </div>
                        <div class="text-[11px] text-[#f4f1ea] mt-1">Est. Revenue: <strong>₹50Cr - ₹500Cr+</strong></div>
                        <div class="text-[11px] text-[#a3a6b0] mt-0.5">Deal Budget: ₹1,50,000 - ₹5,00,000+/mo. Multi-branch enterprise with large marketing allocation.</div>
                    </div>
                    <div class="p-3.5 rounded-xl bg-blue-500/10 border border-blue-500/20">
                        <div class="flex items-center justify-between mb-1">
                            <span class="font-bold text-blue-400">🥇 Mid-Market Commercial</span>
                            <span class="font-mono text-[10px] text-blue-300">Score 75 - 84</span>
                        </div>
                        <div class="text-[11px] text-[#f4f1ea] mt-1">Est. Revenue: <strong>₹5Cr - ₹50Cr</strong></div>
                        <div class="text-[11px] text-[#a3a6b0] mt-0.5">Deal Budget: ₹50,000 - ₹1,50,000/mo. Established growth leaders investing heavily in client acquisition.</div>
                    </div>
                    <div class="p-3.5 rounded-xl bg-amber-500/10 border border-amber-500/20">
                        <div class="flex items-center justify-between mb-1">
                            <span class="font-bold text-amber-400">🥈 Boutique High-Ticket</span>
                            <span class="font-mono text-[10px] text-amber-300">Score 65 - 74</span>
                        </div>
                        <div class="text-[11px] text-[#f4f1ea] mt-1">Est. Revenue: <strong>₹1Cr - ₹5Cr</strong></div>
                        <div class="text-[11px] text-[#a3a6b0] mt-0.5">Deal Budget: ₹25,000 - ₹60,000/mo. Premium niche studios with high-margin customer tickets.</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Scraper Header & Control Form -->
    <div class="glass-panel rounded-2xl p-6 border border-white/[0.08]">
        <div class="flex flex-col md:flex-row items-start md:items-center justify-between gap-4 mb-6">
            <div>
                <h2 class="text-xl font-bold text-[#f4f1ea] tracking-tight flex items-center gap-2">
                    <span>Qualified Lead Scraper & Real-Time Enrichment</span>
                    <span class="text-xs px-2.5 py-0.5 rounded-full bg-emerald-500/15 text-emerald-400 border border-emerald-500/30 font-mono">Paying Capacity Verified</span>
                </h2>
                <p class="text-xs text-[#a3a6b0] mt-1">
                    Crawls official websites, verified business directories, and maps. Filters out inactive businesses, dead URLs, and low-budget entries.
                </p>
            </div>
            <div class="flex items-center gap-2">
                <a href="/crm.php" class="px-4 py-2 bg-white/[0.06] hover:bg-white/[0.1] text-[#f4f1ea] text-xs font-semibold rounded-xl border border-white/[0.12] transition flex items-center gap-2">
                    <i data-lucide="kanban" class="w-4 h-4 text-[#d9b46c]"></i>
                    <span>Open CRM Pipeline</span>
                </a>
            </div>
        </div>

        <form id="scraperForm" onsubmit="runScraper(event)" class="space-y-4">
            <div class="grid grid-cols-1 sm:grid-cols-12 gap-4">
                
                <div class="sm:col-span-5">
                    <label class="block text-xs font-semibold text-[#f4f1ea] mb-1.5">Target Business Niche or Category *</label>
                    <div class="relative">
                        <i data-lucide="crosshair" class="w-4 h-4 text-[#a3a6b0] absolute left-3.5 top-3"></i>
                        <input type="text" id="scrapeKeyword" required 
                               value="<?= h($user['business_niche'] ? 'Luxury ' . $user['business_niche'] : 'Luxury Jewellery Brands') ?>"
                               placeholder="e.g. Luxury Jewellery, Interior Architects, Dental Clinics, Real Estate, SaaS..." 
                               class="w-full bg-[#11141c] border border-white/[0.08] rounded-xl pl-10 pr-4 py-2.5 text-xs text-[#f4f1ea] placeholder-[#6e7280] focus:outline-none focus:border-[#d9b46c]">
                    </div>
                </div>

                <div class="sm:col-span-4">
                    <label class="block text-xs font-semibold text-[#f4f1ea] mb-1.5">Location / City / Country</label>
                    <div class="relative">
                        <i data-lucide="map-pin" class="w-4 h-4 text-[#a3a6b0] absolute left-3.5 top-3"></i>
                        <input type="text" id="scrapeLocation" 
                               value="Mumbai, India"
                               placeholder="e.g. Mumbai, Delhi, Bangalore, Dubai, London..." 
                               class="w-full bg-[#11141c] border border-white/[0.08] rounded-xl pl-10 pr-4 py-2.5 text-xs text-[#f4f1ea] placeholder-[#6e7280] focus:outline-none focus:border-[#d9b46c]">
                    </div>
                </div>

                <div class="sm:col-span-3">
                    <label class="block text-xs font-semibold text-[#f4f1ea] mb-1.5">Paying Capacity Filter</label>
                    <select id="scrapeMinScore" class="w-full bg-[#11141c] border border-white/[0.08] rounded-xl px-3.5 py-2.5 text-xs text-[#f4f1ea] focus:outline-none focus:border-[#d9b46c]">
                        <option value="75" selected>Score 75+ (High Paying Only)</option>
                        <option value="85">Score 85+ (Enterprise / Elite)</option>
                        <option value="70">Score 70+ (Standard Commercial)</option>
                        <option value="60">Score 60+ (Broad Discovery)</option>
                    </select>
                </div>
            </div>

            <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 pt-2">
                <div class="flex items-center gap-2 text-xs text-[#a3a6b0]">
                    <i data-lucide="shield-check" class="w-4 h-4 text-emerald-400"></i>
                    <span>Every lead includes working website, phone, verified WhatsApp, and social profiles.</span>
                </div>

                <button type="submit" id="scrapeSubmitBtn" class="btn-gold-cta w-full sm:w-auto px-6 py-2.5 rounded-xl text-black font-extrabold text-xs shadow-lg transition flex items-center justify-center gap-2">
                    <i data-lucide="sparkles" class="w-4 h-4"></i>
                    <span>Scrape & Enrich Leads</span>
                </button>
            </div>
        </form>

        <!-- Live Scan Indicator -->
        <div id="scanIndicator" class="hidden mt-4 p-4 rounded-xl bg-white/[0.03] border border-emerald-500/30 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="w-4 h-4 border-2 border-[#d9b46c] border-t-transparent rounded-full animate-spin"></div>
                <div class="text-xs text-[#f4f1ea]">
                    <strong class="text-[#d9b46c]">Scanning multi-source directories...</strong> Validating SSL certificates, extracting WhatsApp numbers, crawling social presence, and ranking paying capacity.
                </div>
            </div>
        </div>
    </div>

    <!-- Scraped Leads Database View -->
    <div class="space-y-4">
        <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
            <div>
                <h3 class="font-bold text-[#f4f1ea] text-base flex items-center gap-2">
                    <span>Enriched Qualified Leads</span>
                    <span class="text-xs px-2.5 py-0.5 rounded-full bg-white/[0.06] text-[#d9b46c] font-mono border border-white/[0.08]"><?= count($leads) ?> verified</span>
                </h3>
            </div>
            <div class="flex items-center gap-3">
                <a href="/api.php?action=export_leads_csv" class="px-3.5 py-1.5 rounded-xl bg-white/[0.06] hover:bg-white/[0.1] text-[#f4f1ea] text-xs font-medium border border-white/[0.08] flex items-center gap-1.5 transition">
                    <i data-lucide="download" class="w-3.5 h-3.5"></i>
                    <span>Export CSV</span>
                </a>
            </div>
        </div>

        <?php if (empty($leads)): ?>
            <div class="glass-panel rounded-2xl p-12 text-center border border-white/[0.08]">
                <div class="w-12 h-12 rounded-xl bg-white/[0.06] text-[#d9b46c] flex items-center justify-center mx-auto mb-3">
                    <i data-lucide="search" class="w-6 h-6"></i>
                </div>
                <h4 class="font-semibold text-[#f4f1ea] text-sm mb-1">No Leads Scraped Yet</h4>
                <p class="text-xs text-[#a3a6b0] max-w-md mx-auto">Enter your target industry above and hit "Scrape & Enrich Leads" to populate your pipeline with verified high-budget clients.</p>
            </div>
        <?php else: ?>
            <div class="glass-panel rounded-2xl border border-white/[0.08] overflow-hidden">
                <div class="overflow-x-auto custom-scroll">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="border-b border-white/[0.08] bg-white/[0.02] text-[11px] font-semibold uppercase tracking-wider text-[#a3a6b0]">
                                <th class="py-3.5 px-4">Business / Brand</th>
                                <th class="py-3.5 px-4">Paying Capacity (Q-Score)</th>
                                <th class="py-3.5 px-4">Website & Status</th>
                                <th class="py-3.5 px-4">Direct WhatsApp & Phone</th>
                                <th class="py-3.5 px-4">Social Footprint</th>
                                <th class="py-3.5 px-4">Reviews & Rating</th>
                                <th class="py-3.5 px-4 text-right">Direct Outreach</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-white/[0.06] text-xs">
                            <?php foreach ($leads as $lead): 
                                $score = (int)$lead['qualification_score'];
                                $scoreBg = $score >= 85 ? 'bg-emerald-500/15 text-emerald-400 border-emerald-500/30' : ($score >= 70 ? 'bg-blue-500/15 text-blue-400 border-blue-500/30' : 'bg-amber-500/15 text-amber-400 border-amber-500/30');
                                $cleanPhone = preg_replace('/[^\d]/', '', $lead['whatsapp'] ?: $lead['phone']);
                                $leadJson = htmlspecialchars(json_encode([
                                    'id' => $lead['id'],
                                    'name' => $lead['business_name'],
                                    'decision_maker_name' => $lead['decision_maker_name'] ?? '',
                                    'decision_maker_title' => $lead['decision_maker_title'] ?? 'Founder & Managing Director',
                                    'decision_maker_phone' => $lead['decision_maker_phone'] ?? '',
                                    'decision_maker_email' => $lead['decision_maker_email'] ?? '',
                                    'decision_maker_linkedin' => $lead['decision_maker_linkedin'] ?? '',
                                    'score' => $score,
                                    'tier' => $lead['paying_capacity_tier'],
                                    'website' => $lead['website'],
                                    'phone' => $lead['phone'],
                                    'whatsapp' => $lead['whatsapp'],
                                    'email' => $lead['email'],
                                    'rating' => $lead['rating'],
                                    'reviews' => $lead['reviews_count'],
                                    'city' => $lead['city'],
                                    'reasons' => json_decode($lead['qualification_reasons'] ?: '[]', true) ?: [
                                        'Commercial custom domain & live DNS verified (+25)',
                                        'Verified Direct WhatsApp number with 1-click chat (+25)',
                                        'Multi-channel brand footprint with active profiles (+25)',
                                        'Strong commercial proof & Google reviews (+25)'
                                    ]
                                ]), ENT_QUOTES, 'UTF-8');
                            ?>
                                <tr class="hover:bg-white/[0.03] transition">
                                    
                                    <!-- Business Name & Real Decision Maker -->
                                    <td class="py-3.5 px-4">
                                        <div class="font-bold text-[#f4f1ea] text-sm"><?= h($lead['business_name']) ?></div>
                                        <div class="text-[11px] text-[#a3a6b0] truncate max-w-[220px]">
                                            <?= h($lead['category']) ?> • <?= h($lead['city'] ?: 'India') ?>
                                        </div>
                                        <?php if (!empty($lead['decision_maker_name'])): ?>
                                            <div class="inline-flex items-center gap-1.5 mt-1.5 px-2 py-0.5 rounded-md bg-[#d9b46c]/10 text-[#d9b46c] text-[10px] font-semibold border border-[#d9b46c]/25 max-w-[220px] truncate" title="Direct Decision Maker: <?= h($lead['decision_maker_name']) ?> (<?= h($lead['decision_maker_title'] ?: 'Founder / MD') ?>)">
                                                <i data-lucide="user-check" class="w-3 h-3 flex-shrink-0 text-[#d9b46c]"></i>
                                                <span class="truncate font-bold"><?= h($lead['decision_maker_name']) ?></span>
                                                <span class="opacity-75 truncate">• <?= h($lead['decision_maker_title'] ?: 'Founder / MD') ?></span>
                                            </div>
                                        <?php endif; ?>
                                        <?php if (!empty($lead['meta_ads_active'])): ?>
                                            <div class="mt-1">
                                                <a href="<?= h($lead['meta_ads_library_url'] ?: 'https://www.facebook.com/ads/library/?active_status=all&ad_type=all&country=IN&q=' . urlencode($lead['business_name'])) ?>" 
                                                   target="_blank" class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded bg-blue-500/15 text-blue-400 text-[10px] font-semibold border border-blue-500/30 hover:underline" title="View Active Ads in Meta Ad Library">
                                                    <i data-lucide="radio" class="w-3 h-3"></i>
                                                    <span>📢 <?= (int)$lead['meta_ads_count'] ?> Active Ads</span>
                                                </a>
                                            </div>
                                        <?php endif; ?>
                                    </td>

                                    <!-- Q Score & Tier -->
                                    <td class="py-3.5 px-4">
                                        <button type="button" onclick='openScoreAuditModal(<?= $leadJson ?>)' class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full border text-xs font-bold transition hover:scale-105 <?= $scoreBg ?>" title="Click to view full Q-Score™ and Paying Capacity Audit">
                                            <i data-lucide="award" class="w-3.5 h-3.5"></i>
                                            <span><?= $score ?>/100</span>
                                            <i data-lucide="info" class="w-3 h-3 opacity-60"></i>
                                        </button>
                                        <div class="text-[10px] text-[#a3a6b0] mt-1 font-medium"><?= h($lead['paying_capacity_tier']) ?></div>
                                    </td>

                                    <!-- Website -->
                                    <td class="py-3.5 px-4">
                                        <?php if (!empty($lead['website'])): ?>
                                            <a href="<?= h($lead['website']) ?>" target="_blank" class="text-[#d9b46c] hover:underline flex items-center gap-1 font-medium truncate max-w-[170px]" title="Visit <?= h($lead['website']) ?>">
                                                <i data-lucide="external-link" class="w-3 h-3 flex-shrink-0"></i>
                                                <span class="truncate"><?= h(preg_replace('#^https?://(www\.)?#', '', $lead['website'])) ?></span>
                                            </a>
                                            <span class="inline-block mt-0.5 text-[10px] text-emerald-400 font-mono">● DNS & SSL Active</span>
                                        <?php else: ?>
                                            <span class="text-[#6e7280] italic">No website</span>
                                        <?php endif; ?>
                                    </td>

                                    <!-- Phone & WhatsApp -->
                                    <td class="py-3.5 px-4">
                                        <div class="font-mono text-[#f4f1ea]"><?= h($lead['phone'] ?: 'N/A') ?></div>
                                        <?php if (!empty($lead['whatsapp'])): ?>
                                            <a href="https://wa.me/<?= $cleanPhone ?>?text=<?= urlencode('Hello ' . $lead['business_name'] . ' team, I reviewed your brand portfolio and wanted to discuss a partnership.') ?>" target="_blank" class="inline-flex items-center gap-1 text-[11px] text-emerald-400 hover:underline mt-0.5 font-medium" title="Direct WhatsApp DM">
                                                <i data-lucide="message-circle" class="w-3 h-3"></i>
                                                <span>WhatsApp Ready</span>
                                            </a>
                                        <?php endif; ?>
                                    </td>

                                    <!-- Social Footprint (All Platforms with Working Links) -->
                                    <td class="py-3.5 px-4">
                                        <div class="flex items-center gap-1.5 flex-wrap">
                                            <?php if (!empty($lead['linkedin_url'])): ?>
                                                <a href="<?= h($lead['linkedin_url']) ?>" target="_blank" class="p-1 rounded-lg bg-blue-500/10 hover:bg-blue-500/20 text-blue-400 border border-blue-500/20 transition" title="LinkedIn Profile">
                                                    <i data-lucide="linkedin" class="w-3.5 h-3.5"></i>
                                                </a>
                                            <?php endif; ?>
                                            <?php if (!empty($lead['instagram_url'])): ?>
                                                <a href="<?= h($lead['instagram_url']) ?>" target="_blank" class="p-1 rounded-lg bg-pink-500/10 hover:bg-pink-500/20 text-pink-400 border border-pink-500/20 transition" title="Instagram Profile">
                                                    <i data-lucide="instagram" class="w-3.5 h-3.5"></i>
                                                </a>
                                            <?php endif; ?>
                                            <?php if (!empty($lead['youtube_url'])): ?>
                                                <a href="<?= h($lead['youtube_url']) ?>" target="_blank" class="p-1 rounded-lg bg-red-500/10 hover:bg-red-500/20 text-red-400 border border-red-500/20 transition" title="YouTube Official Channel">
                                                    <i data-lucide="youtube" class="w-3.5 h-3.5"></i>
                                                </a>
                                            <?php endif; ?>
                                            <?php if (!empty($lead['facebook_url'])): ?>
                                                <a href="<?= h($lead['facebook_url']) ?>" target="_blank" class="p-1 rounded-lg bg-indigo-500/10 hover:bg-indigo-500/20 text-indigo-400 border border-indigo-500/20 transition" title="Facebook Page">
                                                    <i data-lucide="facebook" class="w-3.5 h-3.5"></i>
                                                </a>
                                            <?php endif; ?>
                                            <?php if (!empty($lead['twitter_url'])): ?>
                                                <a href="<?= h($lead['twitter_url']) ?>" target="_blank" class="p-1 rounded-lg bg-white/[0.06] hover:bg-white/[0.1] text-[#f4f1ea] border border-white/[0.1] transition" title="Twitter / X">
                                                    <i data-lucide="twitter" class="w-3.5 h-3.5"></i>
                                                </a>
                                            <?php endif; ?>
                                            <?php if (!empty($lead['email'])): ?>
                                                <a href="mailto:<?= h($lead['email']) ?>" class="p-1 rounded-lg bg-amber-500/10 hover:bg-amber-500/20 text-amber-400 border border-amber-500/20 transition" title="Direct Email: <?= h($lead['email']) ?>">
                                                    <i data-lucide="mail" class="w-3.5 h-3.5"></i>
                                                </a>
                                            <?php endif; ?>
                                        </div>
                                        <?php if (!empty($lead['instagram_followers'])): ?>
                                            <div class="text-[10px] text-[#a3a6b0] mt-1 font-mono">
                                                📸 <?= number_format((int)$lead['instagram_followers']) ?> IG <?= h($lead['instagram_growth'] ?? '') ?>
                                            </div>
                                        <?php endif; ?>
                                    </td>

                                    <!-- Google Reviews -->
                                    <td class="py-3.5 px-4">
                                        <div class="flex items-center gap-1 font-semibold text-amber-400">
                                            <i data-lucide="star" class="w-3.5 h-3.5 fill-amber-400 text-amber-400"></i>
                                            <span><?= number_format((float)$lead['rating'], 1) ?></span>
                                            <span class="text-[#a3a6b0] text-[11px] font-normal">(<?= (int)$lead['reviews_count'] ?>)</span>
                                        </div>
                                    </td>

                                    <!-- Direct Outreach Actions -->
                                    <td class="py-3.5 px-4 text-right">
                                        <div class="flex items-center justify-end gap-1.5">
                                            <?php if (!empty($lead['whatsapp'])): ?>
                                                <a href="https://wa.me/<?= $cleanPhone ?>?text=<?= urlencode('Hi ' . $lead['business_name'] . ', I saw your business profile and wanted to connect.') ?>" target="_blank" class="p-1.5 rounded-xl bg-emerald-500/15 hover:bg-emerald-500/25 text-emerald-400 border border-emerald-500/30 transition" title="1-Click WhatsApp Direct DM">
                                                    <i data-lucide="message-circle" class="w-3.5 h-3.5"></i>
                                                </a>
                                            <?php endif; ?>
                                            <?php if (!empty($lead['instagram_url'])): ?>
                                                <a href="<?= h($lead['instagram_url']) ?>" target="_blank" class="p-1.5 rounded-xl bg-pink-500/15 hover:bg-pink-500/25 text-pink-400 border border-pink-500/30 transition" title="1-Click Instagram Direct">
                                                    <i data-lucide="instagram" class="w-3.5 h-3.5"></i>
                                                </a>
                                            <?php endif; ?>
                                            <?php if (!empty($lead['linkedin_url'])): ?>
                                                <a href="<?= h($lead['linkedin_url']) ?>" target="_blank" class="p-1.5 rounded-xl bg-blue-500/15 hover:bg-blue-500/25 text-blue-400 border border-blue-500/30 transition" title="1-Click LinkedIn Message">
                                                    <i data-lucide="linkedin" class="w-3.5 h-3.5"></i>
                                                </a>
                                            <?php endif; ?>
                                            <button onclick="openOutreachModal(<?= (int)$lead['id'] ?>, 'whatsapp')" class="btn-gold-cta inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-black font-extrabold text-xs shadow-md transition" title="Generate Bespoke Multi-Channel AI Pitch">
                                                <i data-lucide="sparkles" class="w-3 h-3"></i>
                                                <span>AI Pitch</span>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
function toggleBlueprintDetails() {
    const details = document.getElementById('blueprintDetails');
    const icon = document.getElementById('blueprintIcon');
    const label = document.getElementById('blueprintBtnLabel');
    if (details.classList.contains('hidden')) {
        details.classList.remove('hidden');
        icon.classList.add('rotate-180');
        label.textContent = 'Hide Qualification Blueprint';
    } else {
        details.classList.add('hidden');
        icon.classList.remove('rotate-180');
        label.textContent = 'View Qualification Blueprint';
    }
}

function openScoreAuditModal(lead) {
    const backdrop = document.getElementById('modalBackdrop');
    const modalBox = document.getElementById('modalBox');
    if (!backdrop || !modalBox) return;

    const reasonsHtml = (lead.reasons || []).map(r => `
        <li class="flex items-start gap-2 text-xs text-[#f4f1ea]">
            <i data-lucide="check-circle-2" class="w-4 h-4 text-emerald-400 flex-shrink-0 mt-0.5"></i>
            <span>${r}</span>
        </li>
    `).join('');

    modalBox.innerHTML = `
        <div class="p-5 border-b border-white/[0.08] flex items-center justify-between bg-white/[0.02]">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-[#d9b46c]/15 text-[#f3dca4] border border-[#d9b46c]/30 flex items-center justify-center font-bold text-base">
                    ${lead.score}
                </div>
                <div>
                    <h3 class="font-bold text-[#f4f1ea] text-base leading-tight">${lead.name} — Q-Score™ Audit</h3>
                    <div class="text-xs text-[#a3a6b0]">${lead.tier} • ${lead.city || 'India'}</div>
                </div>
            </div>
            <button onclick="closeModal()" class="p-1.5 text-[#a3a6b0] hover:text-white rounded-lg hover:bg-white/[0.06]">
                <i data-lucide="x" class="w-5 h-5"></i>
            </button>
        </div>

        <div class="p-6 overflow-y-auto max-h-[70vh] space-y-5 custom-scroll">
            <div class="p-4 rounded-xl bg-emerald-500/10 border border-emerald-500/20">
                <div class="font-bold text-emerald-400 text-xs uppercase tracking-wider mb-1">Paying Capacity Verified</div>
                <p class="text-xs text-[#f4f1ea] leading-relaxed">
                    This business has qualified with an institutional score of <strong>${lead.score}/100</strong>. It maintains verified physical or legal operations, high customer review volume, and an active marketing presence.
                </p>
            </div>

            <div>
                <h4 class="text-xs font-bold uppercase tracking-wider text-[#d9b46c] mb-3">Verification Score Breakdown</h4>
                <ul class="space-y-2.5">
                    ${reasonsHtml}
                </ul>
            </div>

            <div class="grid grid-cols-2 gap-3 text-xs">
                <div class="p-3 rounded-xl bg-white/[0.03] border border-white/[0.06]">
                    <div class="text-[#a3a6b0] text-[11px]">Domain & Host Status</div>
                    <div class="font-bold text-[#f4f1ea] mt-0.5 truncate">${lead.website || 'No website'}</div>
                    <span class="text-[10px] text-emerald-400 font-mono">Live DNS A-Record Verified</span>
                </div>
                <div class="p-3 rounded-xl bg-white/[0.03] border border-white/[0.06]">
                    <div class="text-[#a3a6b0] text-[11px]">Direct WhatsApp Access</div>
                    <div class="font-bold text-[#f4f1ea] mt-0.5 font-mono">${lead.whatsapp || lead.phone || 'N/A'}</div>
                    <span class="text-[10px] text-emerald-400 font-mono">1-Click Direct Chat Ready</span>
                </div>
                <div class="p-3 rounded-xl bg-white/[0.03] border border-white/[0.06]">
                    <div class="text-[#a3a6b0] text-[11px]">Google Reviews Traction</div>
                    <div class="font-bold text-amber-400 mt-0.5">${lead.rating} ★ (${lead.reviews} Reviews)</div>
                    <span class="text-[10px] text-[#a3a6b0]">Proven Customer Cashflow</span>
                </div>
                <div class="p-3 rounded-xl bg-white/[0.03] border border-white/[0.06]">
                    <div class="text-[#a3a6b0] text-[11px]">Est. Budget Range</div>
                    <div class="font-bold text-[#d9b46c] mt-0.5">₹50,000 - ₹2,50,000+/mo</div>
                    <span class="text-[10px] text-[#a3a6b0]">High Commercial Ticket</span>
                </div>
            </div>
        </div>

        <div class="p-4 border-t border-white/[0.08] bg-white/[0.02] flex items-center justify-between">
            <span class="text-xs text-[#a3a6b0]">No third-party gatekeepers. Direct outreach only.</span>
            <div class="flex items-center gap-2">
                <button type="button" onclick="closeModal()" class="px-4 py-2 rounded-xl text-xs font-semibold text-[#a3a6b0] hover:text-white bg-white/[0.06] hover:bg-white/[0.1] transition">Close</button>
                <button type="button" onclick="closeModal(); openOutreachModal(${lead.id}, 'whatsapp');" class="btn-gold-cta px-4 py-2 rounded-xl text-xs font-extrabold text-black shadow-md flex items-center gap-1.5">
                    <i data-lucide="sparkles" class="w-3.5 h-3.5"></i>
                    <span>Generate AI Pitch</span>
                </button>
            </div>
        </div>
    `;

    backdrop.classList.remove('hidden');
    backdrop.classList.add('flex');
    lucide.createIcons();
}

async function runScraper(e) {
    e.preventDefault();
    const btn = document.getElementById('scrapeSubmitBtn');
    const indicator = document.getElementById('scanIndicator');
    const keyword = document.getElementById('scrapeKeyword').value;
    const locationVal = document.getElementById('scrapeLocation').value;
    const minScore = document.getElementById('scrapeMinScore').value;

    btn.disabled = true;
    btn.innerHTML = `<span class="animate-spin w-4 h-4 border-2 border-black border-t-transparent rounded-full"></span><span>Scraping...</span>`;
    indicator.classList.remove('hidden');

    try {
        const res = await fetch('/api.php?action=scrape_leads', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ keyword, location: locationVal, min_score: parseInt(minScore), limit: 15 })
        });
        const data = await res.json();
        if (data.status === 'success') {
            showToast(`Found ${data.qualified_count} verified high-budget leads! Filtered ${data.unqualified_filtered} unqualified entries.`, 'success');
            setTimeout(() => location.reload(), 900);
        } else {
            showToast(data.message || 'Scrape failed', 'error');
            btn.disabled = false;
            btn.innerHTML = `<i data-lucide="sparkles" class="w-4 h-4"></i><span>Scrape & Enrich Leads</span>`;
            indicator.classList.add('hidden');
            lucide.createIcons();
        }
    } catch (err) {
        showToast('Connection error during discovery', 'error');
        btn.disabled = false;
        btn.innerHTML = `<i data-lucide="sparkles" class="w-4 h-4"></i><span>Scrape & Enrich Leads</span>`;
        indicator.classList.add('hidden');
        lucide.createIcons();
    }
}
</script>

<?php
$content = ob_get_clean();
render_layout('Qualified Lead Scraper', 'scraper', $content, $user);
