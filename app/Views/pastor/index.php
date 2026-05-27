<?php
    $currency = strtoupper(trim((string)($currency ?? 'GHS')));
    if (!preg_match('/^[A-Z]{2,5}$/', $currency)) $currency = 'GHS';

    $members = $members ?? [];
    $stats = $stats ?? [];
    $departments = $departments ?? [];
    $filters = $filters ?? ['search' => '', 'department' => '', 'status' => ''];
    $recentTransactions = $recent_transactions ?? [];
    $pendingChangeRequests = $pending_change_requests ?? [];
    $pendingDepartmentExpenseRequests = $pending_department_expense_requests ?? [];
    $upcomingBirthdays = $upcoming_birthdays ?? [];
?>

<div class="flex flex-col sm:flex-row justify-between items-start mb-10 gap-4">
    <div>
        <h2 class="text-4xl sm:text-5xl font-black text-white tracking-tighter">Pastor</h2>
        <p class="text-slate-400 font-bold mt-2 uppercase tracking-widest text-xs">Read-only oversight • Reports • Birthdays • Messaging</p>
    </div>
    <div class="flex flex-wrap gap-3">
        <a href="<?php echo BASE_URL; ?>/reports" class="glass-card px-5 py-3 rounded-2xl border-white/10 text-slate-300 font-black text-[10px] uppercase tracking-widest hover:bg-white/5 transition-all">
            <i class="fas fa-chart-pie mr-2 text-accent"></i> Open Reports
        </a>
        <a href="<?php echo BASE_URL; ?>/reports/download?type=finance_summary" class="glass-card px-5 py-3 rounded-2xl border-white/10 text-slate-300 font-black text-[10px] uppercase tracking-widest hover:bg-white/5 transition-all">
            <i class="fas fa-download mr-2 text-accent"></i> Finance Summary CSV
        </a>
        <a href="<?php echo BASE_URL; ?>/reports/download?type=members_summary" class="glass-card px-5 py-3 rounded-2xl border-white/10 text-slate-300 font-black text-[10px] uppercase tracking-widest hover:bg-white/5 transition-all">
            <i class="fas fa-download mr-2 text-accent"></i> Members Summary CSV
        </a>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-10">
    <div class="glass-card rounded-[2.5rem] p-8 border-white/5 card-interaction">
        <p class="text-[10px] font-black uppercase tracking-[0.3em] text-slate-400">Total Members</p>
        <h3 class="text-4xl font-black mt-3 text-white tracking-tighter"><?php echo number_format((int)($stats['total'] ?? 0)); ?></h3>
        <p class="text-[10px] font-black uppercase tracking-widest mt-6 text-slate-500"><?php echo number_format((int)($stats['active'] ?? 0)); ?> active</p>
    </div>
    <div class="glass-card rounded-[2.5rem] p-8 border-white/5 card-interaction">
        <p class="text-[10px] font-black uppercase tracking-[0.3em] text-slate-400">Upcoming Birthdays</p>
        <h3 class="text-4xl font-black mt-3 text-accent tracking-tighter"><?php echo number_format(count($upcomingBirthdays)); ?></h3>
        <p class="text-[10px] font-black uppercase tracking-widest mt-6 text-slate-500">Next few members</p>
    </div>
    <div class="glass-card rounded-[2.5rem] p-8 border-white/5 card-interaction">
        <p class="text-[10px] font-black uppercase tracking-[0.3em] text-slate-400">Pending Requests</p>
        <h3 class="text-4xl font-black mt-3 text-rose-300 tracking-tighter"><?php echo number_format(count($pendingChangeRequests) + count($pendingDepartmentExpenseRequests)); ?></h3>
        <p class="text-[10px] font-black uppercase tracking-widest mt-6 text-slate-500">Read-only</p>
    </div>
</div>

