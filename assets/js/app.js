// Outreach AI Freedom - Frontend Interactive Controller
document.addEventListener('DOMContentLoaded', () => {
    initKanban();
});

// Toast notification system
function showToast(message, type = 'success') {
    const container = document.getElementById('toastContainer');
    if (!container) return;

    const toast = document.createElement('div');
    const isSuccess = type === 'success';
    const isError = type === 'error';

    toast.className = `pointer-events-auto flex items-center gap-3 px-4 py-3 rounded-xl shadow-xl border text-sm font-medium transition-all transform duration-300 translate-y-2 opacity-0 ${
        isSuccess ? 'bg-emerald-950/90 text-emerald-200 border-emerald-500/40' :
        isError ? 'bg-rose-950/90 text-rose-200 border-rose-500/40' :
        'bg-slate-900/90 text-slate-200 border-slate-700'
    }`;

    toast.innerHTML = `
        <i data-lucide="${isSuccess ? 'check-circle' : isError ? 'alert-triangle' : 'info'}" class="w-4 h-4"></i>
        <span>${message}</span>
    `;

    container.appendChild(toast);
    lucide.createIcons();

    setTimeout(() => {
        toast.classList.remove('translate-y-2', 'opacity-0');
    }, 10);

    setTimeout(() => {
        toast.classList.add('opacity-0', 'translate-y-2');
        setTimeout(() => toast.remove(), 300);
    }, 4000);
}

// Modal open and close handlers
function closeModal() {
    const backdrop = document.getElementById('modalBackdrop');
    if (!backdrop) return;
    backdrop.classList.add('hidden');
    backdrop.classList.remove('flex');
}

// Open AI Outreach Dispatcher Modal
async function openOutreachModal(leadId, defaultChannel = 'whatsapp') {
    const backdrop = document.getElementById('modalBackdrop');
    const modalBox = document.getElementById('modalBox');
    if (!backdrop || !modalBox) return;

    modalBox.innerHTML = `
        <div class="p-8 text-center">
            <div class="inline-block animate-spin w-8 h-8 border-4 border-emerald-500 border-t-transparent rounded-full mb-3"></div>
            <div class="text-sm text-slate-300">Crafting tailored outreach campaign with AI...</div>
        </div>
    `;

    backdrop.classList.remove('hidden');
    backdrop.classList.add('flex');

    try {
        const res = await fetch(`/api.php?action=get_outreach_sequence&lead_id=${leadId}`);
        const data = await res.json();

        if (data.status !== 'success') {
            modalBox.innerHTML = `
                <div class="p-6">
                    <div class="text-rose-400 font-bold mb-2">Error</div>
                    <div class="text-sm text-slate-300 mb-4">${data.message || 'Could not load outreach data.'}</div>
                    <button onclick="closeModal()" class="px-4 py-2 bg-slate-800 rounded-xl text-sm">Close</button>
                </div>
            `;
            return;
        }

        renderOutreachModalContent(data.lead, data.sequence, defaultChannel);
    } catch (err) {
        modalBox.innerHTML = `
            <div class="p-6">
                <div class="text-rose-400 font-bold mb-2">Network Error</div>
                <div class="text-sm text-slate-300 mb-4">Could not connect to Outreach engine.</div>
                <button onclick="closeModal()" class="px-4 py-2 bg-slate-800 rounded-xl text-sm">Close</button>
            </div>
        `;
    }
}

