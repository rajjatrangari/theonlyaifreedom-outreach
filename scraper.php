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

<div class="space-y-8">
    
    <!-- Scraper Header & Control Form -->
    <div class="glass-panel rounded-2xl p-6 border border-slate-800">
        <div class="flex flex-col md:flex-row items-start md:items-center justify-between gap-4 mb-6">
            <div>
                <h2 class="text-xl font-bold text-white tracking-tight flex items-center gap-2">
                    <span>Qualified Lead Scraper & Real-Time Enrichment</span>
                    <span class="text-xs px-2.5 py-0.5 rounded-full bg-emerald-500/10 text-emerald-400 border border-emerald-500/20 font-mono">Paying Capacity Verified</span>
                </h2>
                <p class="text-xs text-slate-400 mt-1">
                    Crawls official websites, verified business directories, and maps. Filters out inactive businesses, dead URLs, and low-budget entries.
                </p>
            </div>
            <div class="flex items-center gap-2">
                <a href="/crm.php" class="px-4 py-2 bg-slate-800 hover:bg-slate-700 text-slate-300 text-xs font-semibold rounded-xl border border-slate-700/80 transition flex items-center gap-2">
                    <i data-lucide="kanban" class="w-4 h-4 text-emerald-400"></i>
                    <span>Open CRM Pipeline</span>
                </a>
            </div>
        </div>

        <form id="scraperForm" onsubmit="runScraper(event)" class="space-y-4">
            <div class="grid grid-cols-1 sm:grid-cols-12 gap-4">
                
                <div class="sm:col-span-5">
                    <label class="block text-xs font-semibold text-slate-300 mb-1.5">Target Business Niche or Category *</label>
                    <div class="relative">
                        <i data-lucide="crosshair" class="w-4 h-4 text-slate-500 absolute left-3.5 top-3"></i>
                        <input type="text" id="scrapeKeyword" required 
                               value="<?= h($user['business_niche'] ? 'Luxury ' . $user['business_niche'] : 'Luxury Interior Designers') ?>"
                               placeholder="e.g. High-end Jewellery, Dental Clinics, Luxury Real Estate, B2B SaaS..." 
                               class="w-full bg-slate-900 border border-slate-700 rounded-xl pl-10 pr-4 py-2.5 text-xs text-white placeholder-slate-500 focus:outline-none focus:border-emerald-500">
                    </div>
                </div>

                <div class="sm:col-span-4">
                    <label class="block text-xs font-semibold text-slate-300 mb-1.5">Location / City / Country</label>
                    <div class="relative">
                        <i data-lucide="map-pin" class="w-4 h-4 text-slate-500 absolute left-3.5 top-3"></i>
                        <input type="text" id="scrapeLocation" 
                               value="Mumbai, India"
                               placeholder="e.g. Mumbai, Dubai, London, New York..." 
                               class="w-full bg-slate-900 border border-slate-700 rounded-xl pl-10 pr-4 py-2.5 text-xs text-white placeholder-slate-500 focus:outline-none focus:border-emerald-500">
                    </div>
                </div>

                <div class="sm:col-span-3">
                    <label class="block text-xs font-semibold text-slate-300 mb-1.5">Paying Capacity Filter</label>
                    <select id="scrapeMinScore" class="w-full bg-slate-900 border border-slate-700 rounded-xl px-3.5 py-2.5 text-xs text-white focus:outline-none focus:border-emerald-500">
                        <option value="75" selected>Score 75+ (High Paying Only)</option>
                        <option value="85">Score 85+ (Enterprise / Elite)</option>
                        <option value="70">Score 70+ (Standard Commercial)</option>
                        <option value="60">Score 60+ (Broad Discovery)</option>
                    </select>
                </div>
            </div>

            <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 pt-2">
                <div class="flex items-center gap-2 text-xs text-slate-400">
                    <i data-lucide="shield-check" class="w-4 h-4 text-emerald-400"></i>
                    <span>Every lead includes working website, phone, verified WhatsApp, and social profiles.</span>
                </div>

                <button type="submit" id="scrapeSubmitBtn" class="w-full sm:w-auto px-6 py-2.5 rounded-xl bg-gradient-to-r from-emerald-500 to-teal-600 hover:from-emerald-400 hover:to-teal-500 text-black font-bold text-xs shadow-lg shadow-emerald-500/25 transition flex items-center justify-center gap-2">
                    <i data-lucide="sparkles" class="w-4 h-4"></i>
                    <span>Scrape & Enrich Leads</span>
                </button>
            </div>
        </form>

        <!-- Live Scan Indicator -->
        <div id="scanIndicator" class="hidden mt-4 p-4 rounded-xl bg-slate-900/90 border border-emerald-500/30 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="w-4 h-4 border-2 border-emerald-400 border-t-transparent rounded-full animate-spin"></div>
                <div class="text-xs text-slate-200">
                    <strong class="text-emerald-400">Scanning multi-source directories...</strong> Validating SSL certificates, extracting WhatsApp numbers, crawling social presence, and ranking paying capacity.
                </div>
            </div>
        </div>
    </div>

    <!-- Scraped Leads Database View -->
    <div class="space-y-4">
        <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
            <div>
                <h3 class="font-bold text-white text-base flex items-center gap-2">
                    <span>Enriched Qualified Leads</span>
                    <span class="text-xs px-2.5 py-0.5 rounded-full bg-slate-800 text-slate-300 font-mono"><?= count($leads) ?> total</span>
                </h3>
            </div>
            <div class="flex items-center gap-3">
                <a href="/api.php?action=export_leads_csv" class="px-3.5 py-1.5 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300 text-xs font-medium border border-slate-700 flex items-center gap-1.5 transition">
                    <i data-lucide="download" class="w-3.5 h-3.5"></i>
                    <span>Export CSV</span>
                </a>
            </div>
        </div>

        <?php if (empty($leads)): ?>
            <div class="glass-panel rounded-2xl p-12 text-center border border-slate-800">
                <div class="w-12 h-12 rounded-xl bg-slate-800/80 text-slate-400 flex items-center justify-center mx-auto mb-3">
                    <i data-lucide="search" class="w-6 h-6"></i>
                </div>
                <h4 class="font-semibold text-white text-sm mb-1">No Leads Scraped Yet</h4>
                <p class="text-xs text-slate-400 max-w-md mx-auto">Enter your target industry above and hit "Scrape & Enrich Leads" to populate your pipeline with verified high-budget clients.</p>
            </div>
        <?php else: ?>
            <div class="glass-panel rounded-2xl border border-slate-800 overflow-hidden">
                <div class="overflow-x-auto custom-scroll">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="border-b border-slate-800/80 bg-slate-900/60 text-[11px] font-semibold uppercase tracking-wider text-slate-400">
                                <th class="py-3.5 px-4">Business / Brand</th>
                                <th class="py-3.5 px-4">Paying Capacity (Q-Score)</th>
                                <th class="py-3.5 px-4">Website & Status</th>
                                <th class="py-3.5 px-4">Direct WhatsApp & Phone</th>
                                <th class="py-3.5 px-4">Social Footprint</th>
                                <th class="py-3.5 px-4">Reviews & Rating</th>
                                <th class="py-3.5 px-4 text-right">Outreach Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-800/60 text-xs">
                            <?php foreach ($leads as $lead): 
                                $score = (int)$lead['qualification_score'];
                                $scoreBg = $score >= 85 ? 'bg-emerald-500/15 text-emerald-400 border-emerald-500/30' : ($score >= 70 ? 'bg-blue-500/15 text-blue-400 border-blue-500/30' : 'bg-amber-500/15 text-amber-400 border-amber-500/30');
                            ?>
                                <tr class="hover:bg-slate-800/30 transition">
                                    
                                    <!-- Business Name -->
                                    <td class="py-3.5 px-4">
                                        <div class="font-bold text-white"><?= h($lead['business_name']) ?></div>
                                        <div class="text-[11px] text-slate-400 truncate max-w-[200px]">
                                            <?= h($lead['category']) ?> • <?= h($lead['city'] ?: 'India') ?>
                                        </div>
                                    </td>

                                    <!-- Q Score & Tier -->
                                    <td class="py-3.5 px-4">
                                        <div class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full border text-xs font-bold <?= $scoreBg ?>">
                                            <i data-lucide="award" class="w-3.5 h-3.5"></i>
                                            <span><?= $score ?>/100</span>
                                        </div>
                                        <div class="text-[10px] text-slate-400 mt-1 font-medium"><?= h($lead['paying_capacity_tier']) ?></div>
                                    </td>

                                    <!-- Website -->
                                    <td class="py-3.5 px-4">
                                        <?php if (!empty($lead['website'])): ?>
                                            <a href="<?= h($lead['website']) ?>" target="_blank" class="text-blue-400 hover:underline flex items-center gap-1 font-medium truncate max-w-[170px]">
                                                <i data-lucide="external-link" class="w-3 h-3 flex-shrink-0"></i>
                                                <span class="truncate"><?= h(preg_replace('#^https?://(www\.)?#', '', $lead['website'])) ?></span>
                                            </a>
                                            <span class="inline-block mt-0.5 text-[10px] text-emerald-400 font-mono">● HTTPS Verified</span>
                                        <?php else: ?>
                                            <span class="text-slate-500 italic">No website</span>
                                        <?php endif; ?>
                                    </td>

                                    <!-- Phone & WhatsApp -->
                                    <td class="py-3.5 px-4">
                                        <div class="font-mono text-slate-200"><?= h($lead['phone'] ?: 'N/A') ?></div>
                                        <?php if (!empty($lead['whatsapp'])): ?>
                                            <a href="https://wa.me/<?= preg_replace('/[^\d]/', '', $lead['whatsapp']) ?>" target="_blank" class="inline-flex items-center gap-1 text-[11px] text-emerald-400 hover:underline mt-0.5 font-medium">
                                                <i data-lucide="message-circle" class="w-3 h-3"></i>
                                                <span>WhatsApp Ready</span>
                                            </a>
                                        <?php endif; ?>
                                    </td>

                                    <!-- Social Footprint -->
                                    <td class="py-3.5 px-4">
                                        <div class="flex items-center gap-1.5">
                                            <?php if (!empty($lead['linkedin_url'])): ?>
                                                <a href="<?= h($lead['linkedin_url']) ?>" target="_blank" class="p-1 rounded bg-slate-800 hover:bg-slate-700 text-blue-400" title="LinkedIn">
                                                    <i data-lucide="linkedin" class="w-3.5 h-3.5"></i>
                                                </a>
                                            <?php endif; ?>
                                            <?php if (!empty($lead['instagram_url'])): ?>
                                                <a href="<?= h($lead['instagram_url']) ?>" target="_blank" class="p-1 rounded bg-slate-800 hover:bg-slate-700 text-pink-400" title="Instagram">
                                                    <i data-lucide="instagram" class="w-3.5 h-3.5"></i>
                                                </a>
                                            <?php endif; ?>
                                            <?php if (!empty($lead['facebook_url'])): ?>
                                                <a href="<?= h($lead['facebook_url']) ?>" target="_blank" class="p-1 rounded bg-slate-800 hover:bg-slate-700 text-indigo-400" title="Facebook">
                                                    <i data-lucide="facebook" class="w-3.5 h-3.5"></i>
                                                </a>
                                            <?php endif; ?>
                                            <?php if (!empty($lead['twitter_url'])): ?>
                                                <a href="<?= h($lead['twitter_url']) ?>" target="_blank" class="p-1 rounded bg-slate-800 hover:bg-slate-700 text-slate-300" title="Twitter / X">
                                                    <i data-lucide="twitter" class="w-3.5 h-3.5"></i>
                                                </a>
                                            <?php endif; ?>
                                        </div>
                                    </td>

                                    <!-- Google Reviews -->
                                    <td class="py-3.5 px-4">
                                        <div class="flex items-center gap-1 font-semibold text-amber-400">
                                            <i data-lucide="star" class="w-3.5 h-3.5 fill-amber-400 text-amber-400"></i>
                                            <span><?= number_format((float)$lead['rating'], 1) ?></span>
                                            <span class="text-slate-400 text-[11px] font-normal">(<?= (int)$lead['reviews_count'] ?>)</span>
                                        </div>
                                    </td>

                                    <!-- Outreach Action -->
                                    <td class="py-3.5 px-4 text-right">
                                        <button onclick="openOutreachModal(<?= (int)$lead['id'] ?>, 'whatsapp')" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-gradient-to-r from-emerald-500 to-teal-600 hover:from-emerald-400 hover:to-teal-500 text-black font-bold text-xs shadow-md shadow-emerald-500/15 transition">
                                            <i data-lucide="sparkles" class="w-3 h-3"></i>
                                            <span>AI Pitch</span>
                                        </button>
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
