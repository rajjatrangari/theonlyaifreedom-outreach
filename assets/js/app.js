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

    let currentText = '';
    if (activeChannel === 'whatsapp') {
        currentText = sequence.whatsapp.initial;
    } else if (activeChannel === 'linkedin') {
        currentText = sequence.linkedin.initial;
    } else if (activeChannel === 'email') {
        currentText = sequence.email.body;
    } else if (activeChannel === 'instagram') {
        currentText = sequence.instagram.initial;
    }

    modalBox.innerHTML = `
        <div class="p-5 border-b border-slate-800 flex items-center justify-between bg-slate-900/60">
            <div>
                <div class="flex items-center gap-2">
                    <h3 class="font-bold text-white text-base">Direct AI Outreach Dispatcher</h3>
                    <span class="text-xs px-2 py-0.5 rounded-full bg-emerald-500/20 text-emerald-300 border border-emerald-500/30">
                        Q-Score: ${lead.qualification_score}/100
                    </span>
                </div>
                <div class="text-xs text-slate-400 mt-0.5">Prospect: <strong class="text-slate-200">${lead.business_name}</strong> • ${lead.city || 'India'}</div>
            </div>
            <button onclick="closeModal()" class="p-1.5 text-slate-400 hover:text-white rounded-lg hover:bg-slate-800">
                <i data-lucide="x" class="w-5 h-5"></i>
            </button>
        </div>

        <div class="p-5 overflow-y-auto max-h-[70vh] space-y-4 custom-scroll">
            
            <!-- Channel Switcher -->
            <div>
                <label class="block text-xs font-semibold uppercase tracking-wider text-slate-400 mb-2">Select Channel</label>
                <div class="grid grid-cols-4 gap-2">
                    <button type="button" onclick="switchOutreachChannel('whatsapp')" id="tab-whatsapp" class="channel-tab flex items-center justify-center gap-2 py-2 px-3 rounded-xl border text-xs font-semibold transition ${activeChannel === 'whatsapp' ? 'bg-emerald-500/20 text-emerald-300 border-emerald-500/50' : 'bg-slate-800/60 text-slate-400 border-slate-700/60 hover:text-white'}">
                        <i data-lucide="message-circle" class="w-4 h-4 text-emerald-400"></i>
                        <span>WhatsApp</span>
                    </button>
                    <button type="button" onclick="switchOutreachChannel('linkedin')" id="tab-linkedin" class="channel-tab flex items-center justify-center gap-2 py-2 px-3 rounded-xl border text-xs font-semibold transition ${activeChannel === 'linkedin' ? 'bg-blue-500/20 text-blue-300 border-blue-500/50' : 'bg-slate-800/60 text-slate-400 border-slate-700/60 hover:text-white'}">
                        <i data-lucide="linkedin" class="w-4 h-4 text-blue-400"></i>
                        <span>LinkedIn</span>
                    </button>
                    <button type="button" onclick="switchOutreachChannel('email')" id="tab-email" class="channel-tab flex items-center justify-center gap-2 py-2 px-3 rounded-xl border text-xs font-semibold transition ${activeChannel === 'email' ? 'bg-indigo-500/20 text-indigo-300 border-indigo-500/50' : 'bg-slate-800/60 text-slate-400 border-slate-700/60 hover:text-white'}">
                        <i data-lucide="mail" class="w-4 h-4 text-indigo-400"></i>
                        <span>Email</span>
                    </button>
                    <button type="button" onclick="switchOutreachChannel('instagram')" id="tab-instagram" class="channel-tab flex items-center justify-center gap-2 py-2 px-3 rounded-xl border text-xs font-semibold transition ${activeChannel === 'instagram' ? 'bg-pink-500/20 text-pink-300 border-pink-500/50' : 'bg-slate-800/60 text-slate-400 border-slate-700/60 hover:text-white'}">
                        <i data-lucide="instagram" class="w-4 h-4 text-pink-400"></i>
                        <span>Instagram</span>
                    </button>
                </div>
            </div>

            <!-- Follow-up Stage Selector for this lead -->
            <div>
                <label class="block text-xs font-semibold uppercase tracking-wider text-slate-400 mb-1.5">Outreach Touchpoint</label>
                <div class="flex gap-2">
                    <button type="button" onclick="setTouchType('initial')" id="btn-touch-initial" class="touch-btn px-3 py-1.5 rounded-lg text-xs font-medium border bg-emerald-500/20 text-emerald-300 border-emerald-500/40">Initial Pitch</button>
                    <button type="button" onclick="setTouchType('followup_1')" id="btn-touch-followup_1" class="touch-btn px-3 py-1.5 rounded-lg text-xs font-medium border bg-slate-800 text-slate-400 border-slate-700 hover:text-white">Follow-up 1 (Day 2)</button>
                    <button type="button" onclick="setTouchType('followup_2')" id="btn-touch-followup_2" class="touch-btn px-3 py-1.5 rounded-lg text-xs font-medium border bg-slate-800 text-slate-400 border-slate-700 hover:text-white">Follow-up 2 (Day 5)</button>
                    <button type="button" onclick="setTouchType('followup_3')" id="btn-touch-followup_3" class="touch-btn px-3 py-1.5 rounded-lg text-xs font-medium border bg-slate-800 text-slate-400 border-slate-700 hover:text-white">Breakup / Closure</button>
                </div>
            </div>

            <!-- Email Subject Field (Visible only on Email) -->
            <div id="emailSubjectContainer" class="${activeChannel === 'email' ? 'block' : 'hidden'}">
                <label class="block text-xs font-semibold text-slate-400 mb-1">Subject Line</label>
                <input type="text" id="emailSubjectInput" value="${sequence.email.subject}" class="w-full bg-slate-950 border border-slate-700 rounded-xl px-3.5 py-2 text-sm text-white focus:outline-none focus:border-emerald-500">
            </div>

            <!-- Personalized AI Message Area -->
            <div>
                <div class="flex items-center justify-between mb-1.5">
                    <label class="text-xs font-semibold uppercase tracking-wider text-slate-400">Personalized Message</label>
                    <button type="button" onclick="copyMessageText()" class="text-xs text-emerald-400 hover:text-emerald-300 flex items-center gap-1">
                        <i data-lucide="copy" class="w-3.5 h-3.5"></i>
                        <span>Copy Pitch</span>
                    </button>
                </div>
                <textarea id="outreachMessageText" rows="7" class="w-full bg-slate-950 border border-slate-700 rounded-xl p-3.5 text-sm text-slate-200 leading-relaxed font-sans focus:outline-none focus:border-emerald-500 custom-scroll">${currentText}</textarea>
            </div>

            <!-- Lead Active Channels & Contact Details Info -->
            <div class="p-3 rounded-xl bg-slate-950/80 border border-slate-800 text-xs text-slate-300 flex flex-wrap gap-4 items-center">
                <span class="flex items-center gap-1.5">
                    <i data-lucide="phone" class="w-3.5 h-3.5 text-emerald-400"></i>
                    <strong>Phone/WA:</strong> ${lead.whatsapp || lead.phone || 'N/A'}
                </span>
                ${lead.email ? `
                    <span class="flex items-center gap-1.5">
                        <i data-lucide="mail" class="w-3.5 h-3.5 text-indigo-400"></i>
                        <strong>Email:</strong> ${lead.email}
                    </span>
                ` : ''}
                ${lead.website ? `
                    <a href="${lead.website}" target="_blank" class="text-blue-400 hover:underline flex items-center gap-1">
                        <i data-lucide="external-link" class="w-3.5 h-3.5"></i>
                        <span>Visit Website</span>
                    </a>
                ` : ''}
                ${lead.linkedin_url ? `
                    <a href="${lead.linkedin_url}" target="_blank" class="text-blue-400 hover:underline flex items-center gap-1">
                        <i data-lucide="linkedin" class="w-3.5 h-3.5"></i>
                        <span>LinkedIn</span>
                    </a>
                ` : ''}
            </div>
        </div>

        <div class="p-5 border-t border-slate-800 bg-slate-900/60 flex items-center justify-between">
            <div class="text-xs text-slate-400">
                Clicking dispatch opens channel & moves lead to <strong>Outreach Sent</strong>.
            </div>
            <div class="flex items-center gap-2">
                <button type="button" onclick="closeModal()" class="px-4 py-2.5 rounded-xl text-xs font-semibold text-slate-400 hover:text-white bg-slate-800 hover:bg-slate-700 transition">
                    Cancel
                </button>
                <button type="button" onclick="dispatchOutreach(${lead.id})" class="px-5 py-2.5 rounded-xl text-xs font-bold bg-gradient-to-r from-emerald-500 to-teal-600 hover:from-emerald-400 hover:to-teal-500 text-black shadow-lg shadow-emerald-500/20 transition flex items-center gap-2">
                    <i data-lucide="send" class="w-3.5 h-3.5"></i>
                    <span>Open & Dispatch</span>
                </button>
            </div>
        </div>
    `;

    // Store global state for channel switching
    window.__currentOutreach = {
        lead: lead,
        sequence: sequence,
        channel: activeChannel,
        touchType: 'initial'
    };

    lucide.createIcons();
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

    const channel = state.channel;
    const touch = state.touchType;
    const seq = state.sequence;
    const textarea = document.getElementById('outreachMessageText');
    if (!textarea) return;

    let text = '';
    if (channel === 'whatsapp') {
        text = seq.whatsapp[touch] || seq.whatsapp.initial;
    } else if (channel === 'linkedin') {
        text = touch === 'initial' ? seq.linkedin.initial : seq.linkedin.followup_1;
    } else if (channel === 'email') {
        text = touch === 'initial' ? seq.email.body : seq.email.followup_1_body;
    } else if (channel === 'instagram') {
        text = seq.instagram.initial;
    }

    textarea.value = text;
}