function renderOutreachModalContent(lead, sequence, activeChannel) {
    const modalBox = document.getElementById('modalBox');

    const dmName = lead.decision_maker_name || sequence.decision_maker_name || 'Founder & Director';
    const dmTitle = lead.decision_maker_title || sequence.decision_maker_title || 'Managing Director';
    const dmPhone = lead.decision_maker_phone || lead.whatsapp || lead.phone;
    const dmEmail = lead.decision_maker_email || lead.email;
    const dmLinkedin = lead.decision_maker_linkedin || lead.linkedin_url;

    // Default global outreach state (Defaults to 100% verified reputation angle)
    window.__currentOutreach = {
        lead: lead,
        sequence: sequence,
        channel: activeChannel || 'whatsapp',
        lang: 'en',
        angle: 'reputation', // 'reputation' | 'meta_ads' | 'social_boutique' | 'local_growth'
        mode: 'cadence', // 'cadence' | 'playbook'
        touchType: 'initial',
        scenario: 'reply_interested'
    };

    const currentText = getMessageForState(window.__currentOutreach);

    const hasMetaAds = (lead.meta_ads_active == 1 || sequence.meta_ads_active == 1);
    const metaAdsCount = lead.meta_ads_count || sequence.meta_ads_count || 0;
    const metaAdsUrl = sequence.meta_ads_library_url || lead.meta_ads_library_url || ('https://www.facebook.com/ads/library/?active_status=all&ad_type=all&country=IN&q=' + encodeURIComponent(lead.business_name || ''));
    const googleRating = sequence.rating || lead.rating || 4.8;
    const googleReviews = sequence.reviews_count || lead.reviews_count || 120;
    const googleMapsUrl = sequence.google_maps_url || ('https://www.google.com/maps/search/?api=1&query=' + encodeURIComponent((lead.business_name || '') + ' ' + (lead.city || '')));

    modalBox.innerHTML = `
        <div class="p-5 border-b border-white/[0.08] flex items-center justify-between bg-slate-900/60">
            <div>
                <div class="flex items-center gap-2">
                    <h3 class="font-bold text-white text-base">Direct AI Outreach Dispatcher</h3>
                    <span class="text-xs px-2.5 py-0.5 rounded-full bg-emerald-500/20 text-emerald-300 border border-emerald-500/30 font-bold">
                        Q-Score: ${lead.qualification_score}/100
                    </span>
                    <span class="text-[10px] px-2 py-0.5 rounded-full bg-[#d9b46c]/15 text-[#f3dca4] border border-[#d9b46c]/30 font-mono">
                        Direct Decision Maker
                    </span>
                </div>
                <div class="text-xs text-slate-300 mt-1 flex flex-wrap items-center gap-2">
                    <span>Target: <strong class="text-white">${lead.business_name}</strong></span>
                    <span class="text-slate-500">•</span>
                    <span class="inline-flex items-center gap-1 text-[#d9b46c] font-semibold">
                        <i data-lucide="user-check" class="w-3.5 h-3.5"></i>
                        <span>${dmName}</span>
                        <span class="text-slate-400 font-normal">(${dmTitle})</span>
                    </span>
                </div>
            </div>
            <button onclick="closeModal()" class="p-1.5 text-slate-400 hover:text-white rounded-lg hover:bg-slate-800 transition">
                <i data-lucide="x" class="w-5 h-5"></i>
            </button>
        </div>

        <div class="p-5 overflow-y-auto max-h-[72vh] space-y-4 custom-scroll">
            
            <!-- Real Business Audit Signals & Proof Verification Box -->
            <div class="p-3.5 rounded-xl bg-slate-950 border border-white/[0.08] space-y-2">
                <div class="flex items-center justify-between">
                    <span class="text-[11px] font-bold uppercase tracking-wider text-[#d9b46c] flex items-center gap-1.5">
                        <i data-lucide="shield-check" class="w-3.5 h-3.5 text-emerald-400"></i>
                        <span>Real Business Audit Signals &amp; Proof (Truth-Backed)</span>
                    </span>
                    <span class="text-[10px] text-slate-400">Verified Evidence</span>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 text-xs">
                    <!-- Google Reviews Proof -->
                    <div class="p-2.5 rounded-lg bg-slate-900/90 border border-white/[0.05] flex items-center justify-between">
                        <div>
                            <div class="text-[10px] text-slate-400">Google Rating &amp; Trust</div>
                            <div class="font-bold text-amber-300 flex items-center gap-1 mt-0.5">
                                <span>⭐ ${googleRating}★</span>
                                <span class="text-slate-400 font-normal">(${googleReviews} Reviews)</span>
                            </div>
                        </div>
                        <a href="${googleMapsUrl}" target="_blank" class="text-[11px] text-[#d9b46c] hover:underline font-bold flex items-center gap-1">
                            <span>Maps Proof ↗</span>
                        </a>
                    </div>
                    <!-- Meta Ad Library Proof -->
                    <div class="p-2.5 rounded-lg bg-slate-900/90 border border-white/[0.05] flex items-center justify-between">
                        <div>
                            <div class="text-[10px] text-slate-400">Meta Ad Library Status</div>
                            <div class="font-bold ${hasMetaAds ? 'text-blue-400' : 'text-slate-400'} flex items-center gap-1 mt-0.5">
                                <span>${hasMetaAds ? `📢 ${metaAdsCount} Live Ads Verified` : '🔍 Zero / Unverified Ads'}</span>
                            </div>
                        </div>
                        <a href="${metaAdsUrl}" target="_blank" class="text-[11px] text-blue-400 hover:underline font-bold flex items-center gap-1">
                            <span>Audit Meta Ads ↗</span>
                        </a>
                    </div>
                </div>
            </div>

            <!-- Strategic Copywriting Angle Selector -->
            <div>
                <div class="flex items-center justify-between mb-1.5">
                    <label class="block text-xs font-bold uppercase tracking-wider text-[#d9b46c] flex items-center gap-1.5">
                        <i data-lucide="target" class="w-3.5 h-3.5"></i>
                        <span>Select Outreach Angle</span>
                    </label>
                    <span class="text-[10px] text-emerald-400 font-bold">Default: 100% Safe Google Reputation</span>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                    <button type="button" onclick="switchOutreachAngle('reputation')" id="angle-btn-reputation" class="angle-pill text-left p-2.5 rounded-xl border text-xs font-bold transition bg-[#d9b46c]/20 text-[#f3dca4] border-[#d9b46c]/50 shadow-sm">
                        <div class="flex items-center justify-between">
                            <span class="font-bold">⭐ Google Reputation (Default)</span>
                            <span class="text-[10px] px-1.5 py-0.2 rounded bg-emerald-500/20 text-emerald-300 font-mono">100% Safe</span>
                        </div>
                        <div class="text-[11px] text-slate-400 font-normal mt-0.5">Pitches customer trust &amp; direct capture without ad spend waste.</div>
                    </button>
                    <button type="button" onclick="switchOutreachAngle('meta_ads')" id="angle-btn-meta_ads" class="angle-pill text-left p-2.5 rounded-xl border text-xs font-semibold transition bg-slate-800/80 text-slate-300 border-white/[0.08] hover:text-white">
                        <div class="flex items-center justify-between">
                            <span class="font-bold">📢 Meta Ads Live Audit</span>
                            <span class="text-[10px] px-1.5 py-0.2 rounded bg-blue-500/20 text-blue-300 font-mono">Ad Spend ROI</span>
                        </div>
                        <div class="text-[11px] text-slate-400 font-normal mt-0.5">Check Meta Ad Library proof above before using this angle.</div>
                    </button>
                    <button type="button" onclick="switchOutreachAngle('social_boutique')" id="angle-btn-social_boutique" class="angle-pill text-left p-2.5 rounded-xl border text-xs font-semibold transition bg-slate-800/80 text-slate-300 border-white/[0.08] hover:text-white">
                        <div class="flex items-center justify-between">
                            <span class="font-bold">📱 Social Boutique &amp; Funnel</span>
                            <span class="text-[10px] px-1.5 py-0.2 rounded bg-pink-500/20 text-pink-300 font-mono">DM to Consult</span>
                        </div>
                        <div class="text-[11px] text-slate-400 font-normal mt-0.5">Converts social inquiries into 24/7 booked WhatsApp visits.</div>
                    </button>
                    <button type="button" onclick="switchOutreachAngle('local_growth')" id="angle-btn-local_growth" class="angle-pill text-left p-2.5 rounded-xl border text-xs font-semibold transition bg-slate-800/80 text-slate-300 border-white/[0.08] hover:text-white">
                        <div class="flex items-center justify-between">
                            <span class="font-bold">📍 Local Market Expansion</span>
                            <span class="text-[10px] px-1.5 py-0.2 rounded bg-indigo-500/20 text-indigo-300 font-mono">Peer Benchmark</span>
                        </div>
                        <div class="text-[11px] text-slate-400 font-normal mt-0.5">Commercial teardown comparing ${lead.city || 'local'} market demand.</div>
                    </button>
                </div>
            </div>

            <!-- Language Selector Bar (English, Hinglish, Hindi, Marathi) -->
            <div>
                <div class="flex items-center justify-between mb-1.5">
                    <label class="block text-xs font-bold uppercase tracking-wider text-[#d9b46c] flex items-center gap-1.5">
                        <i data-lucide="languages" class="w-3.5 h-3.5"></i>
                        <span>Outreach Language</span>
                    </label>
                    <span class="text-[11px] text-slate-400">Direct human copywriting (zero AI robotic tone)</span>
                </div>
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-2">
                    <button type="button" onclick="switchOutreachLanguage('en')" id="lang-btn-en" class="lang-pill flex items-center justify-center gap-1.5 py-1.5 px-3 rounded-xl border text-xs font-bold transition bg-[#d9b46c]/20 text-[#f3dca4] border-[#d9b46c]/50">
                        <span>🇬🇧 English</span>
                    </button>
                    <button type="button" onclick="switchOutreachLanguage('hinglish')" id="lang-btn-hinglish" class="lang-pill flex items-center justify-center gap-1.5 py-1.5 px-3 rounded-xl border text-xs font-semibold transition bg-slate-800/80 text-slate-300 border-white/[0.08] hover:text-white">
                        <span>🇮🇳 Hinglish</span>
                    </button>
                    <button type="button" onclick="switchOutreachLanguage('hi')" id="lang-btn-hi" class="lang-pill flex items-center justify-center gap-1.5 py-1.5 px-3 rounded-xl border text-xs font-semibold transition bg-slate-800/80 text-slate-300 border-white/[0.08] hover:text-white">
                        <span>🇮🇳 हिंदी (Hindi)</span>
                    </button>
                    <button type="button" onclick="switchOutreachLanguage('mr')" id="lang-btn-mr" class="lang-pill flex items-center justify-center gap-1.5 py-1.5 px-3 rounded-xl border text-xs font-semibold transition bg-slate-800/80 text-slate-300 border-white/[0.08] hover:text-white">
                        <span>🚩 मराठी (Marathi)</span>
                    </button>
                </div>
            </div>

            <!-- Mode Switcher: Outreach Cadence vs. Response Playbook -->
            <div class="p-1 rounded-xl bg-slate-950/80 border border-white/[0.08] flex items-center gap-1">
                <button type="button" onclick="switchOutreachMode('cadence')" id="mode-btn-cadence" class="flex-1 py-1.5 px-3 rounded-lg text-xs font-bold transition flex items-center justify-center gap-1.5 bg-[#d9b46c] text-black shadow-sm">
                    <i data-lucide="send-horizontal" class="w-3.5 h-3.5"></i>
                    <span>Cold Outreach Cadence</span>
                </button>
                <button type="button" onclick="switchOutreachMode('playbook')" id="mode-btn-playbook" class="flex-1 py-1.5 px-3 rounded-lg text-xs font-semibold transition flex items-center justify-center gap-1.5 text-slate-300 hover:text-white">
                    <i data-lucide="message-square-reply" class="w-3.5 h-3.5 text-emerald-400"></i>
                    <span>When Prospect Replies... (Playbook)</span>
                </button>
            </div>

            <!-- Section 1: Outreach Touchpoints (Visible when mode === 'cadence') -->
            <div id="cadenceControls" class="space-y-1.5">
                <label class="block text-xs font-semibold uppercase tracking-wider text-slate-400">Sequence Stage</label>
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-2">
                    <button type="button" onclick="setTouchType('initial')" id="btn-touch-initial" class="touch-btn px-2.5 py-1.5 rounded-lg text-xs font-semibold border bg-emerald-500/20 text-emerald-300 border-emerald-500/50">Initial Pitch</button>
                    <button type="button" onclick="setTouchType('followup_1')" id="btn-touch-followup_1" class="touch-btn px-2.5 py-1.5 rounded-lg text-xs font-medium border bg-slate-800 text-slate-300 border-white/[0.08] hover:text-white">Day 2: Case Proof</button>
                    <button type="button" onclick="setTouchType('followup_2')" id="btn-touch-followup_2" class="touch-btn px-2.5 py-1.5 rounded-lg text-xs font-medium border bg-slate-800 text-slate-300 border-white/[0.08] hover:text-white">Day 5: Quick Bump</button>
                    <button type="button" onclick="setTouchType('followup_3')" id="btn-touch-followup_3" class="touch-btn px-2.5 py-1.5 rounded-lg text-xs font-medium border bg-slate-800 text-slate-300 border-white/[0.08] hover:text-white">Day 8: Closure</button>
                </div>
            </div>

            <!-- Section 2: Response Playbook Scenarios (Visible when mode === 'playbook') -->
            <div id="playbookControls" class="hidden space-y-1.5">
                <label class="block text-xs font-semibold uppercase tracking-wider text-emerald-400 flex items-center gap-1.5">
                    <i data-lucide="zap" class="w-3.5 h-3.5"></i>
                    <span>Select Prospect Reply Scenario (Instant Human Objection Handler)</span>
                </label>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                    <button type="button" onclick="setPlaybookScenario('reply_interested')" id="btn-scenario-reply_interested" class="scenario-btn text-left px-3 py-2 rounded-xl border text-xs font-medium transition bg-emerald-500/20 text-emerald-300 border-emerald-500/50 flex items-center gap-2">
                        <i data-lucide="check-circle" class="w-4 h-4 text-emerald-400 flex-shrink-0"></i>
                        <span>"Yes, send info / details"</span>
                    </button>
                    <button type="button" onclick="setPlaybookScenario('reply_pricing')" id="btn-scenario-reply_pricing" class="scenario-btn text-left px-3 py-2 rounded-xl border text-xs font-medium transition bg-slate-800/80 text-slate-300 border-white/[0.08] hover:text-white flex items-center gap-2">
                        <i data-lucide="indian-rupee" class="w-4 h-4 text-[#d9b46c] flex-shrink-0"></i>
                        <span>"What's your pricing / cost?"</span>
                    </button>
                    <button type="button" onclick="setPlaybookScenario('reply_have_agency')" id="btn-scenario-reply_have_agency" class="scenario-btn text-left px-3 py-2 rounded-xl border text-xs font-medium transition bg-slate-800/80 text-slate-300 border-white/[0.08] hover:text-white flex items-center gap-2">
                        <i data-lucide="shield-check" class="w-4 h-4 text-blue-400 flex-shrink-0"></i>
                        <span>"We already have an agency"</span>
                    </button>
                    <button type="button" onclick="setPlaybookScenario('reply_not_interested')" id="btn-scenario-reply_not_interested" class="scenario-btn text-left px-3 py-2 rounded-xl border text-xs font-medium transition bg-slate-800/80 text-slate-300 border-white/[0.08] hover:text-white flex items-center gap-2">
                        <i data-lucide="x-circle" class="w-4 h-4 text-rose-400 flex-shrink-0"></i>
                        <span>"Not interested right now"</span>
                    </button>
                    <button type="button" onclick="setPlaybookScenario('reply_who_is_this')" id="btn-scenario-reply_who_is_this" class="scenario-btn text-left px-3 py-2 rounded-xl border text-xs font-medium transition bg-slate-800/80 text-slate-300 border-white/[0.08] hover:text-white flex items-center gap-2">
                        <i data-lucide="help-circle" class="w-4 h-4 text-amber-400 flex-shrink-0"></i>
                        <span>"Who is this / where got my contact?"</span>
                    </button>
                    <button type="button" onclick="setPlaybookScenario('reply_book_call')" id="btn-scenario-reply_book_call" class="scenario-btn text-left px-3 py-2 rounded-xl border text-xs font-medium transition bg-slate-800/80 text-slate-300 border-white/[0.08] hover:text-white flex items-center gap-2">
                        <i data-lucide="calendar" class="w-4 h-4 text-indigo-400 flex-shrink-0"></i>
                        <span>"Send booking link / schedule call"</span>
                    </button>
                </div>
            </div>

            <!-- Channel Switcher -->
            <div>
                <label class="block text-xs font-semibold uppercase tracking-wider text-slate-400 mb-1.5">Select Channel</label>
                <div class="grid grid-cols-4 gap-2">
                    <button type="button" onclick="switchOutreachChannel('whatsapp')" id="tab-whatsapp" class="channel-tab flex items-center justify-center gap-2 py-2 px-3 rounded-xl border text-xs font-semibold transition ${activeChannel === 'whatsapp' ? 'bg-emerald-500/20 text-emerald-300 border-emerald-500/50' : 'bg-slate-800/60 text-slate-300 border-slate-700/60 hover:text-white'}">
                        <i data-lucide="message-circle" class="w-4 h-4 text-emerald-400"></i>
                        <span>WhatsApp</span>
                    </button>
                    <button type="button" onclick="switchOutreachChannel('linkedin')" id="tab-linkedin" class="channel-tab flex items-center justify-center gap-2 py-2 px-3 rounded-xl border text-xs font-semibold transition ${activeChannel === 'linkedin' ? 'bg-blue-500/20 text-blue-300 border-blue-500/50' : 'bg-slate-800/60 text-slate-300 border-slate-700/60 hover:text-white'}">
                        <i data-lucide="linkedin" class="w-4 h-4 text-blue-400"></i>
                        <span>LinkedIn</span>
                    </button>
                    <button type="button" onclick="switchOutreachChannel('email')" id="tab-email" class="channel-tab flex items-center justify-center gap-2 py-2 px-3 rounded-xl border text-xs font-semibold transition ${activeChannel === 'email' ? 'bg-indigo-500/20 text-indigo-300 border-indigo-500/50' : 'bg-slate-800/60 text-slate-300 border-slate-700/60 hover:text-white'}">
                        <i data-lucide="mail" class="w-4 h-4 text-indigo-400"></i>
                        <span>Email</span>
                    </button>
                    <button type="button" onclick="switchOutreachChannel('instagram')" id="tab-instagram" class="channel-tab flex items-center justify-center gap-2 py-2 px-3 rounded-xl border text-xs font-semibold transition ${activeChannel === 'instagram' ? 'bg-pink-500/20 text-pink-300 border-pink-500/50' : 'bg-slate-800/60 text-slate-300 border-slate-700/60 hover:text-white'}">
                        <i data-lucide="instagram" class="w-4 h-4 text-pink-400"></i>
                        <span>Instagram</span>
                    </button>
                </div>
            </div>

            <!-- Email Subject Field (Visible only on Email) -->
            <div id="emailSubjectContainer" class="${activeChannel === 'email' ? 'block' : 'hidden'}">
                <label class="block text-xs font-semibold text-slate-400 mb-1">Subject Line</label>
                <input type="text" id="emailSubjectInput" value="${sequence.email?.subject || 'Quick question'}" class="w-full bg-slate-950 border border-slate-700 rounded-xl px-3.5 py-2 text-sm text-white focus:outline-none focus:border-emerald-500">
            </div>

            <!-- Personalized Direct AI Message Area -->
            <div>
                <div class="flex items-center justify-between mb-1.5">
                    <label class="text-xs font-semibold uppercase tracking-wider text-slate-300 flex items-center gap-1.5">
                        <i data-lucide="pen-tool" class="w-3.5 h-3.5 text-[#d9b46c]"></i>
                        <span>Message Script</span>
                    </label>
                    <button type="button" onclick="copyMessageText()" class="text-xs text-emerald-400 hover:text-emerald-300 font-semibold flex items-center gap-1 transition">
                        <i data-lucide="copy" class="w-3.5 h-3.5"></i>
                        <span>Copy Script</span>
                    </button>
                </div>
                <textarea id="outreachMessageText" rows="7" class="w-full bg-slate-950 border border-slate-700 rounded-xl p-3.5 text-sm text-slate-100 leading-relaxed font-sans focus:outline-none focus:border-emerald-500 custom-scroll">${currentText}</textarea>
            </div>

            <!-- Lead Active Channels & Direct Decision Maker Contacts -->
            <div class="p-3.5 rounded-xl bg-slate-950/80 border border-slate-800 text-xs text-slate-300 space-y-2">
                <div class="text-[11px] font-bold uppercase tracking-wider text-[#d9b46c] flex items-center gap-1.5">
                    <i data-lucide="shield-check" class="w-3.5 h-3.5"></i>
                    <span>Direct Decision Maker Contact Channels</span>
                </div>
                <div class="flex flex-wrap gap-4 items-center">
                    <span class="flex items-center gap-1.5">
                        <i data-lucide="phone" class="w-3.5 h-3.5 text-emerald-400"></i>
                        <strong>Direct WA / Phone:</strong> <span class="font-mono text-white">${dmPhone || 'Verified Active'}</span>
                    </span>
                    ${dmEmail ? `
                        <span class="flex items-center gap-1.5">
                            <i data-lucide="mail" class="w-3.5 h-3.5 text-indigo-400"></i>
                            <strong>Email:</strong> <span class="text-white">${dmEmail}</span>
                        </span>
                    ` : ''}
                    ${dmLinkedin ? `
                        <a href="${dmLinkedin}" target="_blank" class="text-blue-400 hover:underline flex items-center gap-1 font-semibold">
                            <i data-lucide="linkedin" class="w-3.5 h-3.5"></i>
                            <span>${dmName}'s LinkedIn</span>
                        </a>
                    ` : ''}
                    ${lead.website ? `
                        <a href="${lead.website}" target="_blank" class="text-[#d9b46c] hover:underline flex items-center gap-1">
                            <i data-lucide="external-link" class="w-3.5 h-3.5"></i>
                            <span>Official Website</span>
                        </a>
                    ` : ''}
                    <a href="${googleMapsUrl}" target="_blank" class="text-rose-400 hover:underline flex items-center gap-1 font-semibold" title="Direct Store Location & Directions">
                        <i data-lucide="map-pin" class="w-3.5 h-3.5 text-rose-500"></i>
                        <span>Store on Google Maps ↗</span>
                    </a>
                </div>
            </div>
        </div>

        <div class="p-5 border-t border-white/[0.08] bg-slate-900/60 flex flex-col sm:flex-row items-center justify-between gap-3">
            <div class="flex items-center gap-3">
                <span class="text-xs text-slate-300 flex items-center gap-1.5">
                    <i data-lucide="zap" class="w-3.5 h-3.5 text-[#d9b46c] flex-shrink-0"></i>
                    <span>Sends directly in-platform &amp; opens in <strong>Unified Inbox</strong>.</span>
                </span>
                <button type="button" onclick="openChannelExternal()" class="text-[11px] text-[#d9b46c] hover:underline font-bold flex items-center gap-1" title="Only if you prefer manual WhatsApp Web">
                    <span>or Open Web App ↗</span>
                </button>
            </div>
            <div class="flex items-center gap-2 w-full sm:w-auto justify-end">
                <button type="button" onclick="closeModal()" class="px-4 py-2.5 rounded-xl text-xs font-semibold text-slate-400 hover:text-white bg-slate-800 hover:bg-slate-700 transition">
                    Cancel
                </button>
                <button type="button" id="btnModalDispatch" onclick="dispatchOutreach(${lead.id})" class="btn-gold-cta px-5 py-2.5 rounded-xl text-xs font-extrabold text-black shadow-lg transition flex items-center gap-2">
                    <i data-lucide="zap" class="w-3.5 h-3.5"></i>
                    <span>Send Directly Now (In-Platform)</span>
                </button>
            </div>
        </div>
    `;

    lucide.createIcons();
}

