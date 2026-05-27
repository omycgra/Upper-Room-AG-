<?php
    $churchName = AppConfig::getSetting('church_name', 'Church Management');
    $currency = strtoupper(trim($bank['currency'] ?? 'GHS'));
    if (!preg_match('/^[A-Z]{2,5}$/', $currency)) $currency = 'GHS';
    $deptHeadTotals = $department_head_totals ?? null;
    $isStaff = !empty($isStaff);
    $staffTotals = $staff_totals ?? null;
    $receiptData = $receipt_data ?? null;
    $generalAllTime = $general_all_time ?? null;
    $departmentAllTime = $department_all_time ?? null;
    $combinedAllTime = $combined_all_time ?? null;
    $pendingChangeRequests = $pending_change_requests ?? [];
    $pendingDeptExpenseRequests = $pending_department_expense_requests ?? [];
    $myChangeRequests = $my_change_requests ?? [];
    $activeChangeRequestMap = $active_change_request_map ?? [];
    $approvedChangeRequestMap = $approved_change_request_map ?? [];
    $recentExpenses = $recent_expenses ?? [];
    $expFrom = trim((string)($exp_from ?? ''));
    $expTo = trim((string)($exp_to ?? ''));
?>

<div class="flex flex-col sm:flex-row justify-between items-start mb-10 gap-4">
    <div>
        <h2 class="text-4xl sm:text-5xl font-black text-white tracking-tighter">Finance</h2>
        <p class="text-slate-400 font-bold mt-2 uppercase tracking-widest text-xs"><?php echo $isStaff ? 'Your cashier transactions and member receipts for' : 'Financial dashboard for'; ?> <span class="text-accent"><?php echo htmlspecialchars($churchName); ?></span></p>
    </div>
    <?php if (empty($isDeptHead)): ?>
    <a href="<?php echo BASE_URL; ?>/finance/add" class="glass-card flex items-center px-6 py-3.5 rounded-2xl bg-accent text-slate-900 font-black text-xs uppercase tracking-widest hover:scale-[1.03] transition-all shadow-xl shadow-yellow-500/20">
        <i class="fas fa-plus mr-2"></i> <?php echo $isStaff ? 'Record Transaction' : 'Add Transaction'; ?>
    </a>
    <?php endif; ?>
</div>

<div class="glass-card rounded-[3rem] border-white/5 overflow-hidden mb-10 card-interaction">
    <div class="px-8 sm:px-10 py-8 sm:py-10 bg-slate-900/40 border-b border-white/5">
        <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-6">
            <div class="flex flex-wrap items-center gap-3">
                <div class="px-4 py-2 rounded-2xl bg-white/5 border border-white/10 text-[10px] font-black uppercase tracking-widest text-slate-300">
                    <i class="far fa-calendar-alt mr-2 text-accent"></i> <?php echo htmlspecialchars($month_label ?? date('F Y')); ?>
                </div>
                <div class="px-4 py-2 rounded-2xl bg-white/5 border border-white/10 text-[10px] font-black uppercase tracking-widest <?php echo in_array(($health ?? 'Good'), ['Good', 'Active'], true) ? 'text-emerald-400' : 'text-rose-400'; ?>">
                    <i class="fas fa-heart-pulse mr-2 <?php echo in_array(($health ?? 'Good'), ['Good', 'Active'], true) ? 'text-emerald-400' : 'text-rose-400'; ?>"></i>
                    Financial Health: <?php echo htmlspecialchars($health ?? 'Good'); ?>
                </div>
                <div class="px-4 py-2 rounded-2xl bg-white/5 border border-white/10 text-[10px] font-black uppercase tracking-widest text-accent">
                    <i class="fas fa-scale-balanced mr-2"></i> Balance: <?php echo $currency . ' ' . number_format((float)($monthly_balance ?? 0), 2); ?>
                </div>
                <?php if (!empty($isDeptHead)): ?>
                    <div class="px-4 py-2 rounded-2xl bg-white/5 border border-white/10 text-[10px] font-black uppercase tracking-widest text-slate-300">
                        <i class="fas fa-receipt mr-2 text-accent"></i> Total Transactions: <?php echo number_format((int)(($deptHeadTotals['transaction_count'] ?? 0))); ?>
                    </div>
                <?php elseif ($isStaff): ?>
                    <div class="px-4 py-2 rounded-2xl bg-white/5 border border-white/10 text-[10px] font-black uppercase tracking-widest text-slate-300">
                        <i class="fas fa-receipt mr-2 text-accent"></i> Your Receipts: <?php echo number_format((int)(($staffTotals['transaction_count'] ?? 0))); ?>
                    </div>
                <?php endif; ?>
            </div>
            <div class="flex flex-wrap gap-3">
                <a href="<?php echo BASE_URL; ?>/transactions" class="glass-card px-5 py-3 rounded-2xl border-white/10 text-slate-300 font-black text-[10px] uppercase tracking-widest hover:bg-white/5 transition-all">
                    <i class="fas fa-receipt mr-2 text-accent"></i> Transactions
                </a>
                <?php if ($isStaff): ?>
                <a href="<?php echo BASE_URL; ?>/finance/add" class="glass-card px-5 py-3 rounded-2xl border-white/10 text-slate-300 font-black text-[10px] uppercase tracking-widest hover:bg-white/5 transition-all">
                    <i class="fas fa-calculator mr-2 text-accent"></i> Quick Entry
                </a>
                <a href="<?php echo BASE_URL; ?>/department-savings" class="glass-card px-5 py-3 rounded-2xl border-white/10 text-slate-300 font-black text-[10px] uppercase tracking-widest hover:bg-white/5 transition-all">
                    <i class="fas fa-sitemap mr-2 text-accent"></i> Departmental Savings
                </a>
                <?php else: ?>
                <a href="<?php echo BASE_URL; ?>/department-savings" class="glass-card px-5 py-3 rounded-2xl border-white/10 text-slate-300 font-black text-[10px] uppercase tracking-widest hover:bg-white/5 transition-all">
                    <i class="fas fa-sitemap mr-2 text-accent"></i> Departmental Savings
                </a>
                <a href="#bank-details" class="glass-card px-5 py-3 rounded-2xl border-white/10 text-slate-300 font-black text-[10px] uppercase tracking-widest hover:bg-white/5 transition-all">
                    <i class="fas fa-building-columns mr-2 text-accent"></i> Bank Details
                </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php if ($isStaff && !empty($staffTotals)): ?>
<div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-6 mb-12">
    <div class="glass-card rounded-[2.5rem] p-8 border-white/5 card-interaction">
        <div class="w-14 h-14 bg-white/5 rounded-2xl flex items-center justify-center mb-8 border border-white/10">
            <i class="fas fa-sack-dollar text-accent text-xl"></i>
        </div>
        <p class="text-[10px] font-black uppercase tracking-[0.3em] text-slate-400">Collected Today</p>
        <h3 class="text-3xl font-black mt-2 text-emerald-400 tracking-tighter"><?php echo $currency . ' ' . number_format((float)($staffTotals['today_total'] ?? 0), 2); ?></h3>
        <p class="text-[10px] font-black text-slate-500 uppercase tracking-widest mt-6">Your payments today</p>
    </div>
    <div class="glass-card rounded-[2.5rem] p-8 border-white/5 card-interaction">
        <div class="w-14 h-14 bg-white/5 rounded-2xl flex items-center justify-center mb-8 border border-white/10">
            <i class="fas fa-calendar-check text-accent text-xl"></i>
        </div>
        <p class="text-[10px] font-black uppercase tracking-[0.3em] text-slate-400">This Month</p>
        <h3 class="text-3xl font-black mt-2 text-white tracking-tighter"><?php echo $currency . ' ' . number_format((float)($staffTotals['month_total'] ?? 0), 2); ?></h3>
        <p class="text-[10px] font-black text-slate-500 uppercase tracking-widest mt-6"><?php echo htmlspecialchars($month_label ?? date('F Y')); ?></p>
    </div>
    <div class="glass-card rounded-[2.5rem] p-8 border-white/5 card-interaction">
        <div class="w-14 h-14 bg-white/5 rounded-2xl flex items-center justify-center mb-8 border border-white/10">
            <i class="fas fa-wallet text-accent text-xl"></i>
        </div>
        <p class="text-[10px] font-black uppercase tracking-[0.3em] text-slate-400">All Time</p>
        <h3 class="text-3xl font-black mt-2 text-accent tracking-tighter"><?php echo $currency . ' ' . number_format((float)($staffTotals['overall_total'] ?? 0), 2); ?></h3>
        <p class="text-[10px] font-black text-slate-500 uppercase tracking-widest mt-6">Everything recorded by you</p>
    </div>
    <div class="glass-card rounded-[2.5rem] p-8 border-white/5 card-interaction">
        <div class="w-14 h-14 bg-white/5 rounded-2xl flex items-center justify-center mb-8 border border-white/10">
            <i class="fas fa-print text-accent text-xl"></i>
        </div>
        <p class="text-[10px] font-black uppercase tracking-[0.3em] text-slate-400">Receipts</p>
        <h3 class="text-3xl font-black mt-2 text-white tracking-tighter"><?php echo number_format((int)($staffTotals['transaction_count'] ?? 0)); ?></h3>
        <p class="text-[10px] font-black text-slate-500 uppercase tracking-widest mt-6">Ready to view and print</p>
    </div>
</div>
<?php else: ?>
<div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-12">
    <div class="glass-card relative overflow-hidden rounded-[2.5rem] p-8 group border-white/5 card-interaction">
        <div class="relative z-10">
            <div class="w-14 h-14 bg-white/5 rounded-2xl flex items-center justify-center mb-8 border border-white/10 group-hover:bg-emerald-500 transition-all duration-500">
                <i class="fas fa-arrow-trend-up text-slate-400 group-hover:text-white text-xl transition-colors"></i>
            </div>
            <p class="text-[10px] font-black uppercase tracking-[0.3em] text-slate-400">Total Income</p>
            <h3 class="text-3xl font-black mt-2 text-white tracking-tighter"><?php echo $currency . ' ' . number_format((float)($monthly_income ?? 0), 2); ?></h3>
            <p class="text-[10px] font-black text-slate-500 uppercase tracking-widest mt-6">This month</p>
        </div>
        <div class="absolute -bottom-10 -right-10 w-32 h-32 bg-emerald-500/10 rounded-full blur-3xl group-hover:bg-emerald-500/15 transition-all"></div>
    </div>

    <div class="glass-card relative overflow-hidden rounded-[2.5rem] p-8 group border-white/5 card-interaction">
        <div class="relative z-10">
            <div class="w-14 h-14 bg-white/5 rounded-2xl flex items-center justify-center mb-8 border border-white/10 group-hover:bg-rose-500 transition-all duration-500">
                <i class="fas fa-arrow-trend-down text-slate-400 group-hover:text-white text-xl transition-colors"></i>
            </div>
            <p class="text-[10px] font-black uppercase tracking-[0.3em] text-slate-400">Total Expenses</p>
            <h3 class="text-3xl font-black mt-2 text-white tracking-tighter"><?php echo $currency . ' ' . number_format((float)($monthly_expenses ?? 0), 2); ?></h3>
            <p class="text-[10px] font-black text-slate-500 uppercase tracking-widest mt-6">This month</p>
        </div>
        <div class="absolute -bottom-10 -right-10 w-32 h-32 bg-rose-500/10 rounded-full blur-3xl group-hover:bg-rose-500/15 transition-all"></div>
    </div>

    <div class="glass-card relative overflow-hidden rounded-[2.5rem] p-8 group border-white/5 card-interaction">
        <div class="relative z-10">
            <div class="w-14 h-14 bg-white/5 rounded-2xl flex items-center justify-center mb-8 border border-white/10 group-hover:bg-accent transition-all duration-500">
                <i class="fas fa-wallet text-slate-400 group-hover:text-slate-900 text-xl transition-colors"></i>
            </div>
            <p class="text-[10px] font-black uppercase tracking-[0.3em] text-slate-400">Net Balance</p>
            <h3 class="text-3xl font-black mt-2 <?php echo (float)($monthly_balance ?? 0) >= 0 ? 'text-accent' : 'text-rose-400'; ?> tracking-tighter">
                <?php echo $currency . ' ' . number_format((float)($monthly_balance ?? 0), 2); ?>
            </h3>
            <p class="text-[10px] font-black text-slate-500 uppercase tracking-widest mt-6">This month</p>
        </div>
        <div class="absolute -bottom-10 -right-10 w-32 h-32 bg-accent/10 rounded-full blur-3xl group-hover:bg-accent/15 transition-all"></div>
    </div>