function copyMessageText() {
    const textarea = document.getElementById('outreachMessageText');
    if (!textarea) return;
    navigator.clipboard.writeText(textarea.value).then(() => {
        showToast('Message pitch copied to clipboard! 📋', 'success');
    });
}

// Open channel directly & log to CRM pipeline
async function dispatchOutreach(leadId) {
    const state = window.__currentOutreach;
    if (!state) return;

    const textarea = document.getElementById('outreachMessageText');
    const message = textarea ? textarea.value : '';
    const lead = state.lead;
    const channel = state.channel;
    const touchType = state.touchType;

    // Automatically copy message to clipboard so user has it ready
    if (message) {
        navigator.clipboard.writeText(message).catch(() => {});
    }

    // 1. Open target channel
    if (channel === 'whatsapp') {
        const rawPhone = lead.whatsapp || lead.phone;
        const clean = (rawPhone || '').replace(/[^\d]/g, '');
        if (clean) {
            window.open(`https://wa.me/${clean}?text=${encodeURIComponent(message)}`, '_blank');
        } else {
            showToast('No valid WhatsApp number found for this lead.', 'error');
            return;
        }
    } else if (channel === 'linkedin') {
        if (lead.linkedin_url) {
            window.open(lead.linkedin_url, '_blank');
            showToast('LinkedIn profile opened! Pitch copied to clipboard.', 'success');
        } else {
            showToast('No LinkedIn URL for this lead.', 'error');
            return;
        }
    } else if (channel === 'email') {
        const email = lead.email;
        const subject = document.getElementById('emailSubjectInput')?.value || `Opportunity for ${lead.business_name}`;
        if (email) {
            window.open(`mailto:${email}?subject=${encodeURIComponent(subject)}&body=${encodeURIComponent(message)}`, '_blank');
        } else {
            showToast('No valid email address for this lead.', 'error');
            return;
        }
    } else if (channel === 'instagram') {
        if (lead.instagram_url) {
            window.open(lead.instagram_url, '_blank');
            showToast('Instagram profile opened! Pitch copied to clipboard.', 'success');
        } else {
            showToast('No Instagram URL for this lead.', 'error');
            return;
        }
    }

    // 2. Log touchpoint to CRM and update stage
    try {
        const res = await fetch('/api.php?action=record_outreach', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                lead_id: leadId,
                channel: channel,
                touch_type: touchType,
                message: message
            })
        });
        const resData = await res.json();
        if (resData.status === 'success') {
            showToast('Outreach dispatched & lead moved to Outreach Sent in CRM! 🚀', 'success');
            closeModal();
            // If on CRM page, refresh Kanban column
            if (typeof refreshKanban === 'function') {
                refreshKanban();
            } else {
                setTimeout(() => location.reload(), 1200);
            }
        }
    } catch (err) {
        console.error('Record outreach err:', err);
    }
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
