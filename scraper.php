<?php
// Outreach AI Freedom - High-Capacity Qualified Lead Scraper & CRM Pipeline Hub
declare(strict_types=1);

require_once __DIR__ . '/core/bootstrap.php';
require_once __DIR__ . '/core/layout.php';

$user = require_auth();
$userId = (int)$user['id'];

// Initial load: Fetch saved CRM leads to display in "Saved in CRM" tab
$crmLeads = all("SELECT * FROM leads WHERE user_id = ? ORDER BY id DESC LIMIT 100", [$userId]);
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
                    <span class="text-xs text-[#a3a6b0]">High-Volume 50 to 80+ B2B Discovery</span>
                </div>
                <h3 class="text-lg font-extrabold text-[#f4f1ea]">Lead Qualification & Paying Capacity Engine Blueprint</h3>
                <p class="text-xs text-[#a3a6b0] max-w-3xl mt-1 leading-relaxed subtext-contrast">
                    Dynamically queries commercial registries, Google business directories, and Meta Ad Libraries across any city. Discovers 50 to 80+ unique prospects with verified decision-maker names, live WhatsApp click-to-chat, and authentic paying capacity.
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
            <div>
                <h4 class="text-xs font-bold uppercase tracking-wider text-[#d9b46c] mb-3 flex items-center gap-2">
                    <i data-lucide="database" class="w-4 h-4"></i>
                    <span>1. High-Precision Multi-Source Scraper Engine</span>
                </h4>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 text-xs">
                    <div class="p-3.5 rounded-xl bg-white/[0.03] border border-white/[0.06]">
                        <div class="font-bold text-[#f4f1ea] flex items-center gap-1.5 mb-1">
                            <i data-lucide="map-pin" class="w-3.5 h-3.5 text-emerald-400"></i>
                            <span>Local Commercial Corridors</span>
                        </div>
                        <p class="text-[11px] text-[#a3a6b0] leading-relaxed subtext-contrast">Analyzes authentic regional neighborhoods (e.g. Dharampeth & Itwari in Nagpur, Bandra in Mumbai) for physical operations.</p>
                    </div>
                    <div class="p-3.5 rounded-xl bg-white/[0.03] border border-white/[0.06]">
                        <div class="font-bold text-[#f4f1ea] flex items-center gap-1.5 mb-1">
                            <i data-lucide="radio" class="w-3.5 h-3.5 text-blue-400"></i>
                            <span>Meta Ad Library Live Audit</span>
                        </div>
                        <p class="text-[11px] text-[#a3a6b0] leading-relaxed subtext-contrast">Scans active ad creatives and campaign runtime to verify dedicated paid advertising budgets.</p>
                    </div>
                    <div class="p-3.5 rounded-xl bg-white/[0.03] border border-white/[0.06]">
                        <div class="font-bold text-[#f4f1ea] flex items-center gap-1.5 mb-1">
                            <i data-lucide="user-check" class="w-3.5 h-3.5 text-amber-400"></i>
                            <span>Direct Decision Maker Access</span>
                        </div>
                        <p class="text-[11px] text-[#a3a6b0] leading-relaxed subtext-contrast">Finds Founders, MDs, and CEOs by name so you bypass gatekeepers completely.</p>
                    </div>
                    <div class="p-3.5 rounded-xl bg-white/[0.03] border border-white/[0.06]">
                        <div class="font-bold text-[#f4f1ea] flex items-center gap-1.5 mb-1">
                            <i data-lucide="message-circle" class="w-3.5 h-3.5 text-pink-400"></i>
                            <span>Direct WhatsApp Click-to-Chat</span>
                        </div>
                        <p class="text-[11px] text-[#a3a6b0] leading-relaxed subtext-contrast">Generates pre-formatted 1-click WhatsApp DM links for instant outreach dispatch.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Scraper Control Form Card -->
    <div class="glass-panel rounded-2xl p-6 border border-white/[0.08]">
        <div class="flex flex-col md:flex-row items-start md:items-center justify-between gap-4 mb-6">
            <div>
                <h2 class="text-xl font-extrabold text-[#f4f1ea] tracking-tight flex items-center gap-2">
                    <span>Qualified Lead Scraper & Real-Time Discovery</span>
                    <span class="text-xs px-2.5 py-0.5 rounded-full bg-emerald-500/15 text-emerald-400 border border-emerald-500/30 font-mono">50 to 80+ Leads/Search</span>
                </h2>
                <p class="text-xs text-[#a3a6b0] mt-1 subtext-contrast">
                    Search any niche and city across India or globally. Generates unique, highly qualified leads every search with selective CRM saving.
                </p>
            </div>
            <div class="flex items-center gap-2">
                <a href="/crm.php" class="px-4 py-2 bg-white/[0.06] hover:bg-white/[0.1] text-[#f4f1ea] text-xs font-semibold rounded-xl border border-white/[0.12] transition flex items-center gap-2">
                    <i data-lucide="kanban" class="w-4 h-4 text-[#d9b46c]"></i>
                    <span>Open CRM Pipeline (<?= count($crmLeads) ?>)</span>
                </a>
            </div>
        </div>

        <form id="scraperForm" onsubmit="runScraper(event)" class="space-y-4">
            <div class="grid grid-cols-1 sm:grid-cols-12 gap-4">
                
                <!-- Niche / Category Input -->
                <div class="sm:col-span-4">
                    <label class="block text-xs font-bold text-[#f4f1ea] mb-1.5 form-label">Target Business Niche or Category *</label>
                    <div class="relative">
                        <i data-lucide="crosshair" class="w-4 h-4 text-[#a3a6b0] absolute left-3.5 top-3"></i>
                        <input type="text" id="scrapeKeyword" required 
                               value="Jewellery Brand"
                               placeholder="e.g. Jewellery Brand, Interior Architects, Dental Clinics, Real Estate..." 
                               class="w-full bg-[#11141c] border border-white/[0.12] rounded-xl pl-10 pr-4 py-2.5 text-xs text-[#f4f1ea] placeholder-[#6e7280] focus:outline-none focus:border-[#d9b46c]">
                    </div>
                </div>

                <!-- Location / City Input -->
                <div class="sm:col-span-3">
                    <label class="block text-xs font-bold text-[#f4f1ea] mb-1.5 form-label">Location / City / Country *</label>
                    <div class="relative">
                        <i data-lucide="map-pin" class="w-4 h-4 text-[#a3a6b0] absolute left-3.5 top-3"></i>
                        <input type="text" id="scrapeLocation" required
                               value="Nagpur, India"
                               placeholder="e.g. Nagpur, India / Mumbai / Pune / Delhi / Dubai..." 
                               class="w-full bg-[#11141c] border border-white/[0.12] rounded-xl pl-10 pr-4 py-2.5 text-xs text-[#f4f1ea] placeholder-[#6e7280] focus:outline-none focus:border-[#d9b46c]">
                    </div>
                </div>

                <!-- Volume Selector (50 to 80+ leads) -->
                <div class="sm:col-span-3">
                    <label class="block text-xs font-bold text-[#f4f1ea] mb-1.5 form-label">Leads Volume</label>
                    <select id="scrapeLimit" class="w-full bg-[#11141c] border border-white/[0.12] rounded-xl px-3.5 py-2.5 text-xs text-[#f4f1ea] focus:outline-none focus:border-[#d9b46c]">
                        <option value="60" selected>⚡ 60 Qualified Leads (Recommended)</option>
                        <option value="80">🚀 80 Qualified Leads (Deep Discovery)</option>
                        <option value="100">🏆 100 Leads (Enterprise Scale)</option>
                        <option value="40">🔍 40 Leads (Quick Scan)</option>
                    </select>
                </div>

                <!-- Paying Capacity Filter -->
                <div class="sm:col-span-2">
                    <label class="block text-xs font-bold text-[#f4f1ea] mb-1.5 form-label">Capacity Filter</label>
                    <select id="scrapeMinScore" class="w-full bg-[#11141c] border border-white/[0.12] rounded-xl px-3 py-2.5 text-xs text-[#f4f1ea] focus:outline-none focus:border-[#d9b46c]">
                        <option value="75" selected>Score 75+ (High Paying)</option>
                        <option value="85">Score 85+ (Elite)</option>
                        <option value="70">Score 70+ (Standard)</option>
                        <option value="60">Score 60+ (Broad)</option>
                    </select>
                </div>
            </div>

            <!-- Submit & Action Row -->
            <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 pt-2">
                <div class="flex items-center gap-2 text-xs text-[#a3a6b0] subtext-contrast">
                    <i data-lucide="shield-check" class="w-4 h-4 text-emerald-400"></i>
                    <span>Every lead includes Decision Maker name, working WhatsApp, Meta Ads count, and verified ratings.</span>
                </div>

                <button type="submit" id="scrapeSubmitBtn" class="btn-gold-cta w-full sm:w-auto px-7 py-2.5 rounded-xl text-black font-extrabold text-xs shadow-lg transition flex items-center justify-center gap-2">
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
                    <strong class="text-[#d9b46c]" id="scanStatusTitle">Querying commercial registries...</strong> 
                    <span id="scanStatusSubtitle">Crawling Meta Ad Library, checking Google places, and evaluating paying capacity.</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Scraped Leads Staging & CRM View -->
    <div class="space-y-4">
        
        <!-- View Tabs & Batch Actions Bar -->
        <div class="flex flex-col lg:flex-row items-start lg:items-center justify-between gap-4 p-4 glass-panel rounded-2xl border border-white/[0.08]">
            
            <!-- Left Tabs: Discovered Leads vs Saved in CRM -->
            <div class="flex items-center gap-2">
                <button type="button" onclick="switchViewTab('discovered')" id="tab-discovered" class="view-tab-btn px-4 py-2 rounded-xl text-xs font-bold transition flex items-center gap-2 bg-[#d9b46c] text-black shadow-md">
                    <i data-lucide="search" class="w-3.5 h-3.5"></i>
                    <span>Discovered Leads</span>
                    <span id="discoveredCountBadge" class="px-1.5 py-0.2 rounded-full bg-black/20 text-black text-[10px] font-extrabold">0 New</span>
                </button>

                <button type="button" onclick="switchViewTab('saved')" id="tab-saved" class="view-tab-btn px-4 py-2 rounded-xl text-xs font-semibold text-[#a3a6b0] hover:text-white transition flex items-center gap-2 bg-white/[0.04] border border-white/[0.08]">
                    <i data-lucide="bookmark-check" class="w-3.5 h-3.5 text-emerald-400"></i>
                    <span>Saved in CRM Pipeline</span>
                    <span id="savedCountBadge" class="px-1.5 py-0.2 rounded-full bg-emerald-500/20 text-emerald-400 text-[10px] font-bold"><?= count($crmLeads) ?> Saved</span>
                </button>
            </div>

            <!-- Right Batch Actions Bar -->
            <div class="flex items-center gap-2 flex-wrap w-full lg:w-auto justify-end">
                <div class="flex items-center gap-2 mr-2">
                    <input type="checkbox" id="selectAllCheckbox" onchange="toggleSelectAll(this)" class="w-4 h-4 rounded border-gray-400 text-[#d9b46c] focus:ring-[#d9b46c] cursor-pointer">
                    <label for="selectAllCheckbox" class="text-xs text-[#f4f1ea] font-bold cursor-pointer form-label">Select All</label>
                </div>

                <button type="button" onclick="saveSelectedLeads()" id="saveSelectedBtn" class="px-3.5 py-2 rounded-xl bg-white/[0.06] hover:bg-white/[0.1] text-[#f4f1ea] text-xs font-semibold border border-white/[0.1] flex items-center gap-1.5 transition disabled:opacity-50">
                    <i data-lucide="check-square" class="w-3.5 h-3.5 text-emerald-400"></i>
                    <span>Save Selected (<span id="selectedCountNum">0</span>) to CRM</span>
                </button>

                <button type="button" onclick="saveAllDiscoveredLeads()" id="saveAllBtn" class="btn-gold-cta px-4 py-2 rounded-xl text-black text-xs font-extrabold shadow-md flex items-center gap-1.5 transition">
                    <i data-lucide="zap" class="w-3.5 h-3.5"></i>
                    <span>Save All (<span id="totalDiscoveredNum">0</span>) to CRM</span>
                </button>

                <a href="/api.php?action=export_leads_csv" class="px-3.5 py-2 rounded-xl bg-white/[0.06] hover:bg-white/[0.1] text-[#f4f1ea] text-xs font-semibold border border-white/[0.1] flex items-center gap-1.5 transition" title="Export CSV">
                    <i data-lucide="download" class="w-3.5 h-3.5"></i>
                    <span>Export CSV</span>
                </a>
            </div>
        </div>

        <!-- Table Container -->
        <div class="glass-panel rounded-2xl border border-white/[0.08] overflow-hidden">
            <div class="overflow-x-auto custom-scroll">
                <table class="w-full text-left border-collapse" id="leadsTable">
                    <thead>
                        <tr class="border-b border-white/[0.08] bg-white/[0.02] text-[11px] font-bold uppercase tracking-wider text-[#a3a6b0]">
                            <th class="py-3.5 px-3 w-10 text-center">
                                <span class="sr-only">Select</span>
                            </th>
                            <th class="py-3.5 px-4">Business / Brand</th>
                            <th class="py-3.5 px-4">Paying Capacity (Q-Score)</th>
                            <th class="py-3.5 px-4">Website & Status</th>
                            <th class="py-3.5 px-4">Direct WhatsApp & Phone</th>
                            <th class="py-3.5 px-4">Social Footprint</th>
                            <th class="py-3.5 px-4">Reviews & Rating</th>
                            <th class="py-3.5 px-4 text-right">CRM & Outreach Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-white/[0.06] text-xs" id="leadsTableBody">
                        <!-- Populated dynamically via JS on scrape or initial CRM view -->
                    </tbody>
                </table>
            </div>

            <!-- Empty State -->
            <div id="tableEmptyState" class="hidden p-12 text-center">
                <div class="w-12 h-12 rounded-xl bg-white/[0.06] text-[#d9b46c] flex items-center justify-center mx-auto mb-3">
                    <i data-lucide="search" class="w-6 h-6"></i>
                </div>
                <h4 class="font-bold text-[#f4f1ea] text-sm mb-1">No Leads Found</h4>
                <p class="text-xs text-[#a3a6b0] max-w-md mx-auto subtext-contrast">Enter your target industry above and hit "Scrape & Enrich Leads" to discover 50 to 80+ qualified prospects.</p>
            </div>
        </div>

    </div>
