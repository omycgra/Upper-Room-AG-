<?php
    $prefillRecipients = array_filter(array_map('trim', explode(',', $_GET['recipients'] ?? '')));
    $prefillMessage = $_GET['message'] ?? '';
    $validPhones = 0;
    foreach (($members ?? []) as $m) {
        if (trim($m['phone'] ?? '') !== '') $validPhones++;
    }
?>

<div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4 mb-10">
    <div>
        <h2 class="text-4xl sm:text-5xl font-black text-white tracking-tighter">Send SMS</h2>
        <p class="text-slate-400 font-bold mt-2 uppercase tracking-widest text-xs">Broadcast & targeted messaging</p>
    </div>
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 w-full lg:w-auto">
        <div class="glass-card w-full px-5 sm:px-6 py-4 rounded-2xl flex items-center gap-4 border-white/5">
            <div class="w-12 h-12 bg-accent/10 rounded-2xl flex items-center justify-center border border-accent/20">
                <i class="fas fa-signal text-accent"></i>
            </div>
            <div>
                <p class="text-[9px] font-black text-slate-500 uppercase tracking-widest">Valid Phone Numbers</p>
                <p class="text-xl font-black text-white"><?php echo number_format($validPhones); ?></p>
            </div>
        </div>

        <div class="glass-card w-full px-5 sm:px-6 py-4 rounded-2xl border-white/5">
            <div class="flex items-center gap-4">
                <div class="w-12 h-12 bg-sky-500/10 rounded-2xl flex items-center justify-center border border-sky-400/20">
                    <i class="fas fa-wallet text-sky-300"></i>
                </div>
                <div class="min-w-0 flex-1">
                    <div class="flex items-center justify-between gap-3">
                        <p class="text-[9px] font-black text-slate-500 uppercase tracking-widest">SMS Balance</p>
                        <button type="button" id="sms-balance-refresh" class="text-[10px] font-black uppercase tracking-widest text-sky-300 hover:text-white transition-colors">
                            Refresh
                        </button>
                    </div>
                    <p id="sms-balance-value" class="text-xl font-black text-white mt-1">Checking...</p>
                    <p id="sms-balance-meta" class="text-[10px] font-black text-slate-500 uppercase tracking-widest mt-2">Connecting to provider</p>
                </div>
            </div>
        </div>
    </div>
</div>

