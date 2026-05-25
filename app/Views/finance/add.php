<?php
    $churchName = AppConfig::getSetting('church_name', 'Church Management');
    $isStaff = !empty($isStaff);
    $isFinanceUser = Auth::isFinance();
    $staffAllowedTypes = $staffAllowedTypes ?? ['Offering', 'Tithe', 'Departmental Savings', 'Welfare'];
    $defaultType = $defaultType ?? 'Offering';
?>

<div class="flex flex-col sm:flex-row justify-between items-start mb-10 gap-4">
    <div>
        <h2 class="text-4xl sm:text-5xl font-black text-white tracking-tighter"><?php echo $isStaff ? 'Staff Cashier Entry' : 'Add Financial Transaction'; ?></h2>
        <p class="text-slate-400 font-bold mt-2 uppercase tracking-widest text-xs"><?php echo $isStaff ? 'Quick cashier entry and printable receipt' : 'Record a new transaction for'; ?> <span class="text-accent"><?php echo htmlspecialchars($churchName); ?></span></p>
    </div>
    <a href="<?php echo BASE_URL; ?>/finance" class="glass-card flex items-center px-6 py-3.5 rounded-2xl border-white/10 text-slate-300 font-black text-xs uppercase tracking-widest hover:bg-white/5 transition-all">
        <i class="fas fa-arrow-left mr-2"></i> Back to Finance
    </a>
</div>