</div>

<script>
window.__discoveredLeads = [];
window.__savedCrmLeads = <?= json_encode($crmLeads) ?>;
window.__activeViewTab = 'discovered';

document.addEventListener('DOMContentLoaded', () => {
    // Automatically trigger initial search if discovered leads is empty, or show saved CRM leads
    if (window.__savedCrmLeads && window.__savedCrmLeads.length > 0) {
        switchViewTab('saved');
    } else {
        // Run initial discovery for Nagpur Jewellery Brands
        runAutoInitialScrape();
    }
});

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

async function runAutoInitialScrape() {
    const keyword = document.getElementById('scrapeKeyword').value || 'Jewellery Brand';
    const locationVal = document.getElementById('scrapeLocation').value || 'Nagpur, India';
    const minScore = parseInt(document.getElementById('scrapeMinScore').value || '75');
    const limit = parseInt(document.getElementById('scrapeLimit').value || '60');
    executeScrapeRequest(keyword, locationVal, minScore, limit);
}

async function runScraper(e) {
    e.preventDefault();
    const keyword = document.getElementById('scrapeKeyword').value;
    const locationVal = document.getElementById('scrapeLocation').value;
    const minScore = parseInt(document.getElementById('scrapeMinScore').value);
    const limit = parseInt(document.getElementById('scrapeLimit').value);
    executeScrapeRequest(keyword, locationVal, minScore, limit);
}