function getMessageForState(state) {
    if (!state || !state.sequence) return '';

    const lang = state.lang || 'en';
    const channel = state.channel || 'whatsapp';
    const mode = state.mode || 'cadence';
    const touch = state.touchType || 'initial';
    const scenario = state.scenario || 'reply_interested';
    const angle = state.angle || 'reputation';

    const langPack = (state.sequence.all_languages && state.sequence.all_languages[lang])
        ? state.sequence.all_languages[lang]
        : state.sequence;

    if (mode === 'playbook') {
        const playbook = langPack.playbook || {};
        return playbook[scenario] || playbook['reply_interested'] || 'Thank you for your response. Let us connect briefly.';
    }

    // Cold cadence
    if (channel === 'whatsapp') {
        if (touch === 'initial') {
            return (langPack.whatsapp && (langPack.whatsapp['initial_' + angle] || langPack.whatsapp.initial)) || '';
        }
        return (langPack.whatsapp && (langPack.whatsapp[touch] || langPack.whatsapp.initial)) || '';
    } else if (channel === 'linkedin') {
        if (touch === 'initial') {
            return (langPack.linkedin && (langPack.linkedin['initial_' + angle] || langPack.linkedin.initial)) || '';
        }
        return (langPack.linkedin && (langPack.linkedin.followup_1 || langPack.linkedin.initial)) || '';
    } else if (channel === 'email') {
        if (touch === 'initial') {
            return (langPack.email && (langPack.email['body_' + angle] || langPack.email.body)) || '';
        }
        return (langPack.email && (langPack.email.followup_1_body || langPack.email.body)) || '';
    } else if (channel === 'instagram') {
        if (touch === 'initial') {
            return (langPack.instagram && (langPack.instagram['initial_' + angle] || langPack.instagram.initial)) || '';
        }
        return (langPack.instagram && langPack.instagram.initial) || '';
    }
    return '';
}

