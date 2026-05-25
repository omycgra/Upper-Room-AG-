<?php
    $churchName = AppConfig::getSetting('church_name', 'Church Management');
    $currency = strtoupper(trim($currency ?? 'GHS'));
    if (!preg_match('/^[A-Z]{2,5}$/', $currency)) $currency = 'GHS';

    $monthLabels = $monthLabels ?? [];
    $incomeSeries = $incomeSeries ?? [];
    $expenseSeries = $expenseSeries ?? [];
    $newMembersSeries = $newMembersSeries ?? [];
    $genderMap = $genderMap ?? ['male' => 0, 'female' => 0, 'other' => 0];
    $deptRows = $deptRows ?? [];
    $typeRows = $typeRows ?? [];
    $totals = $totals ?? ['members' => 0, 'active_members' => 0, 'transactions' => 0, 'income' => 0, 'expenses' => 0, 'balance' => 0];
?>

<div class="flex flex-col sm:flex-row justify-between items-start mb-10 gap-4">
    <div>
        <h2 class="text-4xl sm:text-5xl font-black text-white tracking-tighter">Reports</h2>
        <p class="text-slate-400 font-bold mt-2 uppercase tracking-widest text-xs">Analytics for <span class="text-accent"><?php echo htmlspecialchars($churchName); ?></span></p>
    </div>
    <?php if (Session::get('user_role') === 'admin'): ?>
        <div class="w-full sm:w-auto">
            <div class="glass-card rounded-2xl border-white/10 p-2 grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-2">
                <a href="<?php echo BASE_URL; ?>/reports/download?type=finance_summary" class="px-5 py-3 rounded-2xl bg-white/5 border border-white/10 text-slate-200 font-black text-[10px] uppercase tracking-widest hover:bg-white/10 transition-all text-center">
                    <i class="fas fa-download mr-2 text-accent"></i> Finance Summary
                </a>
                <a href="<?php echo BASE_URL; ?>/reports/download?type=income_expense_6m" class="px-5 py-3 rounded-2xl bg-white/5 border border-white/10 text-slate-200 font-black text-[10px] uppercase tracking-widest hover:bg-white/10 transition-all text-center">
                    <i class="fas fa-download mr-2 text-accent"></i> Income vs Expense (6M)
                </a>
                <a href="<?php echo BASE_URL; ?>/reports/download?type=members_summary" class="px-5 py-3 rounded-2xl bg-white/5 border border-white/10 text-slate-200 font-black text-[10px] uppercase tracking-widest hover:bg-white/10 transition-all text-center">
                    <i class="fas fa-download mr-2 text-accent"></i> Members Summary
                </a>
                <a href="<?php echo BASE_URL; ?>/reports/download?type=department_members" class="px-5 py-3 rounded-2xl bg-white/5 border border-white/10 text-slate-200 font-black text-[10px] uppercase tracking-widest hover:bg-white/10 transition-all text-center">
                    <i class="fas fa-download mr-2 text-accent"></i> Department Members
                </a>
            </div>
            <div class="mt-2 text-[10px] font-black text-slate-500 uppercase tracking-widest">Downloads are admin-only</div>
        </div>
    <?php endif; ?>
</div>