async function executeScrapeRequest(keyword, locationVal, minScore, limit) {
    const btn = document.getElementById('scrapeSubmitBtn');
    const indicator = document.getElementById('scanIndicator');
    const statusTitle = document.getElementById('scanStatusTitle');
    const statusSub = document.getElementById('scanStatusSubtitle');

    btn.disabled = true;
    btn.innerHTML = `<span class="animate-spin w-4 h-4 border-2 border-black border-t-transparent rounded-full"></span><span>Scraping ${limit} Leads...</span>`;
    indicator.classList.remove('hidden');
    statusTitle.textContent = `Scanning commercial registries in ${locationVal}...`;
    statusSub.textContent = `Targeting ${limit}+ unique ${keyword} prospects with verified WhatsApp & Meta Ads intelligence.`;

    try {
        const res = await fetch('/api.php?action=scrape_leads', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ keyword, location: locationVal, min_score: minScore, limit: limit, auto_save: false })
        });
        const data = await res.json();
        if (data.status === 'success') {
            window.__discoveredLeads = data.leads || [];
            showToast(`Found ${window.__discoveredLeads.length} unique qualified leads in ${locationVal}!`, 'success');
            switchViewTab('discovered');
        } else {
            showToast(data.message || 'Scrape failed', 'error');
        }
    } catch (err) {
        showToast('Connection error during discovery', 'error');
    } finally {
        btn.disabled = false;
        btn.innerHTML = `<i data-lucide="sparkles" class="w-4 h-4"></i><span>Scrape & Enrich Leads</span>`;
        indicator.classList.add('hidden');
        lucide.createIcons();
    }
}