</div>
<?php endif; ?>

<?php if ($isStaff): ?>
<div class="glass-card rounded-[2.5rem] sm:rounded-[3rem] border-white/5 overflow-hidden mb-12 card-interaction">
    <div class="px-6 sm:px-8 lg:px-10 py-6 sm:py-8 border-b border-white/5 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 bg-white/[0.02]">
        <div class="flex items-center">
            <div class="w-10 h-10 bg-accent/10 rounded-xl flex items-center justify-center mr-4 border border-accent/20">
                <i class="fas fa-clipboard-check text-accent text-sm"></i>
            </div>
            <h4 class="text-xl font-black text-white tracking-tight">Edit Requests</h4>
        </div>
        <div class="text-[10px] font-black text-slate-500 uppercase tracking-widest"><?php echo count($myChangeRequests ?? []); ?> request<?php echo count($myChangeRequests ?? []) === 1 ? '' : 's'; ?></div>
    </div>
    <div class="p-5 sm:p-6 lg:p-10">
        <?php if (empty($myChangeRequests)): ?>
            <div class="px-4 py-12 text-center text-slate-500 italic font-bold">No edit requests yet. Use “Request Edit” on a transaction when you need a correction.</div>
        <?php else: ?>
            <div class="space-y-4">
                <?php foreach ($myChangeRequests as $r): ?>
                    <?php
                        $status = strtolower(trim((string)($r['status'] ?? 'pending')));
                        $statusClass = $status === 'approved'
                            ? 'bg-emerald-500/10 text-emerald-400 border-emerald-500/20'
                            : ($status === 'rejected' ? 'bg-rose-500/10 text-rose-400 border-rose-500/20' : 'bg-white/5 text-slate-300 border-white/10');
                        $canEdit = $status === 'approved' && empty($r['fulfilled_at']);
                    ?>
                    <div class="glass-card rounded-[2rem] p-5 border-white/10 flex flex-col lg:flex-row lg:items-center justify-between gap-4">
                        <div class="min-w-0">
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="px-3 py-1.5 text-[9px] font-black rounded-full uppercase tracking-widest border <?php echo $statusClass; ?>"><?php echo htmlspecialchars($status); ?></span>
                                <span class="text-sm font-black text-slate-200"><?php echo htmlspecialchars($r['transaction_type'] ?? ''); ?></span>
                                <span class="text-sm font-black text-slate-400"><?php echo $currency . ' ' . number_format((float)($r['current_amount'] ?? 0), 2); ?></span>
                                <span class="text-xs font-bold text-slate-500"><?php echo htmlspecialchars($r['transaction_number'] ?? ''); ?></span>
                            </div>
                            <p class="text-xs font-bold text-slate-400 mt-2 line-clamp-2"><?php echo htmlspecialchars($r['reason'] ?? ''); ?></p>
                        </div>
                        <div class="flex items-center gap-2 justify-end">
                            <?php if ($canEdit): ?>
                                <button type="button" onclick="openEditTransactionModal(<?php echo (int)($r['finance_id'] ?? 0); ?>)" class="h-10 px-5 inline-flex items-center justify-center rounded-xl bg-accent text-slate-900 font-black text-[10px] uppercase tracking-widest hover:scale-[1.02] transition-all">
                                    <i class="fas fa-pen-nib text-xs mr-2"></i> Edit Now
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>
<?php endif; ?>

<?php if (empty($isDeptHead) && !empty($combinedAllTime)): ?>
<div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-12">
    <div class="glass-card relative overflow-hidden rounded-[2.5rem] p-8 group border-white/5 card-interaction">
        <div class="relative z-10">
            <div class="w-14 h-14 bg-white/5 rounded-2xl flex items-center justify-center mb-8 border border-white/10 group-hover:bg-sky-500 transition-all duration-500">
                <i class="fas fa-church text-slate-400 group-hover:text-white text-xl transition-colors"></i>
            </div>
            <p class="text-[10px] font-black uppercase tracking-[0.3em] text-slate-400">General Church Balance</p>
            <h3 class="text-3xl font-black mt-2 text-white tracking-tighter"><?php echo $currency . ' ' . number_format((float)($generalAllTime['balance'] ?? 0), 2); ?></h3>
            <p class="text-[10px] font-black text-slate-500 uppercase tracking-widest mt-6">All time (excluding departments)</p>
        </div>
        <div class="absolute -bottom-10 -right-10 w-32 h-32 bg-sky-500/10 rounded-full blur-3xl group-hover:bg-sky-500/15 transition-all"></div>
    </div>

    <div class="glass-card relative overflow-hidden rounded-[2.5rem] p-8 group border-white/5 card-interaction">
        <div class="relative z-10">
            <div class="w-14 h-14 bg-white/5 rounded-2xl flex items-center justify-center mb-8 border border-white/10 group-hover:bg-violet-500 transition-all duration-500">
                <i class="fas fa-sitemap text-slate-400 group-hover:text-white text-xl transition-colors"></i>
            </div>
            <p class="text-[10px] font-black uppercase tracking-[0.3em] text-slate-400">Departments Total Balance</p>
            <h3 class="text-3xl font-black mt-2 text-white tracking-tighter"><?php echo $currency . ' ' . number_format((float)($departmentAllTime['balance'] ?? 0), 2); ?></h3>
            <p class="text-[10px] font-black text-slate-500 uppercase tracking-widest mt-6">All time (all departments)</p>
        </div>
        <div class="absolute -bottom-10 -right-10 w-32 h-32 bg-violet-500/10 rounded-full blur-3xl group-hover:bg-violet-500/15 transition-all"></div>
    </div>

    <div class="glass-card relative overflow-hidden rounded-[2.5rem] p-8 group border-white/5 card-interaction">
        <div class="relative z-10">
            <div class="w-14 h-14 bg-white/5 rounded-2xl flex items-center justify-center mb-8 border border-white/10 group-hover:bg-accent transition-all duration-500">
                <i class="fas fa-scale-balanced text-slate-400 group-hover:text-slate-900 text-xl transition-colors"></i>
            </div>
            <p class="text-[10px] font-black uppercase tracking-[0.3em] text-slate-400">Total Church Balance</p>
            <h3 class="text-3xl font-black mt-2 text-accent tracking-tighter"><?php echo $currency . ' ' . number_format((float)($combinedAllTime['balance'] ?? 0), 2); ?></h3>
            <p class="text-[10px] font-black text-slate-500 uppercase tracking-widest mt-6">General + Departments</p>
        </div>
        <div class="absolute -bottom-10 -right-10 w-32 h-32 bg-accent/10 rounded-full blur-3xl group-hover:bg-accent/15 transition-all"></div>
    </div>

    <div class="glass-card relative overflow-hidden rounded-[2.5rem] p-8 group border-white/5 card-interaction">
        <div class="relative z-10">
            <div class="w-14 h-14 bg-white/5 rounded-2xl flex items-center justify-center mb-8 border border-white/10 group-hover:bg-rose-500 transition-all duration-500">
                <i class="fas fa-arrow-trend-down text-slate-400 group-hover:text-white text-xl transition-colors"></i>
            </div>
            <p class="text-[10px] font-black uppercase tracking-[0.3em] text-slate-400">Total Church Expenses</p>
            <h3 class="text-3xl font-black mt-2 text-rose-400 tracking-tighter"><?php echo $currency . ' ' . number_format((float)($combinedAllTime['expense_total'] ?? 0), 2); ?></h3>
            <p class="text-[10px] font-black text-slate-500 uppercase tracking-widest mt-6">All time (includes departments)</p>
        </div>
        <div class="absolute -bottom-10 -right-10 w-32 h-32 bg-rose-500/10 rounded-full blur-3xl group-hover:bg-rose-500/15 transition-all"></div>
    </div>
</div>
<?php endif; ?>