<div class="grid grid-cols-1 md:grid-cols-3 gap-4 sm:gap-6 mb-12">
    <div class="glass-card relative overflow-hidden rounded-[2.5rem] p-8 group border-white/5 card-interaction">
        <div class="relative z-10">
            <div class="w-14 h-14 bg-white/5 rounded-2xl flex items-center justify-center mb-8 border border-white/10 group-hover:bg-accent transition-all duration-500">
                <i class="fas fa-users text-slate-400 group-hover:text-slate-900 text-xl transition-colors"></i>
            </div>
            <p class="text-[10px] font-black uppercase tracking-[0.3em] text-slate-400">Members</p>
            <h3 class="text-3xl font-black mt-2 text-white tracking-tighter"><?php echo number_format((int)($totals['members'] ?? 0)); ?></h3>
            <p class="text-[10px] font-black text-slate-500 uppercase tracking-widest mt-6"><?php echo number_format((int)($totals['active_members'] ?? 0)); ?> active</p>
        </div>
        <div class="absolute -bottom-10 -right-10 w-32 h-32 bg-accent/10 rounded-full blur-3xl group-hover:bg-accent/15 transition-all"></div>
    </div>

    <div class="glass-card relative overflow-hidden rounded-[2.5rem] p-8 group border-white/5 card-interaction">
        <div class="relative z-10">
            <div class="w-14 h-14 bg-white/5 rounded-2xl flex items-center justify-center mb-8 border border-white/10 group-hover:bg-emerald-500 transition-all duration-500">
                <i class="fas fa-arrow-trend-up text-slate-400 group-hover:text-white text-xl transition-colors"></i>
            </div>
            <p class="text-[10px] font-black uppercase tracking-[0.3em] text-slate-400">Total Income</p>
            <h3 class="text-3xl font-black mt-2 text-white tracking-tighter"><?php echo $currency . ' ' . number_format((float)($totals['income'] ?? 0), 2); ?></h3>
            <p class="text-[10px] font-black text-slate-500 uppercase tracking-widest mt-6">All time</p>
        </div>
        <div class="absolute -bottom-10 -right-10 w-32 h-32 bg-emerald-500/10 rounded-full blur-3xl group-hover:bg-emerald-500/15 transition-all"></div>
    </div>

    <div class="glass-card relative overflow-hidden rounded-[2.5rem] p-8 group border-white/5 card-interaction">
        <div class="relative z-10">
            <div class="w-14 h-14 bg-white/5 rounded-2xl flex items-center justify-center mb-8 border border-white/10 group-hover:bg-rose-500 transition-all duration-500">
                <i class="fas fa-arrow-trend-down text-slate-400 group-hover:text-white text-xl transition-colors"></i>
            </div>
            <p class="text-[10px] font-black uppercase tracking-[0.3em] text-slate-400">Total Expenses</p>
            <h3 class="text-3xl font-black mt-2 text-white tracking-tighter"><?php echo $currency . ' ' . number_format((float)($totals['expenses'] ?? 0), 2); ?></h3>
            <p class="text-[10px] font-black text-slate-500 uppercase tracking-widest mt-6">Balance: <span class="<?php echo (float)($totals['balance'] ?? 0) >= 0 ? 'text-accent' : 'text-rose-400'; ?>"><?php echo $currency . ' ' . number_format((float)($totals['balance'] ?? 0), 2); ?></span></p>
        </div>
        <div class="absolute -bottom-10 -right-10 w-32 h-32 bg-rose-500/10 rounded-full blur-3xl group-hover:bg-rose-500/15 transition-all"></div>
    </div>
</div>

<div class="grid grid-cols-1 xl:grid-cols-2 gap-8 mb-12">
    <div class="glass-card rounded-[3rem] border-white/5 overflow-hidden card-interaction">
        <div class="px-6 sm:px-10 py-7 sm:py-8 border-b border-white/5 bg-white/[0.02] flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <div class="flex items-center">
                <div class="w-10 h-10 bg-accent/10 rounded-xl flex items-center justify-center mr-4 border border-accent/20">
                    <i class="fas fa-chart-line text-accent text-sm"></i>
                </div>
                <h4 class="text-xl font-black text-white tracking-tight">Income vs Expenses</h4>
            </div>
            <div class="text-[10px] font-black text-slate-500 uppercase tracking-widest">Last 6 months</div>
        </div>
        <div class="p-6 sm:p-10">
            <div class="relative h-64 sm:h-72">
                <canvas id="chart-income-expense"></canvas>
            </div>
        </div>
    </div>

    <div class="glass-card rounded-[3rem] border-white/5 overflow-hidden card-interaction">
        <div class="px-6 sm:px-10 py-7 sm:py-8 border-b border-white/5 bg-white/[0.02] flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <div class="flex items-center">
                <div class="w-10 h-10 bg-accent/10 rounded-xl flex items-center justify-center mr-4 border border-accent/20">
                    <i class="fas fa-user-plus text-accent text-sm"></i>
                </div>
                <h4 class="text-xl font-black text-white tracking-tight">New Members</h4>
            </div>
            <div class="text-[10px] font-black text-slate-500 uppercase tracking-widest">Last 6 months</div>
        </div>
        <div class="p-6 sm:p-10">
            <div class="relative h-64 sm:h-72">
                <canvas id="chart-new-members"></canvas>
            </div>
        </div>
    </div>