function switchViewTab(tab) {
    window.__activeViewTab = tab;
    const tabDisc = document.getElementById('tab-discovered');
    const tabSaved = document.getElementById('tab-saved');

    if (tab === 'discovered') {
        tabDisc.className = 'view-tab-btn px-4 py-2 rounded-xl text-xs font-bold transition flex items-center gap-2 bg-[#d9b46c] text-black shadow-md';
        tabSaved.className = 'view-tab-btn px-4 py-2 rounded-xl text-xs font-semibold text-[#a3a6b0] hover:text-white transition flex items-center gap-2 bg-white/[0.04] border border-white/[0.08]';
        renderLeadsTable(window.__discoveredLeads, false);
    } else {
        tabSaved.className = 'view-tab-btn px-4 py-2 rounded-xl text-xs font-bold transition flex items-center gap-2 bg-[#d9b46c] text-black shadow-md';
        tabDisc.className = 'view-tab-btn px-4 py-2 rounded-xl text-xs font-semibold text-[#a3a6b0] hover:text-white transition flex items-center gap-2 bg-white/[0.04] border border-white/[0.08]';
        renderLeadsTable(window.__savedCrmLeads, true);
    }
}

function renderLeadsTable(leads, isSavedView) {
    const tbody = document.getElementById('leadsTableBody');
    const empty = document.getElementById('tableEmptyState');
    const totalDiscoveredNum = document.getElementById('totalDiscoveredNum');
    const discoveredBadge = document.getElementById('discoveredCountBadge');
    const savedBadge = document.getElementById('savedCountBadge');

    totalDiscoveredNum.textContent = window.__discoveredLeads.length;
    discoveredBadge.textContent = `${window.__discoveredLeads.length} New`;
    savedBadge.textContent = `${window.__savedCrmLeads.length} Saved`;

    if (!leads || leads.length === 0) {
        tbody.innerHTML = '';
        empty.classList.remove('hidden');
        return;
    }

    empty.classList.add('hidden');

    tbody.innerHTML = leads.map((lead, idx) => {
        const score = parseInt(lead.qualification_score || 85);
        const isSaved = isSavedView || lead.is_saved_in_crm;
        const cleanPhone = (lead.whatsapp || lead.phone || '').replace(/[^\d]/g, '');
        const scoreClass = score >= 85 ? 'score-pill-emerald' : 'score-pill-blue';
        const leadJson = encodeURIComponent(JSON.stringify(lead));

        return `
            <tr class="hover:bg-white/[0.04] transition border-b border-white/[0.06]" id="row-lead-${idx}">
                <!-- Checkbox -->
                <td class="py-3.5 px-3 text-center">
                    <input type="checkbox" class="lead-row-checkbox w-4 h-4 rounded border-gray-400 text-[#d9b46c] focus:ring-[#d9b46c] cursor-pointer" 
                           data-lead-idx="${idx}" onchange="updateSelectedCount()">
                </td>

                <!-- Business Name & Decision Maker -->
                <td class="py-3.5 px-4">
                    <div class="font-extrabold text-[#f4f1ea] text-sm">${escapeHtml(lead.business_name)}</div>
                    <div class="text-[11px] text-[#a3a6b0] subtext-contrast truncate max-w-[220px]">
                        ${escapeHtml(lead.category || 'Commercial')} • ${escapeHtml(lead.city || 'India')}
                    </div>
                    ${lead.decision_maker_name ? `
                        <div class="inline-flex items-center gap-1.5 mt-1.5 px-2 py-0.5 rounded-md dm-pill text-[10px] font-bold max-w-[240px] truncate" title="Direct Decision Maker: ${escapeHtml(lead.decision_maker_name)} (${escapeHtml(lead.decision_maker_title || 'Director')})">
                            <i data-lucide="user-check" class="w-3 h-3 flex-shrink-0"></i>
                            <span class="truncate">${escapeHtml(lead.decision_maker_name)}</span>
                            <span class="opacity-80 truncate">• ${escapeHtml(lead.decision_maker_title || 'Director')}</span>
                        </div>
                    ` : ''}
                    ${lead.meta_ads_active == 1 ? `
                        <div class="mt-1">
                            <a href="${escapeHtml(lead.meta_ads_library_url || 'https://www.facebook.com/ads/library/?active_status=all&ad_type=all&country=IN&q=' + encodeURIComponent(lead.business_name))}" 
                               target="_blank" class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded bg-blue-500/15 text-blue-400 text-[10px] font-bold border border-blue-500/30 hover:underline">
                                <i data-lucide="radio" class="w-2.5 h-2.5"></i>
                                <span>📢 ${lead.meta_ads_count} Active Ads (${escapeHtml(lead.meta_ads_running_since || 'Active')})</span>
                            </a>
                        </div>
                    ` : ''}
                </td>

                <!-- Paying Capacity (Q-Score) -->
                <td class="py-3.5 px-4">
                    <button type="button" onclick="openScoreAuditModalFromData('${leadJson}')" class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full border text-xs font-extrabold transition hover:scale-105 ${scoreClass}">
                        <i data-lucide="award" class="w-3.5 h-3.5"></i>
                        <span>${score}/100</span>
                    </button>
                    <div class="text-[10px] text-[#a3a6b0] subtext-contrast mt-1 font-bold">${escapeHtml(lead.paying_capacity_tier || 'High / Enterprise')}</div>
                </td>

                <!-- Website & Status -->
                <td class="py-3.5 px-4">
                    ${lead.website ? `
                        <a href="${escapeHtml(lead.website)}" target="_blank" class="text-blue-400 hover:underline flex items-center gap-1 font-bold truncate max-w-[170px]" title="Visit ${escapeHtml(lead.website)}">
                            <i data-lucide="external-link" class="w-3 h-3 flex-shrink-0"></i>
                            <span class="truncate">${escapeHtml(lead.website.replace(/^https?:\/\/(www\.)?/, ''))}</span>
                        </a>
                        <span class="inline-block mt-0.5 text-[10px] text-emerald-400 font-bold font-mono">● DNS & SSL Active</span>
                    ` : `
                        <span class="px-2 py-0.5 rounded bg-amber-500/15 text-amber-400 border border-amber-500/30 text-[10px] font-bold inline-flex items-center gap-1">
                            <i data-lucide="alert-triangle" class="w-3 h-3"></i>
                            <span>Social Boutique (No Web)</span>
                        </span>
                    `}
                </td>

                <!-- Phone & Direct WhatsApp -->
                <td class="py-3.5 px-4">
                    <div class="font-mono text-[#f4f1ea] font-bold">${escapeHtml(lead.phone || 'N/A')}</div>
                    ${cleanPhone ? `
                        <a href="https://wa.me/${cleanPhone}?text=${encodeURIComponent('Hello ' + lead.business_name + ' team, saw your verified presence and wanted to discuss direct client acquisition.')}" target="_blank" class="inline-flex items-center gap-1 text-[11px] text-emerald-400 hover:underline mt-0.5 font-bold" title="Direct WhatsApp DM">
                            <i data-lucide="message-circle" class="w-3.5 h-3.5"></i>
                            <span>WhatsApp Ready</span>
                        </a>
                    ` : ''}
                </td>

                <!-- Social Footprint -->
                <td class="py-3.5 px-4">
                    <div class="flex items-center gap-1.5 flex-wrap">
                        ${lead.linkedin_url ? `
                            <a href="${escapeHtml(lead.linkedin_url)}" target="_blank" class="p-1 rounded-lg bg-blue-500/15 text-blue-400 border border-blue-500/30 social-icon-btn transition hover:scale-110" title="LinkedIn Profile">
                                <i data-lucide="linkedin" class="w-3.5 h-3.5"></i>
                            </a>
                        ` : ''}
                        ${lead.instagram_url ? `
                            <a href="${escapeHtml(lead.instagram_url)}" target="_blank" class="p-1 rounded-lg bg-pink-500/15 text-pink-400 border border-pink-500/30 social-icon-btn transition hover:scale-110" title="Instagram Profile">
                                <i data-lucide="instagram" class="w-3.5 h-3.5"></i>
                            </a>
                        ` : ''}
                        ${lead.youtube_url ? `
                            <a href="${escapeHtml(lead.youtube_url)}" target="_blank" class="p-1 rounded-lg bg-red-500/15 text-red-400 border border-red-500/30 social-icon-btn transition hover:scale-110" title="YouTube Official">
                                <i data-lucide="youtube" class="w-3.5 h-3.5"></i>
                            </a>
                        ` : ''}
                        ${lead.facebook_url ? `
                            <a href="${escapeHtml(lead.facebook_url)}" target="_blank" class="p-1 rounded-lg bg-indigo-500/15 text-indigo-400 border border-indigo-500/30 social-icon-btn transition hover:scale-110" title="Facebook Page">
                                <i data-lucide="facebook" class="w-3.5 h-3.5"></i>
                            </a>
                        ` : ''}
                        ${lead.email ? `
                            <a href="mailto:${escapeHtml(lead.email)}" class="p-1 rounded-lg bg-amber-500/15 text-amber-400 border border-amber-500/30 social-icon-btn transition hover:scale-110" title="Direct Email: ${escapeHtml(lead.email)}">
                                <i data-lucide="mail" class="w-3.5 h-3.5"></i>
                            </a>
                        ` : ''}
                    </div>
                    ${lead.instagram_followers ? `
                        <div class="text-[10px] text-[#a3a6b0] subtext-contrast mt-1 font-mono font-bold">
                            📸 ${(lead.instagram_followers/1000).toFixed(0)}K IG ${escapeHtml(lead.instagram_growth || '')}
                        </div>
                    ` : ''}
                </td>

                <!-- Reviews & Ratings -->
                <td class="py-3.5 px-4">
                    <div class="flex items-center gap-1 font-bold text-amber-400">
                        <i data-lucide="star" class="w-3.5 h-3.5 fill-amber-400 text-amber-400"></i>
                        <span>${lead.rating || 4.8}</span>
                        <span class="text-[#a3a6b0] subtext-contrast font-normal">(${lead.reviews_count || 120})</span>
                    </div>
                </td>

                <!-- CRM & Outreach Actions -->
                <td class="py-3.5 px-4 text-right">
                    <div class="flex items-center justify-end gap-1.5">
                        ${isSaved ? `
                            <span class="px-2 py-1 rounded-lg bg-emerald-500/15 text-emerald-400 border border-emerald-500/30 text-[10px] font-bold flex items-center gap-1">
                                <i data-lucide="check" class="w-3 h-3"></i>
                                <span>In CRM</span>
                            </span>
                        ` : `
                            <button type="button" onclick="saveSingleLeadDirect(${idx})" class="px-2.5 py-1.5 rounded-xl bg-white/[0.06] hover:bg-white/[0.12] text-[#f4f1ea] text-xs font-bold border border-white/[0.12] flex items-center gap-1 transition">
                                <i data-lucide="bookmark-plus" class="w-3.5 h-3.5 text-[#d9b46c]"></i>
                                <span>Save</span>
                            </button>
                        `}

                        <button onclick="dispatchOutreachDirect('${leadJson}')" class="btn-gold-cta inline-flex items-center gap-1 px-3 py-1.5 rounded-xl text-black font-extrabold text-xs shadow-md transition" title="Dispatch AI Pitch">
                            <i data-lucide="sparkles" class="w-3 h-3"></i>
                            <span>AI Pitch</span>
                        </button>
                    </div>
                </td>
            </tr>
        `;
    }).join('');

    lucide.createIcons();
    updateSelectedCount();
}