<?php if (empty($isDeptHead)): ?>
<div id="recent-expenses" class="glass-card rounded-[2.5rem] sm:rounded-[3rem] border-white/5 overflow-hidden mb-12 card-interaction">
    <div class="px-6 sm:px-8 lg:px-10 py-6 sm:py-8 border-b border-white/5 flex flex-col lg:flex-row lg:items-center justify-between gap-4 bg-white/[0.02]">
        <div class="flex items-center">
            <div class="w-10 h-10 bg-rose-500/10 rounded-xl flex items-center justify-center mr-4 border border-rose-500/20">
                <i class="fas fa-receipt text-rose-300 text-sm"></i>
            </div>
            <h4 class="text-xl font-black text-white tracking-tight">Recent Expenses</h4>
        </div>
        <div class="w-full lg:w-auto flex flex-col sm:flex-row gap-3 sm:items-end sm:justify-end">
            <form method="GET" action="<?php echo BASE_URL; ?>/finance" class="flex flex-col sm:flex-row gap-3 items-stretch sm:items-end">
                <div>
                    <label class="block text-[9px] font-black text-slate-500 uppercase tracking-widest ml-1 mb-2">From</label>
                    <input type="date" name="exp_from" value="<?php echo htmlspecialchars($expFrom); ?>" class="w-full bg-white/5 border border-white/10 focus:border-rose-400 rounded-2xl px-5 py-3 text-sm font-bold text-white transition-all outline-none">
                </div>
                <div>
                    <label class="block text-[9px] font-black text-slate-500 uppercase tracking-widest ml-1 mb-2">To</label>
                    <input type="date" name="exp_to" value="<?php echo htmlspecialchars($expTo); ?>" class="w-full bg-white/5 border border-white/10 focus:border-rose-400 rounded-2xl px-5 py-3 text-sm font-bold text-white transition-all outline-none">
                </div>
                <button type="submit" class="glass-card px-6 py-3.5 rounded-2xl border-white/10 text-slate-300 font-black text-[10px] uppercase tracking-widest hover:bg-white/5 transition-all">
                    <i class="fas fa-search mr-2 text-rose-300"></i> Search
                </button>
            </form>
            <?php if (Auth::isStaff() || Auth::isAdmin() || Auth::isAuditor()): ?>
                <div class="flex gap-2">
                    <a href="<?php echo BASE_URL; ?>/finance/downloadExpenses?mode=all" class="glass-card px-5 py-3.5 rounded-2xl border-white/10 text-slate-300 font-black text-[10px] uppercase tracking-widest hover:bg-white/5 transition-all inline-flex items-center">
                        <i class="fas fa-download mr-2 text-rose-300"></i> Download All
                    </a>
                    <a href="<?php echo BASE_URL; ?>/finance/downloadExpenses?mode=date&from=<?php echo urlencode($expFrom); ?>&to=<?php echo urlencode($expTo !== '' ? $expTo : $expFrom); ?>" class="glass-card px-5 py-3.5 rounded-2xl border-white/10 text-slate-300 font-black text-[10px] uppercase tracking-widest hover:bg-white/5 transition-all inline-flex items-center">
                        <i class="fas fa-calendar-day mr-2 text-rose-300"></i> Download By Date
                    </a>
                </div>
            <?php endif; ?>
            <div class="text-[10px] font-black text-slate-500 uppercase tracking-widest sm:self-center lg:self-end"><?php echo count($recentExpenses ?? []); ?> records</div>
        </div>
    </div>
    <div class="p-5 sm:p-6 lg:p-10">
        <?php if (empty($recentExpenses)): ?>
            <div class="px-4 py-12 text-center text-slate-500 italic font-bold">No expenses recorded yet.</div>
        <?php else: ?>
            <div class="overflow-x-auto custom-scrollbar">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="text-[10px] font-black text-slate-500 uppercase tracking-[0.3em] border-b border-white/5 bg-white/[0.01]">
                            <th class="px-6 py-5">Date</th>
                            <th class="px-6 py-5">Amount</th>
                            <th class="px-6 py-5 hidden lg:table-cell">Department</th>
                            <th class="px-6 py-5 hidden lg:table-cell">Reference</th>
                            <th class="px-6 py-5">Recorded By</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-white/[0.02]">
                        <?php foreach ($recentExpenses as $tx): ?>
                            <tr class="hover:bg-white/[0.03] transition-all duration-300">
                                <td class="px-6 py-5">
                                    <p class="text-sm font-black text-slate-200"><?php echo htmlspecialchars(date('M d, Y', strtotime($tx['transaction_date']))); ?></p>
                                    <p class="text-[10px] font-black text-slate-500 uppercase tracking-widest mt-1"><?php echo htmlspecialchars($tx['transaction_number'] ?? ''); ?></p>
                                </td>
                                <td class="px-6 py-5">
                                    <p class="text-sm font-black text-rose-400"><?php echo $currency . ' ' . number_format((float)($tx['amount'] ?? 0), 2); ?></p>
                                </td>
                                <td class="px-6 py-5 hidden lg:table-cell">
                                    <p class="text-xs font-bold text-slate-400"><?php echo htmlspecialchars(($tx['department_name'] ?? '') !== '' ? $tx['department_name'] : 'Church'); ?></p>
                                </td>
                                <td class="px-6 py-5 hidden lg:table-cell">
                                    <p class="text-xs font-bold text-slate-400"><?php echo htmlspecialchars(($tx['reference_no'] ?? '') !== '' ? $tx['reference_no'] : 'N/A'); ?></p>
                                </td>
                                <td class="px-6 py-5">
                                    <p class="text-sm font-black text-slate-200"><?php echo htmlspecialchars($tx['recorded_by_name'] ?? ''); ?></p>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>
<?php endif; ?>

<?php if (!empty($isDeptHead) && !empty($deptHeadTotals)): ?>
    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-6 mb-12">
        <div class="glass-card rounded-[2.5rem] p-8 border-white/5 card-interaction">
            <div class="w-14 h-14 bg-white/5 rounded-2xl flex items-center justify-center mb-8 border border-white/10">
                <i class="fas fa-sack-dollar text-accent text-xl"></i>
            </div>
            <p class="text-[10px] font-black uppercase tracking-[0.3em] text-slate-400">Department Total Money In</p>
            <h3 class="text-3xl font-black mt-2 text-emerald-400 tracking-tighter">
                <?php echo $currency . ' ' . number_format((float)($deptHeadTotals['income_total'] ?? 0), 2); ?>
            </h3>
            <p class="text-[10px] font-black text-slate-500 uppercase tracking-widest mt-6">All bank transfers</p>
        </div>

        <div class="glass-card rounded-[2.5rem] p-8 border-white/5 card-interaction">
            <div class="w-14 h-14 bg-white/5 rounded-2xl flex items-center justify-center mb-8 border border-white/10">
                <i class="fas fa-money-bill-trend-up text-accent text-xl"></i>
            </div>
            <p class="text-[10px] font-black uppercase tracking-[0.3em] text-slate-400">Department Total Expenses</p>
            <h3 class="text-3xl font-black mt-2 text-rose-400 tracking-tighter">
                <?php echo $currency . ' ' . number_format((float)($deptHeadTotals['expense_total'] ?? 0), 2); ?>
            </h3>
            <p class="text-[10px] font-black text-slate-500 uppercase tracking-widest mt-6">All bank transfers</p>
        </div>

        <div class="glass-card rounded-[2.5rem] p-8 border-white/5 card-interaction">
            <div class="w-14 h-14 bg-white/5 rounded-2xl flex items-center justify-center mb-8 border border-white/10">
                <i class="fas fa-wallet text-accent text-xl"></i>
            </div>
            <p class="text-[10px] font-black uppercase tracking-[0.3em] text-slate-400">Department Total Balance</p>
            <h3 class="text-3xl font-black mt-2 <?php echo (float)($deptHeadTotals['balance'] ?? 0) >= 0 ? 'text-accent' : 'text-rose-400'; ?> tracking-tighter">
                <?php echo $currency . ' ' . number_format((float)($deptHeadTotals['balance'] ?? 0), 2); ?>
            </h3>
            <p class="text-[10px] font-black text-slate-500 uppercase tracking-widest mt-6">All bank transfers</p>
        </div>

        <div class="glass-card rounded-[2.5rem] p-8 border-white/5 card-interaction">
            <div class="w-14 h-14 bg-white/5 rounded-2xl flex items-center justify-center mb-8 border border-white/10">
                <i class="fas fa-receipt text-accent text-xl"></i>
            </div>
            <p class="text-[10px] font-black uppercase tracking-[0.3em] text-slate-400">Total Transactions</p>
            <h3 class="text-3xl font-black mt-2 text-white tracking-tighter">
                <?php echo number_format((int)($deptHeadTotals['transaction_count'] ?? 0)); ?>
            </h3>
            <p class="text-[10px] font-black text-slate-500 uppercase tracking-widest mt-6">All recorded transactions</p>
        </div>
    </div>
<?php endif; ?>

<?php if (!$isStaff): ?>
<div id="bank-details" class="glass-card rounded-[2.5rem] sm:rounded-[3rem] border-white/5 overflow-hidden mb-12 card-interaction">
        <div class="px-6 sm:px-8 lg:px-10 py-6 sm:py-8 border-b border-white/5 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 bg-white/[0.02]">
            <div class="flex items-center">
                <div class="w-10 h-10 bg-accent/10 rounded-xl flex items-center justify-center mr-4 border border-accent/20">
                    <i class="fas fa-building-columns text-accent text-sm"></i>
                </div>
                <h4 class="text-xl font-black text-white tracking-tight"><?php echo !empty($isDeptHead) ? 'Department Bank Details' : 'Bank Account Details'; ?></h4>
            </div>
            <button type="button" onclick="openBankModal()" class="glass-card w-full sm:w-auto px-6 py-3 rounded-2xl border-white/10 text-slate-300 font-black text-[10px] uppercase tracking-widest hover:bg-white/5 transition-all">
                <i class="fas fa-pen-nib mr-2 text-accent"></i> Edit
            </button>
        </div>

        <div class="p-5 sm:p-6 lg:p-10">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="bg-white/5 rounded-[2rem] p-6 border border-white/10">
                    <p class="text-[9px] font-black text-slate-500 uppercase tracking-widest">Bank Name</p>
                    <p class="text-sm font-black text-slate-200 mt-3"><?php echo htmlspecialchars(($bank['bank_name'] ?? '') !== '' ? $bank['bank_name'] : 'Not configured'); ?></p>
                </div>
                <div class="bg-white/5 rounded-[2rem] p-6 border border-white/10">
                    <p class="text-[9px] font-black text-slate-500 uppercase tracking-widest">Account Name</p>
                    <p class="text-sm font-black text-slate-200 mt-3"><?php echo htmlspecialchars(($bank['account_name'] ?? '') !== '' ? $bank['account_name'] : 'Not configured'); ?></p>
                </div>
                <div class="bg-white/5 rounded-[2rem] p-6 border border-white/10">
                    <p class="text-[9px] font-black text-slate-500 uppercase tracking-widest">Account Number</p>
                    <p class="text-sm font-black text-slate-200 mt-3"><?php echo htmlspecialchars(($bank['account_number'] ?? '') !== '' ? $bank['account_number'] : 'Not configured'); ?></p>
                </div>
                <div class="bg-white/5 rounded-[2rem] p-6 border border-white/10">
                    <p class="text-[9px] font-black text-slate-500 uppercase tracking-widest">Branch</p>
                    <p class="text-sm font-black text-slate-200 mt-3"><?php echo htmlspecialchars(($bank['branch'] ?? '') !== '' ? $bank['branch'] : 'Not configured'); ?></p>
                </div>
                <?php if (!empty($isDeptHead)): ?>
                    <div class="bg-white/5 rounded-[2rem] p-6 border border-white/10 md:col-span-2">
                        <p class="text-[9px] font-black text-slate-500 uppercase tracking-widest">Transaction Coverage</p>
                        <p class="text-sm font-black text-slate-200 mt-3">
                            <?php echo number_format((int)(($deptHeadTotals['transaction_count'] ?? 0))); ?> total department transaction<?php echo ((int)(($deptHeadTotals['transaction_count'] ?? 0)) === 1) ? '' : 's'; ?>
                        </p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
</div>
<?php endif; ?>