<form action="<?php echo BASE_URL; ?>/sms/send" method="POST" data-loader="top" class="grid grid-cols-1 lg:grid-cols-2 gap-8">
    <div class="glass-card rounded-[2.5rem] sm:rounded-[3rem] border-white/5 overflow-hidden">
        <div class="px-6 sm:px-8 lg:px-10 py-6 sm:py-8 border-b border-white/5 bg-white/[0.02] flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3">
            <div class="flex items-center">
                <div class="w-10 h-10 bg-accent/10 rounded-xl flex items-center justify-center mr-4 border border-accent/20">
                    <i class="fas fa-users text-accent text-sm"></i>
                </div>
                <h4 class="text-xl font-black text-white tracking-tight">Select Recipients</h4>
            </div>
            <span id="selected-count" class="text-[10px] font-black text-slate-500 uppercase tracking-[0.2em]">0 selected</span>
        </div>

        <div class="p-6 sm:p-10">
            <input type="hidden" name="send_all" id="send_all" value="0">
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-2 mb-8">
                <button type="button" data-tab="all" class="sms-tab active-link flex items-center justify-center gap-2 px-4 py-4 rounded-2xl text-[10px] font-black uppercase tracking-widest">
                    <i class="fas fa-people-group text-[10px]"></i> All
                </button>
                <button type="button" data-tab="group" class="sms-tab flex items-center justify-center gap-2 px-4 py-4 rounded-2xl text-[10px] font-black uppercase tracking-widest bg-white/5 border border-white/10 text-slate-400">
                    <i class="fas fa-layer-group text-[10px]"></i> Group
                </button>
                <button type="button" data-tab="individual" class="sms-tab flex items-center justify-center gap-2 px-4 py-4 rounded-2xl text-[10px] font-black uppercase tracking-widest bg-white/5 border border-white/10 text-slate-400">
                    <i class="fas fa-user text-[10px]"></i> Members
                </button>
            </div>

            <div id="tab-all" class="sms-tab-panel">
                <label class="glass-card p-6 rounded-3xl border-white/10 flex items-start gap-4 cursor-pointer hover:bg-white/5 transition-all">
                    <input type="checkbox" id="send-all-checkbox" class="mt-1 w-5 h-5 accent-yellow-400">
                    <div class="flex-1">
                        <p class="text-sm font-black text-white">Send to all members with valid phone numbers</p>
                        <p class="text-xs font-bold text-slate-400 mt-2">Your message will be sent to <?php echo number_format($validPhones); ?> members with valid phone numbers.</p>
                    </div>
                </label>
                <div class="mt-6 glass-card p-5 rounded-3xl border-white/10 bg-white/[0.02] flex items-start gap-4">
                    <div class="w-10 h-10 bg-accent/10 rounded-2xl flex items-center justify-center border border-accent/20">
                        <i class="fas fa-circle-info text-accent"></i>
                    </div>
                    <p class="text-xs font-bold text-slate-400 leading-relaxed">When sending to all members, make sure your message is relevant to everyone.</p>
                </div>
            </div>

            <div id="tab-group" class="sms-tab-panel hidden">
                <div class="space-y-4">
                    <div class="space-y-2">
                        <label class="block text-[9px] font-black text-slate-500 uppercase tracking-widest ml-1">Choose Group</label>
                        <div class="relative group">
                            <i class="fas fa-layer-group absolute left-5 top-1/2 -translate-y-1/2 text-slate-600"></i>
                            <select name="group_id" id="group_id" class="w-full bg-white/5 border border-white/10 focus:border-accent rounded-2xl pl-14 pr-10 py-4 text-sm font-bold text-white transition-all outline-none appearance-none cursor-pointer">
                                <option value="">Select group</option>
                                <?php foreach (($clusters ?? []) as $c): ?>
                                    <option value="<?php echo (int)$c['id']; ?>"><?php echo htmlspecialchars($c['name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                            <i class="fas fa-chevron-down absolute right-5 top-1/2 -translate-y-1/2 text-slate-600 text-[10px] pointer-events-none"></i>
                        </div>
                    </div>
                    <div class="glass-card p-5 rounded-3xl border-white/10 bg-white/[0.02] flex items-start gap-4">
                        <div class="w-10 h-10 bg-accent/10 rounded-2xl flex items-center justify-center border border-accent/20">
                            <i class="fas fa-users text-accent"></i>
                        </div>
                        <p id="group-summary" class="text-xs font-bold text-slate-400 leading-relaxed">Select a group to target only members inside it.</p>
                    </div>
                </div>
            </div>

            <div id="tab-individual" class="sms-tab-panel hidden">
                <div class="space-y-4">
                    <div class="relative group">
                        <i class="fas fa-search absolute left-5 top-1/2 -translate-y-1/2 text-slate-500 group-focus-within:text-accent transition-colors"></i>
                        <input type="text" id="member-search" placeholder="Search members..." class="w-full pl-14 pr-6 py-4 bg-white/5 border border-white/10 rounded-2xl text-sm font-bold text-white focus:ring-2 focus:ring-accent focus:bg-white/10 outline-none transition-all">
                    </div>

                    <div class="max-h-[420px] overflow-y-auto custom-scrollbar pr-2 space-y-2">
                        <div class="glass-card p-4 rounded-2xl border-white/10 flex items-center justify-between">
                            <label class="flex items-center gap-3 cursor-pointer">
                                <input type="checkbox" id="select-all-members" class="w-4 h-4 accent-yellow-400">
                                <span class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Select all visible</span>
                            </label>
                            <span class="text-[10px] font-black text-slate-500 uppercase tracking-widest"><?php echo count($members ?? []); ?> members</span>
                        </div>

                        <?php foreach (($members ?? []) as $member): ?>
                            <?php $phone = trim($member['phone'] ?? ''); ?>
                            <label class="member-row glass-card p-4 rounded-2xl border-white/10 flex items-center gap-4 cursor-pointer hover:bg-white/5 transition-all" data-name="<?php echo htmlspecialchars(strtolower(($member['first_name'] ?? '') . ' ' . ($member['last_name'] ?? '') . ' ' . ($member['member_code'] ?? '') . ' ' . ($member['phone'] ?? ''))); ?>">
                                <input type="checkbox" name="recipients[]" value="<?php echo $phone; ?>" class="recipient-check w-4 h-4 accent-yellow-400" <?php echo ($phone !== '' && in_array($phone, $prefillRecipients, true)) ? 'checked' : ''; ?> <?php echo $phone === '' ? 'disabled' : ''; ?>>
                                <div class="w-11 h-11 rounded-2xl overflow-hidden bg-slate-800 border border-white/10 flex items-center justify-center flex-shrink-0">
                                    <?php if (!empty($member['photo_path'])): ?>
                                        <img src="<?php echo BASE_URL . '/' . ltrim($member['photo_path'], '/'); ?>" class="w-full h-full object-cover">
                                    <?php else: ?>
                                        <span class="text-accent font-black text-sm"><?php echo strtoupper(substr($member['first_name'], 0, 1)); ?></span>
                                    <?php endif; ?>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm font-black text-slate-200 truncate"><?php echo htmlspecialchars(($member['first_name'] ?? '') . ' ' . ($member['last_name'] ?? '')); ?></p>
                                    <p class="text-[10px] font-black text-slate-500 uppercase tracking-widest mt-1 truncate">
                                        <?php echo htmlspecialchars($member['member_code'] ?? ''); ?>
                                        <?php if ($phone !== ''): ?> · <?php echo htmlspecialchars($phone); ?><?php else: ?> · NO PHONE<?php endif; ?>
                                    </p>
                                </div>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="glass-card rounded-[2.5rem] sm:rounded-[3rem] border-white/5 overflow-hidden">
        <div class="px-6 sm:px-8 lg:px-10 py-6 sm:py-8 border-b border-white/5 bg-white/[0.02] flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3">
            <div class="flex items-center">
                <div class="w-10 h-10 bg-accent/10 rounded-xl flex items-center justify-center mr-4 border border-accent/20">
                    <i class="fas fa-message text-accent text-sm"></i>
                </div>
                <h4 class="text-xl font-black text-white tracking-tight">Message Content</h4>
            </div>
            <span id="char-count" class="text-[10px] font-black text-slate-500 uppercase tracking-[0.2em]">0 / 1000</span>
        </div>

        <div class="p-6 sm:p-10 space-y-6">
            <div class="space-y-2">
                <label class="block text-[9px] font-black text-slate-500 uppercase tracking-widest ml-1">Message Templates</label>
                <div class="relative group">
                    <i class="fas fa-layer-group absolute left-5 top-1/2 -translate-y-1/2 text-slate-600"></i>
                    <select id="template-select" class="w-full bg-white/5 border border-white/10 focus:border-accent rounded-2xl pl-14 pr-10 py-4 text-sm font-bold text-white transition-all outline-none appearance-none cursor-pointer">
                        <option value="">Select a template or write your own</option>
                        <option value="Sunday Service Reminder">Sunday Service Reminder</option>
                        <option value="Prayer Meeting Invitation">Prayer Meeting Invitation</option>
                        <option value="Conference Reminder">Conference Reminder</option>
                        <option value="Birthday Wishes">Birthday Wishes</option>
                    </select>
                    <i class="fas fa-chevron-down absolute right-5 top-1/2 -translate-y-1/2 text-slate-600 text-[10px] pointer-events-none"></i>
                </div>
            </div>

            <div class="space-y-2">
                <label class="block text-[9px] font-black text-slate-500 uppercase tracking-widest ml-1">Message</label>
                <textarea name="message" id="message-body" rows="10" required maxlength="1000" class="w-full bg-white/5 border border-white/10 focus:border-accent rounded-3xl px-6 py-5 text-sm font-bold text-white outline-none resize-none"><?php echo htmlspecialchars($prefillMessage); ?></textarea>
                <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                    <p class="text-[10px] font-black text-slate-500 uppercase tracking-widest">Max 1000 characters</p>
                    <p id="sms-split-hint" class="text-[10px] font-black text-slate-500 uppercase tracking-widest"></p>
                </div>
            </div>

            <button type="submit" id="send-btn" class="w-full bg-accent text-slate-900 py-5 rounded-2xl font-black text-xs uppercase tracking-[0.2em] hover:scale-[1.02] active:scale-95 transition-all shadow-xl shadow-yellow-500/10">
                <i class="fas fa-paper-plane mr-2"></i> Send SMS
            </button>
        </div>
    </div>
</form>

<script>
    (function () {
        const tabButtons = Array.from(document.querySelectorAll('.sms-tab'));
        const panels = {
            all: document.getElementById('tab-all'),
            group: document.getElementById('tab-group'),
            individual: document.getElementById('tab-individual')
        };

        const sendAllCheckbox = document.getElementById('send-all-checkbox');
        const sendAllInput = document.getElementById('send_all');
        const groupSelect = document.getElementById('group_id');
        const groupSummary = document.getElementById('group-summary');

        const memberSearch = document.getElementById('member-search');
        const memberRows = Array.from(document.querySelectorAll('.member-row'));
        const selectAllVisible = document.getElementById('select-all-members');
        const recipientChecks = Array.from(document.querySelectorAll('.recipient-check'));
        const selectedCount = document.getElementById('selected-count');

        const templateSelect = document.getElementById('template-select');
        const messageBody = document.getElementById('message-body');
        const charCount = document.getElementById('char-count');
        const splitHint = document.getElementById('sms-split-hint');
        const balanceValue = document.getElementById('sms-balance-value');
        const balanceMeta = document.getElementById('sms-balance-meta');
        const balanceRefresh = document.getElementById('sms-balance-refresh');
        let balanceLoading = false;

        const members = <?php echo json_encode(array_map(function ($m) {
            return [
                'id' => $m['id'] ?? null,
                'cluster_id' => $m['cluster_id'] ?? null,
                'phone' => trim($m['phone'] ?? '')
            ];
        }, $members ?? [])); ?>;

        const templates = {
            'Sunday Service Reminder': 'Reminder: Sunday Service starts at [TIME]. We look forward to seeing you. God bless you.',
            'Prayer Meeting Invitation': 'You are invited to our Prayer Meeting at [TIME]. Come and be blessed.',
            'Conference Reminder': 'Conference Reminder: Join us at [VENUE] on [DATE]. God bless you.',
            'Birthday Wishes': 'Happy Birthday! May God bless you abundantly.'
        };

        function setActiveTab(tab) {
            tabButtons.forEach(btn => {
                const isActive = btn.dataset.tab === tab;
                btn.classList.toggle('active-link', isActive);
                btn.classList.toggle('bg-white/5', !isActive);
                btn.classList.toggle('border', !isActive);
                btn.classList.toggle('border-white/10', !isActive);
                btn.classList.toggle('text-slate-400', !isActive);
            });

            Object.keys(panels).forEach(k => panels[k].classList.toggle('hidden', k !== tab));

            if (tab !== 'all') {
                sendAllCheckbox.checked = false;
                sendAllInput.value = '0';
            }
            if (tab !== 'group' && groupSelect) groupSelect.value = '';
            updateSelectedCount();
        }

        tabButtons.forEach(btn => btn.addEventListener('click', () => setActiveTab(btn.dataset.tab)));

        if (sendAllCheckbox) {
            sendAllCheckbox.addEventListener('change', () => {
                sendAllInput.value = sendAllCheckbox.checked ? '1' : '0';
                updateSelectedCount();
            });
        }

        function updateGroupSummary() {
            if (!groupSelect || !groupSummary) return;
            const groupId = groupSelect.value ? Number(groupSelect.value) : null;
            if (!groupId) {
                groupSummary.textContent = 'Select a group to target only members inside it.';
                return;
            }
            const count = members.filter(m => Number(m.cluster_id) === groupId && m.phone).length;
            groupSummary.textContent = `This message will be sent to ${count} members with valid phone numbers in the selected group.`;
        }

        if (groupSelect) groupSelect.addEventListener('change', () => { updateGroupSummary(); updateSelectedCount(); });
        updateGroupSummary();

        function updateSelectedCount() {
            let count = 0;
            if (sendAllCheckbox && sendAllCheckbox.checked) {
                count = <?php echo (int)$validPhones; ?>;
            } else if (groupSelect && groupSelect.value) {
                const groupId = Number(groupSelect.value);
                count = members.filter(m => Number(m.cluster_id) === groupId && m.phone).length;
            } else {
                count = recipientChecks.filter(c => c.checked && !c.disabled && c.value).length;
            }
            if (selectedCount) selectedCount.textContent = `${count} selected`;
        }

        recipientChecks.forEach(c => c.addEventListener('change', updateSelectedCount));
        updateSelectedCount();

        function filterMembers() {
            const q = (memberSearch?.value || '').trim().toLowerCase();
            memberRows.forEach(row => {
                const hay = row.dataset.name || '';
                row.classList.toggle('hidden', q !== '' && !hay.includes(q));
            });
            if (selectAllVisible) selectAllVisible.checked = false;
        }

        if (memberSearch) memberSearch.addEventListener('input', filterMembers);

        if (selectAllVisible) {
            selectAllVisible.addEventListener('change', () => {
                const visibleRows = memberRows.filter(r => !r.classList.contains('hidden'));
                visibleRows.forEach(r => {
                    const cb = r.querySelector('input.recipient-check');
                    if (cb && !cb.disabled) cb.checked = selectAllVisible.checked;
                });
                updateSelectedCount();
            });
        }

        function updateCharCount() {
            const len = (messageBody?.value || '').length;
            if (charCount) charCount.textContent = `${len} / 1000`;
            if (splitHint) {
                if (len <= 160) splitHint.textContent = '';
                else {
                    const parts = Math.ceil(len / 160);
                    splitHint.textContent = `${parts} SMS parts`;
                }
            }
        }

        if (messageBody) messageBody.addEventListener('input', updateCharCount);
        updateCharCount();

        if (templateSelect && messageBody) {
            templateSelect.addEventListener('change', () => {
                const t = templateSelect.value;
                if (templates[t]) {
                    messageBody.value = templates[t];
                    updateCharCount();
                }
            });
        }

        function setBalanceState(valueText, metaText, isError = false) {
            if (balanceValue) {
                balanceValue.textContent = valueText;
                balanceValue.classList.toggle('text-rose-300', isError);
                balanceValue.classList.toggle('text-white', !isError);
            }
            if (balanceMeta) {
                balanceMeta.textContent = metaText;
                balanceMeta.classList.toggle('text-rose-300', isError);
                balanceMeta.classList.toggle('text-slate-500', !isError);
            }
        }

        async function refreshSmsBalance() {
            if (!balanceValue || balanceLoading) return;
            balanceLoading = true;
            if (balanceRefresh) balanceRefresh.disabled = true;
            setBalanceState('Checking...', 'Fetching latest provider balance');

            try {
                const response = await fetch(`<?php echo BASE_URL; ?>/sms/balance`, {
                    headers: {
                        'Accept': 'application/json'
                    }
                });
                const data = await response.json();
                const provider = String(data.provider || 'sms').toUpperCase();

                if (response.ok && data.ok) {
                    const now = new Date();
                    setBalanceState(
                        data.display || 'Available',
                        `${provider} updated ${now.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })}`
                    );
                } else {
                    setBalanceState(
                        'Unavailable',
                        data.error || 'Could not fetch SMS balance right now.',
                        true
                    );
                }
            } catch (error) {
                setBalanceState('Unavailable', 'Could not fetch SMS balance right now.', true);
            } finally {
                balanceLoading = false;
                if (balanceRefresh) balanceRefresh.disabled = false;
            }
        }

        if (balanceRefresh) {
            balanceRefresh.addEventListener('click', refreshSmsBalance);
        }

        refreshSmsBalance();
        window.setInterval(refreshSmsBalance, 45000);
    })();
</script>