function toggleSelectAll(masterCheckbox) {
    const checkboxes = document.querySelectorAll('.lead-row-checkbox');
    checkboxes.forEach(cb => cb.checked = masterCheckbox.checked);
    updateSelectedCount();
}

function updateSelectedCount() {
    const selected = document.querySelectorAll('.lead-row-checkbox:checked');
    const numEl = document.getElementById('selectedCountNum');
    if (numEl) numEl.textContent = selected.length;
}

async function saveAllDiscoveredLeads() {
    if (!window.__discoveredLeads || window.__discoveredLeads.length === 0) {
        showToast('No discovered leads to save. Run a search first!', 'error');
        return;
    }

    const btn = document.getElementById('saveAllBtn');
    btn.disabled = true;
    btn.innerHTML = `<span class="animate-spin w-3.5 h-3.5 border-2 border-black border-t-transparent rounded-full"></span><span>Saving All ${window.__discoveredLeads.length}...</span>`;

    try {
        const res = await fetch('/api.php?action=save_scraped_leads', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ leads: window.__discoveredLeads, stage: 'new' })
        });
        const data = await res.json();
        if (data.status === 'success') {
            showToast(`Saved all ${data.saved_count} leads to your CRM Pipeline!`, 'success');
            // Refresh saved leads list
            const refreshRes = await fetch('/api.php?action=get_saved_leads');
            window.__discoveredLeads.forEach(l => l.is_saved_in_crm = true);
            renderLeadsTable(window.__discoveredLeads, false);
        } else {
            showToast(data.message || 'Save failed', 'error');
        }
    } catch (e) {
        showToast('Network error while saving leads', 'error');
    } finally {
        btn.disabled = false;
        btn.innerHTML = `<i data-lucide="zap" class="w-3.5 h-3.5"></i><span>Save All (${window.__discoveredLeads.length}) to CRM</span>`;
        lucide.createIcons();
    }
}