<?php if (empty($isDeptHead) && !$isStaff): ?>
<div id="member-ledger" class="glass-card rounded-[2.5rem] sm:rounded-[3rem] border-white/5 overflow-hidden mb-12 card-interaction">
    <div class="px-6 sm:px-8 lg:px-10 py-6 sm:py-8 border-b border-white/5 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 bg-white/[0.02]">
        <div class="flex items-center">
            <div class="w-10 h-10 bg-accent/10 rounded-xl flex items-center justify-center mr-4 border border-accent/20">
                <i class="fas fa-user-check text-accent text-sm"></i>
            </div>
            <h4 class="text-xl font-black text-white tracking-tight">Member Transactions</h4>
        </div>
        <div class="text-[10px] font-black text-slate-500 uppercase tracking-widest">Offering excluded • Department excluded</div>
    </div>

    <div class="p-5 sm:p-6 lg:p-10 space-y-8">
        <div class="space-y-3 max-w-xl">
            <label class="block text-[9px] font-black text-slate-500 uppercase tracking-widest ml-1">Select Member</label>
            <div class="relative group">
                <i class="fas fa-user absolute left-5 top-1/2 -translate-y-1/2 text-slate-600"></i>
                <select id="member-ledger-select" class="w-full bg-white/5 border border-white/10 focus:border-accent rounded-2xl pl-14 pr-10 py-5 text-sm font-bold text-white transition-all outline-none appearance-none cursor-pointer">
                    <option value="">Choose member</option>
                    <?php foreach (($membersForSelect ?? []) as $m): ?>
                        <option value="<?php echo (int)$m['id']; ?>"><?php echo htmlspecialchars(trim(($m['first_name'] ?? '') . ' ' . ($m['last_name'] ?? ''))); ?></option>
                    <?php endforeach; ?>
                </select>
                <i class="fas fa-chevron-down absolute right-5 top-1/2 -translate-y-1/2 text-slate-600 text-[10px] pointer-events-none"></i>
            </div>
        </div>

        <div id="member-ledger-loading" class="hidden glass-card p-10 rounded-[2.5rem] border-white/5 text-center text-slate-500 font-bold italic">
            Loading member transactions...
        </div>

        <div id="member-ledger-empty" class="hidden glass-card p-10 rounded-[2.5rem] border-white/5 text-center text-slate-500 font-bold italic">
            Select a member to view transactions.
        </div>

        <div id="member-ledger-body" class="hidden space-y-6">
            <div id="member-ledger-totals"></div>
            <div class="glass-card rounded-[2.5rem] border-white/5 overflow-hidden">
                <div class="px-8 py-6 border-b border-white/5 bg-white/[0.02] flex items-center justify-between">
                    <h5 class="text-[10px] font-black text-slate-400 uppercase tracking-[0.3em]">Transaction List</h5>
                    <span id="member-ledger-count" class="text-[10px] font-black text-slate-500 uppercase tracking-widest"></span>
                </div>
                <div class="overflow-x-auto custom-scrollbar">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="text-[10px] font-black text-slate-500 uppercase tracking-[0.3em] border-b border-white/5 bg-white/[0.01]">
                                <th class="px-8 py-5">Date</th>
                    <th class="px-8 py-5">Transaction Type</th>
                                <th class="px-8 py-5">Amount</th>
                                <th class="px-8 py-5 hidden md:table-cell">Method</th>
                                <th class="px-8 py-5 hidden lg:table-cell">Recorded By</th>
                            </tr>
                        </thead>
                        <tbody id="member-ledger-rows" class="divide-y divide-white/[0.02]"></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<?php if (Auth::isFinanceHead() || (!$isStaff && empty($isDeptHead))): ?>
<div id="change-requests" class="glass-card rounded-[2.5rem] sm:rounded-[3rem] border-white/5 overflow-hidden mb-12 card-interaction">
    <div class="px-6 sm:px-8 lg:px-10 py-6 sm:py-8 border-b border-white/5 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 bg-white/[0.02]">
        <div class="flex items-center">
            <div class="w-10 h-10 bg-accent/10 rounded-xl flex items-center justify-center mr-4 border border-accent/20">
                <i class="fas fa-user-shield text-accent text-sm"></i>
            </div>
            <h4 class="text-xl font-black text-white tracking-tight">Change Requests</h4>
        </div>
        <div class="text-[10px] font-black text-slate-500 uppercase tracking-widest"><?php echo count($pendingChangeRequests ?? []); ?> pending</div>
    </div>
    <div class="p-5 sm:p-6 lg:p-10">
        <?php if (empty($pendingChangeRequests)): ?>
            <div class="px-4 py-12 text-center text-slate-500 italic font-bold">No pending change requests.</div>
        <?php else: ?>
            <div class="overflow-x-auto custom-scrollbar">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="text-[10px] font-black text-slate-500 uppercase tracking-[0.3em] border-b border-white/5 bg-white/[0.01]">
                            <th class="px-6 py-5">Requested By</th>
                            <th class="px-6 py-5">Transaction</th>
                            <th class="px-6 py-5">Amount</th>
                            <th class="px-6 py-5 hidden lg:table-cell">Reason</th>
                            <th class="px-6 py-5 text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-white/[0.02]">
                        <?php foreach ($pendingChangeRequests as $r): ?>
                            <tr class="hover:bg-white/[0.03] transition-all duration-300">
                                <td class="px-6 py-5">
                                    <p class="text-sm font-black text-slate-200"><?php echo htmlspecialchars($r['requested_by_name'] ?? ''); ?></p>
                                    <p class="text-[10px] font-black text-slate-500 uppercase tracking-widest mt-1"><?php echo htmlspecialchars($r['created_at'] ?? ''); ?></p>
                                </td>
                                <td class="px-6 py-5">
                                    <p class="text-sm font-black text-slate-200"><?php echo htmlspecialchars($r['transaction_type'] ?? ''); ?></p>
                                    <p class="text-[10px] font-black text-slate-500 uppercase tracking-widest mt-1"><?php echo htmlspecialchars($r['transaction_number'] ?? ''); ?></p>
                                </td>
                                <td class="px-6 py-5">
                                    <p class="text-sm font-black text-accent"><?php echo $currency . ' ' . number_format((float)($r['current_amount'] ?? 0), 2); ?></p>
                                    <p class="text-[10px] font-black text-slate-500 uppercase tracking-widest mt-1"><?php echo htmlspecialchars($r['current_transaction_date'] ?? ''); ?></p>
                                </td>
                                <td class="px-6 py-5 hidden lg:table-cell">
                                    <p class="text-xs font-bold text-slate-400 max-w-xl"><?php echo htmlspecialchars($r['reason'] ?? ''); ?></p>
                                </td>
                                <td class="px-6 py-5 text-right">
                                    <?php if (Auth::isFinanceHead()): ?>
                                        <div class="flex items-center justify-end gap-2">
                                            <form action="<?php echo BASE_URL; ?>/finance/approveChangeRequest" method="POST">
                                                <input type="hidden" name="request_id" value="<?php echo (int)($r['id'] ?? 0); ?>">
                                                <button type="submit" class="h-10 px-4 rounded-xl bg-emerald-500/15 text-emerald-300 hover:bg-emerald-500/20 transition-all border border-emerald-500/20 text-[10px] font-black uppercase tracking-widest">
                                                    Approve
                                                </button>
                                            </form>
                                            <form action="<?php echo BASE_URL; ?>/finance/rejectChangeRequest" method="POST">
                                                <input type="hidden" name="request_id" value="<?php echo (int)($r['id'] ?? 0); ?>">
                                                <button type="submit" class="h-10 px-4 rounded-xl bg-rose-500/15 text-rose-300 hover:bg-rose-500/20 transition-all border border-rose-500/20 text-[10px] font-black uppercase tracking-widest">
                                                    Reject
                                                </button>
                                            </form>
                                        </div>
                                    <?php else: ?>
                                        <span class="text-[10px] font-black uppercase tracking-widest text-slate-500">Awaiting head approval</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>
<?php endif; ?>

<?php if (empty($isDeptHead)): ?>
<div id="department-expense-requests" class="glass-card rounded-[2.5rem] sm:rounded-[3rem] border-white/5 overflow-hidden mb-12 card-interaction">
    <div class="px-6 sm:px-8 lg:px-10 py-6 sm:py-8 border-b border-white/5 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 bg-white/[0.02]">
        <div class="flex items-center">
            <div class="w-10 h-10 bg-accent/10 rounded-xl flex items-center justify-center mr-4 border border-accent/20">
                <i class="fas fa-file-invoice-dollar text-accent text-sm"></i>
            </div>
            <h4 class="text-xl font-black text-white tracking-tight">Department Expense Requests</h4>
        </div>
        <div class="text-[10px] font-black text-slate-500 uppercase tracking-widest"><?php echo count($pendingDeptExpenseRequests ?? []); ?> pending</div>
    </div>
    <div class="p-5 sm:p-6 lg:p-10">
        <?php if (empty($pendingDeptExpenseRequests)): ?>
            <div class="px-4 py-12 text-center text-slate-500 italic font-bold">No pending department expense requests.</div>
        <?php else: ?>
            <div class="overflow-x-auto custom-scrollbar">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="text-[10px] font-black text-slate-500 uppercase tracking-[0.3em] border-b border-white/5 bg-white/[0.01]">
                            <th class="px-6 py-5">Department</th>
                            <th class="px-6 py-5">Requested By</th>
                            <th class="px-6 py-5">Amount</th>
                            <th class="px-6 py-5 hidden lg:table-cell">Purpose</th>
                            <th class="px-6 py-5">Status</th>
                            <th class="px-6 py-5 text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-white/[0.02]">
                        <?php foreach ($pendingDeptExpenseRequests as $r): ?>
                            <?php
                                $financeApproved = !empty($r['approved_by']);
                                $pastorApproved = !empty($r['pastor_approved_by']);
                                $approvalLabel = 'Pending';
                                if ($pastorApproved && !$financeApproved) $approvalLabel = 'Approved by pastor, waiting for head of finance';
                                if ($financeApproved && !$pastorApproved) $approvalLabel = 'Approved by head of finance, waiting for pastor';
                                if ($financeApproved && $pastorApproved) $approvalLabel = 'Approved by pastor + head of finance';
                            ?>
                            <tr class="hover:bg-white/[0.03] transition-all duration-300">
                                <td class="px-6 py-5">
                                    <p class="text-sm font-black text-slate-200"><?php echo htmlspecialchars($r['department_name'] ?? ''); ?></p>
                                    <p class="text-[10px] font-black text-slate-500 uppercase tracking-widest mt-1"><?php echo htmlspecialchars($r['created_at'] ?? ''); ?></p>
                                </td>
                                <td class="px-6 py-5">
                                    <p class="text-sm font-black text-slate-200"><?php echo htmlspecialchars($r['requested_by_name'] ?? ''); ?></p>
                                </td>
                                <td class="px-6 py-5">
                                    <p class="text-sm font-black text-accent"><?php echo $currency . ' ' . number_format((float)($r['amount'] ?? 0), 2); ?></p>
                                </td>
                                <td class="px-6 py-5 hidden lg:table-cell">
                                    <p class="text-xs font-bold text-slate-400 max-w-xl"><?php echo htmlspecialchars($r['purpose'] ?? ''); ?></p>
                                </td>
                                <td class="px-6 py-5">
                                    <p class="text-[10px] font-black uppercase tracking-widest text-slate-400"><?php echo htmlspecialchars($approvalLabel); ?></p>
                                </td>
                                <td class="px-6 py-5 text-right">
                                    <div class="flex items-center justify-end gap-2">
                                        <?php if (Auth::isFinanceHead()): ?>
                                            <?php if (empty($r['approved_by'])): ?>
                                                <form action="<?php echo BASE_URL; ?>/finance/approveDepartmentExpenseRequest" method="POST">
                                                    <input type="hidden" name="request_id" value="<?php echo (int)($r['id'] ?? 0); ?>">
                                                    <button type="submit" class="h-10 px-4 rounded-xl bg-emerald-500/15 text-emerald-300 hover:bg-emerald-500/20 transition-all border border-emerald-500/20 text-[10px] font-black uppercase tracking-widest">
                                                        Approve
                                                    </button>
                                                </form>
                                                <form action="<?php echo BASE_URL; ?>/finance/rejectDepartmentExpenseRequest" method="POST">
                                                    <input type="hidden" name="request_id" value="<?php echo (int)($r['id'] ?? 0); ?>">
                                                    <button type="submit" class="h-10 px-4 rounded-xl bg-rose-500/15 text-rose-300 hover:bg-rose-500/20 transition-all border border-rose-500/20 text-[10px] font-black uppercase tracking-widest">
                                                        Reject
                                                    </button>
                                                </form>
                                            <?php else: ?>
                                                <span class="text-[10px] font-black uppercase tracking-widest text-slate-500">Awaiting pastor approval</span>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <span class="text-[10px] font-black uppercase tracking-widest text-slate-500">Awaiting head approval</span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>
