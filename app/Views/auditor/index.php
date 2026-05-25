<?php
    $currency = strtoupper(trim((string)($currency ?? 'GHS')));
    if (!preg_match('/^[A-Z]{2,5}$/', $currency)) $currency = 'GHS';
    $monthLabel = $month_label ?? date('F Y');
    $generalAllTime = $general_all_time ?? [];
    $departmentAllTime = $department_all_time ?? [];
    $combinedAllTime = $combined_all_time ?? [];
    $recentTransactions = $recent_transactions ?? [];
    $recentExpenses = $recent_expenses ?? [];
    $departmentSavings = $department_savings ?? [];
?>

<div class="flex flex-col sm:flex-row justify-between items-start mb-10 gap-4">
    <div>
        <h2 class="text-4xl sm:text-5xl font-black text-white tracking-tighter">Auditor</h2>
        <p class="text-slate-400 font-bold mt-2 uppercase tracking-widest text-xs">Read-only monitoring and report downloads</p>
    </div>
    <div class="flex flex-wrap gap-3">
        <a href="<?php echo BASE_URL; ?>/reports" class="glass-card px-5 py-3 rounded-2xl border-white/10 text-slate-300 font-black text-[10px] uppercase tracking-widest hover:bg-white/5 transition-all">
            <i class="fas fa-chart-pie mr-2 text-accent"></i> Open Reports
        </a>
        <a href="<?php echo BASE_URL; ?>/reports/download?type=finance_summary" class="glass-card px-5 py-3 rounded-2xl border-white/10 text-slate-300 font-black text-[10px] uppercase tracking-widest hover:bg-white/5 transition-all">
            <i class="fas fa-download mr-2 text-accent"></i> Finance Summary CSV
        </a>
        <a href="<?php echo BASE_URL; ?>/reports/download?type=income_expense_6m" class="glass-card px-5 py-3 rounded-2xl border-white/10 text-slate-300 font-black text-[10px] uppercase tracking-widest hover:bg-white/5 transition-all">
            <i class="fas fa-download mr-2 text-accent"></i> Income/Expense 6M CSV
        </a>
    </div>
</div>

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

<div class="grid grid-cols-1 xl:grid-cols-2 gap-8">
    <div class="glass-card rounded-[3rem] border-white/5 overflow-hidden card-interaction">
        <div class="px-10 py-8 border-b border-white/5 flex items-center justify-between bg-white/[0.02]">
            <div class="flex items-center">
                <div class="w-10 h-10 bg-accent/10 rounded-xl flex items-center justify-center mr-4 border border-accent/20">
                    <i class="fas fa-sitemap text-accent text-sm"></i>
                </div>
                <h4 class="text-xl font-black text-white tracking-tight">Departmental Savings</h4>
            </div>
            <div class="text-[10px] font-black text-slate-500 uppercase tracking-widest"><?php echo htmlspecialchars($monthLabel); ?></div>
        </div>
        <div class="p-5 sm:p-6 lg:p-10">
            <?php if (empty($departmentSavings)): ?>
                <div class="px-4 py-12 text-center text-slate-500 italic font-bold">No departmental savings found.</div>
            <?php else: ?>
                <div class="overflow-x-auto custom-scrollbar">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="text-[10px] font-black text-slate-500 uppercase tracking-[0.3em] border-b border-white/5 bg-white/[0.01]">
                                <th class="px-6 py-5">Department</th>
                                <th class="px-6 py-5">Income</th>
                                <th class="px-6 py-5">Expenses</th>
                                <th class="px-6 py-5">Net</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-white/[0.02]">
                            <?php foreach ($departmentSavings as $row): ?>
                                <?php
                                    $income = (float)($row['income_total'] ?? 0);
                                    $expense = (float)($row['expense_total'] ?? 0);
                                    $net = (float)($row['balance'] ?? ($income - $expense));
                                ?>
                                <tr class="hover:bg-white/[0.03] transition-all duration-300">
                                    <td class="px-6 py-5">
                                        <p class="text-sm font-black text-slate-200"><?php echo htmlspecialchars($row['department_name'] ?? ''); ?></p>
                                    </td>
                                    <td class="px-6 py-5">
                                        <p class="text-sm font-black text-emerald-400"><?php echo $currency . ' ' . number_format($income, 2); ?></p>
                                    </td>
                                    <td class="px-6 py-5">
                                        <p class="text-sm font-black text-rose-400"><?php echo $currency . ' ' . number_format($expense, 2); ?></p>
                                    </td>
                                    <td class="px-6 py-5">
                                        <p class="text-sm font-black <?php echo $net >= 0 ? 'text-accent' : 'text-rose-400'; ?>"><?php echo $currency . ' ' . number_format($net, 2); ?></p>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="glass-card rounded-[3rem] border-white/5 overflow-hidden card-interaction">
        <div class="px-10 py-8 border-b border-white/5 flex items-center justify-between bg-white/[0.02]">
            <div class="flex items-center">
                <div class="w-10 h-10 bg-rose-500/10 rounded-xl flex items-center justify-center mr-4 border border-rose-500/20">
                    <i class="fas fa-receipt text-rose-300 text-sm"></i>
                </div>
                <h4 class="text-xl font-black text-white tracking-tight">Recent Expenses</h4>
            </div>
            <div class="text-[10px] font-black text-slate-500 uppercase tracking-widest"><?php echo count($recentExpenses); ?> records</div>
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
</div>