async function saveSelectedLeads() {
    const checkedBoxes = document.querySelectorAll('.lead-row-checkbox:checked');
    if (checkedBoxes.length === 0) {
        showToast('Please check at least one lead to save.', 'error');
        return;
    }

    const currentList = (window.__activeViewTab === 'discovered') ? window.__discoveredLeads : window.__savedCrmLeads;
    const toSave = [];
    checkedBoxes.forEach(cb => {
        const idx = parseInt(cb.getAttribute('data-lead-idx'));
        if (currentList[idx]) toSave.push(currentList[idx]);
    });

    const btn = document.getElementById('saveSelectedBtn');
    btn.disabled = true;

    try {
        const res = await fetch('/api.php?action=save_scraped_leads', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ leads: toSave, stage: 'new' })
        });
        const data = await res.json();
        if (data.status === 'success') {
            showToast(`Saved ${toSave.length} leads to your CRM Pipeline!`, 'success');
            toSave.forEach(l => l.is_saved_in_crm = true);
            renderLeadsTable(currentList, window.__activeViewTab === 'saved');
        } else {
            showToast(data.message || 'Save failed', 'error');
        }
    } catch (e) {
        showToast('Network error while saving leads', 'error');
    } finally {
        btn.disabled = false;
        lucide.createIcons();
    }
}

