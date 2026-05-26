<?php
    $churchName = AppConfig::getSetting('church_name', 'Church Management');
    $currency = strtoupper(trim(($bank['currency'] ?? 'GHS')));
    if (!preg_match('/^[A-Z]{2,5}$/', $currency)) $currency = 'GHS';
    $month = (int)($month ?? (int)date('m'));
    $year = (int)($year ?? (int)date('Y'));
?>

<div class="flex flex-col sm:flex-row justify-between items-start mb-10 gap-4">
    <div>
        <h2 class="text-4xl sm:text-5xl font-black text-white tracking-tighter">Departmental Savings</h2>
        <p class="text-slate-400 font-bold mt-2 uppercase tracking-widest text-xs">Quick access summary for <span class="text-accent"><?php echo htmlspecialchars($churchName); ?></span></p>
    </div>
    <div class="flex flex-wrap gap-3">
        <a href="<?php echo BASE_URL; ?>/finance" class="glass-card flex items-center px-6 py-3.5 rounded-2xl border-white/10 text-slate-300 font-black text-xs uppercase tracking-widest hover:bg-white/5 transition-all">
            <i class="fas fa-arrow-left mr-2"></i> Finance
        </a>
        <a href="<?php echo BASE_URL; ?>/transactions" class="glass-card flex items-center px-6 py-3.5 rounded-2xl border-white/10 text-slate-300 font-black text-xs uppercase tracking-widest hover:bg-white/5 transition-all">
            <i class="fas fa-receipt mr-2"></i> Transactions
        </a>
    </div>
</div>

<div class="glass-card rounded-[2.5rem] sm:rounded-[3rem] border-white/5 overflow-hidden mb-12 card-interaction">
    <div class="px-6 sm:px-8 lg:px-10 py-6 sm:py-8 border-b border-white/5 flex flex-col lg:flex-row items-start lg:items-center justify-between gap-6 bg-white/[0.02]">
        <div class="flex items-center">
            <div class="w-10 h-10 bg-accent/10 rounded-xl flex items-center justify-center mr-4 border border-accent/20">
                <i class="fas fa-piggy-bank text-accent text-sm"></i>
            </div>
            <div>
                <h4 class="text-xl font-black text-white tracking-tight">Summary</h4>
                <div class="text-[10px] font-black text-slate-500 uppercase tracking-widest mt-2">
                    <?php echo !empty($isDeptHead) ? 'Your Department' : htmlspecialchars($month_label ?? date('F Y')); ?>
                </div>
            </div>
        </div>

        <form method="GET" action="<?php echo BASE_URL; ?>/department-savings" class="w-full lg:w-auto flex flex-col sm:flex-row gap-3 items-stretch sm:items-end">
            <div class="flex-1 sm:flex-none">
                <label class="block text-[9px] font-black text-slate-500 uppercase tracking-widest ml-1 mb-2">Month</label>
                <select name="month" class="w-full bg-white/5 border border-white/10 focus:border-accent rounded-2xl px-5 py-4 text-sm font-bold text-white transition-all outline-none appearance-none cursor-pointer">
                    <?php
                        $months = [
                            1=>'January',2=>'February',3=>'March',4=>'April',5=>'May',6=>'June',
                            7=>'July',8=>'August',9=>'September',10=>'October',11=>'November',12=>'December'
                        ];
                        foreach ($months as $m => $label):
                    ?>
                        <option value="<?php echo (int)$m; ?>" <?php echo ((int)$month === (int)$m) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($label); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="flex-1 sm:flex-none">
                <label class="block text-[9px] font-black text-slate-500 uppercase tracking-widest ml-1 mb-2">Year</label>
                <select name="year" class="w-full bg-white/5 border border-white/10 focus:border-accent rounded-2xl px-5 py-4 text-sm font-bold text-white transition-all outline-none appearance-none cursor-pointer">
                    <?php
                        $yNow = (int)date('Y');
                        for ($y = $yNow + 1; $y >= ($yNow - 5); $y--):
                    ?>
                        <option value="<?php echo (int)$y; ?>" <?php echo ((int)$year === (int)$y) ? 'selected' : ''; ?>>
                            <?php echo (int)$y; ?>
                        </option>
                    <?php endfor; ?>
                </select>
            </div>
            <button type="submit" class="glass-card px-6 py-4 rounded-2xl border-white/10 text-slate-300 font-black text-[10px] uppercase tracking-widest hover:bg-white/5 transition-all">
                <i class="fas fa-filter mr-2 text-accent"></i> Apply
            </button>
        </form>
    </div>

    <div class="p-5 sm:p-6 lg:p-10">
        <?php if (empty($department_savings)): ?>
            <div class="glass-card p-16 rounded-[2.5rem] border-white/5 text-center">
                <div class="w-20 h-20 bg-white/5 rounded-[2rem] flex items-center justify-center mx-auto mb-6 border border-white/10">
                    <i class="fas fa-sitemap text-slate-700 text-3xl"></i>
                </div>
                <p class="text-slate-400 font-black uppercase tracking-widest text-xs">No departmental savings found</p>
            </div>
        <?php else: ?>
            <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">
                <?php foreach (($department_savings ?? []) as $row): ?>
                    <?php
                        $deptIncome = (float)($row['income_total'] ?? 0);
                        $deptExpense = (float)($row['expense_total'] ?? 0);
                        $deptBalance = (float)($row['balance'] ?? 0);
                    ?>
                    <div class="glass-card rounded-[2.5rem] p-5 sm:p-6 lg:p-8 border-white/5 hover:bg-white/[0.03] transition-all duration-500 group relative overflow-hidden card-interaction">
                        <div class="absolute top-0 right-0 -mr-10 -mt-10 w-40 h-40 bg-accent/5 rounded-full blur-3xl group-hover:bg-accent/10 transition-all duration-700"></div>
                        <div class="relative z-10">
                            <div class="flex items-start justify-between mb-8">
                                <div>
                                    <p class="text-[10px] font-black text-slate-500 uppercase tracking-[0.3em]">Department</p>
                                    <h5 class="text-xl font-black text-white tracking-tight mt-2"><?php echo htmlspecialchars($row['department_name'] ?? ''); ?></h5>
                                </div>
                                <div class="w-12 h-12 bg-white/5 rounded-2xl flex items-center justify-center border border-white/10 group-hover:bg-accent transition-all">
                                    <i class="fas fa-coins text-accent group-hover:text-slate-900 transition-colors"></i>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-8">
                                <div class="bg-white/5 rounded-2xl p-4 border border-white/5">
                                    <p class="text-[9px] font-black text-slate-500 uppercase tracking-widest">Income</p>
                                    <p class="text-sm font-black text-emerald-400 mt-2"><?php echo $currency . ' ' . number_format($deptIncome, 2); ?></p>
                                </div>
                                <div class="bg-white/5 rounded-2xl p-4 border border-white/5">
                                    <p class="text-[9px] font-black text-slate-500 uppercase tracking-widest">Expenses</p>
                                    <p class="text-sm font-black text-rose-400 mt-2"><?php echo $currency . ' ' . number_format($deptExpense, 2); ?></p>
                                </div>
                            </div>

                            <div class="pt-6 border-t border-white/5 flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                                <p class="text-[10px] font-black text-slate-500 uppercase tracking-widest">Net</p>
                                <p class="text-lg font-black <?php echo $deptBalance >= 0 ? 'text-accent' : 'text-rose-400'; ?>">
                                    <?php echo $currency . ' ' . number_format($deptBalance, 2); ?>
                                </p>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