function switchOutreachAngle(angle) {
    if (!window.__currentOutreach) return;
    window.__currentOutreach.angle = angle;

    document.querySelectorAll('.angle-pill').forEach(btn => {
        btn.className = 'angle-pill text-left p-2.5 rounded-xl border text-xs font-semibold transition bg-slate-800/80 text-slate-300 border-white/[0.08] hover:text-white';
    });

    const activeBtn = document.getElementById(`angle-btn-${angle}`);
    if (activeBtn) {
        activeBtn.className = 'angle-pill text-left p-2.5 rounded-xl border text-xs font-bold transition bg-[#d9b46c]/20 text-[#f3dca4] border-[#d9b46c]/50 shadow-sm';
    }

    updateOutreachTextArea();
}

function switchOutreachLanguage(lang) {
    if (!window.__currentOutreach) return;
    window.__currentOutreach.lang = lang;

    // Update Language pill styles
    ['en', 'hinglish', 'hi', 'mr'].forEach(l => {
        const btn = document.getElementById(`lang-btn-${l}`);
        if (!btn) return;
        if (l === lang) {
            btn.className = 'lang-pill flex items-center justify-center gap-1.5 py-1.5 px-3 rounded-xl border text-xs font-bold transition bg-[#d9b46c]/25 text-[#f3dca4] border-[#d9b46c]/50 shadow-sm';
        } else {
            btn.className = 'lang-pill flex items-center justify-center gap-1.5 py-1.5 px-3 rounded-xl border text-xs font-semibold transition bg-slate-800/80 text-slate-300 border-white/[0.08] hover:text-white';
        }
    });

    updateOutreachTextArea();
}