<div class="grid grid-cols-1 xl:grid-cols-2 gap-8 mb-10">
    <div class="glass-card rounded-[3rem] border-white/5 overflow-hidden card-interaction">
        <div class="px-10 py-8 border-b border-white/5 flex items-center justify-between bg-white/[0.02]">
            <div class="flex items-center">
                <div class="w-10 h-10 bg-accent/10 rounded-xl flex items-center justify-center mr-4 border border-accent/20">
                    <i class="fas fa-cake-candles text-accent text-sm"></i>
                </div>
                <h4 class="text-xl font-black text-white tracking-tight">Upcoming Birthdays</h4>
            </div>
            <div class="text-[10px] font-black text-slate-500 uppercase tracking-widest"><?php echo date('F'); ?> • <?php echo date('Y'); ?></div>
        </div>
        <div class="p-5 sm:p-6 lg:p-10">
            <?php if (empty($upcomingBirthdays)): ?>
                <div class="px-4 py-12 text-center text-slate-500 italic font-bold">No upcoming birthdays found.</div>
            <?php else: ?>
                <div class="space-y-3">
                    <?php foreach ($upcomingBirthdays as $m): ?>
                        <?php
                            $photo = trim((string)($m['photo_path'] ?? ''));
                            $photoSrc = $photo !== '' ? (BASE_URL . '/' . ltrim($photo, '/')) : (BASE_URL . '/public/assets/user-placeholder.png');
                            $label = trim((string)($m['first_name'] ?? '') . ' ' . (string)($m['last_name'] ?? ''));
                        ?>
                        <div class="flex items-center justify-between gap-4 bg-white/5 border border-white/10 rounded-2xl px-5 py-4">
                            <div class="flex items-center gap-4 min-w-0">
                                <img src="<?php echo htmlspecialchars($photoSrc); ?>" class="w-11 h-11 rounded-xl object-cover border border-white/10" alt="">
                                <div class="min-w-0">
                                    <div class="text-sm font-black text-slate-200 truncate"><?php echo htmlspecialchars($label !== '' ? $label : 'MEMBER'); ?></div>
                                    <div class="text-[10px] font-black uppercase tracking-widest text-slate-500 truncate"><?php echo htmlspecialchars((string)($m['birthday_display'] ?? '')); ?></div>
                                </div>
                            </div>
                            <div class="text-[10px] font-black uppercase tracking-widest text-slate-400"><?php echo htmlspecialchars((string)($m['phone'] ?? '')); ?></div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="glass-card rounded-[3rem] border-white/5 overflow-hidden card-interaction">
        <div class="px-10 py-8 border-b border-white/5 flex items-center justify-between bg-white/[0.02]">
            <div class="flex items-center">
                <div class="w-10 h-10 bg-rose-500/10 rounded-xl flex items-center justify-center mr-4 border border-rose-500/20">
                    <i class="fas fa-hourglass-half text-rose-300 text-sm"></i>
                </div>
                <h4 class="text-xl font-black text-white tracking-tight">Pending Finance Requests</h4>
            </div>
            <div class="text-[10px] font-black text-slate-500 uppercase tracking-widest">Read-only</div>
        </div>
        <div class="p-5 sm:p-6 lg:p-10 space-y-8">
            <div>
                <div class="text-[10px] font-black uppercase tracking-[0.3em] text-slate-500 mb-3">Transaction Change Requests</div>
                <?php if (empty($pendingChangeRequests)): ?>
                    <div class="px-4 py-8 text-center text-slate-500 italic font-bold">No pending change requests.</div>
                <?php else: ?>
                    <div class="overflow-x-auto custom-scrollbar">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr class="text-[10px] font-black text-slate-500 uppercase tracking-[0.3em] border-b border-white/5 bg-white/[0.01]">
                                    <th class="px-6 py-5">Txn</th>
                                    <th class="px-6 py-5">Type</th>
                                    <th class="px-6 py-5">Requested By</th>
                                    <th class="px-6 py-5">Created</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-white/[0.02]">
                                <?php foreach (array_slice($pendingChangeRequests, 0, 18) as $r): ?>
                                    <tr class="hover:bg-white/[0.03] transition-all duration-300">
                                        <td class="px-6 py-5">
                                            <div class="text-sm font-black text-slate-200"><?php echo htmlspecialchars((string)($r['transaction_number'] ?? '')); ?></div>
                                            <div class="text-[10px] font-black uppercase tracking-widest text-slate-500"><?php echo htmlspecialchars((string)($r['member_name'] ?? $r['department_name'] ?? '')); ?></div>
                                        </td>
                                        <td class="px-6 py-5 text-sm font-black text-slate-300"><?php echo htmlspecialchars((string)($r['transaction_type'] ?? '')); ?></td>
                                        <td class="px-6 py-5 text-sm font-black text-slate-300"><?php echo htmlspecialchars((string)($r['requested_by_name'] ?? '')); ?></td>
                                        <td class="px-6 py-5 text-[10px] font-black uppercase tracking-widest text-slate-500"><?php echo htmlspecialchars((string)($r['created_at'] ?? '')); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>

            <div>
                <div class="text-[10px] font-black uppercase tracking-[0.3em] text-slate-500 mb-3">Department Expense Requests</div>
                <?php if (empty($pendingDepartmentExpenseRequests)): ?>
                    <div class="px-4 py-8 text-center text-slate-500 italic font-bold">No pending department expense requests.</div>
                <?php else: ?>
                    <div class="overflow-x-auto custom-scrollbar">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr class="text-[10px] font-black text-slate-500 uppercase tracking-[0.3em] border-b border-white/5 bg-white/[0.01]">
                                    <th class="px-6 py-5">Department</th>
                                    <th class="px-6 py-5">Amount</th>
                                    <th class="px-6 py-5">Requested By</th>
                                    <th class="px-6 py-5">Created</th>
                                    <th class="px-6 py-5">Status</th>
                                    <th class="px-6 py-5 text-right">Action</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-white/[0.02]">
                                <?php foreach (array_slice($pendingDepartmentExpenseRequests, 0, 18) as $r): ?>
                                    <?php
                                        $financeApproved = !empty($r['approved_by']);
                                        $pastorApproved = !empty($r['pastor_approved_by']);
                                        $approvalLabel = 'Pending';
                                        if ($pastorApproved && !$financeApproved) $approvalLabel = 'Approved by pastor, waiting for head of finance';
                                        if ($financeApproved && !$pastorApproved) $approvalLabel = 'Approved by head of finance, waiting for pastor';
                                        if ($financeApproved && $pastorApproved) $approvalLabel = 'Approved by pastor + head of finance';
                                    ?>
                                    <tr class="hover:bg-white/[0.03] transition-all duration-300">
                                        <td class="px-6 py-5 text-sm font-black text-slate-200"><?php echo htmlspecialchars((string)($r['department_name'] ?? '')); ?></td>
                                        <td class="px-6 py-5 text-sm font-black text-rose-300"><?php echo $currency . ' ' . number_format((float)($r['amount'] ?? 0), 2); ?></td>
                                        <td class="px-6 py-5 text-sm font-black text-slate-300"><?php echo htmlspecialchars((string)($r['requested_by_name'] ?? '')); ?></td>
                                        <td class="px-6 py-5 text-[10px] font-black uppercase tracking-widest text-slate-500"><?php echo htmlspecialchars((string)($r['created_at'] ?? '')); ?></td>
                                        <td class="px-6 py-5">
                                            <span class="text-[10px] font-black uppercase tracking-widest text-slate-400"><?php echo htmlspecialchars($approvalLabel); ?></span>
                                        </td>
                                        <td class="px-6 py-5 text-right">
                                            <div class="flex items-center justify-end gap-2">
                                                <?php if (empty($r['pastor_approved_by'])): ?>
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
                                                    <span class="text-[10px] font-black uppercase tracking-widest text-slate-500">Awaiting head of finance</span>
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
    </div>
</div>