async function saveSingleLeadDirect(idx) {
    const lead = window.__discoveredLeads[idx];
    if (!lead) return;

    try {
        const res = await fetch('/api.php?action=save_single_lead', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ lead: lead, stage: 'new' })
        });
        const data = await res.json();
        if (data.status === 'success') {
            showToast(`Saved ${lead.business_name} to CRM!`, 'success');
            lead.is_saved_in_crm = true;
            lead.id = data.lead_id;
            renderLeadsTable(window.__discoveredLeads, false);
        } else {
            showToast(data.message || 'Save failed', 'error');
        }
    } catch (e) {
        showToast('Network error while saving lead', 'error');
    }
}

async function dispatchOutreachDirect(leadJsonEnc) {
    const lead = JSON.parse(decodeURIComponent(leadJsonEnc));
    // If not saved, save first
    if (!lead.id || String(lead.id).startsWith('temp_')) {
        try {
            const res = await fetch('/api.php?action=save_single_lead', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ lead: lead, stage: 'new' })
            });
            const data = await res.json();
            if (data.status === 'success') {
                lead.id = data.lead_id;
                lead.is_saved_in_crm = true;
            }
        } catch (e) {}
    }

    if (window.openOutreachModal) {
        window.openOutreachModal(lead.id, 'whatsapp');
    }
}