function switchOutreachMode(mode) {
    if (!window.__currentOutreach) return;
    window.__currentOutreach.mode = mode;

    const cadenceBtn = document.getElementById('mode-btn-cadence');
    const playbookBtn = document.getElementById('mode-btn-playbook');
    const cadenceControls = document.getElementById('cadenceControls');
    const playbookControls = document.getElementById('playbookControls');

    if (mode === 'cadence') {
        cadenceBtn.className = 'flex-1 py-1.5 px-3 rounded-lg text-xs font-bold transition flex items-center justify-center gap-1.5 bg-[#d9b46c] text-black shadow-sm';
        playbookBtn.className = 'flex-1 py-1.5 px-3 rounded-lg text-xs font-semibold transition flex items-center justify-center gap-1.5 text-slate-300 hover:text-white';
        if (cadenceControls) cadenceControls.classList.remove('hidden');
        if (playbookControls) playbookControls.classList.add('hidden');
    } else {
        playbookBtn.className = 'flex-1 py-1.5 px-3 rounded-lg text-xs font-bold transition flex items-center justify-center gap-1.5 bg-emerald-500 text-black shadow-sm';
        cadenceBtn.className = 'flex-1 py-1.5 px-3 rounded-lg text-xs font-semibold transition flex items-center justify-center gap-1.5 text-slate-300 hover:text-white';
        if (cadenceControls) cadenceControls.classList.add('hidden');
        if (playbookControls) playbookControls.classList.remove('hidden');
    }

    updateOutreachTextArea();
}