</div>

<div class="grid grid-cols-1 xl:grid-cols-3 gap-8">
    <div class="xl:col-span-1 glass-card rounded-[3rem] border-white/5 overflow-hidden card-interaction">
        <div class="px-6 sm:px-10 py-7 sm:py-8 border-b border-white/5 bg-white/[0.02] flex items-center justify-between">
            <div class="flex items-center">
                <div class="w-10 h-10 bg-accent/10 rounded-xl flex items-center justify-center mr-4 border border-accent/20">
                    <i class="fas fa-venus-mars text-accent text-sm"></i>
                </div>
                <h4 class="text-xl font-black text-white tracking-tight">Gender</h4>
            </div>
        </div>
        <div class="p-6 sm:p-10">
            <div class="relative h-72">
                <canvas id="chart-gender"></canvas>
            </div>
            <div class="mt-8 grid grid-cols-1 sm:grid-cols-3 gap-3 text-center">
                <div class="bg-white/5 rounded-2xl p-4 border border-white/10">
                    <p class="text-[9px] font-black text-slate-500 uppercase tracking-widest">Male</p>
                    <p class="text-sm font-black text-slate-200 mt-2"><?php echo number_format((int)($genderMap['male'] ?? 0)); ?></p>
                </div>
                <div class="bg-white/5 rounded-2xl p-4 border border-white/10">
                    <p class="text-[9px] font-black text-slate-500 uppercase tracking-widest">Female</p>
                    <p class="text-sm font-black text-slate-200 mt-2"><?php echo number_format((int)($genderMap['female'] ?? 0)); ?></p>
                </div>
                <div class="bg-white/5 rounded-2xl p-4 border border-white/10">
                    <p class="text-[9px] font-black text-slate-500 uppercase tracking-widest">Other</p>
                    <p class="text-sm font-black text-slate-200 mt-2"><?php echo number_format((int)($genderMap['other'] ?? 0)); ?></p>
                </div>
            </div>
        </div>
    </div>

    <div class="xl:col-span-2 glass-card rounded-[3rem] border-white/5 overflow-hidden card-interaction">
        <div class="px-6 sm:px-10 py-7 sm:py-8 border-b border-white/5 bg-white/[0.02] flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <div class="flex items-center">
                <div class="w-10 h-10 bg-accent/10 rounded-xl flex items-center justify-center mr-4 border border-accent/20">
                    <i class="fas fa-sitemap text-accent text-sm"></i>
                </div>
                <h4 class="text-xl font-black text-white tracking-tight">Departments</h4>
            </div>
            <div class="text-[10px] font-black text-slate-500 uppercase tracking-widest">Top 10</div>
        </div>
        <div class="p-6 sm:p-10">
            <?php if (empty($deptRows)): ?>
                <div class="text-center text-slate-500 font-bold italic py-10">No department data available.</div>
            <?php else: ?>
                <div class="space-y-3">
                    <?php foreach ($deptRows as $r): ?>
                        <?php
                            $name = (string)($r['department_name'] ?? '');
                            $count = (int)($r['member_count'] ?? 0);
                        ?>
                        <div class="glass-card p-5 rounded-2xl border-white/5 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                            <div>
                                <p class="text-sm font-black text-slate-200"><?php echo htmlspecialchars($name); ?></p>
                                <p class="text-[10px] font-black text-slate-500 uppercase tracking-widest mt-1">Members</p>
                            </div>
                            <div class="text-xl font-black text-accent"><?php echo number_format($count); ?></div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<div class="mt-12 glass-card rounded-[3rem] border-white/5 overflow-hidden card-interaction">
    <div class="px-6 sm:px-10 py-7 sm:py-8 border-b border-white/5 bg-white/[0.02] flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div class="flex items-center">
            <div class="w-10 h-10 bg-accent/10 rounded-xl flex items-center justify-center mr-4 border border-accent/20">
                <i class="fas fa-tags text-accent text-sm"></i>
            </div>
            <h4 class="text-xl font-black text-white tracking-tight">Income Breakdown</h4>
        </div>
        <div class="text-[10px] font-black text-slate-500 uppercase tracking-widest">Top categories</div>
    </div>
    <div class="p-6 sm:p-10">
        <?php if (empty($typeRows)): ?>
            <div class="text-center text-slate-500 font-bold italic py-10">No income records found.</div>
        <?php else: ?>
            <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4">
                <?php foreach ($typeRows as $r): ?>
                    <div class="glass-card p-6 rounded-[2.5rem] border-white/5">
                        <p class="text-[10px] font-black text-slate-500 uppercase tracking-widest"><?php echo htmlspecialchars((string)($r['transaction_type'] ?? '')); ?></p>
                        <p class="text-lg font-black text-emerald-400 mt-3"><?php echo $currency . ' ' . number_format((float)($r['total'] ?? 0), 2); ?></p>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