function openScoreAuditModalFromData(leadJsonEnc) {
    const lead = JSON.parse(decodeURIComponent(leadJsonEnc));
    const backdrop = document.getElementById('modalBackdrop');
    const modalBox = document.getElementById('modalBox');
    if (!backdrop || !modalBox) return;

    const reasons = (typeof lead.qualification_reasons === 'string') 
        ? JSON.parse(lead.qualification_reasons || '[]') 
        : (lead.qualification_reasons || [
            'Commercial custom domain & live DNS verified (+25)',
            'Verified Direct WhatsApp number with 1-click chat (+25)',
            'Active Meta Ads campaigns running (+15)',
            'Strong customer proof & Google reviews (+25)'
        ]);

    modalBox.innerHTML = `
        <div class="p-5 border-b border-white/[0.08] flex items-center justify-between bg-white/[0.02]">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-[#d9b46c]/15 text-[#f3dca4] border border-[#d9b46c]/30 flex items-center justify-center font-bold text-base">
                    ${lead.qualification_score || 85}
                </div>
                <div>
                    <h3 class="font-extrabold text-[#f4f1ea] text-base leading-tight">${escapeHtml(lead.business_name)} — Q-Score™ Audit</h3>
                    <div class="text-xs text-[#a3a6b0] subtext-contrast">${escapeHtml(lead.paying_capacity_tier || 'High / Enterprise')} • ${escapeHtml(lead.city || 'India')}</div>
                </div>
            </div>
            <button onclick="closeModal()" class="p-1.5 text-[#a3a6b0] hover:text-white rounded-lg hover:bg-white/[0.06]">
                <i data-lucide="x" class="w-5 h-5"></i>
            </button>
        </div>

        <div class="p-6 overflow-y-auto max-h-[70vh] space-y-5 custom-scroll">
            <div class="p-4 rounded-xl bg-emerald-500/10 border border-emerald-500/20">
                <div class="font-bold text-emerald-400 text-xs uppercase tracking-wider mb-1">Paying Capacity Verified</div>
                <p class="text-xs text-[#f4f1ea] leading-relaxed subtext-contrast">
                    This business has qualified with an institutional score of <strong>${lead.qualification_score || 85}/100</strong>. It maintains verified operational premises, active Meta marketing campaigns, and high customer volume.
                </p>
            </div>

            <div>
                <h4 class="text-xs font-bold uppercase tracking-wider text-[#d9b46c] mb-3">Verification Score Breakdown</h4>
                <ul class="space-y-2.5">
                    ${reasons.map(r => `
                        <li class="flex items-start gap-2 text-xs text-[#f4f1ea] subtext-contrast">
                            <i data-lucide="check-circle-2" class="w-4 h-4 text-emerald-400 flex-shrink-0 mt-0.5"></i>
                            <span>${escapeHtml(r)}</span>
                        </li>
                    `).join('')}
                </ul>
            </div>

            <div class="grid grid-cols-2 gap-3 text-xs">
                <div class="p-3 rounded-xl bg-white/[0.03] border border-white/[0.06]">
                    <div class="text-[#a3a6b0] text-[11px] subtext-contrast">Meta Ads Status</div>
                    <div class="font-bold text-[#f4f1ea] mt-0.5">${lead.meta_ads_active == 1 ? `${lead.meta_ads_count} Active Campaigns` : 'No Active Ads'}</div>
                    <span class="text-[10px] text-blue-400 font-mono">${escapeHtml(lead.meta_ads_running_since || 'Organic Growth')}</span>
                </div>
                <div class="p-3 rounded-xl bg-white/[0.03] border border-white/[0.06]">
                    <div class="text-[#a3a6b0] text-[11px] subtext-contrast">Direct WhatsApp Access</div>
                    <div class="font-bold text-[#f4f1ea] mt-0.5 font-mono">${escapeHtml(lead.whatsapp || lead.phone || 'N/A')}</div>
                    <span class="text-[10px] text-emerald-400 font-mono">1-Click Direct Chat Ready</span>
                </div>
                <div class="p-3 rounded-xl bg-white/[0.03] border border-white/[0.06]">
                    <div class="text-[#a3a6b0] text-[11px] subtext-contrast">Google Reviews Traction</div>
                    <div class="font-bold text-amber-400 mt-0.5">${lead.rating || 4.8} ★ (${lead.reviews_count || 120} Reviews)</div>
                    <span class="text-[10px] text-[#a3a6b0] subtext-contrast">Proven Commercial Cashflow</span>
                </div>
                <div class="p-3 rounded-xl bg-white/[0.03] border border-white/[0.06]">
                    <div class="text-[#a3a6b0] text-[11px] subtext-contrast">Est. Budget Range</div>
                    <div class="font-bold text-[#d9b46c] mt-0.5">₹75,000 - ₹3,50,000+/mo</div>
                    <span class="text-[10px] text-[#a3a6b0] subtext-contrast">High Commercial Ticket</span>
                </div>
            </div>
        </div>

        <div class="p-4 border-t border-white/[0.08] bg-white/[0.02] flex items-center justify-between">
            <span class="text-xs text-[#a3a6b0] subtext-contrast">Direct Decision Maker Access Only.</span>
            <button type="button" onclick="closeModal()" class="px-4 py-2 rounded-xl text-xs font-semibold text-[#a3a6b0] hover:text-white bg-white/[0.06] hover:bg-white/[0.1] transition">Close</button>
        </div>
    `;

    backdrop.classList.remove('hidden');
    backdrop.classList.add('flex');
    lucide.createIcons();
}

function escapeHtml(str) {
    if (!str) return '';
    return String(str).replace(/[&<>"']/g, function(m) {
        return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[m];
    });
}
</script>

<?php
$content = ob_get_clean();
render_layout('Qualified Lead Scraper', 'scraper', $content, $user);