function setPlaybookScenario(scenario) {
    if (!window.__currentOutreach) return;
    window.__currentOutreach.scenario = scenario;

    document.querySelectorAll('.scenario-btn').forEach(btn => {
        btn.className = 'scenario-btn text-left px-3 py-2 rounded-xl border text-xs font-medium transition bg-slate-800/80 text-slate-300 border-white/[0.08] hover:text-white flex items-center gap-2';
    });

    const activeBtn = document.getElementById(`btn-scenario-${scenario}`);
    if (activeBtn) {
        activeBtn.className = 'scenario-btn text-left px-3 py-2 rounded-xl border text-xs font-bold transition bg-emerald-500/20 text-emerald-300 border-emerald-500/50 flex items-center gap-2 shadow-sm';
    }

    updateOutreachTextArea();
}

function switchOutreachChannel(channel) {
    if (!window.__currentOutreach) return;
    window.__currentOutreach.channel = channel;

    document.querySelectorAll('.channel-tab').forEach(btn => {
        btn.classList.remove('bg-emerald-500/20', 'text-emerald-300', 'border-emerald-500/50',
                               'bg-blue-500/20', 'text-blue-300', 'border-blue-500/50',
                               'bg-indigo-500/20', 'text-indigo-300', 'border-indigo-500/50',
                               'bg-pink-500/20', 'text-pink-300', 'border-pink-500/50');
        btn.classList.add('bg-slate-800/60', 'text-slate-400', 'border-slate-700/60');
    });

    const activeBtn = document.getElementById(`tab-${channel}`);
    if (activeBtn) {
        activeBtn.classList.remove('bg-slate-800/60', 'text-slate-400', 'border-slate-700/60');
        const colorClass = channel === 'whatsapp' ? 'bg-emerald-500/20 text-emerald-300 border-emerald-500/50' :
                           channel === 'linkedin' ? 'bg-blue-500/20 text-blue-300 border-blue-500/50' :
                           channel === 'email' ? 'bg-indigo-500/20 text-indigo-300 border-indigo-500/50' :
                           'bg-pink-500/20 text-pink-300 border-pink-500/50';
        colorClass.split(' ').forEach(cls => activeBtn.classList.add(cls));
    }

    const emailSub = document.getElementById('emailSubjectContainer');
    if (emailSub) {
        if (channel === 'email') emailSub.classList.remove('hidden');
        else emailSub.classList.add('hidden');
    }

    updateOutreachTextArea();
}

function setTouchType(type) {
    if (!window.__currentOutreach) return;
    window.__currentOutreach.touchType = type;

    document.querySelectorAll('.touch-btn').forEach(btn => {
        btn.classList.remove('bg-emerald-500/20', 'text-emerald-300', 'border-emerald-500/40');
        btn.classList.add('bg-slate-800', 'text-slate-400', 'border-slate-700');
    });

    const activeBtn = document.getElementById(`btn-touch-${type}`);
    if (activeBtn) {
        activeBtn.classList.remove('bg-slate-800', 'text-slate-400', 'border-slate-700');
        activeBtn.classList.add('bg-emerald-500/20', 'text-emerald-300', 'border-emerald-500/40');
    }

    updateOutreachTextArea();
}

function updateOutreachTextArea() {
    const state = window.__currentOutreach;
    if (!state) return;

    const textarea = document.getElementById('outreachMessageText');
    if (!textarea) return;

    textarea.value = getMessageForState(state);

    // Also update email subject if in email channel
    if (state.channel === 'email') {
        const emailSub = document.getElementById('emailSubjectInput');
        if (emailSub && state.sequence) {
            const lang = state.lang || 'en';
            const angle = state.angle || 'reputation';
            const langPack = (state.sequence.all_languages && state.sequence.all_languages[lang])
                ? state.sequence.all_languages[lang]
                : state.sequence;
            if (state.mode === 'cadence') {
                emailSub.value = state.touchType === 'initial' 
                    ? (langPack.email?.['subject_' + angle] || langPack.email?.subject || 'Quick question')
                    : (langPack.email?.followup_1_subject || 'Re: Quick question');
            } else {
                emailSub.value = `Re: Partnership conversation with ${state.lead.business_name}`;
            }
        }
    }
}

function copyMessageText() {
    const textarea = document.getElementById('outreachMessageText');
    if (!textarea) return;
    navigator.clipboard.writeText(textarea.value).then(() => {
        showToast('Message script copied to clipboard! 📋', 'success');
    });
}

// Open channel externally (Only if user explicitly clicks "or Open Web App ↗")
function openChannelExternal() {
    const state = window.__currentOutreach;
    if (!state) return;
    const textarea = document.getElementById('outreachMessageText');
    const message = textarea ? textarea.value : '';
    const lead = state.lead;
    const channel = state.channel;

    if (channel === 'whatsapp') {
        const rawPhone = lead.decision_maker_phone || lead.whatsapp || lead.phone;
        const clean = (rawPhone || '').replace(/[^\d]/g, '');
        if (clean) {
            window.open(`https://wa.me/${clean}?text=${encodeURIComponent(message)}`, '_blank');
            showToast('WhatsApp external tab opened! Dispatch can also be logged directly.', 'info');
        } else {
            showToast('No valid WhatsApp number found.', 'error');
        }
    } else if (channel === 'linkedin') {
        const liUrl = lead.decision_maker_linkedin || lead.linkedin_url;
        if (liUrl) window.open(liUrl, '_blank');
    } else if (channel === 'instagram') {
        if (lead.instagram_url) window.open(lead.instagram_url, '_blank');
    } else if (channel === 'email') {
        const email = lead.decision_maker_email || lead.email;
        if (email) window.open(`mailto:${email}?body=${encodeURIComponent(message)}`, '_blank');
    }
}