<script>
    const labels = <?php echo json_encode($monthLabels, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
    const income = <?php echo json_encode($incomeSeries, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
    const expenses = <?php echo json_encode($expenseSeries, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
    const newMembers = <?php echo json_encode($newMembersSeries, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;

    const genderLabels = ['Male', 'Female', 'Other'];
    const genderData = <?php echo json_encode([(int)($genderMap['male'] ?? 0), (int)($genderMap['female'] ?? 0), (int)($genderMap['other'] ?? 0)], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;

    const gridColor = 'rgba(255,255,255,0.06)';
    const tickColor = 'rgba(148,163,184,0.9)';
    const fontFamily = "'Plus Jakarta Sans', sans-serif";

    const common = {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                labels: {
                    color: tickColor,
                    font: { family: fontFamily, weight: '800', size: 10 }
                }
            },
            tooltip: {
                backgroundColor: 'rgba(2,6,23,0.92)',
                borderColor: 'rgba(255,255,255,0.08)',
                borderWidth: 1,
                titleColor: '#e2e8f0',
                bodyColor: '#e2e8f0'
            }
        },
        scales: {
            x: {
                ticks: { color: tickColor, font: { family: fontFamily, weight: '800', size: 10 } },
                grid: { color: gridColor }
            },
            y: {
                ticks: { color: tickColor, font: { family: fontFamily, weight: '800', size: 10 } },
                grid: { color: gridColor }
            }
        }
    };

    const ctx1 = document.getElementById('chart-income-expense');
    new Chart(ctx1, {
        type: 'line',
        data: {
            labels,
            datasets: [
                {
                    label: 'Income',
                    data: income,
                    borderColor: 'rgba(34,197,94,0.95)',
                    backgroundColor: 'rgba(34,197,94,0.15)',
                    fill: true,
                    tension: 0.35,
                    pointRadius: 3,
                    pointBackgroundColor: 'rgba(34,197,94,1)'
                },
                {
                    label: 'Expenses',
                    data: expenses,
                    borderColor: 'rgba(244,63,94,0.95)',
                    backgroundColor: 'rgba(244,63,94,0.12)',
                    fill: true,
                    tension: 0.35,
                    pointRadius: 3,
                    pointBackgroundColor: 'rgba(244,63,94,1)'
                }
            ]
        },
        options: common
    });

    const ctx2 = document.getElementById('chart-new-members');
    new Chart(ctx2, {
        type: 'bar',
        data: {
            labels,
            datasets: [{
                label: 'New Members',
                data: newMembers,
                backgroundColor: 'rgba(251,191,36,0.35)',
                borderColor: 'rgba(251,191,36,0.9)',
                borderWidth: 1,
                borderRadius: 12
            }]
        },
        options: common
    });

    const ctx3 = document.getElementById('chart-gender');
    new Chart(ctx3, {
        type: 'doughnut',
        data: {
            labels: genderLabels,
            datasets: [{
                data: genderData,
                backgroundColor: [
                    'rgba(59,130,246,0.65)',
                    'rgba(168,85,247,0.65)',
                    'rgba(148,163,184,0.5)'
                ],
                borderColor: 'rgba(255,255,255,0.08)',
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: { color: tickColor, font: { family: fontFamily, weight: '800', size: 10 } }
                }
            }
        }
    });
</script>