<?php endif; ?>

<div id="bank-modal" class="fixed inset-0 bg-slate-950/80 backdrop-blur-xl z-50 hidden flex items-center justify-center p-4">
    <div class="glass-card w-full max-w-lg rounded-[3rem] sm:rounded-[3.5rem] overflow-hidden shadow-2xl transform transition-all duration-500 scale-95 opacity-0 border-white/10 max-h-[90vh] flex flex-col" id="bank-modal-content">
        <div class="px-6 sm:px-10 py-6 sm:py-10 bg-slate-900 relative overflow-hidden border-b border-white/5">
            <div class="relative z-10 flex justify-between items-center text-white">
                <div>
                    <h3 class="text-3xl font-black tracking-tighter">Bank Details</h3>
                    <p class="text-accent text-[10px] font-black mt-2 tracking-[0.3em] uppercase"><?php echo !empty($isDeptHead) ? 'Department Finance Configuration' : 'Finance Configuration'; ?></p>
                </div>
                <button type="button" onclick="closeBankModal()" class="w-12 h-12 bg-white/5 hover:bg-accent hover:text-slate-900 rounded-2xl flex items-center justify-center transition-all border border-white/10">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        </div>

        <form action="<?php echo BASE_URL; ?>/finance/updateBankDetails" method="POST" class="p-6 sm:p-10 bg-slate-900/50 overflow-y-auto custom-scrollbar flex-1 space-y-8">
            <div class="space-y-3">
                <label class="block text-[9px] font-black text-slate-500 uppercase tracking-widest ml-1">Bank Name</label>
                <div class="relative group">
                    <i class="fas fa-building-columns absolute left-5 top-1/2 -translate-y-1/2 text-slate-600 group-focus-within:text-accent transition-colors"></i>
                    <input type="text" name="finance_bank_name" value="<?php echo htmlspecialchars($bank['bank_name'] ?? ''); ?>" class="w-full bg-white/5 border border-white/10 focus:border-accent rounded-2xl pl-14 pr-6 py-5 text-sm font-bold text-white transition-all outline-none">
                </div>
            </div>

            <div class="space-y-3">
                <label class="block text-[9px] font-black text-slate-500 uppercase tracking-widest ml-1">Account Name</label>
                <div class="relative group">
                    <i class="fas fa-user-check absolute left-5 top-1/2 -translate-y-1/2 text-slate-600 group-focus-within:text-accent transition-colors"></i>
                    <input type="text" name="finance_account_name" value="<?php echo htmlspecialchars($bank['account_name'] ?? ''); ?>" class="w-full bg-white/5 border border-white/10 focus:border-accent rounded-2xl pl-14 pr-6 py-5 text-sm font-bold text-white transition-all outline-none">
                </div>
            </div>

            <div class="space-y-3">
                <label class="block text-[9px] font-black text-slate-500 uppercase tracking-widest ml-1">Account Number</label>
                <div class="relative group">
                    <i class="fas fa-hashtag absolute left-5 top-1/2 -translate-y-1/2 text-slate-600 group-focus-within:text-accent transition-colors"></i>
                    <input type="text" name="finance_account_number" value="<?php echo htmlspecialchars($bank['account_number'] ?? ''); ?>" class="w-full bg-white/5 border border-white/10 focus:border-accent rounded-2xl pl-14 pr-6 py-5 text-sm font-bold text-white transition-all outline-none">
                </div>
            </div>

            <div class="space-y-3">
                <label class="block text-[9px] font-black text-slate-500 uppercase tracking-widest ml-1">Branch</label>
                <div class="relative group">
                    <i class="fas fa-code-branch absolute left-5 top-1/2 -translate-y-1/2 text-slate-600 group-focus-within:text-accent transition-colors"></i>
                    <input type="text" name="finance_bank_branch" value="<?php echo htmlspecialchars($bank['branch'] ?? ''); ?>" class="w-full bg-white/5 border border-white/10 focus:border-accent rounded-2xl pl-14 pr-6 py-5 text-sm font-bold text-white transition-all outline-none">
                </div>
            </div>

            <div class="space-y-3">
                <label class="block text-[9px] font-black text-slate-500 uppercase tracking-widest ml-1">Currency</label>
                <div class="relative group">
                    <i class="fas fa-coins absolute left-5 top-1/2 -translate-y-1/2 text-slate-600 group-focus-within:text-accent transition-colors"></i>
                    <input type="text" name="finance_currency" value="<?php echo htmlspecialchars($currency); ?>" class="w-full bg-white/5 border border-white/10 focus:border-accent rounded-2xl pl-14 pr-6 py-5 text-sm font-bold text-white transition-all outline-none <?php echo !empty($isDeptHead) ? 'cursor-not-allowed opacity-70' : ''; ?>" placeholder="GHS" <?php echo !empty($isDeptHead) ? 'readonly' : ''; ?>>
                </div>
                <?php if (!empty($isDeptHead)): ?>
                    <p class="text-[10px] font-bold text-slate-500">Currency is controlled globally by admin. Department heads can update only their department bank details.</p>
                <?php endif; ?>
            </div>

            <div class="mt-6 flex flex-col sm:flex-row gap-4">
                <button type="submit" class="flex-1 bg-accent text-slate-900 py-6 rounded-2xl font-black text-xs uppercase tracking-[0.3em] hover:scale-[1.02] active:scale-95 transition-all shadow-xl shadow-yellow-500/10">
                    Save
                </button>
                <button type="button" onclick="closeBankModal()" class="px-10 py-6 bg-white/5 text-slate-400 rounded-2xl font-black text-xs uppercase tracking-[0.3em] hover:bg-white/10 transition-all">
                    Cancel
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    const financeTransactions = <?php echo json_encode($recent_transactions ?? [], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
    const financeReceiptSeed = <?php echo json_encode($receiptData, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
    const receiptLogoUrl = <?php echo json_encode((function () {
        $logoPath = Branding::getLogoPath();
        return $logoPath ? BASE_URL . '/' . ltrim($logoPath, '/') : '';
    })()); ?>;
    let activeReceiptTx = null;

    function openBankModal() {
        const modal = document.getElementById('bank-modal');
        const content = document.getElementById('bank-modal-content');
        if (!modal || !content) return;
        modal.classList.remove('hidden');
        setTimeout(() => {
            content.classList.remove('scale-95', 'opacity-0');
            content.classList.add('scale-100', 'opacity-100');
        }, 10);
    }

    function closeBankModal() {
        const modal = document.getElementById('bank-modal');
        const content = document.getElementById('bank-modal-content');
        if (!modal || !content) return;
        content.classList.remove('scale-100', 'opacity-100');
        content.classList.add('scale-95', 'opacity-0');
        setTimeout(() => modal.classList.add('hidden'), 300);
    }

    const bankModalEl = document.getElementById('bank-modal');
    if (bankModalEl) {
        bankModalEl.addEventListener('click', (e) => {
            if (e.target === bankModalEl) closeBankModal();
        });
    }

    function openTransactionModal(id) {
        const modal = document.getElementById('transaction-modal');
        const content = document.getElementById('transaction-modal-content');
        const tx = (financeTransactions || []).find(t => String(t.id) === String(id));
        if (!tx || !modal || !content) return;

        const currency = <?php echo json_encode($currency); ?>;
        const dateStr = tx.transaction_date ? new Date(tx.transaction_date).toLocaleDateString(undefined, { year: 'numeric', month: 'short', day: '2-digit' }) : '';
        const isExpense = String(tx.transaction_type || '') === 'Expense';

        document.getElementById('tx-title').textContent = 'Transaction Details';
        document.getElementById('tx-type').textContent = tx.transaction_type || '';
        document.getElementById('tx-type').className = `px-4 py-1.5 text-[9px] font-black rounded-full uppercase tracking-widest border ${isExpense ? 'bg-rose-500/10 text-rose-400 border-rose-500/20' : 'bg-emerald-500/10 text-emerald-400 border-emerald-500/20'}`;
        document.getElementById('tx-amount').textContent = `${isExpense ? '-' : ''}${currency} ${Number(tx.amount || 0).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
        document.getElementById('tx-type-label').textContent = tx.transaction_type || 'N/A';
        document.getElementById('tx-date').textContent = dateStr || 'N/A';
        document.getElementById('tx-method').textContent = tx.payment_method || 'N/A';
        document.getElementById('tx-ref').textContent = tx.reference_no || 'N/A';
        document.getElementById('tx-member').textContent = (tx.member_name || '').trim() !== '' ? tx.member_name : 'N/A';
        document.getElementById('tx-dept').textContent = (tx.department_name || '').trim() !== '' ? tx.department_name : 'N/A';
        document.getElementById('tx-recorder').textContent = (tx.recorded_by_name || '').trim() !== '' ? tx.recorded_by_name : 'N/A';
        document.getElementById('tx-desc').textContent = (tx.description || '').trim() !== '' ? tx.description : 'N/A';

        const summaryWrap = document.getElementById('tx-member-summary');
        const summaryLoading = document.getElementById('tx-member-summary-loading');
        const summaryEmpty = document.getElementById('tx-member-summary-empty');
        const summaryBody = document.getElementById('tx-member-summary-body');

        summaryWrap.classList.remove('hidden');
        summaryLoading.classList.remove('hidden');
        summaryEmpty.classList.add('hidden');
        summaryBody.classList.add('hidden');
        summaryBody.innerHTML = '';

        const memberId = Number(tx.member_id || 0);
        if (!memberId) {
            summaryLoading.classList.add('hidden');
            summaryEmpty.classList.remove('hidden');
        } else {
            fetch(`<?php echo BASE_URL; ?>/finance/memberSummary?member_id=${encodeURIComponent(memberId)}`)
                .then(r => r.json())
                .then(data => {
                    summaryLoading.classList.add('hidden');
                    const income = Number(data.income_total || 0);
                    const expense = Number(data.expense_total || 0);
                    const net = Number(data.net_total || 0);
                    const byType = Array.isArray(data.by_type) ? data.by_type : [];

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
                        <div class="mt-5 text-[10px] font-black text-slate-500 uppercase tracking-widest">Offering excluded • Department excluded</div>
                        <div class="mt-6 space-y-2">
                            ${byType.length ? byType.map(r => `
                                <div class="flex items-center justify-between bg-white/5 rounded-2xl p-4 border border-white/5">
                                    <span class="text-[10px] font-black uppercase tracking-widest text-slate-300">${(r.transaction_type || '').replace(/_/g,' ')}</span>
                                    <span class="text-xs font-black text-slate-200">${currency} ${Number(r.total || 0).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</span>
                                </div>
                            `).join('') : '<div class="text-slate-500 text-xs font-bold italic">No member transactions found.</div>'}
                        </div>
                    `;
                })
                .catch(() => {
                    summaryLoading.classList.add('hidden');
                    summaryEmpty.classList.remove('hidden');
                });
        }

        modal.classList.remove('hidden');
        setTimeout(() => {
            content.classList.remove('scale-95', 'opacity-0');
            content.classList.add('scale-100', 'opacity-100');
        }, 10);
    }

    function closeTransactionModal() {
        const modal = document.getElementById('transaction-modal');
        const content = document.getElementById('transaction-modal-content');
        if (!modal || !content) return;
        content.classList.remove('scale-100', 'opacity-100');
        content.classList.add('scale-95', 'opacity-0');
        setTimeout(() => modal.classList.add('hidden'), 300);
    }

    function escapeReceiptHtml(value) {
        return String(value ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    }

    function buildReceiptMarkup(tx, copyLabel = 'Member Copy') {
        const currency = <?php echo json_encode($currency); ?>;
        let typeLabel = String(tx.transaction_type || '') === 'Departmental Savings' ? 'Department Offering' : (tx.transaction_type || 'Transaction');
        if (String(tx.transaction_type || '') === 'Offering' && String(tx.offering_subtype || '').trim() !== '') {
            typeLabel = `Offering (${String(tx.offering_subtype).trim()})`;
        }
        const amount = Number(tx.amount || 0).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        const dateStr = tx.transaction_date ? new Date(tx.transaction_date).toLocaleDateString(undefined, { year: 'numeric', month: 'short', day: '2-digit' }) : 'N/A';
        const paidBy = (tx.member_name || '').trim() || 'N/A';
        const receiptNo = tx.transaction_number || tx.reference_no || 'N/A';
        const method = tx.payment_method || 'Cash';
        const notes = (tx.description || '').trim() || `${typeLabel} payment recorded successfully.`;
        const dept = (tx.department_name || '').trim();
        const recordedBy = (tx.recorded_by_name || '').trim() || 'Staff';

        return `
            <div style="background:#ffffff;color:#123f78;border:3px solid #2563eb;position:relative;overflow:hidden;padding:22px 24px 20px;min-height:520px;">
                <div style="position:absolute;top:16px;right:18px;background:#eff6ff;color:#1d4ed8;border:1px solid #93c5fd;border-radius:999px;padding:5px 12px;font-size:11px;font-weight:800;letter-spacing:0.18em;text-transform:uppercase;">${escapeReceiptHtml(copyLabel)}</div>
                <div style="display:grid;grid-template-columns:110px 1fr 260px;gap:18px;align-items:start;border-bottom:3px solid #dbeafe;padding-bottom:16px;">
                    <div style="display:flex;align-items:center;justify-content:center;">
                        <img src="${receiptLogoUrl}" alt="AG Logo" style="width:92px;height:auto;object-fit:contain;">
                    </div>
                    <div>
                        <div style="font-size:28px;font-weight:900;letter-spacing:0.04em;text-transform:uppercase;color:#2563eb;">Assemblies Of God, Ghana</div>
                        <div style="font-size:34px;font-weight:900;line-height:1.05;text-transform:uppercase;color:#123f78;margin-top:6px;">Upper Room Assembly</div>
                        <div style="font-size:13px;font-weight:700;text-transform:uppercase;letter-spacing:0.05em;color:#475569;margin-top:8px;">P. O. Box 101 Mampong-Ashanti | Mob.: 0256 531265 / 020 1638748</div>
                        <div style="font-size:14px;font-weight:800;letter-spacing:0.12em;text-transform:uppercase;color:#64748b;margin-top:10px;">Official Church Payment Receipt</div>
                    </div>
                    <div style="background:#eff6ff;border:2px solid #bfdbfe;border-radius:18px;padding:16px 18px;">
                        <div style="display:grid;grid-template-columns:88px 1fr;gap:10px;font-size:13px;line-height:1.5;">
                            <div style="font-weight:800;text-transform:uppercase;color:#64748b;">Receipt No</div>
                            <div style="font-weight:900;color:#123f78;">${escapeReceiptHtml(receiptNo)}</div>
                            <div style="font-weight:800;text-transform:uppercase;color:#64748b;">Date</div>
                            <div style="font-weight:900;color:#123f78;">${escapeReceiptHtml(dateStr)}</div>
                            <div style="font-weight:800;text-transform:uppercase;color:#64748b;">Method</div>
                            <div style="font-weight:900;color:#123f78;">${escapeReceiptHtml(method)}</div>
                            <div style="font-weight:800;text-transform:uppercase;color:#64748b;">Type</div>
                            <div style="font-weight:900;color:#123f78;">${escapeReceiptHtml(typeLabel)}</div>
                        </div>
                    </div>
                </div>
                <div style="display:grid;grid-template-columns:1.55fr 1fr;gap:22px;padding-top:18px;">
                    <div style="display:flex;flex-direction:column;gap:14px;">
                        <div>
                            <div style="font-size:12px;font-weight:800;letter-spacing:0.16em;text-transform:uppercase;color:#64748b;margin-bottom:6px;">Received From</div>
                            <div style="border:2px solid #93c5fd;border-radius:16px;min-height:54px;padding:14px 16px;font-size:22px;font-weight:900;color:#123f78;background:#ffffff;">${escapeReceiptHtml(paidBy)}</div>
                        </div>
                        <div>
                            <div style="font-size:12px;font-weight:800;letter-spacing:0.16em;text-transform:uppercase;color:#64748b;margin-bottom:6px;">Amount Received</div>
                            <div style="border:2px solid #93c5fd;border-radius:16px;min-height:54px;padding:14px 16px;font-size:22px;font-weight:900;color:#123f78;background:#ffffff;">${escapeReceiptHtml(currency)} ${escapeReceiptHtml(amount)}</div>
                        </div>
                        <div>
                            <div style="font-size:12px;font-weight:800;letter-spacing:0.16em;text-transform:uppercase;color:#64748b;margin-bottom:6px;">Purpose / Being</div>
                            <div style="border:2px solid #93c5fd;border-radius:16px;min-height:96px;padding:14px 16px;font-size:18px;font-weight:800;color:#123f78;line-height:1.45;background:#ffffff;">${escapeReceiptHtml(typeLabel)}${dept ? ' - ' + escapeReceiptHtml(dept) : ''}<br><span style="font-size:14px;font-weight:700;color:#475569;">${escapeReceiptHtml(notes)}</span></div>
                        </div>
                        <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;">
                            <div style="border:2px solid #93c5fd;border-radius:16px;padding:14px 16px;min-height:78px;background:#ffffff;">
                                <div style="font-size:11px;font-weight:800;letter-spacing:0.16em;text-transform:uppercase;color:#64748b;">Department</div>
                                <div style="font-size:18px;font-weight:900;color:#123f78;margin-top:8px;">${dept ? escapeReceiptHtml(dept) : 'General'}</div>
                            </div>
                            <div style="border:2px solid #93c5fd;border-radius:16px;padding:14px 16px;min-height:78px;background:#ffffff;">
                                <div style="font-size:11px;font-weight:800;letter-spacing:0.16em;text-transform:uppercase;color:#64748b;">Recorded By</div>
                                <div style="font-size:18px;font-weight:900;color:#123f78;margin-top:8px;">${escapeReceiptHtml(recordedBy)}</div>
                            </div>
                        </div>
                    </div>
                    <div style="display:flex;flex-direction:column;gap:14px;">
                        <div style="border-radius:22px;background:linear-gradient(135deg,#1d4ed8 0%,#2563eb 58%,#60a5fa 100%);padding:20px 22px;color:#ffffff;min-height:160px;display:flex;flex-direction:column;justify-content:space-between;">
                            <div style="font-size:12px;font-weight:800;letter-spacing:0.18em;text-transform:uppercase;opacity:0.9;">Receipt Total</div>
                            <div style="font-size:42px;font-weight:900;line-height:1;">${escapeReceiptHtml(currency)} ${escapeReceiptHtml(amount)}</div>
                            <div style="display:flex;justify-content:space-between;gap:10px;font-size:13px;font-weight:700;opacity:0.95;">
                                <span>${escapeReceiptHtml(typeLabel)}</span>
                                <span>${escapeReceiptHtml(method)}</span>
                            </div>
                        </div>
                        <div style="border:2px solid #93c5fd;border-radius:18px;padding:16px 18px;background:#f8fbff;">
                            <div style="font-size:12px;font-weight:800;letter-spacing:0.16em;text-transform:uppercase;color:#64748b;">Payment Breakdown</div>
                            <div style="display:grid;grid-template-columns:1fr auto;gap:10px;margin-top:12px;align-items:center;">
                                <div style="font-size:15px;font-weight:800;color:#123f78;">Cash / Value Received</div>
                                <div style="font-size:18px;font-weight:900;color:#123f78;">${escapeReceiptHtml(currency)} ${escapeReceiptHtml(amount)}</div>
                                <div style="font-size:15px;font-weight:800;color:#123f78;">Balance</div>
                                <div style="font-size:18px;font-weight:900;color:#123f78;">${escapeReceiptHtml(currency)} 0.00</div>
                                <div style="font-size:15px;font-weight:800;color:#123f78;">Cheque Ref</div>
                                <div style="font-size:16px;font-weight:900;color:#123f78;">${method === 'Check' ? escapeReceiptHtml(receiptNo) : 'N/A'}</div>
                            </div>
                        </div>
                        <div style="border:2px dashed #93c5fd;border-radius:18px;padding:16px 18px;flex:1;display:flex;flex-direction:column;justify-content:flex-end;background:#ffffff;">
                            <div style="font-size:12px;font-weight:800;letter-spacing:0.16em;text-transform:uppercase;color:#64748b;">Authorized Signature</div>
                            <div style="border-bottom:2px solid #2563eb;height:42px;margin-top:20px;"></div>
                            <div style="display:flex;justify-content:space-between;gap:10px;margin-top:10px;font-size:12px;font-weight:800;color:#64748b;text-transform:uppercase;">
                                <span>Upper Room Assembly</span>
                                <span>Thank You</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `;
    }

    function openReceiptModal(id = null) {
        const modal = document.getElementById('receipt-modal');
        const content = document.getElementById('receipt-modal-content');
        const body = document.getElementById('receipt-body');
        if (!modal || !content || !body) return;

        let tx = financeReceiptSeed;
        if (id !== null) {
            tx = (financeTransactions || []).find(t => String(t.id) === String(id));
        }
        if (!tx) return;
        activeReceiptTx = tx;

        body.innerHTML = buildReceiptMarkup(tx, 'Preview Copy');

        modal.classList.remove('hidden');
        setTimeout(() => {
            content.classList.remove('scale-95', 'opacity-0');
            content.classList.add('scale-100', 'opacity-100');
        }, 10);
    }

    function closeReceiptModal() {
        const modal = document.getElementById('receipt-modal');
        const content = document.getElementById('receipt-modal-content');
        if (!modal || !content) return;
        content.classList.remove('scale-100', 'opacity-100');
        content.classList.add('scale-95', 'opacity-0');
        setTimeout(() => modal.classList.add('hidden'), 300);
    }

    function printReceipt() {
        if (!activeReceiptTx) return;
        const receiptWindow = window.open('', '_blank', 'width=1200,height=820');
        if (!receiptWindow) return;
        const printable = buildReceiptMarkup(activeReceiptTx, 'Official Copy');
        receiptWindow.document.write(`
            <html>
            <head>
                <title>Payment Receipt</title>
                <style>
                    * { box-sizing: border-box; }
                    @page { size: A4 landscape; margin: 12mm; }
                    body { font-family: Arial, Helvetica, sans-serif; margin: 0; background: #ffffff; color: #1d4f91; }
                    .print-sheet { width: 100%; min-height: 100vh; display: flex; align-items: center; justify-content: center; }
                    .receipt-holder { width: 100%; max-width: 1080px; }
                    @media print {
                        body { margin: 0; }
                    }
                </style>
            </head>
            <body>
                <div class="print-sheet">
                    <div class="receipt-holder">${printable}</div>
                </div>
            </body>
            </html>
        `);
        receiptWindow.document.close();
        receiptWindow.focus();
        receiptWindow.print();
    }

    function openEditTransactionModal(id) {
        const tx = (financeTransactions || []).find(t => String(t.id) === String(id));
        const modal = document.getElementById('edit-tx-modal');
        const content = document.getElementById('edit-tx-modal-content');
        if (!tx || !modal || !content) return;

        const idEl = document.getElementById('edit_tx_id');
        const amountEl = document.getElementById('edit_tx_amount');
        const dateEl = document.getElementById('edit_tx_date');
        const refEl = document.getElementById('edit_tx_ref');
        const descEl = document.getElementById('edit_tx_desc');
        if (!idEl || !amountEl || !dateEl || !refEl || !descEl) return;

        idEl.value = tx.id || '';
        amountEl.value = tx.amount || '';
        dateEl.value = (tx.transaction_date || '').slice(0, 10);
        refEl.value = tx.reference_no || '';
        descEl.value = tx.description || '';

        modal.classList.remove('hidden');
        setTimeout(() => {
            content.classList.remove('scale-95', 'opacity-0');
            content.classList.add('scale-100', 'opacity-100');
        }, 10);
    }

    function closeEditTransactionModal() {
        const modal = document.getElementById('edit-tx-modal');
        const content = document.getElementById('edit-tx-modal-content');
        if (!modal || !content) return;
        content.classList.remove('scale-100', 'opacity-100');
        content.classList.add('scale-95', 'opacity-0');
        setTimeout(() => modal.classList.add('hidden'), 300);
    }

    (function () {
        const select = document.getElementById('member-ledger-select');
        const loading = document.getElementById('member-ledger-loading');
        const empty = document.getElementById('member-ledger-empty');
        const body = document.getElementById('member-ledger-body');
        const totalsEl = document.getElementById('member-ledger-totals');
        const countEl = document.getElementById('member-ledger-count');
        const rowsEl = document.getElementById('member-ledger-rows');
        if (!select || !loading || !empty || !body || !totalsEl || !countEl || !rowsEl) return;

        const currency = <?php echo json_encode($currency); ?>;

        const renderTotals = (data) => {
            const income = Number(data?.income_total || 0);
            const expense = Number(data?.expense_total || 0);
            const net = Number(data?.net_total || 0);
            totalsEl.innerHTML = `
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
            `;
        };

        const renderRows = (items) => {
            countEl.textContent = `${items.length} record${items.length === 1 ? '' : 's'}`;
            rowsEl.innerHTML = items.length ? items.map(tx => {
                const isExpense = String(tx.transaction_type || '') === 'Expense';
                const dateStr = tx.transaction_date ? new Date(tx.transaction_date).toLocaleDateString(undefined, { year: 'numeric', month: 'short', day: '2-digit' }) : '';
                const amount = Number(tx.amount || 0).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                return `
                    <tr class="hover:bg-white/[0.03] transition-all duration-300">
                        <td class="px-8 py-5">
                            <p class="text-xs font-black text-slate-200">${dateStr || 'N/A'}</p>
                        </td>
                        <td class="px-8 py-5">
                            <span class="px-4 py-1.5 text-[9px] font-black rounded-full uppercase tracking-widest border ${isExpense ? 'bg-rose-500/10 text-rose-400 border-rose-500/20' : 'bg-emerald-500/10 text-emerald-400 border-emerald-500/20'}">
                                ${tx.transaction_type || ''}
                            </span>
                        </td>
                        <td class="px-8 py-5">
                            <p class="text-sm font-black ${isExpense ? 'text-rose-400' : 'text-emerald-400'}">
                                ${(isExpense ? '-' : '') + currency + ' ' + amount}
                            </p>
                        </td>
                        <td class="px-8 py-5 hidden md:table-cell">
                            <p class="text-xs font-bold text-slate-400">${tx.payment_method || 'N/A'}</p>
                        </td>
                        <td class="px-8 py-5 hidden lg:table-cell">
                            <p class="text-xs font-bold text-slate-400">${tx.recorded_by_name || 'N/A'}</p>
                        </td>
                    </tr>
                `;
            }).join('') : `
                <tr><td colspan="5" class="px-8 py-12 text-center text-slate-500 italic font-bold">No member transactions found.</td></tr>
            `;
        };

        const load = () => {
            const memberId = Number(select.value || 0);
            body.classList.add('hidden');
            loading.classList.add('hidden');
            empty.classList.add('hidden');
            totalsEl.innerHTML = '';
            rowsEl.innerHTML = '';
            countEl.textContent = '';

            if (!memberId) {
                empty.classList.remove('hidden');
                return;
            }

            loading.classList.remove('hidden');
            Promise.all([
                fetch(`<?php echo BASE_URL; ?>/finance/memberSummary?member_id=${encodeURIComponent(memberId)}`).then(r => r.json()),
                fetch(`<?php echo BASE_URL; ?>/finance/memberTransactions?member_id=${encodeURIComponent(memberId)}&limit=50`).then(r => r.json())
            ])
                .then(([summary, tx]) => {
                    loading.classList.add('hidden');
                    body.classList.remove('hidden');
                    renderTotals(summary);
                    renderRows(Array.isArray(tx?.transactions) ? tx.transactions : []);
                })
                .catch(() => {
                    loading.classList.add('hidden');
                    empty.classList.remove('hidden');
                });
        };

        select.addEventListener('change', load);
        empty.classList.remove('hidden');
    })();

    const receiptModalEl = document.getElementById('receipt-modal');
    if (receiptModalEl) {
        receiptModalEl.addEventListener('click', (e) => {
            if (e.target === receiptModalEl) closeReceiptModal();
        });
    }
    if (financeReceiptSeed && financeReceiptSeed.id) {
        setTimeout(() => openReceiptModal(financeReceiptSeed.id), 200);
    }
</script>

<div id="transaction-modal" class="fixed inset-0 bg-slate-950/80 backdrop-blur-xl z-50 hidden flex items-center justify-center p-4">
    <div class="glass-card w-full max-w-2xl rounded-[3.5rem] overflow-hidden shadow-2xl transform transition-all duration-500 scale-95 opacity-0 border-white/10 max-h-[90vh] flex flex-col" id="transaction-modal-content">
        <div class="px-10 py-10 bg-slate-900 relative overflow-hidden border-b border-white/5 flex-shrink-0">
            <div class="relative z-10 flex justify-between items-center text-white">
                <div>
                    <h3 id="tx-title" class="text-3xl font-black tracking-tighter">Transaction Details</h3>
                    <p class="text-accent text-[10px] font-black mt-2 tracking-[0.3em] uppercase">Finance Ledger</p>
                </div>
                <button type="button" onclick="closeTransactionModal()" class="w-12 h-12 bg-white/5 hover:bg-accent hover:text-slate-900 rounded-2xl flex items-center justify-center transition-all border border-white/10">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        </div>

        <div class="p-6 sm:p-10 bg-slate-900/50 overflow-y-auto custom-scrollbar flex-1 space-y-8">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <span id="tx-type" class="px-4 py-1.5 text-[9px] font-black rounded-full uppercase tracking-widest border bg-white/5 text-slate-300 border-white/10"></span>
                <div id="tx-amount" class="text-2xl font-black text-white tracking-tighter"></div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                <div class="bg-white/5 rounded-[2rem] p-6 border border-white/10">
                    <p class="text-[9px] font-black text-slate-500 uppercase tracking-widest">Transaction Type</p>
                    <p id="tx-type-label" class="text-sm font-black text-slate-200 mt-3"></p>
                </div>
                <div class="bg-white/5 rounded-[2rem] p-6 border border-white/10">
                    <p class="text-[9px] font-black text-slate-500 uppercase tracking-widest">Date</p>
                    <p id="tx-date" class="text-sm font-black text-slate-200 mt-3"></p>
                </div>
                <div class="bg-white/5 rounded-[2rem] p-6 border border-white/10">
                    <p class="text-[9px] font-black text-slate-500 uppercase tracking-widest">Payment Method</p>
                    <p id="tx-method" class="text-sm font-black text-slate-200 mt-3"></p>
                </div>
                <div class="bg-white/5 rounded-[2rem] p-6 border border-white/10">
                    <p class="text-[9px] font-black text-slate-500 uppercase tracking-widest">Reference</p>
                    <p id="tx-ref" class="text-sm font-black text-slate-200 mt-3"></p>
                </div>
                <div class="bg-white/5 rounded-[2rem] p-6 border border-white/10">
                    <p class="text-[9px] font-black text-slate-500 uppercase tracking-widest">Recorded By</p>
                    <p id="tx-recorder" class="text-sm font-black text-slate-200 mt-3"></p>
                </div>
                <div class="bg-white/5 rounded-[2rem] p-6 border border-white/10">
                    <p class="text-[9px] font-black text-slate-500 uppercase tracking-widest">Member</p>
                    <p id="tx-member" class="text-sm font-black text-slate-200 mt-3"></p>
                </div>
                <div class="bg-white/5 rounded-[2rem] p-6 border border-white/10">
                    <p class="text-[9px] font-black text-slate-500 uppercase tracking-widest">Department</p>
                    <p id="tx-dept" class="text-sm font-black text-slate-200 mt-3"></p>
                </div>
            </div>

            <div class="bg-white/5 rounded-[2.5rem] p-6 border border-white/10">
                <p class="text-[9px] font-black text-slate-500 uppercase tracking-widest">Description</p>
                <p id="tx-desc" class="text-sm font-bold text-slate-200 mt-3 whitespace-pre-wrap"></p>
            </div>

            <div id="tx-member-summary" class="space-y-4 hidden">
                <div class="flex items-center justify-between">
                    <h4 class="text-sm font-black text-white uppercase tracking-widest">Member Transactions</h4>
                </div>
                <div id="tx-member-summary-loading" class="glass-card p-10 rounded-[2.5rem] border-white/5 text-center text-slate-500 font-bold italic">
                    Loading member totals...
                </div>
                <div id="tx-member-summary-empty" class="hidden glass-card p-10 rounded-[2.5rem] border-white/5 text-center text-slate-500 font-bold italic">
                    No member linked to this transaction.
                </div>
                <div id="tx-member-summary-body" class="hidden"></div>
            </div>
        </div>
    </div>
</div>

<div id="receipt-modal" class="fixed inset-0 bg-slate-950/80 backdrop-blur-xl z-50 hidden flex items-center justify-center p-4">
    <div class="glass-card w-full max-w-xl rounded-[3.5rem] overflow-hidden shadow-2xl transform transition-all duration-500 scale-95 opacity-0 border-white/10 max-h-[90vh] flex flex-col" id="receipt-modal-content">
        <div class="px-10 py-10 bg-slate-900 relative overflow-hidden border-b border-white/5 flex-shrink-0">
            <div class="relative z-10 flex justify-between items-center text-white">
                <div>
                    <h3 class="text-3xl font-black tracking-tighter">Receipt Ready</h3>
                    <p class="text-accent text-[10px] font-black mt-2 tracking-[0.3em] uppercase">Print Confirmation</p>
                </div>
                <button type="button" onclick="closeReceiptModal()" class="w-12 h-12 bg-white/5 hover:bg-accent hover:text-slate-900 rounded-2xl flex items-center justify-center transition-all border border-white/10">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        </div>
        <div class="p-6 sm:p-10 bg-slate-900/50 overflow-y-auto custom-scrollbar flex-1">
            <div id="receipt-body" class="bg-white rounded-[2.5rem] p-8"></div>
        </div>
        <div class="px-6 sm:px-10 pb-8 bg-slate-900/50 flex flex-col sm:flex-row gap-4">
            <button type="button" onclick="printReceipt()" class="flex-1 bg-accent text-slate-900 py-5 rounded-2xl font-black text-xs uppercase tracking-[0.3em] hover:scale-[1.02] active:scale-95 transition-all shadow-xl shadow-yellow-500/10">
                Print Receipt
            </button>
            <button type="button" onclick="closeReceiptModal()" class="px-10 py-5 bg-white/5 text-slate-300 rounded-2xl font-black text-xs uppercase tracking-[0.3em] hover:bg-white/10 transition-all">
                Close
            </button>
        </div>
    </div>
</div>

<?php if ($isStaff): ?>
<div id="request-change-modal" class="fixed inset-0 bg-slate-950/80 backdrop-blur-xl z-50 hidden flex items-center justify-center p-4">
    <div class="glass-card w-full max-w-lg rounded-[3.5rem] overflow-hidden shadow-2xl transform transition-all duration-500 scale-95 opacity-0 border-white/10 max-h-[90vh] flex flex-col" id="request-change-modal-content">
        <div class="px-10 py-10 bg-slate-900 relative overflow-hidden border-b border-white/5 flex-shrink-0">
            <div class="relative z-10 flex justify-between items-center text-white">
                <div>
                    <h3 class="text-3xl font-black tracking-tighter">Request Edit</h3>
                    <p class="text-accent text-[10px] font-black mt-2 tracking-[0.3em] uppercase" id="request-change-subtitle">Send to head of finance for approval</p>
                </div>
                <button type="button" onclick="closeRequestChangeModal()" class="w-12 h-12 bg-white/5 hover:bg-accent hover:text-slate-900 rounded-2xl flex items-center justify-center transition-all border border-white/10">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        </div>

        <form action="<?php echo BASE_URL; ?>/finance/requestChange" method="POST" class="p-6 sm:p-10 bg-slate-900/50 overflow-y-auto custom-scrollbar flex-1 space-y-6">
            <input type="hidden" name="finance_id" id="request_change_finance_id">
            <div class="bg-white/5 rounded-[2.5rem] p-6 border border-white/10">
                <p class="text-[9px] font-black text-slate-500 uppercase tracking-widest">Transaction</p>
                <p class="text-sm font-black text-slate-200 mt-3" id="request-change-tx-label"></p>
            </div>

            <div class="space-y-2">
                <label class="block text-[9px] font-black text-slate-500 uppercase tracking-widest ml-1">Reason</label>
                <div class="relative group">
                    <i class="fas fa-message absolute left-5 top-5 text-slate-600 group-focus-within:text-accent transition-colors"></i>
                    <textarea name="reason" id="request_change_reason" rows="4" required class="w-full bg-white/5 border border-white/10 focus:border-accent rounded-3xl pl-14 pr-6 py-5 text-sm font-bold text-white transition-all outline-none resize-none" placeholder="Explain what is wrong and what should be corrected"></textarea>
                </div>
            </div>

            <div class="flex flex-col sm:flex-row gap-4 pt-2">
                <button type="submit" class="flex-1 bg-accent text-slate-900 py-5 rounded-2xl font-black text-xs uppercase tracking-[0.2em] hover:scale-[1.02] active:scale-95 transition-all shadow-xl shadow-yellow-500/10">
                    Send Request
                </button>
                <button type="button" onclick="closeRequestChangeModal()" class="px-10 py-5 bg-white/5 text-slate-400 rounded-2xl font-black text-xs uppercase tracking-[0.3em] hover:bg-white/10 transition-all">
                    Cancel
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    function openRequestChangeModal(id) {
        const tx = (financeTransactions || []).find(t => String(t.id) === String(id));
        const modal = document.getElementById('request-change-modal');
        const content = document.getElementById('request-change-modal-content');
        const idEl = document.getElementById('request_change_finance_id');
        const labelEl = document.getElementById('request-change-tx-label');
        const reasonEl = document.getElementById('request_change_reason');
        if (!tx || !modal || !content || !idEl || !labelEl || !reasonEl) return;

        const type = tx.transaction_type || 'Transaction';
        const amount = Number(tx.amount || 0).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        const date = (tx.transaction_date || '').slice(0, 10);
        const ref = (tx.reference_no || '').trim();

        idEl.value = tx.id || '';
        labelEl.textContent = `${type} • ${date || 'N/A'} • <?php echo $currency; ?> ${amount}${ref ? ' • Ref: ' + ref : ''}`;
        reasonEl.value = '';

        modal.classList.remove('hidden');
        setTimeout(() => {
            content.classList.remove('scale-95', 'opacity-0');
            content.classList.add('scale-100', 'opacity-100');
        }, 10);
    }

    function closeRequestChangeModal() {
        const modal = document.getElementById('request-change-modal');
        const content = document.getElementById('request-change-modal-content');
        if (!modal || !content) return;
        content.classList.remove('scale-100', 'opacity-100');
        content.classList.add('scale-95', 'opacity-0');
        setTimeout(() => modal.classList.add('hidden'), 300);
    }

    document.getElementById('request-change-modal')?.addEventListener('click', (e) => {
        if (e.target === document.getElementById('request-change-modal')) closeRequestChangeModal();
    });
</script>
<?php endif; ?>

<?php if (!empty($isDeptHead) || $isStaff): ?>
<div id="edit-tx-modal" class="fixed inset-0 bg-slate-950/80 backdrop-blur-xl z-50 hidden flex items-center justify-center p-4">
    <div class="glass-card w-full max-w-lg rounded-[3.5rem] overflow-hidden shadow-2xl transform transition-all duration-500 scale-95 opacity-0 border-white/10 max-h-[90vh] flex flex-col" id="edit-tx-modal-content">
        <div class="px-10 py-10 bg-slate-900 relative overflow-hidden border-b border-white/5 flex-shrink-0">
            <div class="relative z-10 flex justify-between items-center text-white">
                <div>
                    <h3 class="text-3xl font-black tracking-tighter">Update Transaction</h3>
                    <p class="text-accent text-[10px] font-black mt-2 tracking-[0.3em] uppercase"><?php echo !empty($isDeptHead) ? 'Department Bank Transaction' : 'Approved Change Request'; ?></p>
                </div>
                <button type="button" onclick="closeEditTransactionModal()" class="w-12 h-12 bg-white/5 hover:bg-accent hover:text-slate-900 rounded-2xl flex items-center justify-center transition-all border border-white/10">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        </div>

        <form action="<?php echo BASE_URL; ?>/finance/updateTransaction" method="POST" class="p-6 sm:p-10 bg-slate-900/50 overflow-y-auto custom-scrollbar flex-1 space-y-6">
            <input type="hidden" name="id" id="edit_tx_id">

            <div class="space-y-2">
                <label class="block text-[9px] font-black text-slate-500 uppercase tracking-widest ml-1">Amount</label>
                <div class="relative group">
                    <i class="fas fa-coins absolute left-5 top-1/2 -translate-y-1/2 text-slate-600 group-focus-within:text-accent transition-colors"></i>
                    <input type="number" step="0.01" min="0" name="amount" id="edit_tx_amount" required class="w-full bg-white/5 border border-white/10 focus:border-accent rounded-2xl pl-14 pr-6 py-4 text-sm font-bold text-white transition-all outline-none">
                </div>
            </div>

            <div class="space-y-2">
                <label class="block text-[9px] font-black text-slate-500 uppercase tracking-widest ml-1">Transaction Date</label>
                <div class="relative group">
                    <i class="fas fa-calendar-alt absolute left-5 top-1/2 -translate-y-1/2 text-slate-600 group-focus-within:text-accent transition-colors"></i>
                    <input type="date" name="transaction_date" id="edit_tx_date" required class="w-full bg-white/5 border border-white/10 focus:border-accent rounded-2xl pl-14 pr-6 py-4 text-sm font-bold text-white transition-all outline-none [color-scheme:dark]">
                </div>
            </div>

            <div class="space-y-2">
                <label class="block text-[9px] font-black text-slate-500 uppercase tracking-widest ml-1">Reference</label>
                <div class="relative group">
                    <i class="fas fa-hashtag absolute left-5 top-1/2 -translate-y-1/2 text-slate-600 group-focus-within:text-accent transition-colors"></i>
                    <input type="text" name="reference_no" id="edit_tx_ref" class="w-full bg-white/5 border border-white/10 focus:border-accent rounded-2xl pl-14 pr-6 py-4 text-sm font-bold text-white transition-all outline-none" placeholder="Optional reference">
                </div>
            </div>

            <div class="space-y-2">
                <label class="block text-[9px] font-black text-slate-500 uppercase tracking-widest ml-1">Description</label>
                <div class="relative group">
                    <i class="fas fa-note-sticky absolute left-5 top-5 text-slate-600 group-focus-within:text-accent transition-colors"></i>
                    <textarea name="description" id="edit_tx_desc" rows="3" class="w-full bg-white/5 border border-white/10 focus:border-accent rounded-3xl pl-14 pr-6 py-5 text-sm font-bold text-white transition-all outline-none resize-none" placeholder="Optional notes"></textarea>
                </div>
            </div>

            <div class="flex flex-col sm:flex-row gap-4 pt-2">
                <button type="submit" class="flex-1 bg-accent text-slate-900 py-5 rounded-2xl font-black text-xs uppercase tracking-[0.2em] hover:scale-[1.02] active:scale-95 transition-all shadow-xl shadow-yellow-500/10">
                    Save Changes
                </button>
                <button type="button" onclick="closeEditTransactionModal()" class="px-10 py-5 bg-white/5 text-slate-400 rounded-2xl font-black text-xs uppercase tracking-[0.3em] hover:bg-white/10 transition-all">
                    Cancel
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    document.getElementById('edit-tx-modal')?.addEventListener('click', (e) => {
        if (e.target === document.getElementById('edit-tx-modal')) closeEditTransactionModal();
    });
</script>
<?php endif; ?>

<script>
    document.getElementById('transaction-modal').addEventListener('click', (e) => {
        if (e.target === document.getElementById('transaction-modal')) closeTransactionModal();
    });
</script>