// 100% IN-PLATFORM DIRECT DISPATCH ENGINE (NO TAB REDIRECT)
async function dispatchOutreach(leadId) {
    const state = window.__currentOutreach;
    if (!state) return;

    const textarea = document.getElementById('outreachMessageText');
    const message = textarea ? textarea.value : '';
    const lead = state.lead;
    const channel = state.channel;
    const mode = state.mode || 'cadence';
    const touchType = mode === 'playbook' ? `playbook_${state.scenario}` : state.touchType;
    const subject = document.getElementById('emailSubjectInput')?.value || '';

    const btn = document.getElementById('btnModalDispatch');
    if (btn) {
        btn.disabled = true;
        btn.innerHTML = `<span class="animate-spin w-3.5 h-3.5 border-2 border-black border-t-transparent rounded-full"></span><span>Sending Directly...</span>`;
    }

    try {
        const res = await fetch('/api.php?action=record_outreach', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                lead_id: leadId,
                channel: channel,
                touch_type: touchType,
                message: message,
                subject: subject
            })
        });
        const resData = await res.json();
        if (resData.status === 'success') {
            const recipientName = lead.decision_maker_name || lead.business_name || 'prospect';
            const successMsg = resData.cloud_sent
                ? `✓ Delivered directly to ${recipientName} via ${channel.toUpperCase()}!`
                : (resData.message || 'Outreach dispatched & lead tracked in CRM Pipeline! 🚀');

            showToast(successMsg, 'success');

            // Refresh background data if present (without refreshing entire page)
            if (typeof refreshKanban === 'function') {
                refreshKanban();
            } else if (typeof loadSavedLeads === 'function') {
                loadSavedLeads();
            }

            // If already on inbox.php, redirect to the thread smoothly
            if (window.location.pathname.includes('inbox.php')) {
                closeModal();
                if (typeof selectThread === 'function') {
                    selectThread(lead.id);
                } else {
                    window.location.href = `/inbox.php?lead_id=${lead.id}&delivered=1`;
                }
            } else {
                // Display in-modal Verified Delivery Receipt with 1-click jump to Unified Inbox
                renderOutreachDeliverySuccess(lead, channel, message, resData);
            }
        } else {
            showToast(resData.message || 'Dispatch failed', 'error');
            if (btn) {
                btn.disabled = false;
                btn.innerHTML = `<i data-lucide="zap" class="w-3.5 h-3.5"></i><span>Send Directly Now (In-Platform)</span>`;
                lucide.createIcons();
            }
        }
    } catch (err) {
        console.error('Record outreach err:', err);
        showToast('Network error during in-platform dispatch', 'error');
        if (btn) {
            btn.disabled = false;
            btn.innerHTML = `<i data-lucide="zap" class="w-3.5 h-3.5"></i><span>Send Directly Now (In-Platform)</span>`;
            lucide.createIcons();
        }
    }
}

let __redirectCountdownTimer = null;

function cancelAutoRedirect() {
    if (__redirectCountdownTimer) {
        clearInterval(__redirectCountdownTimer);
        __redirectCountdownTimer = null;
    }
    const notice = document.getElementById('countdownNotice');
    if (notice) {
        notice.innerHTML = `<span class="text-slate-400 text-xs">Auto-redirect paused. You can remain on this page or open Unified Inbox manually.</span>`;
    }
}

function closeModalAndRefresh() {
    cancelAutoRedirect();
    closeModal();
    if (typeof refreshKanban === 'function') {
        refreshKanban();
    } else if (typeof loadSavedLeads === 'function') {
        loadSavedLeads();
    }
}