<div class="glass-card rounded-[3rem] border-white/5 overflow-hidden card-interaction">
    <div class="px-8 sm:px-10 py-8 sm:py-10 bg-slate-900/40 border-b border-white/5">
        <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-6">
            <div class="flex items-center">
                <div class="w-12 h-12 bg-accent/10 rounded-2xl flex items-center justify-center mr-4 border border-accent/20">
                    <i class="fas fa-plus text-accent"></i>
                </div>
                <div>
                    <h3 class="text-2xl font-black text-white tracking-tight">Transaction Form</h3>
                    <p class="text-[10px] font-black text-slate-500 uppercase tracking-[0.3em] mt-2">Finance operations</p>
                </div>
            </div>
        </div>
    </div>

    <form action="<?php echo BASE_URL; ?>/finance/store" method="POST" data-loader="top" class="p-6 sm:p-10 bg-slate-900/50">
        <input type="hidden" name="transaction_type" id="transaction_type" value="<?php echo htmlspecialchars($defaultType); ?>">

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
            <div class="space-y-8">
                <div class="space-y-3">
                    <label class="block text-[9px] font-black text-slate-500 uppercase tracking-widest ml-1">Transaction Mode</label>
                    <div class="flex items-center gap-3">
                        <button type="button" id="mode-income" class="flex-1 px-6 py-4 rounded-2xl border text-[10px] font-black uppercase tracking-widest transition-all bg-emerald-500/10 text-emerald-300 border-emerald-500/20">
                            <i class="fas fa-arrow-trend-up mr-2"></i> Income
                        </button>
                        <button type="button" id="mode-expense" class="flex-1 px-6 py-4 rounded-2xl border text-[10px] font-black uppercase tracking-widest transition-all bg-white/5 text-slate-300 border-white/10 hover:bg-white/10">
                            <i class="fas fa-arrow-trend-down mr-2"></i> Expense
                        </button>
                    </div>
                </div>

                <div id="income-category-wrap" class="space-y-3">
                    <label class="block text-[9px] font-black text-slate-500 uppercase tracking-widest ml-1">Income Category</label>
                    <?php
                        $incomeTypeButtons = [];
                        if ($isStaff) {
                            $incomeTypeButtons[] = ['value' => 'Offering', 'label' => 'General Offering', 'icon' => 'fa-receipt'];
                            if ($isFinanceUser) {
                                $incomeTypeButtons[] = ['value' => 'Sunday School', 'label' => 'Sunday School', 'icon' => 'fa-chalkboard-teacher'];
                                $incomeTypeButtons[] = ['value' => 'Annual Harvest', 'label' => 'Annual Harvest', 'icon' => 'fa-wheat-awn'];
                                $incomeTypeButtons[] = ['value' => 'Mini Harvest', 'label' => 'Mini Harvest', 'icon' => 'fa-seedling'];
                            }
                            $incomeTypeButtons[] = ['value' => 'Tithe', 'label' => 'Tithe', 'icon' => 'fa-hand-holding-dollar'];
                            $incomeTypeButtons[] = ['value' => 'Departmental Savings', 'label' => 'Department Offering', 'icon' => 'fa-sitemap'];
                            $incomeTypeButtons[] = ['value' => 'Welfare', 'label' => 'Welfare', 'icon' => 'fa-heart'];
                        } else {
                            $incomeTypeButtons = [
                                ['value' => 'Offering', 'label' => 'Offering', 'icon' => 'fa-receipt'],
                                ['value' => 'Sunday School', 'label' => 'Sunday School', 'icon' => 'fa-chalkboard-teacher'],
                                ['value' => 'Annual Harvest', 'label' => 'Annual Harvest', 'icon' => 'fa-wheat-awn'],
                                ['value' => 'Mini Harvest', 'label' => 'Mini Harvest', 'icon' => 'fa-seedling'],
                                ['value' => 'Tithe', 'label' => 'Tithe', 'icon' => 'fa-hand-holding-dollar'],
                                ['value' => 'Project Offering', 'label' => 'Project Offering', 'icon' => 'fa-handshake'],
                                ['value' => 'Donation', 'label' => 'Donation', 'icon' => 'fa-gift'],
                                ['value' => 'Pledge Fulfillment', 'label' => 'Pledge Fulfillment', 'icon' => 'fa-clipboard-check'],
                                ['value' => 'Welfare', 'label' => 'Welfare', 'icon' => 'fa-heart'],
                                ['value' => 'Seed', 'label' => 'Seed', 'icon' => 'fa-seedling'],
                                ['value' => 'Departmental Savings', 'label' => 'Department Offering', 'icon' => 'fa-sitemap'],
                                ['value' => 'Others', 'label' => 'Other', 'icon' => 'fa-layer-group'],
                            ];
                        }
                    ?>
                    <select id="income-category" class="hidden">
                        <?php foreach ($incomeTypeButtons as $opt): ?>
                            <option value="<?php echo htmlspecialchars($opt['value']); ?>" <?php echo $defaultType === $opt['value'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($opt['label']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
                        <?php foreach ($incomeTypeButtons as $opt): ?>
                            <?php $active = $defaultType === $opt['value']; ?>
                            <button
                                type="button"
                                data-income-type="1"
                                data-value="<?php echo htmlspecialchars($opt['value']); ?>"
                                class="<?php echo $active ? 'bg-accent text-slate-900 border-accent/30 shadow-xl shadow-yellow-500/10' : 'bg-white/5 text-slate-200 border-white/10 hover:bg-white/10'; ?> w-full flex items-center gap-3 rounded-2xl border px-4 py-4 transition-all"
                            >
                                <span class="<?php echo $active ? 'text-slate-900' : 'text-accent'; ?> w-9 h-9 rounded-xl flex items-center justify-center bg-white/5 border border-white/10">
                                    <i class="fas <?php echo htmlspecialchars($opt['icon']); ?> text-sm"></i>
                                </span>
                                <span class="min-w-0 text-left">
                                    <span class="block text-[10px] font-black uppercase tracking-widest truncate"><?php echo htmlspecialchars($opt['label']); ?></span>
                                    <span class="block text-[9px] font-black uppercase tracking-widest <?php echo $active ? 'text-slate-800' : 'text-slate-500'; ?> truncate"><?php echo htmlspecialchars($opt['value']); ?></span>
                                </span>
                            </button>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div id="offering-subtype-wrap" class="space-y-3 hidden">
                    <label class="block text-[9px] font-black text-slate-500 uppercase tracking-widest ml-1">Offering Type</label>
                    <div class="relative group">
                        <i class="fas fa-gift absolute left-5 top-1/2 -translate-y-1/2 text-slate-600 group-focus-within:text-accent transition-colors"></i>
                        <select id="offering_subtype" name="offering_subtype" class="w-full bg-white/5 border border-white/10 focus:border-accent rounded-2xl pl-14 pr-10 py-5 text-sm font-bold text-white transition-all outline-none appearance-none cursor-pointer">
                            <option value="Main Offering">Main Offering</option>
                            <option value="Thanksgiving">Thanksgiving</option>
                        </select>
                        <i class="fas fa-chevron-down absolute right-5 top-1/2 -translate-y-1/2 text-slate-600 text-[10px] pointer-events-none"></i>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                    <div class="space-y-3">
                        <label class="block text-[9px] font-black text-slate-500 uppercase tracking-widest ml-1">Amount (GHS)</label>
                        <div class="relative group">
                            <i class="fas fa-coins absolute left-5 top-1/2 -translate-y-1/2 text-slate-600 group-focus-within:text-accent transition-colors"></i>
                            <input type="number" step="0.01" name="amount" required class="w-full bg-white/5 border border-white/10 focus:border-accent rounded-2xl pl-14 pr-6 py-5 text-sm font-bold text-white transition-all outline-none" placeholder="0.00">
                        </div>
                    </div>
                    <div class="space-y-3">
                        <label class="block text-[9px] font-black text-slate-500 uppercase tracking-widest ml-1">Transaction Date</label>
                        <div class="relative group">
                            <i class="far fa-calendar-alt absolute left-5 top-1/2 -translate-y-1/2 text-slate-600 group-focus-within:text-accent transition-colors"></i>
                            <input type="date" name="transaction_date" required value="<?php echo date('Y-m-d'); ?>" class="w-full bg-white/5 border border-white/10 focus:border-accent rounded-2xl pl-14 pr-6 py-5 text-sm font-bold text-white transition-all outline-none">
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                    <div class="space-y-3">
                        <label class="block text-[9px] font-black text-slate-500 uppercase tracking-widest ml-1">Payment Method</label>
                        <div class="relative group">
                            <i class="fas fa-credit-card absolute left-5 top-1/2 -translate-y-1/2 text-slate-600 group-focus-within:text-accent transition-colors"></i>
                            <select id="payment_method" name="payment_method" required class="w-full bg-white/5 border border-white/10 focus:border-accent rounded-2xl pl-14 pr-10 py-5 text-sm font-bold text-white transition-all outline-none appearance-none cursor-pointer">
                                <option value="Cash">Cash</option>
                                <option value="MoMo">MoMo</option>
                                <option value="Bank Transfer" <?php echo !empty($isDeptHead) ? 'selected' : ''; ?>>Bank Transfer</option>
                                <option value="Check">Check</option>
                            </select>
                            <i class="fas fa-chevron-down absolute right-5 top-1/2 -translate-y-1/2 text-slate-600 text-[10px] pointer-events-none"></i>
                        </div>
                        <?php if (!empty($isDeptHead)): ?>
                            <p class="text-[10px] font-black text-slate-500 uppercase tracking-widest">Department heads can save their transactions with cash, momo, bank transfer or check</p>
                        <?php endif; ?>
                    </div>
                    <div class="space-y-3">
                        <label class="block text-[9px] font-black text-slate-500 uppercase tracking-widest ml-1">Reference No.</label>
                        <div class="relative group">
                            <i class="fas fa-hashtag absolute left-5 top-1/2 -translate-y-1/2 text-slate-600 group-focus-within:text-accent transition-colors"></i>
                            <input type="text" name="reference_no" class="w-full bg-white/5 border border-white/10 focus:border-accent rounded-2xl pl-14 pr-6 py-5 text-sm font-bold text-white transition-all outline-none" placeholder="Receipt / TX ID">
                        </div>
                    </div>
                </div>
            </div>

            <div class="space-y-8">
                <div class="space-y-3">
                    <label class="block text-[9px] font-black text-slate-500 uppercase tracking-widest ml-1"><?php echo $isStaff ? 'Department (For Department Offering)' : 'Department (Optional)'; ?></label>
                    <div class="relative group">
                        <i class="fas fa-sitemap absolute left-5 top-1/2 -translate-y-1/2 text-slate-600"></i>
                        <select id="department_id" name="department_id" class="w-full bg-white/5 border border-white/10 focus:border-accent rounded-2xl pl-14 pr-10 py-5 text-sm font-bold text-white transition-all outline-none appearance-none cursor-pointer" <?php echo !empty($isDeptHead) ? 'disabled' : ''; ?>>
                            <option value=""><?php echo !empty($isDeptHead) ? 'My Department' : 'None'; ?></option>
                            <?php foreach (($departments ?? []) as $d): ?>
                                <option value="<?php echo (int)$d['id']; ?>" <?php echo !empty($isDeptHead) && (int)($myDeptId ?? 0) === (int)$d['id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($d['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                        <i class="fas fa-chevron-down absolute right-5 top-1/2 -translate-y-1/2 text-slate-600 text-[10px] pointer-events-none"></i>
                    </div>
                    <p class="text-[10px] font-black text-slate-500 uppercase tracking-widest"><?php echo $isStaff ? 'Required only when the type is Department Offering' : 'Used for departmental savings totals'; ?></p>
                </div>

                <?php if (empty($isDeptHead)): ?>
                <div class="space-y-3">
                    <label class="block text-[9px] font-black text-slate-500 uppercase tracking-widest ml-1"><?php echo $isStaff ? 'Member (Required For Tithe & Welfare)' : 'Member (Optional)'; ?></label>
                    <div class="relative group">
                        <i class="fas fa-magnifying-glass absolute left-5 top-1/2 -translate-y-1/2 text-slate-600 group-focus-within:text-accent transition-colors"></i>
                        <input id="member_search" type="text" class="w-full bg-white/5 border border-white/10 focus:border-accent rounded-2xl pl-14 pr-6 py-4 text-sm font-bold text-white transition-all outline-none" placeholder="Search member by name...">
                        <div id="member_search_results" class="hidden absolute left-0 right-0 top-full mt-2 z-20 rounded-2xl border border-white/10 bg-slate-950/95 backdrop-blur-xl shadow-2xl overflow-hidden max-h-64 overflow-y-auto custom-scrollbar"></div>
                    </div>
                    <div class="relative group">
                        <i class="fas fa-user absolute left-5 top-1/2 -translate-y-1/2 text-slate-600"></i>
                        <select id="member_id" name="member_id" class="w-full bg-white/5 border border-white/10 focus:border-accent rounded-2xl pl-14 pr-10 py-5 text-sm font-bold text-white transition-all outline-none appearance-none cursor-pointer">
                            <option value=""><?php echo $isStaff ? 'Select member when needed' : 'None / General'; ?></option>
                            <?php foreach ($members as $member): ?>
                                <option value="<?php echo (int)$member['id']; ?>"><?php echo htmlspecialchars(($member['first_name'] ?? '') . ' ' . ($member['last_name'] ?? '')); ?></option>
                            <?php endforeach; ?>
                        </select>
                        <i class="fas fa-chevron-down absolute right-5 top-1/2 -translate-y-1/2 text-slate-600 text-[10px] pointer-events-none"></i>
                    </div>
                    <div id="member-summary" class="hidden bg-white/5 rounded-[2.5rem] p-6 border border-white/10">
                        <div class="flex items-center justify-between">
                            <p class="text-[9px] font-black text-slate-500 uppercase tracking-widest">Member Totals</p>
                            <p class="text-[9px] font-black text-slate-500 uppercase tracking-widest">Offering excluded • Department excluded</p>
                        </div>
                        <div id="member-summary-loading" class="mt-4 text-slate-500 text-xs font-bold italic">Loading...</div>
                        <div id="member-summary-body" class="hidden mt-5"></div>
                    </div>
                </div>
                <?php endif; ?>

                <?php if ($isStaff): ?>
                <div class="space-y-3">
                    <label class="block text-[9px] font-black text-slate-500 uppercase tracking-widest ml-1">Quick Calculator</label>
                    <div class="glass-card rounded-[2.5rem] p-6 border-white/10 space-y-5">
                        <div class="grid grid-cols-4 gap-3">
                            <button type="button" class="calc-quick px-3 py-3 rounded-2xl bg-white/5 border border-white/10 text-[10px] font-black text-slate-200 uppercase tracking-widest" data-value="10">10</button>
                            <button type="button" class="calc-quick px-3 py-3 rounded-2xl bg-white/5 border border-white/10 text-[10px] font-black text-slate-200 uppercase tracking-widest" data-value="20">20</button>
                            <button type="button" class="calc-quick px-3 py-3 rounded-2xl bg-white/5 border border-white/10 text-[10px] font-black text-slate-200 uppercase tracking-widest" data-value="50">50</button>
                            <button type="button" class="calc-quick px-3 py-3 rounded-2xl bg-white/5 border border-white/10 text-[10px] font-black text-slate-200 uppercase tracking-widest" data-value="100">100</button>
                        </div>
                        <div class="grid grid-cols-3 gap-3">
                            <button type="button" class="calc-key px-3 py-4 rounded-2xl bg-white/5 border border-white/10 text-sm font-black text-white" data-key="1">1</button>
                            <button type="button" class="calc-key px-3 py-4 rounded-2xl bg-white/5 border border-white/10 text-sm font-black text-white" data-key="2">2</button>
                            <button type="button" class="calc-key px-3 py-4 rounded-2xl bg-white/5 border border-white/10 text-sm font-black text-white" data-key="3">3</button>
                            <button type="button" class="calc-key px-3 py-4 rounded-2xl bg-white/5 border border-white/10 text-sm font-black text-white" data-key="4">4</button>
                            <button type="button" class="calc-key px-3 py-4 rounded-2xl bg-white/5 border border-white/10 text-sm font-black text-white" data-key="5">5</button>
                            <button type="button" class="calc-key px-3 py-4 rounded-2xl bg-white/5 border border-white/10 text-sm font-black text-white" data-key="6">6</button>
                            <button type="button" class="calc-key px-3 py-4 rounded-2xl bg-white/5 border border-white/10 text-sm font-black text-white" data-key="7">7</button>
                            <button type="button" class="calc-key px-3 py-4 rounded-2xl bg-white/5 border border-white/10 text-sm font-black text-white" data-key="8">8</button>
                            <button type="button" class="calc-key px-3 py-4 rounded-2xl bg-white/5 border border-white/10 text-sm font-black text-white" data-key="9">9</button>
                            <button type="button" class="calc-key px-3 py-4 rounded-2xl bg-white/5 border border-white/10 text-sm font-black text-white" data-key=".">.</button>
                            <button type="button" class="calc-key px-3 py-4 rounded-2xl bg-white/5 border border-white/10 text-sm font-black text-white" data-key="0">0</button>
                            <button type="button" id="calc-clear" class="px-3 py-4 rounded-2xl bg-rose-500/10 border border-rose-500/20 text-sm font-black text-rose-300">C</button>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <div class="space-y-3">
                    <label class="block text-[9px] font-black text-slate-500 uppercase tracking-widest ml-1">Description / Notes</label>
                    <div class="relative group">
                        <i class="fas fa-align-left absolute left-5 top-6 text-slate-600 group-focus-within:text-accent transition-colors"></i>
                        <textarea name="description" rows="7" class="w-full bg-white/5 border border-white/10 focus:border-accent rounded-3xl pl-14 pr-6 py-6 text-sm font-bold text-white transition-all outline-none resize-none" placeholder="Optional notes..."></textarea>
                    </div>
                </div>

                <div class="flex flex-col sm:flex-row gap-4 pt-4">
                    <button type="submit" class="flex-1 bg-accent text-slate-900 py-6 rounded-2xl font-black text-xs uppercase tracking-[0.3em] hover:scale-[1.02] active:scale-95 transition-all shadow-xl shadow-yellow-500/10">
                        <?php echo $isStaff ? 'Save Transaction' : 'Save Transaction'; ?>
                    </button>
                    <a href="<?php echo BASE_URL; ?>/finance" class="px-10 py-6 bg-white/5 text-slate-400 rounded-2xl font-black text-xs uppercase tracking-[0.3em] hover:bg-white/10 transition-all text-center">
                        Cancel
                    </a>
                </div>
            </div>
        </div>
    </form>
</div>

<script>
    (function () {
        const typeInput = document.getElementById('transaction_type');
        const modeIncome = document.getElementById('mode-income');
        const modeExpense = document.getElementById('mode-expense');
        const incomeCategory = document.getElementById('income-category');
        const incomeWrap = document.getElementById('income-category-wrap');
        const amountInput = document.querySelector('input[name="amount"]');
        const memberSelect = document.getElementById('member_id');
        const memberSearch = document.getElementById('member_search');
        const memberSearchResults = document.getElementById('member_search_results');
        const departmentSelect = document.getElementById('department_id');
        const paymentSelect = document.getElementById('payment_method');
        const summaryWrap = document.getElementById('member-summary');
        const summaryLoading = document.getElementById('member-summary-loading');
        const summaryBody = document.getElementById('member-summary-body');
        if (!typeInput || !modeIncome || !incomeCategory || !incomeWrap) return;

        const incomeTypeButtons = Array.from(incomeWrap.querySelectorAll('[data-income-type="1"]'));
        const syncIncomeTypeButtons = function () {
            if (!incomeTypeButtons.length) return;
            const current = String(incomeCategory.value || '');
            incomeTypeButtons.forEach((btn) => {
                const v = String(btn.dataset.value || '');
                const active = v !== '' && v === current;
                btn.classList.toggle('bg-accent', active);
                btn.classList.toggle('text-slate-900', active);
                btn.classList.toggle('border-accent/30', active);
                btn.classList.toggle('shadow-xl', active);
                btn.classList.toggle('shadow-yellow-500/10', active);
                btn.classList.toggle('bg-white/5', !active);
                btn.classList.toggle('text-slate-200', !active);
                btn.classList.toggle('border-white/10', !active);
            });
        };

        incomeTypeButtons.forEach((btn) => {
            btn.addEventListener('click', function () {
                const v = String(btn.dataset.value || '');
                if (!v) return;
                incomeCategory.value = v;
                incomeCategory.dispatchEvent(new Event('change'));
                syncIncomeTypeButtons();
            });
        });

        const currency = 'GHS';
        const isStaff = <?php echo $isStaff ? 'true' : 'false'; ?>;
        const baseMemberPlaceholder = <?php echo json_encode($isStaff ? 'Select member when needed' : 'None / General'); ?>;
        const memberOptions = memberSelect
            ? Array.from(memberSelect.options)
                .filter(option => option.value !== '')
                .map(option => ({ value: option.value, label: option.textContent }))
            : [];

        const hideMemberSuggestions = () => {
            if (!memberSearchResults) return;
            memberSearchResults.classList.add('hidden');
            memberSearchResults.innerHTML = '';
        };

        const chooseMember = (value) => {
            if (!memberSelect) return;
            memberSelect.value = String(value || '');
            const selected = memberOptions.find(option => option.value === String(value || ''));
            if (memberSearch && selected) {
                memberSearch.value = selected.label;
            }
            hideMemberSuggestions();
            loadMemberSummary();
        };

        const renderMemberSuggestions = (searchTerm = '') => {
            if (!memberSearchResults) return;
            const normalized = String(searchTerm || '').trim().toLowerCase();
            if (!normalized) {
                hideMemberSuggestions();
                return;
            }

            const filtered = memberOptions.filter(option => option.label.toLowerCase().includes(normalized)).slice(0, 8);
            if (!filtered.length) {
                memberSearchResults.innerHTML = '<div class="px-5 py-4 text-xs font-bold text-slate-500">No matching member found.</div>';
                memberSearchResults.classList.remove('hidden');
                return;
            }

            memberSearchResults.innerHTML = filtered.map(option => `
                <button
                    type="button"
                    class="member-suggestion w-full text-left px-5 py-4 text-sm font-bold text-slate-200 hover:bg-white/5 transition-all border-b border-white/5 last:border-b-0"
                    data-value="${String(option.value).replace(/"/g, '&quot;')}"
                    data-label="${String(option.label).replace(/"/g, '&quot;')}"
                >
                    ${option.label}
                </button>
            `).join('');
            memberSearchResults.classList.remove('hidden');

            memberSearchResults.querySelectorAll('.member-suggestion').forEach((button) => {
                button.addEventListener('mousedown', (event) => {
                    event.preventDefault();
                    chooseMember(button.dataset.value || '');
                });
            });
        };

        const rebuildMemberOptions = (searchTerm = '') => {
            if (!memberSelect) return;
            const currentValue = String(memberSelect.value || '');
            const normalized = String(searchTerm || '').trim().toLowerCase();
            const filtered = !normalized
                ? memberOptions
                : memberOptions.filter(option => option.label.toLowerCase().includes(normalized));
            const exactMatch = normalized
                ? filtered.find(option => option.label.toLowerCase() === normalized)
                : null;
            const autoSelectedValue = exactMatch
                ? exactMatch.value
                : (filtered.length === 1 ? filtered[0].value : '');

            memberSelect.innerHTML = '';
            const placeholder = document.createElement('option');
            placeholder.value = '';
            placeholder.textContent = filtered.length ? baseMemberPlaceholder : 'No matching member found';
            memberSelect.appendChild(placeholder);

            filtered.forEach((option) => {
                const el = document.createElement('option');
                el.value = option.value;
                el.textContent = option.label;
                if (option.value === currentValue || (autoSelectedValue && option.value === autoSelectedValue)) {
                    el.selected = true;
                }
                memberSelect.appendChild(el);
            });

            if (currentValue && !filtered.some(option => option.value === currentValue)) {
                memberSelect.value = '';
            }
            if (autoSelectedValue) {
                memberSelect.value = autoSelectedValue;
                const selected = memberOptions.find(option => option.value === autoSelectedValue);
                if (memberSearch && selected) {
                    memberSearch.value = selected.label;
                }
            }
        };

        const renderSummary = (data) => {
            if (!summaryWrap || !summaryLoading || !summaryBody) return;
            const income = Number(data?.income_total || 0);
            const expense = Number(data?.expense_total || 0);
            const net = Number(data?.net_total || 0);
            const byType = Array.isArray(data?.by_type) ? data.by_type : [];

            summaryLoading.classList.add('hidden');
            summaryBody.classList.remove('hidden');
            summaryBody.innerHTML = `
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div class="bg-white/5 rounded-2xl p-4 border border-white/5">
                        <p class="text-[9px] font-black text-slate-500 uppercase tracking-widest">Income</p>
                        <p class="text-sm font-black text-emerald-400 mt-2">${currency} ${income.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</p>
                    </div>
                    <div class="bg-white/5 rounded-2xl p-4 border border-white/5">
                        <p class="text-[9px] font-black text-slate-500 uppercase tracking-widest">Expenses</p>
                        <p class="text-sm font-black text-rose-400 mt-2">${currency} ${expense.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</p>
                    </div>
                    <div class="bg-white/5 rounded-2xl p-4 border border-white/5">
                        <p class="text-[9px] font-black text-slate-500 uppercase tracking-widest">Net</p>
                        <p class="text-sm font-black ${net >= 0 ? 'text-accent' : 'text-rose-400'} mt-2">${currency} ${net.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</p>
                    </div>
                </div>
                <div class="mt-5 space-y-2">
                    ${byType.length ? byType.map(r => `
                        <div class="flex items-center justify-between bg-white/5 rounded-2xl p-4 border border-white/5">
                            <span class="text-[10px] font-black uppercase tracking-widest text-slate-300">${(r.transaction_type || '').replace(/_/g,' ')}</span>
                            <span class="text-xs font-black text-slate-200">${currency} ${Number(r.total || 0).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</span>
                        </div>
                    `).join('') : '<div class="text-slate-500 text-xs font-bold italic">No member transactions found.</div>'}
                </div>
            `;
        };

        const loadMemberSummary = () => {
            if (!memberSelect || !summaryWrap || !summaryLoading || !summaryBody) return;
            const memberId = Number(memberSelect.value || 0);
            if (!memberId || memberSelect.disabled) {
                summaryWrap.classList.add('hidden');
                return;
            }
            summaryWrap.classList.remove('hidden');
            summaryLoading.classList.remove('hidden');
            summaryBody.classList.add('hidden');
            summaryBody.innerHTML = '';

            fetch(`<?php echo BASE_URL; ?>/finance/memberSummary?member_id=${encodeURIComponent(memberId)}`)
                .then(r => r.json())
                .then(renderSummary)
                .catch(() => {
                    summaryLoading.classList.add('hidden');
                    summaryBody.classList.remove('hidden');
                    summaryBody.innerHTML = '<div class="text-slate-500 text-xs font-bold italic">Unable to load member totals.</div>';
                });
        };

        const syncOfferingRules = () => {
            if (!departmentSelect) return;
            const isIncome = typeInput.value !== 'Expense';
            const selectedType = incomeCategory.value || '';
            const isOffering = selectedType === 'Offering';
            const requiresDepartment = selectedType === 'Departmental Savings';
            const requiresMember = selectedType === 'Tithe' || selectedType === 'Welfare';
            const isChurchOnlyIncome = isIncome && !isOffering && !requiresDepartment && !requiresMember;
            const offeringSubtypeWrap = document.getElementById('offering-subtype-wrap');
            const offeringSubtype = document.getElementById('offering_subtype');

            if (!isIncome) {
                departmentSelect.value = '';
                departmentSelect.disabled = true;
                departmentSelect.classList.add('opacity-60', 'cursor-not-allowed');
                if (memberSelect) {
                    memberSelect.value = '';
                    memberSelect.disabled = true;
                    memberSelect.classList.add('opacity-60', 'cursor-not-allowed');
                }
                if (memberSearch) {
                    memberSearch.value = '';
                    memberSearch.disabled = true;
                    memberSearch.classList.add('opacity-60', 'cursor-not-allowed');
                    rebuildMemberOptions('');
                    hideMemberSuggestions();
                }
                if (summaryWrap) summaryWrap.classList.add('hidden');
                if (offeringSubtypeWrap) offeringSubtypeWrap.classList.add('hidden');
                if (offeringSubtype) offeringSubtype.required = false;
                return;
            }

            if (offeringSubtypeWrap) {
                if (isOffering) {
                    offeringSubtypeWrap.classList.remove('hidden');
                    if (offeringSubtype) offeringSubtype.required = true;
                } else {
                    offeringSubtypeWrap.classList.add('hidden');
                    if (offeringSubtype) offeringSubtype.required = false;
                }
            }

            if (isIncome && isOffering) {
                departmentSelect.value = '';
                departmentSelect.disabled = true;
                departmentSelect.classList.add('opacity-60', 'cursor-not-allowed');
                if (memberSelect) {
                    memberSelect.value = '';
                    memberSelect.disabled = true;
                    memberSelect.classList.add('opacity-60', 'cursor-not-allowed');
                }
                if (memberSearch) {
                    memberSearch.value = '';
                    memberSearch.disabled = true;
                    memberSearch.classList.add('opacity-60', 'cursor-not-allowed');
                    rebuildMemberOptions('');
                    hideMemberSuggestions();
                }
                if (summaryWrap) summaryWrap.classList.add('hidden');
                return;
            }

            if (isChurchOnlyIncome) {
                departmentSelect.value = '';
                departmentSelect.disabled = true;
                departmentSelect.classList.add('opacity-60', 'cursor-not-allowed');
                if (memberSelect) {
                    memberSelect.value = '';
                    memberSelect.disabled = true;
                    memberSelect.classList.add('opacity-60', 'cursor-not-allowed');
                }
                if (memberSearch) {
                    memberSearch.value = '';
                    memberSearch.disabled = true;
                    memberSearch.classList.add('opacity-60', 'cursor-not-allowed');
                    rebuildMemberOptions('');
                    hideMemberSuggestions();
                }
                if (summaryWrap) summaryWrap.classList.add('hidden');
                return;
            }

            departmentSelect.disabled = false;
            departmentSelect.classList.remove('opacity-60', 'cursor-not-allowed');
            if (memberSelect) {
                memberSelect.disabled = false;
                memberSelect.classList.remove('opacity-60', 'cursor-not-allowed');
            }
            if (memberSearch) {
                memberSearch.disabled = false;
                memberSearch.classList.remove('opacity-60', 'cursor-not-allowed');
            }

            if (isStaff && !requiresDepartment) {
                departmentSelect.value = '';
                departmentSelect.disabled = true;
                departmentSelect.classList.add('opacity-60', 'cursor-not-allowed');
            }

            if (isStaff && memberSelect) {
                if (requiresMember) {
                    memberSelect.disabled = false;
                    memberSelect.classList.remove('opacity-60', 'cursor-not-allowed');
                    if (memberSearch) {
                        memberSearch.disabled = false;
                        memberSearch.classList.remove('opacity-60', 'cursor-not-allowed');
                    }
                } else {
                    memberSelect.value = '';
                    memberSelect.disabled = true;
                    memberSelect.classList.add('opacity-60', 'cursor-not-allowed');
                    if (memberSearch) {
                        memberSearch.value = '';
                        memberSearch.disabled = true;
                        memberSearch.classList.add('opacity-60', 'cursor-not-allowed');
                        rebuildMemberOptions('');
                        hideMemberSuggestions();
                    }
                    if (summaryWrap) summaryWrap.classList.add('hidden');
                }
            }

            const hasDept = String(departmentSelect.value || '') !== '';
            const hasMember = memberSelect ? String(memberSelect.value || '') !== '' : false;

            if (hasDept && memberSelect && !isStaff) {
                memberSelect.value = '';
                memberSelect.disabled = true;
                memberSelect.classList.add('opacity-60', 'cursor-not-allowed');
                if (summaryWrap) summaryWrap.classList.add('hidden');
            } else if (hasMember && !isStaff) {
                departmentSelect.value = '';
            }

            loadMemberSummary();
        };

        const enforceDeptHead = () => {
            const isDeptHead = <?php echo !empty($isDeptHead) ? 'true' : 'false'; ?>;
            if (!isDeptHead) return;
            if (departmentSelect) {
                departmentSelect.disabled = true;
            }
            if (paymentSelect && isDeptHead) {
                paymentSelect.value = 'Bank Transfer';
            }
        };

        if (departmentSelect) {
            departmentSelect.addEventListener('change', syncOfferingRules);
        }
        const setMode = (mode) => {
            const isExpense = mode === 'expense';
            if (isExpense) {
                typeInput.value = 'Expense';
                incomeWrap.classList.add('hidden');
                if (modeExpense) {
                modeExpense.classList.remove('bg-white/5', 'text-slate-300', 'border-white/10');
                modeExpense.classList.add('bg-rose-500/10', 'text-rose-300', 'border-rose-500/20');
                }
                modeIncome.classList.remove('bg-emerald-500/10', 'text-emerald-300', 'border-emerald-500/20');
                modeIncome.classList.add('bg-white/5', 'text-slate-300', 'border-white/10');
            } else {
                typeInput.value = incomeCategory.value || 'Offering';
                incomeWrap.classList.remove('hidden');
                modeIncome.classList.remove('bg-white/5', 'text-slate-300', 'border-white/10');
                modeIncome.classList.add('bg-emerald-500/10', 'text-emerald-300', 'border-emerald-500/20');
                if (modeExpense) {
                    modeExpense.classList.remove('bg-rose-500/10', 'text-rose-300', 'border-rose-500/20');
                    modeExpense.classList.add('bg-white/5', 'text-slate-300', 'border-white/10');
                }
            }
            syncOfferingRules();
        };

        modeIncome.addEventListener('click', () => setMode('income'));
        if (modeExpense) {
            modeExpense.addEventListener('click', () => setMode('expense'));
        }
        incomeCategory.addEventListener('change', () => {
            if (typeInput.value !== 'Expense') typeInput.value = incomeCategory.value || 'Offering';
            syncOfferingRules();
            syncIncomeTypeButtons();
        });
        if (memberSelect) {
            memberSelect.addEventListener('change', () => {
                const selected = memberOptions.find(option => option.value === String(memberSelect.value || ''));
                if (memberSearch) {
                    memberSearch.value = selected ? selected.label : '';
                }
                loadMemberSummary();
            });
        }
        if (memberSearch) {
            memberSearch.addEventListener('input', () => {
                renderMemberSuggestions(memberSearch.value);
                rebuildMemberOptions(memberSearch.value);
                loadMemberSummary();
            });
            memberSearch.addEventListener('focus', () => {
                renderMemberSuggestions(memberSearch.value);
            });
            memberSearch.addEventListener('blur', () => {
                setTimeout(hideMemberSuggestions, 150);
            });
        }
        document.addEventListener('click', (event) => {
            if (!memberSearchResults || !memberSearch) return;
            if (!memberSearch.contains(event.target) && !memberSearchResults.contains(event.target)) {
                hideMemberSuggestions();
            }
        });

        if (isStaff && amountInput) {
            document.querySelectorAll('.calc-quick').forEach((button) => {
                button.addEventListener('click', () => {
                    amountInput.value = button.dataset.value || '';
                    amountInput.focus();
                });
            });
            document.querySelectorAll('.calc-key').forEach((button) => {
                button.addEventListener('click', () => {
                    const current = String(amountInput.value || '');
                    const key = button.dataset.key || '';
                    if (key === '.' && current.includes('.')) return;
                    amountInput.value = current + key;
                    amountInput.focus();
                });
            });
            const clearBtn = document.getElementById('calc-clear');
            if (clearBtn) {
                clearBtn.addEventListener('click', () => {
                    amountInput.value = '';
                    amountInput.focus();
                });
            }
        }

        const initialType = <?php echo json_encode((string)$defaultType); ?>;
        if (String(initialType || '').toLowerCase() === 'expense') {
            setMode('expense');
        } else {
            setMode('income');
        }
        syncIncomeTypeButtons();
        rebuildMemberOptions('');
        enforceDeptHead();
    })();
</script>