<div class="glass-card rounded-[3rem] border-white/5 overflow-hidden mt-10 card-interaction">
    <div class="px-10 py-8 border-b border-white/5 flex items-center justify-between bg-white/[0.02]">
        <div class="flex items-center">
            <div class="w-10 h-10 bg-accent/10 rounded-xl flex items-center justify-center mr-4 border border-accent/20">
                <i class="fas fa-wallet text-accent text-sm"></i>
            </div>
            <h4 class="text-xl font-black text-white tracking-tight">Recent Transactions</h4>
        </div>
        <div class="text-[10px] font-black text-slate-500 uppercase tracking-widest"><?php echo count($recentTransactions); ?> records</div>
    </div>
    <div class="p-5 sm:p-6 lg:p-10">
        <?php if (empty($recentTransactions)): ?>
            <div class="px-4 py-12 text-center text-slate-500 italic font-bold">No transactions recorded yet.</div>
        <?php else: ?>
            <div class="overflow-x-auto custom-scrollbar">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="text-[10px] font-black text-slate-500 uppercase tracking-[0.3em] border-b border-white/5 bg-white/[0.01]">
                            <th class="px-6 py-5">Date</th>
                            <th class="px-6 py-5">Type</th>
                            <th class="px-6 py-5">Amount</th>
                            <th class="px-6 py-5 hidden lg:table-cell">Method</th>
                            <th class="px-6 py-5 hidden lg:table-cell">Department</th>
                            <th class="px-6 py-5">Recorded By</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-white/[0.02]">
                        <?php foreach ($recentTransactions as $tx): ?>
                            <?php
                                $isExpense = ($tx['transaction_type'] ?? '') === 'Expense';
                                $label = (string)($tx['transaction_type'] ?? '');
                                if ($label === 'Offering' && !empty($tx['offering_subtype'])) {
                                    $label = 'Offering (' . (string)$tx['offering_subtype'] . ')';
                                }
                            ?>
                            <tr class="hover:bg-white/[0.03] transition-all duration-300">
                                <td class="px-6 py-5">
                                    <p class="text-sm font-black text-slate-200"><?php echo htmlspecialchars(date('M d, Y', strtotime($tx['transaction_date']))); ?></p>
                                    <p class="text-[10px] font-black text-slate-500 uppercase tracking-widest mt-1"><?php echo htmlspecialchars($tx['transaction_number'] ?? ''); ?></p>
                                </td>
                                <td class="px-6 py-5">
                                    <p class="text-sm font-black text-slate-200"><?php echo htmlspecialchars($label); ?></p>
                                    <p class="text-[10px] font-black text-slate-500 uppercase tracking-widest mt-1"><?php echo htmlspecialchars(($tx['member_name'] ?? '') !== '' ? $tx['member_name'] : ''); ?></p>
                                </td>
                                <td class="px-6 py-5">
                                    <p class="text-sm font-black <?php echo $isExpense ? 'text-rose-400' : 'text-emerald-400'; ?>">
                                        <?php echo ($isExpense ? '-' : '') . $currency . ' ' . number_format((float)($tx['amount'] ?? 0), 2); ?>
                                    </p>
                                </td>
                                <td class="px-6 py-5 hidden lg:table-cell">
                                    <p class="text-xs font-bold text-slate-400"><?php echo htmlspecialchars($tx['payment_method'] ?? ''); ?></p>
                                </td>
                                <td class="px-6 py-5 hidden lg:table-cell">
                                    <p class="text-xs font-bold text-slate-400"><?php echo htmlspecialchars(($tx['department_name'] ?? '') !== '' ? $tx['department_name'] : 'Church'); ?></p>
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