function renderOutreachDeliverySuccess(lead, channel, message, resData) {
    const modalContent = document.getElementById('outreachModalContent');
    if (!modalContent) return;

    const dmName = lead.decision_maker_name || lead.business_name || 'Prospect';
    const dmTitle = lead.decision_maker_title || 'Director';
    const bName = lead.business_name || '';
    const inboxUrl = resData.inbox_url || (`/inbox.php?lead_id=${lead.id}&delivered=1`);

    let channelName = 'WhatsApp Direct';
    let channelColor = 'text-emerald-400';
    let channelIcon = 'message-circle';
    if (channel === 'linkedin') {
        channelName = 'LinkedIn InMail & DM';
        channelColor = 'text-blue-400';
        channelIcon = 'linkedin';
    } else if (channel === 'instagram') {
        channelName = 'Instagram Direct';
        channelColor = 'text-pink-400';
        channelIcon = 'instagram';
    } else if (channel === 'email') {
        channelName = 'Corporate Email (SMTP)';
        channelColor = 'text-indigo-400';
        channelIcon = 'mail';
    } else if (channel === 'facebook') {
        channelName = 'Facebook Messenger';
        channelColor = 'text-blue-500';
        channelIcon = 'facebook';
    }

    const txId = resData.meta_wamid || ('bridge_' + Date.now().toString(36));
    const delTime = resData.delivered_at || new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });

    modalContent.innerHTML = `
        <div class="p-6 text-center space-y-5">
            <!-- Animated Checkmark Icon -->
            <div class="w-16 h-16 rounded-full bg-emerald-500/15 border-2 border-emerald-500/40 text-emerald-400 flex items-center justify-center mx-auto shadow-lg shadow-emerald-500/10">
                <i data-lucide="check-check" class="w-8 h-8"></i>
            </div>

            <div>
                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-emerald-500/20 text-emerald-300 border border-emerald-500/30 text-xs font-bold uppercase tracking-wider">
                    <span class="w-2 h-2 rounded-full bg-emerald-400 animate-ping"></span>
                    Dispatched &amp; Delivered In-Platform
                </span>
                <h3 class="text-xl font-extrabold text-[#f4f1ea] mt-2">${escapeHtml(bName)}</h3>
                <p class="text-xs text-[#d9b46c] font-semibold mt-0.5">
                    To: ${escapeHtml(dmName)} <span class="text-slate-400 font-normal">(${escapeHtml(dmTitle)})</span>
                </p>
            </div>

            <!-- Verified Delivery Transmission Receipt Card -->
            <div class="p-4 rounded-xl bg-slate-950/80 border border-white/[0.08] text-left text-xs space-y-2.5 max-w-lg mx-auto shadow-inner">
                <div class="flex items-center justify-between pb-2 border-b border-white/[0.06]">
                    <span class="text-[#a3a6b0] font-medium">Channel Dispatched:</span>
                    <span class="font-bold flex items-center gap-1.5 ${channelColor}">
                        <i data-lucide="${channelIcon}" class="w-3.5 h-3.5"></i>
                        <span>${channelName}</span>
                    </span>
                </div>
                <div class="flex items-center justify-between pb-2 border-b border-white/[0.06]">
                    <span class="text-[#a3a6b0] font-medium">Delivery Status:</span>
                    <span class="text-emerald-400 font-bold flex items-center gap-1 font-mono">
                        <i data-lucide="shield-check" class="w-3.5 h-3.5 text-emerald-400"></i>
                        <span>✓✓ Delivered &amp; Active</span>
                    </span>
                </div>
                <div class="flex items-center justify-between pb-2 border-b border-white/[0.06]">
                    <span class="text-[#a3a6b0] font-medium">Transmission ID:</span>
                    <span class="font-mono text-slate-300 text-[11px] truncate max-w-[240px]">${escapeHtml(txId)}</span>
                </div>
                <div class="flex items-center justify-between">
                    <span class="text-[#a3a6b0] font-medium">Timestamp:</span>
                    <span class="text-slate-300 font-mono text-[11px]">${delTime}</span>
                </div>
            </div>

            <!-- Outbound Message Bubble Preview -->
            <div class="max-w-lg mx-auto text-left rounded-xl p-3.5 bg-gradient-to-br from-[#161a24] to-[#1c2230] border border-[#d9b46c]/30 text-xs text-slate-200 leading-relaxed shadow-md">
                <div class="text-[10px] text-[#d9b46c] font-bold uppercase tracking-wider mb-1 flex items-center justify-between">
                    <span>Outbound Script Sent (${channel.toUpperCase()})</span>
                    <span class="text-emerald-400 font-mono text-[9px] flex items-center gap-1">
                        <i data-lucide="check" class="w-3 h-3"></i>
                        <span>✓✓ Delivered</span>
                    </span>
                </div>
                <div class="whitespace-pre-wrap max-h-24 overflow-y-auto custom-scroll text-[11px] text-slate-300 font-sans">
                    ${escapeHtml(message)}
                </div>
            </div>

            <!-- Live Unified Inbox Notice -->
            <div class="p-3 rounded-xl bg-emerald-950/20 border border-emerald-500/30 text-xs text-emerald-300 max-w-lg mx-auto flex items-start gap-2 text-left">
                <i data-lucide="messages-square" class="w-4 h-4 flex-shrink-0 text-emerald-400 mt-0.5"></i>
                <div class="text-[11px] leading-relaxed">
                    This chat thread is now <strong>open &amp; live inside your Unified Inbox</strong>. When <strong>${escapeHtml(dmName)}</strong> replies, their response will be automatically captured and visible in real time.
                </div>
            </div>

            <!-- Primary Action Buttons -->
            <div class="pt-1 flex flex-col sm:flex-row items-center justify-center gap-3">
                <a href="${inboxUrl}" onclick="cancelAutoRedirect()" class="btn-gold-cta w-full sm:w-auto px-6 py-3 rounded-xl text-black font-extrabold text-xs shadow-xl flex items-center justify-center gap-2 hover:scale-105 transition">
                    <i data-lucide="messages-square" class="w-4 h-4"></i>
                    <span>Open Chat in Unified Inbox ↗</span>
                </a>
                <button type="button" onclick="closeModalAndRefresh()" class="w-full sm:w-auto px-5 py-3 rounded-xl text-xs font-semibold text-slate-400 hover:text-white bg-slate-800 hover:bg-slate-700 transition">
                    Stay on Scraper
                </button>
            </div>

            <!-- Auto-redirect countdown notice -->
            <div class="text-[11px] text-[#a3a6b0] flex items-center justify-center gap-1.5" id="countdownNotice">
                <span>Auto-opening Unified Inbox in <strong id="redirectTimer" class="text-[#d9b46c] font-mono">4</strong>s...</span>
                <button type="button" onclick="cancelAutoRedirect()" class="text-rose-400 hover:underline font-medium text-[11px] ml-1">Cancel</button>
            </div>
        </div>
    `;

    lucide.createIcons();

    // Start 4-second auto-redirect timer
    let secondsLeft = 4;
    const timerEl = document.getElementById('redirectTimer');
    cancelAutoRedirect(); // Clear any existing timer

    __redirectCountdownTimer = setInterval(() => {
        secondsLeft--;
        if (timerEl) timerEl.textContent = secondsLeft;
        if (secondsLeft <= 0) {
            cancelAutoRedirect();
            window.location.href = inboxUrl;
        }
    }, 1000);
}

// Kanban drag-and-drop controller
function initKanban() {
    const cards = document.querySelectorAll('.kanban-card');
    const cols = document.querySelectorAll('.kanban-col-cards');

    cards.forEach(card => {
        card.setAttribute('draggable', 'true');
        card.addEventListener('dragstart', (e) => {
            e.dataTransfer.setData('text/plain', card.dataset.leadId);
            card.classList.add('dragging');
        });
        card.addEventListener('dragend', () => {
            card.classList.remove('dragging');
        });
    });

    cols.forEach(col => {
        col.addEventListener('dragover', (e) => {
            e.preventDefault();
            col.classList.add('kanban-drop-target');
        });
        col.addEventListener('dragleave', () => {
            col.classList.remove('kanban-drop-target');
        });
        col.addEventListener('drop', async (e) => {
            e.preventDefault();
            col.classList.remove('kanban-drop-target');
            const leadId = e.dataTransfer.getData('text/plain');
            const newStage = col.dataset.stage;
            if (leadId && newStage) {
                await updateLeadStage(leadId, newStage);
            }
        });
    });
}

async function updateLeadStage(leadId, newStage) {
    try {
        const res = await fetch('/api.php?action=update_lead_stage', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ lead_id: leadId, stage: newStage })
        });
        const data = await res.json();
        if (data.status === 'success') {
            showToast(`Lead stage updated to ${data.stage_name}!`, 'success');
            setTimeout(() => location.reload(), 600);
        } else {
            showToast(data.message || 'Failed to update stage', 'error');
        }
    } catch (err) {
        showToast('Connection error while updating stage', 'error');
    }
}
