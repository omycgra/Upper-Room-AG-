<?php
    $churchName = AppConfig::getSetting('church_name', 'Church Management');
    $dashboardChangeRequests = $changeRequests ?? ['pending' => [], 'mine' => []];
?>
<div class="flex flex-col sm:flex-row justify-between items-start mb-10 gap-4">
    <div>
        <h2 class="text-4xl sm:text-5xl font-black text-white tracking-tighter">
            <?php echo !empty($isDeptHead) ? 'Department Dashboard' : (!empty($isVisitationTeam) ? 'Visitation Dashboard' : (!empty($isFinance) ? 'Finance Department Dashboard' : (!empty($isStaff) ? 'Staff Dashboard' : 'Dashboard'))); ?>
        </h2>
        <p class="text-slate-400 font-bold mt-2 uppercase tracking-widest text-xs">Welcome to <span class="text-accent"><?php echo $churchName; ?></span> Portal</p>
    </div>
    <div class="glass-card px-6 py-3 rounded-2xl flex items-center text-xs font-black text-accent uppercase tracking-widest">
        <i class="far fa-calendar-alt mr-3"></i>
        <?php echo date('l, F j, Y'); ?>
    </div>
</div>

<?php if (!empty($isDeptHead)): ?>
    <?php
        $financeCurrency = strtoupper(trim((AppConfig::getSetting('finance_currency', 'GHS'))));
        if (!preg_match('/^[A-Z]{2,5}$/', $financeCurrency)) $financeCurrency = 'GHS';
        $deptName = (!empty($dept) && !empty($dept['department'])) ? ($dept['department']['name'] ?? 'Department') : 'Department';
        $deptIncome = (!empty($dept) ? (float)($dept['income'] ?? 0) : 0.0);
        $deptExpenses = (!empty($dept) ? (float)($dept['expenses'] ?? 0) : 0.0);
        $deptNet = (!empty($dept) ? (float)($dept['net'] ?? 0) : 0.0);
        $deptTotalBalance = (!empty($dept) ? (float)($dept['total_balance'] ?? 0) : 0.0);
        $deptMembers = (!empty($dept) ? ($dept['members'] ?? []) : []);
        $deptTx = (!empty($dept) ? ($dept['transactions'] ?? []) : []);
        $deptExpenseRequests = (!empty($dept) ? ($dept['expense_requests'] ?? []) : []);
        $deptMonthLabel = (!empty($dept) ? ($dept['month_label'] ?? date('F Y')) : date('F Y'));
        $hasDeptAccess = (!empty($dept) && !empty($dept['department']));
    ?>

    <?php if (!$hasDeptAccess): ?>
        <div class="glass-card rounded-[3rem] border-white/5 overflow-hidden mb-10">
            <div class="p-10">
                <div class="flex items-start justify-between gap-6">
                    <div>
                        <p class="text-[10px] font-black text-slate-400 uppercase tracking-[0.35em]">Department Head</p>
                        <h3 class="text-2xl sm:text-3xl font-black text-white tracking-tight mt-3">Department access not configured</h3>
                        <p class="text-sm font-bold text-slate-400 mt-4 max-w-2xl">Your account has no department assigned. Contact an admin to assign your department, then login again.</p>
                    </div>
                    <a href="<?php echo BASE_URL; ?>/logout" onclick="return confirm('Are you sure you want to logout?');" class="inline-flex items-center justify-center bg-accent text-slate-900 px-6 py-4 rounded-2xl font-black text-xs uppercase tracking-[0.2em] hover:scale-[1.02] active:scale-95 transition-all shadow-xl shadow-yellow-500/10">
                        Logout
                    </a>
                </div>
            </div>
        </div>
    <?php else: ?>
    <div class="glass-card rounded-[3rem] border-white/5 overflow-hidden mb-10">
        <div class="px-10 py-10 bg-gradient-to-br from-accent/10 via-white/[0.02] to-transparent border-b border-white/5">
            <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-6">
                <div>
                    <p class="text-[10px] font-black text-slate-400 uppercase tracking-[0.35em]">Department Head</p>
                    <h3 class="text-3xl sm:text-4xl font-black text-white tracking-tight mt-3"><?php echo htmlspecialchars($deptName); ?></h3>
                    <p class="text-[10px] font-black text-slate-500 uppercase tracking-widest mt-3"><?php echo htmlspecialchars($deptMonthLabel); ?></p>
                </div>
                <div class="glass-card px-6 py-4 rounded-2xl border-white/10">
                    <p class="text-[9px] font-black text-slate-500 uppercase tracking-widest">Access</p>
                    <p class="text-xs font-black text-accent mt-2 uppercase tracking-widest">Department Only</p>
                </div>
            </div>
        </div>
        <div class="p-10 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <div class="bg-white/5 rounded-[2rem] p-6 border border-white/10">
                <p class="text-[9px] font-black text-slate-500 uppercase tracking-widest">Members</p>
                <p class="text-3xl font-black text-white mt-3"><?php echo number_format(count($deptMembers)); ?></p>
                <a href="<?php echo BASE_URL; ?>/members" class="inline-flex items-center text-[10px] font-black uppercase tracking-widest text-accent mt-4 hover:underline">
                    View list <i class="fas fa-arrow-right ml-2"></i>
                </a>
            </div>
            <div class="bg-white/5 rounded-[2rem] p-6 border border-white/10">
                <p class="text-[9px] font-black text-slate-500 uppercase tracking-widest">Bank Income</p>
                <p class="text-xl font-black text-emerald-400 mt-3"><?php echo $financeCurrency . ' ' . number_format($deptIncome, 2); ?></p>
                <p class="text-[9px] font-black text-slate-500 uppercase tracking-widest mt-4"><?php echo htmlspecialchars($deptMonthLabel); ?></p>
            </div>
            <div class="bg-white/5 rounded-[2rem] p-6 border border-white/10">
                <p class="text-[9px] font-black text-slate-500 uppercase tracking-widest">Bank Expenses</p>
                <p class="text-xl font-black text-rose-400 mt-3"><?php echo $financeCurrency . ' ' . number_format($deptExpenses, 2); ?></p>
                <p class="text-[9px] font-black text-slate-500 uppercase tracking-widest mt-4"><?php echo htmlspecialchars($deptMonthLabel); ?></p>
            </div>
            <div class="bg-white/5 rounded-[2rem] p-6 border border-white/10">
                <p class="text-[9px] font-black text-slate-500 uppercase tracking-widest">Department Balance</p>
                <p class="text-2xl font-black mt-3 <?php echo $deptTotalBalance >= 0 ? 'text-accent' : 'text-rose-400'; ?>">
                    <?php echo $financeCurrency . ' ' . number_format($deptTotalBalance, 2); ?>
                </p>
                <a href="<?php echo BASE_URL; ?>/finance" class="inline-flex items-center text-[10px] font-black uppercase tracking-widest text-accent mt-4 hover:underline">
                    Open finance <i class="fas fa-arrow-right ml-2"></i>
                </a>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <div class="lg:col-span-1 space-y-8">
            <div class="glass-card rounded-[3rem] border-white/5 overflow-hidden">
                <div class="px-10 py-8 border-b border-white/5 bg-white/[0.02]">
                    <h4 class="text-xl font-black text-white tracking-tight">Quick Actions</h4>
                    <p class="text-[10px] font-black text-slate-500 uppercase tracking-widest mt-2">Only your department</p>
                </div>
                <div class="p-10 space-y-3">
                    <a href="<?php echo BASE_URL; ?>/members/add" class="w-full inline-flex items-center justify-center bg-white/5 hover:bg-white/10 border border-white/10 py-4 rounded-2xl font-black text-xs uppercase tracking-[0.2em] transition-all">
                        <i class="fas fa-user-plus mr-2 text-accent"></i> Add Member
                    </a>
                    <button type="button" onclick="openQuickEntryModal()" class="w-full inline-flex items-center justify-center bg-accent text-slate-900 py-4 rounded-2xl font-black text-xs uppercase tracking-[0.2em] hover:scale-[1.02] active:scale-95 transition-all shadow-xl shadow-yellow-500/10">
                        <i class="fas fa-bolt mr-2"></i> Quick Entry
                    </button>
                    <button type="button" onclick="openDeptExpenseRequestModal()" class="w-full inline-flex items-center justify-center bg-white/5 hover:bg-white/10 border border-white/10 py-4 rounded-2xl font-black text-xs uppercase tracking-[0.2em] transition-all">
                        <i class="fas fa-hand-holding-dollar mr-2 text-accent"></i> Request Expense
                    </button>
                    <a href="<?php echo BASE_URL; ?>/members" class="w-full inline-flex items-center justify-center bg-white/5 hover:bg-white/10 border border-white/10 py-4 rounded-2xl font-black text-xs uppercase tracking-[0.2em] transition-all">
                        <i class="fas fa-users mr-2 text-accent"></i> Members
                    </a>
                    <a href="<?php echo BASE_URL; ?>/finance" class="w-full inline-flex items-center justify-center bg-white/5 hover:bg-white/10 border border-white/10 py-4 rounded-2xl font-black text-xs uppercase tracking-[0.2em] transition-all">
                        <i class="fas fa-receipt mr-2 text-accent"></i> Finance
                    </a>
                </div>
            </div>

            <div class="glass-card rounded-[3rem] border-white/5 overflow-hidden">
                <div class="px-10 py-8 border-b border-white/5 flex items-center justify-between bg-white/[0.02]">
                    <div class="flex items-center">
                        <div class="w-10 h-10 bg-accent/10 rounded-xl flex items-center justify-center mr-4 border border-accent/20">
                            <i class="fas fa-file-invoice-dollar text-accent text-sm"></i>
                        </div>
                        <h4 class="text-xl font-black text-white tracking-tight">Expense Requests</h4>
                    </div>
                    <button type="button" onclick="openDeptExpenseRequestModal()" class="text-[10px] font-black uppercase tracking-widest text-accent hover:underline">New</button>
                </div>
                <div class="p-10 space-y-3">
                    <?php if (empty($deptExpenseRequests)): ?>
                        <div class="text-center text-slate-500 font-bold italic py-10">No expense requests yet.</div>
                    <?php else: ?>
                        <?php foreach ($deptExpenseRequests as $r): ?>
                            <?php
                                $status = strtolower(trim((string)($r['status'] ?? 'pending')));
                                $statusBadge = 'bg-white/5 text-slate-300 border-white/10';
                                if ($status === 'approved') $statusBadge = 'bg-emerald-500/10 text-emerald-300 border-emerald-500/20';
                                if ($status === 'rejected') $statusBadge = 'bg-rose-500/10 text-rose-300 border-rose-500/20';
                            ?>
                            <div class="glass-card p-5 rounded-2xl border-white/5 hover:bg-white/5 transition-all">
                                <div class="flex items-start justify-between gap-4">
                                    <div>
                                        <p class="text-sm font-black text-slate-200"><?php echo $financeCurrency . ' ' . number_format((float)($r['amount'] ?? 0), 2); ?></p>
                                        <p class="text-[10px] font-black text-slate-500 uppercase tracking-widest mt-2"><?php echo htmlspecialchars((string)($r['purpose'] ?? '')); ?></p>
                                    </div>
                                    <span class="px-3 py-2 text-[9px] font-black rounded-full uppercase tracking-widest border <?php echo $statusBadge; ?>">
                                        <?php echo htmlspecialchars($status); ?>
                                    </span>
                                </div>
                                <p class="text-[10px] font-black text-slate-500 uppercase tracking-widest mt-4">
                                    <?php echo htmlspecialchars((string)($r['created_at'] ?? '')); ?>
                                </p>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
                </div>
            </div>
                </div>
            </div>

            <div class="glass-card rounded-[3rem] border-white/5 overflow-hidden">
                <div class="px-10 py-8 border-b border-white/5 flex items-center justify-between bg-white/[0.02]">
                    <div class="flex items-center">
                        <div class="w-10 h-10 bg-accent/10 rounded-xl flex items-center justify-center mr-4 border border-accent/20">
                            <i class="fas fa-users text-accent text-sm"></i>
                        </div>
                        <h4 class="text-xl font-black text-white tracking-tight">Recent Members</h4>
                    </div>
                    <a href="<?php echo BASE_URL; ?>/members" class="text-[10px] font-black uppercase tracking-widest text-accent hover:underline">View all</a>
                </div>
                <div class="p-10 space-y-3">
                    <?php if (empty($deptMembers)): ?>
                        <div class="text-center text-slate-500 font-bold italic py-10">No members found in your department.</div>
                    <?php else: ?>
                        <?php foreach (array_slice($deptMembers, 0, 8) as $m): ?>
                            <?php
                                $fullName = trim(($m['first_name'] ?? '') . ' ' . ($m['last_name'] ?? ''));
                                $photo = trim((string)($m['photo_path'] ?? ''));
                                $photoUrl = $photo !== '' ? (BASE_URL . '/' . ltrim($photo, '/')) : '';
                            ?>
                            <div class="glass-card p-5 rounded-2xl border-white/5 flex items-center space-x-4 hover:bg-white/5 transition-all">
                                <div class="w-12 h-12 bg-slate-800 rounded-xl flex items-center justify-center border border-white/10 overflow-hidden">
                                    <?php if ($photoUrl): ?>
                                        <img src="<?php echo htmlspecialchars($photoUrl); ?>" class="w-full h-full object-cover">
                                    <?php else: ?>
                                        <span class="text-accent font-black text-sm"><?php echo htmlspecialchars(strtoupper(substr($fullName ?: 'M', 0, 1))); ?></span>
                                    <?php endif; ?>
                                </div>
                                <div class="flex-1">
                                    <p class="text-sm font-black text-slate-200"><?php echo htmlspecialchars($fullName); ?></p>
                                    <p class="text-[10px] font-black text-slate-500 uppercase tracking-widest mt-1"><?php echo htmlspecialchars(($m['phone'] ?? '') !== '' ? $m['phone'] : 'No phone'); ?></p>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="lg:col-span-2 glass-card rounded-[3rem] border-white/5 overflow-hidden">
            <div class="px-10 py-8 border-b border-white/5 flex items-center justify-between bg-white/[0.02]">
                <div class="flex items-center">
                    <div class="w-10 h-10 bg-accent/10 rounded-xl flex items-center justify-center mr-4 border border-accent/20">
                        <i class="fas fa-receipt text-accent text-sm"></i>
                    </div>
                    <h4 class="text-xl font-black text-white tracking-tight">Bank Transactions</h4>
                </div>
                <a href="<?php echo BASE_URL; ?>/finance" class="text-[10px] font-black uppercase tracking-widest text-accent hover:underline">View all</a>
            </div>
            <div class="p-5 sm:p-6 lg:p-10 space-y-3">
                <?php if (empty($deptTx)): ?>
                    <div class="text-center text-slate-500 font-bold italic py-10">No department bank transactions recorded yet.</div>
                <?php else: ?>
                    <?php foreach (array_slice($deptTx, 0, 12) as $tx): ?>
                        <?php $isExpense = ($tx['transaction_type'] ?? '') === 'Expense'; ?>
                        <div class="glass-card p-5 rounded-2xl border-white/5 flex flex-col sm:flex-row sm:items-center justify-between gap-3 hover:bg-white/5 transition-all">
                            <div>
                                <p class="text-sm font-black text-slate-200"><?php echo htmlspecialchars($tx['transaction_type'] ?? ''); ?></p>
                                <p class="text-[10px] font-black text-slate-500 uppercase tracking-widest mt-1"><?php echo htmlspecialchars(date('M d, Y', strtotime($tx['transaction_date']))); ?></p>
                            </div>
                            <div class="text-left sm:text-right">
                                <p class="text-sm font-black <?php echo $isExpense ? 'text-rose-400' : 'text-emerald-400'; ?>">
                                    <?php echo ($isExpense ? '-' : '') . $financeCurrency . ' ' . number_format((float)($tx['amount'] ?? 0), 2); ?>
                                </p>
                                <p class="text-[10px] font-black text-slate-500 uppercase tracking-widest mt-1"><?php echo htmlspecialchars($tx['payment_method'] ?? ''); ?></p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <div id="dept-expense-request-modal" class="fixed inset-0 z-50 hidden">
        <div class="absolute inset-0 bg-slate-950/80 backdrop-blur-sm" onclick="closeDeptExpenseRequestModal()"></div>
        <div class="relative w-full h-full flex items-center justify-center p-4 sm:p-6">
            <div class="glass-card w-full max-w-2xl rounded-[2.5rem] sm:rounded-[3rem] border-white/10 overflow-hidden max-h-[90vh] flex flex-col">
                <div class="px-8 py-6 border-b border-white/5 flex items-center justify-between bg-white/[0.02]">
                    <div>
                        <p class="text-[10px] font-black text-slate-500 uppercase tracking-[0.3em]">Department</p>
                        <h4 class="text-2xl font-black text-white tracking-tight mt-2">Request Expense</h4>
                    </div>
                    <button type="button" onclick="closeDeptExpenseRequestModal()" class="w-11 h-11 rounded-2xl bg-white/5 hover:bg-white/10 border border-white/10 text-slate-300 transition-all">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <form action="<?php echo BASE_URL; ?>/finance/requestDepartmentExpense" method="POST" class="p-6 sm:p-10 overflow-y-auto custom-scrollbar flex-1 space-y-6">
                    <div>
                        <label class="block text-[10px] font-black text-slate-500 uppercase tracking-[0.3em] mb-2">Amount</label>
                        <input type="number" step="0.01" min="0" name="amount" required class="w-full bg-white/5 border border-white/10 rounded-2xl px-5 py-4 text-slate-100 font-bold focus:outline-none focus:ring-2 focus:ring-accent/40" placeholder="0.00">
                    </div>
                    <div>
                        <label class="block text-[10px] font-black text-slate-500 uppercase tracking-[0.3em] mb-2">Purpose</label>
                        <textarea name="purpose" required rows="4" class="w-full bg-white/5 border border-white/10 rounded-2xl px-5 py-4 text-slate-100 font-bold focus:outline-none focus:ring-2 focus:ring-accent/40" placeholder="Describe what the money is for"></textarea>
                    </div>
                    <div class="flex flex-col sm:flex-row gap-3">
                        <button type="submit" class="flex-1 h-12 rounded-2xl bg-accent text-slate-900 font-black text-xs uppercase tracking-[0.2em] hover:scale-[1.01] active:scale-95 transition-all shadow-xl shadow-yellow-500/10">
                            Submit Request
                        </button>
                        <button type="button" onclick="closeDeptExpenseRequestModal()" class="flex-1 h-12 rounded-2xl bg-white/5 hover:bg-white/10 border border-white/10 text-slate-200 font-black text-xs uppercase tracking-[0.2em] transition-all">
                            Cancel
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <script>
        function openDeptExpenseRequestModal() {
            const el = document.getElementById('dept-expense-request-modal');
            if (el) el.classList.remove('hidden');
        }
        function closeDeptExpenseRequestModal() {
            const el = document.getElementById('dept-expense-request-modal');
            if (el) el.classList.add('hidden');
        }
    </script>
    <?php endif; ?>

<?php elseif (!empty($isVisitationTeam)): ?>
    <?php
        $visitationSummary = $visitation['summary'] ?? [];
        $assignedVisitors = $visitation['visitors'] ?? [];
        $meId = (int)Session::get('user_id');
    ?>

    <div class="glass-card rounded-[3rem] border-white/5 overflow-hidden mb-10">
        <div class="px-10 py-10 bg-gradient-to-br from-accent/10 via-white/[0.02] to-transparent border-b border-white/5">
            <div class="flex flex-col lg:flex-row items-start lg:items-center justify-between gap-6">
                <div>
                    <p class="text-[10px] font-black text-slate-400 uppercase tracking-[0.35em]">Visitation Members</p>
                    <h3 class="text-3xl sm:text-4xl font-black text-white tracking-tight mt-3">Assigned Visitor Follow-Up</h3>
                    <p class="text-sm font-bold text-slate-400 mt-4 max-w-2xl">This dashboard focuses only on visitors assigned to visitation members. Use the list below to review details and download the full working sheet.</p>
                </div>
                <a href="<?php echo BASE_URL; ?>/visitors/export" class="inline-flex items-center justify-center bg-accent text-slate-900 px-6 py-4 rounded-2xl font-black text-xs uppercase tracking-[0.2em] hover:scale-[1.02] active:scale-95 transition-all shadow-xl shadow-yellow-500/10">
                    <i class="fas fa-download mr-2"></i> Download List
                </a>
            </div>
        </div>
        <div class="p-10 grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-6 gap-4">
            <div class="bg-white/5 rounded-[2rem] p-6 border border-white/10">
                <p class="text-[9px] font-black text-slate-500 uppercase tracking-widest">Total Visitors</p>
                <p class="text-3xl font-black text-white mt-3"><?php echo number_format((int)($visitationSummary['all_visitors_total'] ?? 0)); ?></p>
            </div>
            <div class="bg-white/5 rounded-[2rem] p-6 border border-white/10">
                <p class="text-[9px] font-black text-slate-500 uppercase tracking-widest">Assigned Visitors</p>
                <p class="text-3xl font-black text-white mt-3"><?php echo number_format((int)($visitationSummary['assigned_total'] ?? 0)); ?></p>
            </div>
            <div class="bg-white/5 rounded-[2rem] p-6 border border-white/10">
                <p class="text-[9px] font-black text-slate-500 uppercase tracking-widest">Pending Follow-Up</p>
                <p class="text-3xl font-black text-accent mt-3"><?php echo number_format((int)($visitationSummary['pending_total'] ?? 0)); ?></p>
            </div>
            <div class="bg-white/5 rounded-[2rem] p-6 border border-white/10">
                <p class="text-[9px] font-black text-slate-500 uppercase tracking-widest">Completed</p>
                <p class="text-3xl font-black text-emerald-400 mt-3"><?php echo number_format((int)($visitationSummary['completed_total'] ?? 0)); ?></p>
            </div>
            <div class="bg-white/5 rounded-[2rem] p-6 border border-white/10">
                <p class="text-[9px] font-black text-slate-500 uppercase tracking-widest">First-Time Guests</p>
                <p class="text-3xl font-black text-white mt-3"><?php echo number_format((int)($visitationSummary['first_time_total'] ?? 0)); ?></p>
            </div>
            <div class="bg-white/5 rounded-[2rem] p-6 border border-white/10">
                <p class="text-[9px] font-black text-slate-500 uppercase tracking-widest">Scheduled Calls</p>
                <p class="text-3xl font-black text-white mt-3"><?php echo number_format((int)($visitationSummary['scheduled_total'] ?? 0)); ?></p>
            </div>
        </div>
    </div>

    <div class="glass-card rounded-[3rem] border-white/5 overflow-hidden">
        <div class="px-10 py-8 border-b border-white/5 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 bg-white/[0.02]">
            <div>
                <h4 class="text-xl font-black text-white tracking-tight">Assigned Visitor List</h4>
                <p class="text-[10px] font-black text-slate-500 uppercase tracking-widest mt-2">Includes assignee name and full follow-up details</p>
            </div>
            <a href="<?php echo BASE_URL; ?>/visitors/export" class="inline-flex items-center text-[10px] font-black uppercase tracking-widest text-accent hover:underline">
                Export CSV <i class="fas fa-arrow-right ml-2"></i>
            </a>
        </div>
        <div class="p-5 sm:p-6 lg:p-10">
            <?php if (empty($assignedVisitors)): ?>
                <div class="text-center text-slate-500 font-bold italic py-10">No visitor has been assigned to you yet.</div>
            <?php else: ?>
                <div class="hidden xl:block overflow-x-auto">
                    <table class="w-full min-w-[1200px] text-left">
                        <thead class="border-b border-white/5">
                            <tr>
                                <th class="pb-4 text-[10px] font-black uppercase tracking-[0.24em] text-slate-500">Visitor</th>
                                <th class="pb-4 text-[10px] font-black uppercase tracking-[0.24em] text-slate-500">Visit Date</th>
                                <th class="pb-4 text-[10px] font-black uppercase tracking-[0.24em] text-slate-500">Service</th>
                                <th class="pb-4 text-[10px] font-black uppercase tracking-[0.24em] text-slate-500">Contact</th>
                                <th class="pb-4 text-[10px] font-black uppercase tracking-[0.24em] text-slate-500">Assigned To</th>
                                <th class="pb-4 text-[10px] font-black uppercase tracking-[0.24em] text-slate-500">Follow-Up</th>
                                <th class="pb-4 text-[10px] font-black uppercase tracking-[0.24em] text-slate-500">Notes</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-white/5">
                            <?php foreach ($assignedVisitors as $visitor): ?>
                                <?php
                                    $isApproved = !empty($visitor['approved_at']);
                                    $isAssignedToMe = (int)($visitor['assigned_to'] ?? 0) === $meId;
                                ?>
                                <tr class="hover:bg-white/[0.03] transition-colors">
                                    <td class="py-5 pr-5">
                                        <p class="text-sm font-black text-white"><?php echo htmlspecialchars(trim(($visitor['first_name'] ?? '') . ' ' . ($visitor['last_name'] ?? ''))); ?></p>
                                        <p class="text-[10px] font-black text-slate-500 uppercase tracking-widest mt-2"><?php echo !empty($visitor['is_first_time']) ? 'First-Time Visitor' : 'Returning Visitor'; ?></p>
                                    </td>
                                    <td class="py-5 pr-5 text-sm font-bold text-slate-300"><?php echo !empty($visitor['visit_date']) ? htmlspecialchars(date('M d, Y', strtotime($visitor['visit_date']))) : 'Not set'; ?></td>
                                    <td class="py-5 pr-5 text-sm font-bold text-slate-300"><?php echo htmlspecialchars($visitor['service_attended'] ?? 'Not specified'); ?></td>
                                    <td class="py-5 pr-5">
                                        <?php if (!$isApproved): ?>
                                            <p class="text-sm font-bold text-slate-400">Hidden until approved</p>
                                            <p class="text-[10px] font-black text-slate-600 uppercase tracking-widest mt-2">Approve to view contact</p>
                                        <?php else: ?>
                                            <p class="text-sm font-bold text-slate-300"><?php echo htmlspecialchars($visitor['phone'] ?: ($visitor['email'] ?? 'No contact')); ?></p>
                                            <p class="text-[10px] font-black text-slate-500 uppercase tracking-widest mt-2"><?php echo htmlspecialchars($visitor['preferred_contact_method'] ?? 'Method not set'); ?></p>
                                        <?php endif; ?>
                                    </td>
                                    <td class="py-5 pr-5">
                                        <p class="text-sm font-black text-white"><?php echo htmlspecialchars($visitor['assigned_to_name'] ?? 'Unassigned'); ?></p>
                                        <p class="text-[10px] font-black text-slate-500 uppercase tracking-widest mt-2"><?php echo htmlspecialchars($visitor['assigned_department_name'] ?? 'Visitation'); ?></p>
                                        <div class="mt-3">
                                            <?php if ($isAssignedToMe && !$isApproved): ?>
                                                <form action="<?php echo BASE_URL; ?>/visitors/approve" method="POST" data-loader="top">
                                                    <input type="hidden" name="visitor_id" value="<?php echo (int)($visitor['id'] ?? 0); ?>">
                                                    <button type="submit" class="h-9 px-4 rounded-xl bg-accent text-slate-900 font-black text-[10px] uppercase tracking-widest">
                                                        Approve
                                                    </button>
                                                </form>
                                            <?php elseif ($isApproved): ?>
                                                <a href="<?php echo BASE_URL; ?>/visitors/details?id=<?php echo (int)($visitor['id'] ?? 0); ?>" class="h-9 px-4 inline-flex items-center justify-center rounded-xl bg-white/10 text-slate-200 font-black text-[10px] uppercase tracking-widest border border-white/10">
                                                    Details
                                                </a>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td class="py-5 pr-5">
                                        <p class="text-sm font-bold text-slate-300"><?php echo htmlspecialchars($visitor['follow_up_status'] ?? 'Pending'); ?></p>
                                        <p class="text-[10px] font-black text-slate-500 uppercase tracking-widest mt-2"><?php echo !empty($visitor['follow_up_date']) ? htmlspecialchars(date('M d, Y', strtotime($visitor['follow_up_date']))) : 'No schedule'; ?></p>
                                    </td>
                                    <td class="py-5 text-sm font-bold text-slate-300 max-w-[18rem]"><?php echo htmlspecialchars($visitor['follow_up_notes'] ?? ($visitor['prayer_request'] ?? 'No notes')); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <div class="xl:hidden space-y-4">
                    <?php foreach ($assignedVisitors as $visitor): ?>
                        <?php
                            $isApproved = !empty($visitor['approved_at']);
                            $isAssignedToMe = (int)($visitor['assigned_to'] ?? 0) === $meId;
                        ?>
                        <div class="glass-card p-5 rounded-[2rem] border-white/5">
                            <div class="flex items-start justify-between gap-4">
                                <div>
                                    <h5 class="text-lg font-black text-white"><?php echo htmlspecialchars(trim(($visitor['first_name'] ?? '') . ' ' . ($visitor['last_name'] ?? ''))); ?></h5>
                                    <p class="text-[10px] font-black text-slate-500 uppercase tracking-widest mt-2"><?php echo !empty($visitor['visit_date']) ? htmlspecialchars(date('M d, Y', strtotime($visitor['visit_date']))) : 'No visit date'; ?></p>
                                </div>
                                <span class="px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-[0.2em] border <?php echo ($visitor['follow_up_status'] ?? '') === 'Completed' ? 'bg-emerald-500/10 text-emerald-300 border-emerald-500/20' : 'bg-accent/10 text-accent border-accent/20'; ?>">
                                    <?php echo htmlspecialchars($visitor['follow_up_status'] ?? 'Pending'); ?>
                                </span>
                            </div>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 mt-5 text-sm font-bold text-slate-300">
                                <div class="bg-white/5 rounded-2xl border border-white/5 px-4 py-3">
                                    <p class="text-[10px] font-black text-slate-500 uppercase tracking-widest">Assigned To</p>
                                    <p class="mt-2"><?php echo htmlspecialchars($visitor['assigned_to_name'] ?? 'Unassigned'); ?></p>
                                </div>
                                <div class="bg-white/5 rounded-2xl border border-white/5 px-4 py-3">
                                    <p class="text-[10px] font-black text-slate-500 uppercase tracking-widest">Service</p>
                                    <p class="mt-2"><?php echo htmlspecialchars($visitor['service_attended'] ?? 'Not specified'); ?></p>
                                </div>
                                <div class="bg-white/5 rounded-2xl border border-white/5 px-4 py-3">
                                    <p class="text-[10px] font-black text-slate-500 uppercase tracking-widest">Contact</p>
                                    <p class="mt-2">
                                        <?php if (!$isApproved): ?>
                                            Hidden until approved
                                        <?php else: ?>
                                            <?php echo htmlspecialchars($visitor['phone'] ?: ($visitor['email'] ?? 'No contact')); ?>
                                        <?php endif; ?>
                                    </p>
                                </div>
                                <div class="bg-white/5 rounded-2xl border border-white/5 px-4 py-3">
                                    <p class="text-[10px] font-black text-slate-500 uppercase tracking-widest">Follow-Up Date</p>
                                    <p class="mt-2"><?php echo !empty($visitor['follow_up_date']) ? htmlspecialchars(date('M d, Y', strtotime($visitor['follow_up_date']))) : 'No schedule'; ?></p>
                                </div>
                            </div>
                            <div class="mt-4 text-sm font-bold text-slate-300">
                                <?php echo htmlspecialchars($visitor['follow_up_notes'] ?? ($visitor['prayer_request'] ?? 'No notes recorded.')); ?>
                            </div>
                            <div class="mt-5">
                                <?php if ($isAssignedToMe && !$isApproved): ?>
                                    <form action="<?php echo BASE_URL; ?>/visitors/approve" method="POST" data-loader="top">
                                        <input type="hidden" name="visitor_id" value="<?php echo (int)($visitor['id'] ?? 0); ?>">
                                        <button type="submit" class="w-full h-11 px-5 rounded-2xl bg-accent text-slate-900 font-black text-[10px] uppercase tracking-widest hover-glow-yellow">
                                            Approve
                                        </button>
                                    </form>
                                <?php elseif ($isApproved): ?>
                                    <a href="<?php echo BASE_URL; ?>/visitors/details?id=<?php echo (int)($visitor['id'] ?? 0); ?>" class="w-full h-11 px-5 rounded-2xl bg-white/10 text-slate-200 font-black text-[10px] uppercase tracking-widest border border-white/10 inline-flex items-center justify-center hover:bg-white/15 transition-all">
                                        View Details
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

<?php elseif (!empty($isFinance)): ?>
    <?php
        $financeCurrency = strtoupper(trim((AppConfig::getSetting('finance_currency', 'GHS'))));
        if (!preg_match('/^[A-Z]{2,5}$/', $financeCurrency)) $financeCurrency = 'GHS';
        $financeDept = $financeDept ?? [];
        $combinedAllTime = $financeDept['combined_all_time'] ?? [];
        $pendingDeptRequests = $financeDept['pending_dept_expense_requests'] ?? [];
        $recentTx = $financeDept['recent_transactions'] ?? [];
        $deptSavings = $financeDept['department_savings'] ?? [];
    ?>

    <div class="glass-card rounded-[3rem] border-white/5 overflow-hidden mb-10">
        <div class="px-10 py-10 bg-gradient-to-br from-accent/10 via-white/[0.02] to-transparent border-b border-white/5">
            <div class="flex flex-col lg:flex-row items-start lg:items-center justify-between gap-6">
                <div>
                    <p class="text-[10px] font-black text-slate-400 uppercase tracking-[0.35em]">Finance Department</p>
                    <h3 class="text-3xl sm:text-4xl font-black text-white tracking-tight mt-3">Financial Overview</h3>
                    <p class="text-sm font-bold text-slate-400 mt-4 max-w-2xl">Full visibility of finance, departmental savings, and expense requests. Only the Head of Finance can approve requests.</p>
                </div>
                <a href="<?php echo BASE_URL; ?>/finance" class="inline-flex items-center justify-center bg-accent text-slate-900 px-6 py-4 rounded-2xl font-black text-xs uppercase tracking-[0.2em] hover:scale-[1.02] active:scale-95 transition-all shadow-xl shadow-yellow-500/10">
                    <i class="fas fa-wallet mr-2"></i> Open Finance
                </a>
            </div>
        </div>
        <div class="p-10 grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4">
            <div class="bg-white/5 rounded-[2rem] p-6 border border-white/10">
                <p class="text-[9px] font-black text-slate-500 uppercase tracking-widest">This Month Income</p>
                <p class="text-2xl font-black text-emerald-400 mt-3"><?php echo $financeCurrency . ' ' . number_format((float)($financeDept['monthly_income'] ?? 0), 2); ?></p>
                <p class="text-[9px] font-black text-slate-500 uppercase tracking-widest mt-4"><?php echo htmlspecialchars($financeDept['month_label'] ?? date('F Y')); ?></p>
            </div>
            <div class="bg-white/5 rounded-[2rem] p-6 border border-white/10">
                <p class="text-[9px] font-black text-slate-500 uppercase tracking-widest">This Month Expenses</p>
                <p class="text-2xl font-black text-rose-400 mt-3"><?php echo $financeCurrency . ' ' . number_format((float)($financeDept['monthly_expenses'] ?? 0), 2); ?></p>
                <p class="text-[9px] font-black text-slate-500 uppercase tracking-widest mt-4"><?php echo htmlspecialchars($financeDept['month_label'] ?? date('F Y')); ?></p>
            </div>
            <div class="bg-white/5 rounded-[2rem] p-6 border border-white/10">
                <p class="text-[9px] font-black text-slate-500 uppercase tracking-widest">This Month Balance</p>
                <p class="text-2xl font-black mt-3 <?php echo ((float)($financeDept['monthly_balance'] ?? 0)) >= 0 ? 'text-accent' : 'text-rose-400'; ?>">
                    <?php echo $financeCurrency . ' ' . number_format((float)($financeDept['monthly_balance'] ?? 0), 2); ?>
                </p>
                <p class="text-[9px] font-black text-slate-500 uppercase tracking-widest mt-4">General + Departments</p>
            </div>
            <div class="bg-white/5 rounded-[2rem] p-6 border border-white/10">
                <p class="text-[9px] font-black text-slate-500 uppercase tracking-widest">All Time Balance</p>
                <p class="text-2xl font-black mt-3 <?php echo ((float)($combinedAllTime['balance'] ?? 0)) >= 0 ? 'text-accent' : 'text-rose-400'; ?>">
                    <?php echo $financeCurrency . ' ' . number_format((float)($combinedAllTime['balance'] ?? 0), 2); ?>
                </p>
                <p class="text-[9px] font-black text-slate-500 uppercase tracking-widest mt-4">Total church</p>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 xl:grid-cols-2 gap-8">
        <div class="glass-card rounded-[3rem] border-white/5 overflow-hidden">
            <div class="px-10 py-8 border-b border-white/5 bg-white/[0.02] flex items-center justify-between gap-3">
                <div>
                    <h4 class="text-xl font-black text-white tracking-tight">Pending Expense Requests</h4>
                    <p class="text-[10px] font-black text-slate-500 uppercase tracking-widest mt-2">Department leaders requests</p>
                </div>
                <a href="<?php echo BASE_URL; ?>/finance#department-expense-requests" class="text-[10px] font-black uppercase tracking-widest text-accent hover:underline">Open list</a>
            </div>
            <div class="p-10 space-y-3">
                <?php if (empty($pendingDeptRequests)): ?>
                    <div class="text-center text-slate-500 font-bold italic py-10">No pending department expense requests.</div>
                <?php else: ?>
                    <?php foreach ($pendingDeptRequests as $r): ?>
                        <div class="glass-card p-5 rounded-2xl border-white/5 hover:bg-white/5 transition-all">
                            <div class="flex items-start justify-between gap-4">
                                <div>
                                    <p class="text-sm font-black text-slate-200"><?php echo htmlspecialchars($r['department_name'] ?? ''); ?></p>
                                    <p class="text-[10px] font-black text-slate-500 uppercase tracking-widest mt-2"><?php echo htmlspecialchars($r['requested_by_name'] ?? ''); ?></p>
                                    <p class="text-[10px] font-black text-slate-500 uppercase tracking-widest mt-2"><?php echo htmlspecialchars($r['purpose'] ?? ''); ?></p>
                                </div>
                                <p class="text-sm font-black text-accent"><?php echo $financeCurrency . ' ' . number_format((float)($r['amount'] ?? 0), 2); ?></p>
                            </div>
                            <?php if (!empty($isFinanceHead)): ?>
                                <div class="mt-4 flex items-center justify-end gap-2">
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
                                </div>
                            <?php endif; ?>
                            <?php if (empty($isFinanceHead)): ?>
                                <p class="text-[10px] font-black uppercase tracking-widest text-slate-500 mt-4">Approval requires Head of Finance login</p>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <div class="glass-card rounded-[3rem] border-white/5 overflow-hidden">
            <div class="px-10 py-8 border-b border-white/5 flex items-center justify-between bg-white/[0.02]">
                <div class="flex items-center">
                    <div class="w-10 h-10 bg-accent/10 rounded-xl flex items-center justify-center mr-4 border border-accent/20">
                        <i class="fas fa-receipt text-accent text-sm"></i>
                    </div>
                    <h4 class="text-xl font-black text-white tracking-tight">Recent Transactions</h4>
                </div>
                <a href="<?php echo BASE_URL; ?>/finance" class="text-[10px] font-black uppercase tracking-widest text-accent hover:underline">Open finance</a>
            </div>
            <div class="p-10 space-y-3">
                <?php if (empty($recentTx)): ?>
                    <div class="text-center text-slate-500 font-bold italic py-10">No transactions yet.</div>
                <?php else: ?>
                    <?php foreach ($recentTx as $tx): ?>
                        <?php
                            $isExpense = ($tx['transaction_type'] ?? '') === 'Expense';
                            $label = (string)($tx['transaction_type'] ?? '');
                            if ($label === 'Offering' && !empty($tx['offering_subtype'])) {
                                $label = 'Offering (' . (string)$tx['offering_subtype'] . ')';
                            } elseif ($label === 'Departmental Savings') {
                                $label = 'Department Offering';
                            }
                        ?>
                        <div class="glass-card p-5 rounded-2xl border-white/5 flex flex-col sm:flex-row sm:items-center justify-between gap-3 hover:bg-white/5 transition-all">
                            <div>
                                <p class="text-sm font-black text-slate-200"><?php echo htmlspecialchars($label); ?></p>
                                <p class="text-[10px] font-black text-slate-500 uppercase tracking-widest mt-1"><?php echo htmlspecialchars(date('M d, Y', strtotime($tx['transaction_date']))); ?> • <?php echo htmlspecialchars(($tx['department_name'] ?? '') !== '' ? $tx['department_name'] : 'Church'); ?></p>
                            </div>
                            <div class="text-right">
                                <p class="text-sm font-black <?php echo $isExpense ? 'text-rose-400' : 'text-emerald-400'; ?>">
                                    <?php echo ($isExpense ? '-' : '') . $financeCurrency . ' ' . number_format((float)($tx['amount'] ?? 0), 2); ?>
                                </p>
                                <p class="text-[10px] font-black text-slate-500 uppercase tracking-widest mt-1"><?php echo htmlspecialchars($tx['recorded_by_name'] ?? ''); ?></p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="glass-card rounded-[3rem] border-white/5 overflow-hidden mt-10">
        <div class="px-10 py-8 border-b border-white/5 bg-white/[0.02] flex items-center justify-between gap-3">
            <div>
                <h4 class="text-xl font-black text-white tracking-tight">Departmental Savings</h4>
                <p class="text-[10px] font-black text-slate-500 uppercase tracking-widest mt-2"><?php echo htmlspecialchars($financeDept['month_label'] ?? date('F Y')); ?></p>
            </div>
            <a href="<?php echo BASE_URL; ?>/department-savings" class="text-[10px] font-black uppercase tracking-widest text-accent hover:underline">View all</a>
        </div>
        <div class="p-10">
            <?php if (empty($deptSavings)): ?>
                <div class="text-center text-slate-500 font-bold italic py-10">No departmental savings found.</div>
            <?php else: ?>
                <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
                    <?php foreach (array_slice($deptSavings, 0, 6) as $row): ?>
                        <?php
                            $income = (float)($row['income_total'] ?? 0);
                            $expense = (float)($row['expense_total'] ?? 0);
                            $net = (float)($row['balance'] ?? ($income - $expense));
                        ?>
                        <div class="glass-card p-5 rounded-2xl border-white/5 hover:bg-white/5 transition-all">
                            <p class="text-sm font-black text-slate-200"><?php echo htmlspecialchars($row['department_name'] ?? ''); ?></p>
                            <div class="mt-4 grid grid-cols-3 gap-2 text-[10px] font-black uppercase tracking-widest text-slate-500">
                                <div>In<br><span class="text-emerald-400 text-xs"><?php echo number_format($income, 2); ?></span></div>
                                <div>Out<br><span class="text-rose-400 text-xs"><?php echo number_format($expense, 2); ?></span></div>
                                <div>Net<br><span class="<?php echo $net >= 0 ? 'text-accent' : 'text-rose-400'; ?> text-xs"><?php echo number_format($net, 2); ?></span></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

<?php elseif (!empty($isStaff)): ?>
    <?php
        $financeCurrency = strtoupper(trim((AppConfig::getSetting('finance_currency', 'GHS'))));
        if (!preg_match('/^[A-Z]{2,5}$/', $financeCurrency)) $financeCurrency = 'GHS';
        $staffSummary = $staff['summary'] ?? [];
        $staffTransactions = array_values(array_filter(($staff['transactions'] ?? []), function ($row) {
            return (int)($row['member_id'] ?? 0) > 0;
        }));
        $staffTypes = $staff['allowed_types'] ?? ['Offering', 'Tithe', 'Departmental Savings', 'Welfare'];
    ?>

    <div class="glass-card rounded-[3rem] border-white/5 overflow-hidden mb-10">
        <div class="px-10 py-10 bg-gradient-to-br from-accent/10 via-white/[0.02] to-transparent border-b border-white/5">
            <div class="flex flex-col lg:flex-row items-start lg:items-center justify-between gap-6">
                <div>
                    <p class="text-[10px] font-black text-slate-400 uppercase tracking-[0.35em]">Cashier Workspace</p>
                    <h3 class="text-3xl sm:text-4xl font-black text-white tracking-tight mt-3">Fast Transaction Entry</h3>
                    <p class="text-sm font-bold text-slate-400 mt-4 max-w-2xl">Record only general offering, tithe, department offering, and welfare. Use the quick buttons below to enter payments faster and print a receipt instantly.</p>
                </div>
                <div class="flex flex-col sm:flex-row gap-3">
                    <button type="button" onclick="openQuickEntryModal()" class="inline-flex items-center justify-center bg-accent text-slate-900 px-6 py-4 rounded-2xl font-black text-xs uppercase tracking-[0.2em] hover:scale-[1.02] active:scale-95 transition-all shadow-xl shadow-yellow-500/10">
                        <i class="fas fa-bolt mr-2"></i> Quick Entry
                    </button>
                    <a href="<?php echo BASE_URL; ?>/finance/add" class="inline-flex items-center justify-center bg-white/5 hover:bg-white/10 border border-white/10 px-6 py-4 rounded-2xl font-black text-xs uppercase tracking-[0.2em] transition-all">
                        <i class="fas fa-plus mr-2 text-accent"></i> Record Payment
                    </a>
                </div>
            </div>
        </div>
        <div class="p-10 grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4">
            <div class="bg-white/5 rounded-[2rem] p-6 border border-white/10">
                <p class="text-[9px] font-black text-slate-500 uppercase tracking-widest">Today</p>
                <p class="text-2xl font-black text-emerald-400 mt-3"><?php echo $financeCurrency . ' ' . number_format((float)($staffSummary['today_total'] ?? 0), 2); ?></p>
                <p class="text-[9px] font-black text-slate-500 uppercase tracking-widest mt-4">Collected by you</p>
            </div>
            <div class="bg-white/5 rounded-[2rem] p-6 border border-white/10">
                <p class="text-[9px] font-black text-slate-500 uppercase tracking-widest">This Month</p>
                <p class="text-2xl font-black text-accent mt-3"><?php echo $financeCurrency . ' ' . number_format((float)($staffSummary['month_total'] ?? 0), 2); ?></p>
                <p class="text-[9px] font-black text-slate-500 uppercase tracking-widest mt-4"><?php echo htmlspecialchars($staff['month_label'] ?? date('F Y')); ?></p>
            </div>
            <div class="bg-white/5 rounded-[2rem] p-6 border border-white/10">
                <p class="text-[9px] font-black text-slate-500 uppercase tracking-widest">All Time</p>
                <p class="text-2xl font-black text-white mt-3"><?php echo $financeCurrency . ' ' . number_format((float)($staffSummary['overall_total'] ?? 0), 2); ?></p>
                <p class="text-[9px] font-black text-slate-500 uppercase tracking-widest mt-4">Your recorded payments</p>
            </div>
            <div class="bg-white/5 rounded-[2rem] p-6 border border-white/10">
                <p class="text-[9px] font-black text-slate-500 uppercase tracking-widest">Receipts</p>
                <p class="text-3xl font-black text-white mt-3"><?php echo number_format((int)($staffSummary['transaction_count'] ?? 0)); ?></p>
                <p class="text-[9px] font-black text-slate-500 uppercase tracking-widest mt-4">Printed or ready to print</p>
            </div>
        </div>
    </div>

    <?php $staffChangeRequests = $staff['change_requests'] ?? []; ?>
    <div class="glass-card rounded-[3rem] border-white/5 overflow-hidden mb-10">
        <div class="px-10 py-8 border-b border-white/5 bg-white/[0.02] flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3">
            <div>
                <h4 class="text-xl font-black text-white tracking-tight">Transaction Change Requests</h4>
                <p class="text-[10px] font-black text-slate-500 uppercase tracking-widest mt-2">Request correction approval from admin</p>
            </div>
            <a href="<?php echo BASE_URL; ?>/finance" class="text-[10px] font-black uppercase tracking-widest text-accent hover:underline">Open finance</a>
        </div>
        <div class="p-10">
            <?php if (empty($staffChangeRequests)): ?>
                <div class="text-center text-slate-500 font-bold italic py-6">No change requests yet. Open Finance and click Request Edit on the specific transaction.</div>
            <?php else: ?>
                <div class="grid grid-cols-1 xl:grid-cols-2 gap-4">
                    <?php foreach ($staffChangeRequests as $request): ?>
                        <?php
                            $status = strtolower(trim((string)($request['status'] ?? 'pending')));
                            $statusClass = $status === 'approved'
                                ? 'bg-emerald-500/10 text-emerald-400 border-emerald-500/20'
                                : ($status === 'rejected' ? 'bg-rose-500/10 text-rose-400 border-rose-500/20' : 'bg-white/5 text-slate-300 border-white/10');
                        ?>
                        <div class="glass-card p-5 rounded-2xl border-white/5 hover:bg-white/5 transition-all">
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="px-3 py-1.5 text-[9px] font-black rounded-full uppercase tracking-widest border <?php echo $statusClass; ?>"><?php echo htmlspecialchars($status); ?></span>
                                <span class="text-sm font-black text-slate-200"><?php echo htmlspecialchars($request['transaction_type'] ?? ''); ?></span>
                                <span class="text-xs font-bold text-slate-500"><?php echo htmlspecialchars($request['transaction_number'] ?? ''); ?></span>
                            </div>
                            <p class="text-sm font-black text-accent mt-3"><?php echo $financeCurrency . ' ' . number_format((float)($request['current_amount'] ?? 0), 2); ?></p>
                            <p class="text-xs font-bold text-slate-400 mt-3"><?php echo htmlspecialchars($request['reason'] ?? ''); ?></p>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <div class="lg:col-span-1 space-y-8">
            <div class="glass-card rounded-[3rem] border-white/5 overflow-hidden">
                <div class="px-10 py-8 border-b border-white/5 bg-white/[0.02]">
                    <h4 class="text-xl font-black text-white tracking-tight">Quick Entry</h4>
                    <p class="text-[10px] font-black text-slate-500 uppercase tracking-widest mt-2">Tap a type and start entering</p>
                </div>
                <div class="p-5 sm:p-6 lg:p-10 grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-2 gap-3">
                    <?php foreach ($staffTypes as $type): ?>
                        <?php
                            $label = $type === 'Departmental Savings' ? 'Department Offering' : $type;
                            $icon = $type === 'Tithe' ? 'fa-hand-holding-dollar'
                                : ($type === 'Departmental Savings' ? 'fa-sitemap'
                                    : ($type === 'Welfare' ? 'fa-heart'
                                        : ($type === 'Sunday School' ? 'fa-chalkboard-teacher'
                                            : ($type === 'Annual Harvest' ? 'fa-wheat-awn'
                                                : ($type === 'Mini Harvest' ? 'fa-seedling'
                                                    : ($type === 'Expense' ? 'fa-arrow-trend-down' : 'fa-receipt'))))));
                        ?>
                        <button type="button" onclick="openQuickEntryModal('<?php echo htmlspecialchars($type); ?>')" class="w-full text-left flex items-center gap-3 rounded-2xl border border-white/10 bg-white/5 hover:bg-white/10 px-4 py-4 transition-all">
                            <span class="w-9 h-9 rounded-xl flex items-center justify-center bg-white/5 border border-white/10 text-accent">
                                <i class="fas <?php echo $icon; ?> text-sm"></i>
                            </span>
                            <span class="min-w-0">
                                <span class="block text-[10px] font-black uppercase tracking-widest text-slate-200 truncate"><?php echo htmlspecialchars($label); ?></span>
                                <span class="block text-[9px] font-black uppercase tracking-widest text-slate-500 truncate"><?php echo htmlspecialchars($type); ?></span>
                            </span>
                        </button>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="glass-card rounded-[3rem] border-white/5 overflow-hidden">
                <div class="px-10 py-8 border-b border-white/5 bg-white/[0.02]">
                    <h4 class="text-xl font-black text-white tracking-tight">Type Totals</h4>
                    <p class="text-[10px] font-black text-slate-500 uppercase tracking-widest mt-2">Everything you have recorded</p>
                </div>
                <div class="p-10 space-y-3">
                    <?php if (empty($staffSummary['by_type'])): ?>
                        <div class="text-center text-slate-500 font-bold italic py-10">No transactions recorded yet.</div>
                    <?php else: ?>
                        <?php foreach ($staffSummary['by_type'] as $row): ?>
                            <?php $rowLabel = ($row['transaction_type'] ?? '') === 'Departmental Savings' ? 'Department Offering' : ($row['transaction_type'] ?? ''); ?>
                            <div class="glass-card p-5 rounded-2xl border-white/5 flex items-center justify-between hover:bg-white/5 transition-all">
                                <div>
                                    <p class="text-sm font-black text-slate-200"><?php echo htmlspecialchars($rowLabel); ?></p>
                                    <p class="text-[10px] font-black text-slate-500 uppercase tracking-widest mt-1"><?php echo number_format((int)($row['count_total'] ?? 0)); ?> receipt<?php echo ((int)($row['count_total'] ?? 0) === 1) ? '' : 's'; ?></p>
                                </div>
                                <p class="text-sm font-black text-emerald-400"><?php echo $financeCurrency . ' ' . number_format((float)($row['total'] ?? 0), 2); ?></p>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="lg:col-span-2 glass-card rounded-[3rem] border-white/5 overflow-hidden">
            <div class="px-10 py-8 border-b border-white/5 flex items-center justify-between bg-white/[0.02]">
                <div class="flex items-center">
                    <div class="w-10 h-10 bg-accent/10 rounded-xl flex items-center justify-center mr-4 border border-accent/20">
                        <i class="fas fa-print text-accent text-sm"></i>
                    </div>
                    <h4 class="text-xl font-black text-white tracking-tight">Recent Member Receipts</h4>
                </div>
                <a href="<?php echo BASE_URL; ?>/finance" class="text-[10px] font-black uppercase tracking-widest text-accent hover:underline">Open finance</a>
            </div>
            <div class="p-10 space-y-3">
                <?php if (empty($staffTransactions)): ?>
                    <div class="text-center text-slate-500 font-bold italic py-10">No member receipts generated yet.</div>
                <?php else: ?>
                    <?php foreach ($staffTransactions as $tx): ?>
                        <?php $txLabel = ($tx['transaction_type'] ?? '') === 'Departmental Savings' ? 'Department Offering' : ($tx['transaction_type'] ?? ''); ?>
                        <div class="glass-card p-5 rounded-2xl border-white/5 flex flex-col sm:flex-row sm:items-center justify-between gap-3 hover:bg-white/5 transition-all">
                            <div>
                                <p class="text-sm font-black text-slate-200"><?php echo htmlspecialchars($txLabel); ?></p>
                                <p class="text-[10px] font-black text-slate-500 uppercase tracking-widest mt-1"><?php echo htmlspecialchars(date('M d, Y', strtotime($tx['transaction_date']))); ?> • <?php echo htmlspecialchars($tx['payment_method'] ?? 'Cash'); ?></p>
                            </div>
                            <div class="text-right">
                                <p class="text-sm font-black text-emerald-400"><?php echo $financeCurrency . ' ' . number_format((float)($tx['amount'] ?? 0), 2); ?></p>
                                <p class="text-[10px] font-black text-slate-500 uppercase tracking-widest mt-1"><?php echo htmlspecialchars(($tx['transaction_number'] ?? '') !== '' ? $tx['transaction_number'] : 'Receipt'); ?></p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

<?php else: ?>
<div id="birthdays-month-modal" class="fixed inset-0 bg-slate-950/80 backdrop-blur-xl z-50 hidden flex items-center justify-center p-4">
    <div class="glass-card w-full max-w-2xl rounded-[3rem] overflow-hidden shadow-2xl transform transition-all duration-500 scale-95 opacity-0 max-h-[90vh] flex flex-col border-white/10" id="birthdays-month-modal-content">
        <div class="px-10 py-10 bg-slate-900 relative overflow-hidden flex-shrink-0 border-b border-white/5">
            <div class="relative z-10 flex justify-between items-center text-white">
                <div>
                    <h3 class="text-3xl font-black tracking-tighter">Birthdays This Month</h3>
                    <p id="birthdays-month-subtitle" class="text-accent text-[10px] font-black mt-2 tracking-[0.3em] uppercase"></p>
                </div>
                <button type="button" onclick="closeBirthdaysThisMonth()" class="w-12 h-12 bg-white/5 hover:bg-accent hover:text-slate-900 rounded-2xl flex items-center justify-center transition-all border border-white/10">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        </div>

        <div class="p-10 bg-slate-900/50 overflow-y-auto custom-scrollbar flex-1">
            <div id="birthdays-month-loading" class="flex flex-col items-center justify-center py-16 opacity-40">
                <i class="fas fa-circle-notch fa-spin text-3xl mb-4"></i>
                <p class="text-[10px] font-black uppercase tracking-widest">Loading</p>
            </div>

            <div id="birthdays-month-empty" class="hidden flex flex-col items-center justify-center py-16 opacity-30">
                <i class="fas fa-calendar-times text-4xl mb-4"></i>
                <p class="text-[10px] font-black uppercase tracking-widest">No Birthdays This Month</p>
            </div>

            <div id="birthdays-month-list" class="hidden space-y-4"></div>
        </div>
    </div>
</div>

<script>
    function showDashboardModal(modalId, contentId) {
        const modal = document.getElementById(modalId);
        const content = document.getElementById(contentId);
        modal.classList.remove('hidden');
        setTimeout(() => {
            content.classList.remove('scale-95', 'opacity-0');
            content.classList.add('scale-100', 'opacity-100');
        }, 10);
    }

    function closeDashboardModal(modalId, contentId) {
        const modal = document.getElementById(modalId);
        const content = document.getElementById(contentId);
        content.classList.remove('scale-100', 'opacity-100');
        content.classList.add('scale-95', 'opacity-0');
        setTimeout(() => modal.classList.add('hidden'), 300);
    }

    async function openBirthdaysThisMonth() {
        const modalId = 'birthdays-month-modal';
        const contentId = 'birthdays-month-modal-content';
        const loadingEl = document.getElementById('birthdays-month-loading');
        const emptyEl = document.getElementById('birthdays-month-empty');
        const listEl = document.getElementById('birthdays-month-list');
        const subtitleEl = document.getElementById('birthdays-month-subtitle');

        loadingEl.classList.remove('hidden');
        emptyEl.classList.add('hidden');
        listEl.classList.add('hidden');
        listEl.innerHTML = '';
        subtitleEl.textContent = '';

        showDashboardModal(modalId, contentId);

        try {
            const response = await fetch(`<?php echo BASE_URL; ?>/dashboard/birthdaysThisMonth`);
            const data = await response.json();

            const birthdays = data.birthdays || [];
            subtitleEl.textContent = `${birthdays.length} record${birthdays.length === 1 ? '' : 's'} found`;

            loadingEl.classList.add('hidden');

            if (!birthdays.length) {
                emptyEl.classList.remove('hidden');
                return;
            }

            listEl.classList.remove('hidden');
            listEl.innerHTML = birthdays.map(b => {
                const initial = (b.first_name || 'M').charAt(0).toUpperCase();
                const fullName = `${b.first_name || ''} ${b.last_name || ''}`.trim();
                const display = b.birthday_display || '';
                const phone = (b.phone || '').trim();
                const photo = (b.photo_path || '').trim();
                const photoUrl = photo
                    ? (/^https?:\/\//i.test(photo) ? photo : `<?php echo BASE_URL; ?>/${photo.replace(/^\/+/, '')}`)
                    : '';
                return `
                    <div class="glass-card p-5 rounded-2xl border-white/5 flex items-center space-x-4 hover:bg-white/5 transition-all">
                        <div class="w-12 h-12 bg-slate-800 rounded-xl flex items-center justify-center border border-white/10 overflow-hidden">
                            ${photoUrl ? `<img src="${photoUrl}" class="w-full h-full object-cover">` : `<span class="text-accent font-black text-sm">${initial}</span>`}
                        </div>
                        <div class="flex-1">
                            <p class="text-sm font-black text-slate-200">${fullName}</p>
                            <div class="flex items-center mt-1 text-[9px] font-black text-slate-500 uppercase tracking-widest">
                                <i class="far fa-calendar-alt mr-2 text-accent"></i> ${display}
                            </div>
                        </div>
                        <button type="button" class="w-10 h-10 bg-white/5 rounded-xl flex items-center justify-center text-slate-500 hover:text-accent hover:bg-white/10 transition-all ${phone ? '' : 'opacity-40 cursor-not-allowed'}" ${phone ? `onclick="openSmsForBirthday('${phone}', '${fullName.replace(/'/g, "\\'")}')"` : ''}>
                            <i class="fas fa-paper-plane text-[10px]"></i>
                        </button>
                    </div>
                `;
            }).join('');
        } catch (error) {
            loadingEl.classList.add('hidden');
            emptyEl.classList.remove('hidden');
            subtitleEl.textContent = 'Unable to load birthday list';
        }
    }

    function closeBirthdaysThisMonth() {
        closeDashboardModal('birthdays-month-modal', 'birthdays-month-modal-content');
    }

    document.getElementById('birthdays-month-modal').addEventListener('click', (e) => {
        if (e.target === document.getElementById('birthdays-month-modal')) closeBirthdaysThisMonth();
    });

    function openSmsForBirthday(phone, fullName) {
        const cleanPhone = (phone || '').trim();
        if (!cleanPhone) {
            alert('This member has no phone number saved.');
            return;
        }
        const msg = `Happy Birthday ${fullName}! May God bless you abundantly.`;
        const url = `<?php echo BASE_URL; ?>/sms?recipients=${encodeURIComponent(cleanPhone)}&message=${encodeURIComponent(msg)}`;
        window.location.href = url;
    }

    function openSmsForBirthdayBroadcast() {
        const phones = <?php
            $phones = [];
            foreach (($birthdays ?? []) as $b) {
                $p = trim($b['phone'] ?? '');
                if ($p !== '') $phones[] = $p;
            }
            echo json_encode(array_values(array_unique($phones)));
        ?>;

        if (!phones.length) {
            alert('No birthdays with phone numbers found.');
            return;
        }

        const msg = 'Happy Birthday! May God bless you abundantly.';
        const url = `<?php echo BASE_URL; ?>/sms?recipients=${encodeURIComponent(phones.join(','))}&message=${encodeURIComponent(msg)}`;
        window.location.href = url;
    }

    window.initializeDashboardInsightsSlider = function () {
        const slider = document.getElementById('insights-slider');
        const dots = Array.from(document.querySelectorAll('.insights-dot'));
        if (!slider || dots.length < 2 || slider.dataset.initialized === 'true') return;

        slider.dataset.initialized = 'true';

        let currentSlide = 0;
        let timerId = null;

        function renderInsightsSlide(index) {
            currentSlide = index;
            slider.style.transform = `translateX(-${index * 100}%)`;

            dots.forEach((dot, dotIndex) => {
                dot.classList.toggle('bg-accent', dotIndex === index);
                dot.classList.toggle('bg-slate-700', dotIndex !== index);
                dot.classList.toggle('scale-125', dotIndex === index);
            });
        }

        function restartInsightsTimer() {
            if (timerId) {
                window.clearInterval(timerId);
            }

            timerId = window.setInterval(() => {
                const next = (currentSlide + 1) % dots.length;
                renderInsightsSlide(next);
            }, 5500);
        }

        dots.forEach((dot, index) => {
            dot.addEventListener('click', () => {
                renderInsightsSlide(index);
                restartInsightsTimer();
            });
        });

        renderInsightsSlide(0);
        restartInsightsTimer();
    };

    window.addEventListener('load', window.initializeDashboardInsightsSlider);
</script>

<?php $adminPendingRequests = $dashboardChangeRequests['pending'] ?? []; ?>
<div class="glass-card rounded-[3rem] border-white/5 overflow-hidden mb-12">
    <div class="px-10 py-8 border-b border-white/5 bg-white/[0.02] flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3">
        <div>
            <h4 class="text-xl font-black text-white tracking-tight">Pending Finance Change Requests</h4>
            <p class="text-[10px] font-black text-slate-500 uppercase tracking-widest mt-2">Visible to admin. Approval is handled by Head of Finance.</p>
        </div>
        <a href="<?php echo BASE_URL; ?>/finance" class="text-[10px] font-black uppercase tracking-widest text-accent hover:underline">Open finance</a>
    </div>
    <div class="p-10">
        <?php if (empty($adminPendingRequests)): ?>
            <div class="text-center text-slate-500 font-bold italic py-6">No pending finance change requests right now.</div>
        <?php else: ?>
            <div class="grid grid-cols-1 xl:grid-cols-2 gap-4">
                <?php foreach ($adminPendingRequests as $request): ?>
                    <div class="glass-card p-5 rounded-2xl border-white/5 hover:bg-white/5 transition-all">
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="px-3 py-1.5 text-[9px] font-black rounded-full uppercase tracking-widest border bg-white/5 text-slate-300 border-white/10">Pending</span>
                            <span class="text-sm font-black text-slate-200"><?php echo htmlspecialchars($request['transaction_type'] ?? ''); ?></span>
                            <span class="text-xs font-bold text-slate-500"><?php echo htmlspecialchars($request['transaction_number'] ?? ''); ?></span>
                        </div>
                        <p class="text-sm font-black text-accent mt-3"><?php echo htmlspecialchars($request['requested_by_name'] ?? ''); ?> requested a correction</p>
                        <p class="text-sm font-black text-white mt-2"><?php echo strtoupper(trim((AppConfig::getSetting('finance_currency', 'GHS')))); ?> <?php echo number_format((float)($request['current_amount'] ?? 0), 2); ?></p>
                        <p class="text-xs font-bold text-slate-400 mt-3"><?php echo htmlspecialchars($request['reason'] ?? ''); ?></p>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Quick Action Buttons -->
<div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4 mb-12">
    <a href="<?php echo BASE_URL; ?>/members/add" class="glass-card flex items-center p-4 rounded-[2rem] hover-glow-yellow transition-all group border-white/5">
        <div class="w-12 h-12 bg-accent rounded-2xl flex items-center justify-center mr-4 shrink-0 shadow-lg shadow-yellow-500/20 group-hover:scale-110 transition-transform">
            <i class="fas fa-user-plus text-slate-900 text-sm"></i>
        </div>
        <span class="text-xs font-black text-slate-200 uppercase tracking-widest">Add Member</span>
    </a>
    <a href="<?php echo BASE_URL; ?>/attendance/mark" class="glass-card flex items-center p-4 rounded-[2rem] hover-glow-blue transition-all group border-white/5">
        <div class="w-12 h-12 bg-slate-800 rounded-2xl flex items-center justify-center mr-4 shrink-0 border border-white/10 group-hover:bg-blue-500 group-hover:border-transparent transition-all">
            <i class="fas fa-check-double text-accent group-hover:text-white text-sm"></i>
        </div>
        <span class="text-xs font-black text-slate-200 uppercase tracking-widest">Attendance</span>
    </a>
    <a href="<?php echo BASE_URL; ?>/finance/add" class="glass-card flex items-center p-4 rounded-[2rem] hover-glow-green transition-all group border-white/5">
        <div class="w-12 h-12 bg-slate-800 rounded-2xl flex items-center justify-center mr-4 shrink-0 border border-white/10 group-hover:bg-green-500 group-hover:border-transparent transition-all">
            <i class="fas fa-receipt text-accent group-hover:text-white text-sm"></i>
        </div>
        <span class="text-xs font-black text-slate-200 uppercase tracking-widest">Finance</span>
    </a>
    <a href="<?php echo BASE_URL; ?>/sms" class="glass-card flex items-center p-4 rounded-[2rem] hover-glow-purple transition-all group border-white/5">
        <div class="w-12 h-12 bg-slate-800 rounded-2xl flex items-center justify-center mr-4 shrink-0 border border-white/10 group-hover:bg-purple-500 group-hover:border-transparent transition-all">
            <i class="fas fa-paper-plane text-accent group-hover:text-white text-sm"></i>
        </div>
        <span class="text-xs font-black text-slate-200 uppercase tracking-widest">SMS Broadcast</span>
    </a>
</div>

<!-- Stat Cards -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-12">
    <!-- Members Card -->
    <div class="glass-card relative overflow-hidden rounded-[2.5rem] p-8 group border-white/5 card-interaction">
        <div class="relative z-10">
            <div class="w-14 h-14 bg-white/5 rounded-2xl flex items-center justify-center mb-8 border border-white/10 group-hover:bg-accent transition-all duration-500">
                <i class="fas fa-users text-slate-400 group-hover:text-slate-900 text-xl transition-colors"></i>
            </div>
            <p class="text-[10px] font-black uppercase tracking-[0.3em] text-slate-400">Total Members</p>
            <h3 class="text-4xl font-black mt-2 text-white"><?php echo number_format($stats['total_members']); ?></h3>
            <div class="mt-8 flex items-center justify-between">
                <a href="<?php echo BASE_URL; ?>/members" class="text-[10px] font-black uppercase tracking-widest text-accent hover:underline">Directory</a>
                <span class="text-[10px] font-bold text-emerald-400">+12% <i class="fas fa-arrow-up ml-1"></i></span>
            </div>
        </div>
        <div class="absolute -bottom-10 -right-10 w-32 h-32 bg-accent/5 rounded-full blur-3xl group-hover:bg-accent/10 transition-all"></div>
    </div>

    <!-- New Members Card -->
    <div class="glass-card relative overflow-hidden rounded-[2.5rem] p-8 group border-white/5 card-interaction">
        <div class="relative z-10">
            <div class="w-14 h-14 bg-white/5 rounded-2xl flex items-center justify-center mb-8 border border-white/10 group-hover:bg-blue-500 transition-all duration-500">
                <i class="fas fa-user-plus text-slate-400 group-hover:text-white text-xl transition-colors"></i>
            </div>
            <p class="text-[10px] font-black uppercase tracking-[0.3em] text-slate-400">Recent Growth</p>
            <h3 class="text-4xl font-black mt-2 text-white"><?php echo (int)($stats['recent_growth'] ?? 0); ?></h3>
            <div class="mt-8 flex items-center justify-between">
                <span class="text-[10px] font-black uppercase tracking-widest text-slate-500 italic">Last 30 days</span>
                <div class="flex -space-x-2">
                    <div class="w-6 h-6 rounded-full border-2 border-slate-900 bg-slate-700"></div>
                    <div class="w-6 h-6 rounded-full border-2 border-slate-900 bg-slate-600"></div>
                    <div class="w-6 h-6 rounded-full border-2 border-slate-900 bg-accent"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Financial Card -->
    <div class="glass-card relative overflow-hidden rounded-[2.5rem] p-8 group border-white/5 card-interaction">
        <div class="relative z-10">
            <div class="w-14 h-14 bg-white/5 rounded-2xl flex items-center justify-center mb-8 border border-white/10 group-hover:bg-green-500 transition-all duration-500">
                <i class="fas fa-wallet text-slate-400 group-hover:text-white text-xl transition-colors"></i>
            </div>
            <p class="text-[10px] font-black uppercase tracking-[0.3em] text-slate-400">Monthly Revenue</p>
            <h3 class="text-3xl font-black mt-2 text-accent tracking-tighter"><?php echo $stats['monthly_donations']; ?></h3>
            <div class="mt-8">
                <div class="w-full bg-white/5 h-1.5 rounded-full overflow-hidden">
                    <div class="bg-accent h-full rounded-full" style="width: 75%"></div>
                </div>
                <p class="text-[9px] font-black text-slate-500 uppercase mt-2 tracking-widest">75% of target</p>
            </div>
        </div>
    </div>

    <!-- Birthdays Card -->
    <div class="glass-card relative overflow-hidden rounded-[2.5rem] p-8 group border-white/5 card-interaction">
        <div class="relative z-10">
            <div class="w-14 h-14 bg-white/5 rounded-2xl flex items-center justify-center mb-8 border border-white/10 group-hover:bg-purple-500 transition-all duration-500">
                <i class="fas fa-gift text-slate-400 group-hover:text-white text-xl transition-colors"></i>
            </div>
            <p class="text-[10px] font-black uppercase tracking-[0.3em] text-slate-400">Celebrations</p>
            <h3 class="text-4xl font-black mt-2 text-white"><?php echo $stats['birthday_count']; ?></h3>
            <div class="mt-8 flex items-center">
                <button type="button" onclick="openBirthdaysThisMonth()" class="px-3 py-1 bg-white/5 rounded-full text-[9px] font-black text-accent uppercase tracking-widest border border-white/10 hover:bg-white/10 hover-glow-yellow transition-all">
                    This Month
                </button>
            </div>
        </div>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6 lg:gap-8">
    <!-- Demographics -->
    <div class="lg:col-span-2 glass-card rounded-[3rem] p-5 sm:p-6 lg:p-10 border-white/5">
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-8 sm:mb-12">
            <h4 class="text-2xl font-black text-white tracking-tight">Member Insights</h4>
            <div class="flex gap-2" id="insights-dots">
                <button type="button" class="insights-dot w-2 h-2 rounded-full bg-accent transition-all duration-300" data-slide="0" aria-label="Show member insights"></button>
                <button type="button" class="insights-dot w-2 h-2 rounded-full bg-slate-700 transition-all duration-300" data-slide="1" aria-label="Show visitor insights"></button>
            </div>
        </div>
        <div class="relative overflow-hidden">
            <div id="insights-slider" class="transition-transform duration-700 ease-out flex w-full">
                <section class="insights-slide min-w-full">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8 lg:gap-16">
                        <div>
                            <h5 class="text-[10px] font-black text-slate-500 uppercase tracking-[0.4em] mb-8 flex items-center">
                                <i class="fas fa-venus-mars mr-3 text-accent"></i> Gender Mix
                            </h5>
                            <div class="space-y-8">
                                <?php 
                                    $genderData = $demographics['gender']['data'];
                                    $genderTotal = $demographics['gender']['total'] ?: 1;
                                    $malePercent = round(($genderData['male'] / $genderTotal) * 100);
                                    $femalePercent = round(($genderData['female'] / $genderTotal) * 100);
                                ?>
                                <div>
                                    <div class="flex justify-between items-center mb-3">
                                        <span class="text-xs font-black text-slate-300 uppercase tracking-widest">Male</span>
                                        <span class="text-xs font-black text-white"><?php echo $malePercent; ?>%</span>
                                    </div>
                                    <div class="w-full bg-white/5 h-2.5 rounded-full overflow-hidden p-0.5 border border-white/5">
                                        <div class="bg-accent h-full rounded-full shadow-[0_0_10px_rgba(251,191,36,0.5)]" style="width: <?php echo $malePercent; ?>%"></div>
                                    </div>
                                </div>
                                <div>
                                    <div class="flex justify-between items-center mb-3">
                                        <span class="text-xs font-black text-slate-300 uppercase tracking-widest">Female</span>
                                        <span class="text-xs font-black text-white"><?php echo $femalePercent; ?>%</span>
                                    </div>
                                    <div class="w-full bg-white/5 h-2.5 rounded-full overflow-hidden p-0.5 border border-white/5">
                                        <div class="bg-sky-400 h-full rounded-full shadow-[0_0_12px_rgba(56,189,248,0.45)]" style="width: <?php echo $femalePercent; ?>%"></div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div>
                            <h5 class="text-[10px] font-black text-slate-500 uppercase tracking-[0.4em] mb-8 flex items-center">
                                <i class="fas fa-chart-line mr-3 text-accent"></i> Age Brackets
                            </h5>
                            <div class="space-y-6">
                                <?php 
                                    $ageData = $demographics['age'];
                                    $ageTotal = $ageData['total'] ?: 0;
                                    $ageDenominator = $ageTotal > 0 ? $ageTotal : 1;
                                    $ageBrackets = [
                                        ['Under 18', (int)$ageData['under_18'], 'rgba(251, 191, 36, 0.98)', '0 0 14px rgba(251,191,36,0.6)'],
                                        ['18-35', (int)$ageData['age_18_35'], 'rgba(59, 130, 246, 0.98)', '0 0 14px rgba(59,130,246,0.55)'],
                                        ['36-60', (int)$ageData['age_36_60'], 'rgba(34, 197, 94, 0.98)', '0 0 14px rgba(34,197,94,0.5)'],
                                        ['Over 60', (int)$ageData['over_60'], 'rgba(168, 85, 247, 0.98)', '0 0 14px rgba(168,85,247,0.5)'],
                                    ];

                                    if (!empty($ageData['unknown_age'])) {
                                        $ageBrackets[] = ['DOB Missing', (int)$ageData['unknown_age'], 'rgba(148, 163, 184, 0.95)', '0 0 12px rgba(148,163,184,0.4)'];
                                    }
                                ?>

                                <?php if ($ageTotal === 0): ?>
                                    <div class="glass-card rounded-2xl border-white/10 p-4">
                                        <p class="text-xs font-black text-white">No member records yet.</p>
                                        <p class="text-[11px] font-bold text-slate-400 mt-2">Add members to unlock age insights on the dashboard.</p>
                                    </div>
                                <?php else: ?>
                                    <?php foreach ($ageBrackets as $bracket): ?>
                                        <?php
                                            $agePercent = round(($bracket[1] / $ageDenominator) * 100);
                                            $ageBarWidth = $agePercent > 0 ? 'max(14px, ' . $agePercent . '%)' : '0%';
                                        ?>
                                        <div class="flex items-center gap-3 sm:gap-4 group">
                                            <span class="inline-flex items-center gap-2 text-[10px] font-black text-slate-400 min-w-[4.9rem] sm:min-w-[5.5rem] uppercase tracking-tighter group-hover:text-accent transition-colors">
                                                <span class="w-2.5 h-2.5 rounded-full shrink-0" style="background: <?php echo $bracket[2]; ?>; box-shadow: <?php echo $bracket[3]; ?>;"></span>
                                                <?php echo $bracket[0]; ?>
                                            </span>
                                            <div class="flex-1 bg-white/5 h-3 rounded-full overflow-hidden border border-white/5">
                                                <div class="h-full rounded-full transition-all duration-500" style="width: <?php echo $ageBarWidth; ?>; background: <?php echo $bracket[2]; ?>; box-shadow: <?php echo $bracket[3]; ?>;"></div>
                                            </div>
                                            <span class="text-[10px] font-black text-white min-w-[3.6rem] text-right"><?php echo $bracket[1]; ?> / <?php echo $agePercent; ?>%</span>
                                        </div>
                                    <?php endforeach; ?>

                                    <?php if (!empty($ageData['unknown_age'])): ?>
                                        <p class="text-[11px] font-bold text-slate-500 pt-1">Add date of birth to older member records to reduce the missing-data bracket.</p>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </section>

                <section class="insights-slide min-w-full">
                    <?php 
                        $visitorInsightSummary = $visitorInsights['summary'] ?? ['total' => 0, 'pending' => 0, 'completed' => 0, 'first_time' => 0, 'today' => 0, 'this_month' => 0];
                        $recentVisitorInsightList = $visitorInsights['recent'] ?? [];
                    ?>
                    <div class="grid grid-cols-1 xl:grid-cols-2 gap-8 lg:gap-12">
                        <div>
                            <h5 class="text-[10px] font-black text-slate-500 uppercase tracking-[0.4em] mb-8 flex items-center">
                                <i class="fas fa-user-clock mr-3 text-accent"></i> Visitor Snapshot
                            </h5>
                            <div class="grid grid-cols-2 gap-4">
                                <div class="glass-card rounded-2xl p-5 border-white/5">
                                    <p class="text-[9px] font-black uppercase tracking-[0.3em] text-slate-500">Today</p>
                                    <p class="text-3xl font-black text-white mt-3"><?php echo number_format((int)$visitorInsightSummary['today']); ?></p>
                                </div>
                                <div class="glass-card rounded-2xl p-5 border-white/5">
                                    <p class="text-[9px] font-black uppercase tracking-[0.3em] text-slate-500">This Month</p>
                                    <p class="text-3xl font-black text-accent mt-3"><?php echo number_format((int)$visitorInsightSummary['this_month']); ?></p>
                                </div>
                                <div class="glass-card rounded-2xl p-5 border-white/5">
                                    <p class="text-[9px] font-black uppercase tracking-[0.3em] text-slate-500">Pending</p>
                                    <p class="text-3xl font-black text-sky-400 mt-3"><?php echo number_format((int)$visitorInsightSummary['pending']); ?></p>
                                </div>
                                <div class="glass-card rounded-2xl p-5 border-white/5">
                                    <p class="text-[9px] font-black uppercase tracking-[0.3em] text-slate-500">First-Time</p>
                                    <p class="text-3xl font-black text-emerald-400 mt-3"><?php echo number_format((int)$visitorInsightSummary['first_time']); ?></p>
                                </div>
                            </div>
                        </div>

                        <div>
                            <h5 class="text-[10px] font-black text-slate-500 uppercase tracking-[0.4em] mb-8 flex items-center">
                                <i class="fas fa-broadcast-tower mr-3 text-accent"></i> Real-Time Visitors
                            </h5>
                            <div class="space-y-4">
                                <?php if (empty($recentVisitorInsightList)): ?>
                                    <div class="glass-card rounded-2xl border-white/10 p-5">
                                        <p class="text-sm font-black text-white">No visitor records yet.</p>
                                        <p class="text-[11px] font-bold text-slate-400 mt-2">New visitor details will show here when the insight slide rotates.</p>
                                    </div>
                                <?php else: ?>
                                    <?php foreach ($recentVisitorInsightList as $visitorInsight): ?>
                                        <div class="glass-card p-4 rounded-2xl border-white/5 flex items-start justify-between gap-4">
                                            <div class="min-w-0">
                                                <p class="text-sm font-black text-white truncate"><?php echo htmlspecialchars(trim(($visitorInsight['first_name'] ?? '') . ' ' . ($visitorInsight['last_name'] ?? ''))); ?></p>
                                                <div class="flex flex-wrap items-center gap-2 mt-2 text-[10px] font-black uppercase tracking-[0.22em] text-slate-500">
                                                    <span><?php echo !empty($visitorInsight['visit_date']) ? htmlspecialchars(date('M d, Y', strtotime($visitorInsight['visit_date']))) : 'No date'; ?></span>
                                                    <span class="text-accent">•</span>
                                                    <span><?php echo htmlspecialchars($visitorInsight['service_attended'] ?? 'Service not set'); ?></span>
                                                </div>
                                                <p class="text-xs font-bold text-slate-300 mt-3 truncate"><?php echo htmlspecialchars($visitorInsight['assigned_to_name'] ?? 'Unassigned'); ?></p>
                                            </div>
                                            <span class="shrink-0 px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-[0.2em] border <?php echo ($visitorInsight['follow_up_status'] ?? '') === 'Completed' ? 'bg-emerald-500/10 text-emerald-300 border-emerald-500/20' : 'bg-sky-500/10 text-sky-300 border-sky-500/20'; ?>">
                                                <?php echo htmlspecialchars($visitorInsight['follow_up_status'] ?? 'Pending'); ?>
                                            </span>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </section>
            </div>
        </div>
    </div>

    <!-- Birthday List -->
    <div class="glass-card rounded-[3rem] p-5 sm:p-6 lg:p-10 border-white/5 flex flex-col">
        <div class="flex justify-between items-center mb-8 sm:mb-10">
            <h4 class="text-2xl font-black text-white tracking-tight">Birthdays</h4>
            <div class="w-10 h-10 bg-accent rounded-2xl flex items-center justify-center shadow-lg shadow-yellow-500/20">
                <i class="fas fa-birthday-cake text-slate-900 text-sm"></i>
            </div>
        </div>
        
        <div class="space-y-4 sm:space-y-6 flex-1 custom-scrollbar overflow-y-auto pr-1 sm:pr-2 sm:max-h-[350px]">
            <?php if (empty($birthdays)): ?>
                <div class="flex flex-col items-center justify-center py-10 opacity-30">
                    <i class="fas fa-calendar-times text-4xl mb-4"></i>
                    <p class="text-[10px] font-black uppercase tracking-widest">No Birthdays Found</p>
                </div>
            <?php else: ?>
                <?php foreach ($birthdays as $birthday): ?>
                    <div class="glass-card p-4 rounded-2xl border-white/5 flex items-center space-x-4 group hover:bg-white/5 transition-all">
                        <div class="w-12 h-12 bg-slate-800 rounded-xl flex items-center justify-center border border-white/10 group-hover:border-accent transition-all overflow-hidden">
                            <?php if (!empty($birthday['photo_path'])): ?>
                                <img src="<?php echo BASE_URL . '/' . ltrim($birthday['photo_path'], '/'); ?>" class="w-full h-full object-cover">
                            <?php else: ?>
                                <span class="text-accent font-black text-sm"><?php echo strtoupper(substr($birthday['first_name'], 0, 1)); ?></span>
                            <?php endif; ?>
                        </div>
                        <div class="flex-1">
                            <p class="text-sm font-black text-slate-200 group-hover:text-white transition-colors"><?php echo $birthday['first_name'] . ' ' . $birthday['last_name']; ?></p>
                            <div class="flex items-center mt-1 text-[9px] font-black text-slate-500 uppercase tracking-widest">
                                <i class="far fa-calendar-alt mr-2 text-accent"></i> <?php echo $birthday['birthday_display']; ?>
                            </div>
                        </div>
                        <button type="button" onclick="openSmsForBirthday('<?php echo addslashes($birthday['phone'] ?? ''); ?>', '<?php echo addslashes($birthday['first_name'] . ' ' . $birthday['last_name']); ?>')" class="w-8 h-8 bg-white/5 rounded-lg flex items-center justify-center text-slate-500 hover:text-accent hover:bg-white/10 transition-all">
                            <i class="fas fa-paper-plane text-[10px]"></i>
                        </button>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        
        <button type="button" onclick="openSmsForBirthdayBroadcast()" class="w-full mt-10 bg-accent text-slate-900 py-5 rounded-2xl font-black text-xs uppercase tracking-[0.2em] hover:scale-[1.02] active:scale-95 transition-all shadow-xl shadow-yellow-500/10">
            Broadcast Wishes
        </button>
    </div>
</div>
<?php endif; ?>

<?php if ($isStaff && !empty($staff)): ?>
<div id="quick-entry-modal" class="fixed inset-0 bg-slate-950/80 backdrop-blur-xl z-[60] hidden flex items-center justify-center p-4">
    <div class="glass-card w-full max-w-4xl rounded-[3.5rem] overflow-hidden shadow-2xl transform transition-all duration-500 scale-95 opacity-0 border-white/10 max-h-[95vh] flex flex-col" id="quick-entry-modal-content">
        <div class="px-8 sm:px-10 py-8 bg-slate-900 relative overflow-hidden border-b border-white/5 flex-shrink-0">
            <div class="relative z-10 flex justify-between items-center text-white">
                <div>
                    <h3 class="text-3xl font-black tracking-tighter">Quick Transaction</h3>
                    <p class="text-accent text-[10px] font-black mt-2 tracking-[0.3em] uppercase">Fast Cashier Entry</p>
                </div>
                <button type="button" onclick="closeQuickEntryModal()" class="w-12 h-12 bg-white/5 hover:bg-accent hover:text-slate-900 rounded-2xl flex items-center justify-center transition-all border border-white/10">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        </div>

        <form action="<?php echo BASE_URL; ?>/finance/store" method="POST" class="p-6 sm:p-10 bg-slate-900/50 overflow-y-auto custom-scrollbar flex-1">
            <input type="hidden" name="transaction_type" id="quick_transaction_type" value="Offering">
            <input type="hidden" name="redirect_to" value="dashboard">

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                <div class="space-y-8">
                    <div id="quick-income-category-wrap" class="space-y-3">
                        <label class="block text-[9px] font-black text-slate-500 uppercase tracking-widest ml-1">Income Category</label>
                        <?php
                            $isFinanceUser = Auth::isFinance();
                            $incomeTypeButtons = [];
                            $incomeTypeButtons[] = ['value' => 'Offering', 'label' => 'General Offering', 'icon' => 'fa-receipt'];
                            if ($isFinanceUser) {
                                $incomeTypeButtons[] = ['value' => 'Sunday School', 'label' => 'Sunday School', 'icon' => 'fa-chalkboard-teacher'];
                                $incomeTypeButtons[] = ['value' => 'Annual Harvest', 'label' => 'Annual Harvest', 'icon' => 'fa-wheat-awn'];
                                $incomeTypeButtons[] = ['value' => 'Mini Harvest', 'label' => 'Mini Harvest', 'icon' => 'fa-seedling'];
                            }
                            $incomeTypeButtons[] = ['value' => 'Tithe', 'label' => 'Tithe', 'icon' => 'fa-hand-holding-dollar'];
                            $incomeTypeButtons[] = ['value' => 'Departmental Savings', 'label' => 'Department Offering', 'icon' => 'fa-sitemap'];
                            $incomeTypeButtons[] = ['value' => 'Welfare', 'label' => 'Welfare', 'icon' => 'fa-heart'];
                        ?>
                        <div class="grid grid-cols-2 gap-3">
                            <?php foreach ($incomeTypeButtons as $opt): ?>
                                <button
                                    type="button"
                                    data-quick-income-type="1"
                                    data-value="<?php echo htmlspecialchars($opt['value']); ?>"
                                    class="bg-white/5 text-slate-200 border-white/10 hover:bg-white/10 w-full flex items-center gap-3 rounded-2xl border px-4 py-4 transition-all"
                                >
                                    <span class="text-accent w-9 h-9 rounded-xl flex items-center justify-center bg-white/5 border border-white/10">
                                        <i class="fas <?php echo htmlspecialchars($opt['icon']); ?> text-sm"></i>
                                    </span>
                                    <span class="min-w-0 text-left">
                                        <span class="block text-[10px] font-black uppercase tracking-widest truncate"><?php echo htmlspecialchars($opt['label']); ?></span>
                                    </span>
                                </button>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <div id="quick-offering-subtype-wrap" class="space-y-3 hidden">
                        <label class="block text-[9px] font-black text-slate-500 uppercase tracking-widest ml-1">Offering Type</label>
                        <div class="relative group">
                            <i class="fas fa-gift absolute left-5 top-1/2 -translate-y-1/2 text-slate-600 group-focus-within:text-accent transition-colors"></i>
                            <select id="quick_offering_subtype" name="offering_subtype" class="w-full bg-white/5 border border-white/10 focus:border-accent rounded-2xl pl-14 pr-10 py-5 text-sm font-bold text-white transition-all outline-none appearance-none cursor-pointer">
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
                                <input type="number" step="0.01" id="quick_amount" name="amount" required class="w-full bg-white/5 border border-white/10 focus:border-accent rounded-2xl pl-14 pr-6 py-5 text-sm font-bold text-white transition-all outline-none" placeholder="0.00">
                            </div>
                        </div>
                        <div class="space-y-3">
                            <label class="block text-[9px] font-black text-slate-500 uppercase tracking-widest ml-1">Payment Method</label>
                            <div class="relative group">
                                <i class="fas fa-credit-card absolute left-5 top-1/2 -translate-y-1/2 text-slate-600 group-focus-within:text-accent transition-colors"></i>
                                <select name="payment_method" required class="w-full bg-white/5 border border-white/10 focus:border-accent rounded-2xl pl-14 pr-10 py-5 text-sm font-bold text-white transition-all outline-none appearance-none cursor-pointer">
                                    <option value="Cash">Cash</option>
                                    <option value="MoMo">MoMo</option>
                                    <option value="Bank Transfer">Bank Transfer</option>
                                    <option value="Check">Check</option>
                                </select>
                                <i class="fas fa-chevron-down absolute right-5 top-1/2 -translate-y-1/2 text-slate-600 text-[10px] pointer-events-none"></i>
                            </div>
                        </div>
                    </div>

                    <div class="space-y-3">
                        <label class="block text-[9px] font-black text-slate-500 uppercase tracking-widest ml-1">Quick Calculator</label>
                        <div class="glass-card rounded-[2.5rem] p-6 border-white/10 space-y-5">
                            <div class="grid grid-cols-4 gap-3">
                                <button type="button" class="quick-calc-quick px-3 py-3 rounded-2xl bg-white/5 border border-white/10 text-[10px] font-black text-slate-200 uppercase tracking-widest" data-value="10">10</button>
                                <button type="button" class="quick-calc-quick px-3 py-3 rounded-2xl bg-white/5 border border-white/10 text-[10px] font-black text-slate-200 uppercase tracking-widest" data-value="20">20</button>
                                <button type="button" class="quick-calc-quick px-3 py-3 rounded-2xl bg-white/5 border border-white/10 text-[10px] font-black text-slate-200 uppercase tracking-widest" data-value="50">50</button>
                                <button type="button" class="quick-calc-quick px-3 py-3 rounded-2xl bg-white/5 border border-white/10 text-[10px] font-black text-slate-200 uppercase tracking-widest" data-value="100">100</button>
                            </div>
                            <div class="grid grid-cols-3 gap-3">
                                <button type="button" class="quick-calc-key px-3 py-4 rounded-2xl bg-white/5 border border-white/10 text-sm font-black text-white" data-key="1">1</button>
                                <button type="button" class="quick-calc-key px-3 py-4 rounded-2xl bg-white/5 border border-white/10 text-sm font-black text-white" data-key="2">2</button>
                                <button type="button" class="quick-calc-key px-3 py-4 rounded-2xl bg-white/5 border border-white/10 text-sm font-black text-white" data-key="3">3</button>
                                <button type="button" class="quick-calc-key px-3 py-4 rounded-2xl bg-white/5 border border-white/10 text-sm font-black text-white" data-key="4">4</button>
                                <button type="button" class="quick-calc-key px-3 py-4 rounded-2xl bg-white/5 border border-white/10 text-sm font-black text-white" data-key="5">5</button>
                                <button type="button" class="quick-calc-key px-3 py-4 rounded-2xl bg-white/5 border border-white/10 text-sm font-black text-white" data-key="6">6</button>
                                <button type="button" class="quick-calc-key px-3 py-4 rounded-2xl bg-white/5 border border-white/10 text-sm font-black text-white" data-key="7">7</button>
                                <button type="button" class="quick-calc-key px-3 py-4 rounded-2xl bg-white/5 border border-white/10 text-sm font-black text-white" data-key="8">8</button>
                                <button type="button" class="quick-calc-key px-3 py-4 rounded-2xl bg-white/5 border border-white/10 text-sm font-black text-white" data-key="9">9</button>
                                <button type="button" class="quick-calc-key px-3 py-4 rounded-2xl bg-white/5 border border-white/10 text-sm font-black text-white" data-key=".">.</button>
                                <button type="button" class="quick-calc-key px-3 py-4 rounded-2xl bg-white/5 border border-white/10 text-sm font-black text-white" data-key="0">0</button>
                                <button type="button" id="quick-calc-clear" class="px-3 py-4 rounded-2xl bg-rose-500/10 border border-rose-500/20 text-sm font-black text-rose-300">C</button>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="space-y-8">
                    <div class="space-y-3">
                        <label class="block text-[9px] font-black text-slate-500 uppercase tracking-widest ml-1">Department (For Department Offering)</label>
                        <div class="relative group">
                            <i class="fas fa-sitemap absolute left-5 top-1/2 -translate-y-1/2 text-slate-600"></i>
                            <select id="quick_department_id" name="department_id" class="w-full bg-white/5 border border-white/10 focus:border-accent rounded-2xl pl-14 pr-10 py-5 text-sm font-bold text-white transition-all outline-none appearance-none cursor-pointer">
                                <option value="">None</option>
                                <?php foreach (($staff['departments'] ?? []) as $d): ?>
                                    <option value="<?php echo (int)$d['id']; ?>"><?php echo htmlspecialchars($d['name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                            <i class="fas fa-chevron-down absolute right-5 top-1/2 -translate-y-1/2 text-slate-600 text-[10px] pointer-events-none"></i>
                        </div>
                    </div>

                    <div class="space-y-3">
                        <label class="block text-[9px] font-black text-slate-500 uppercase tracking-widest ml-1">Member (Required For Tithe & Welfare)</label>
                        <div class="relative group">
                            <i class="fas fa-magnifying-glass absolute left-5 top-1/2 -translate-y-1/2 text-slate-600 group-focus-within:text-accent transition-colors"></i>
                            <input id="quick_member_search" type="text" class="w-full bg-white/5 border border-white/10 focus:border-accent rounded-2xl pl-14 pr-6 py-4 text-sm font-bold text-white transition-all outline-none" placeholder="Search member by name...">
                            <div id="quick_member_search_results" class="hidden absolute left-0 right-0 top-full mt-2 z-[70] rounded-2xl border border-white/10 bg-slate-950/95 backdrop-blur-xl shadow-2xl overflow-hidden max-h-64 overflow-y-auto custom-scrollbar"></div>
                        </div>
                        <div class="relative group mt-3">
                            <i class="fas fa-user absolute left-5 top-1/2 -translate-y-1/2 text-slate-600"></i>
                            <select id="quick_member_id" name="member_id" class="w-full bg-white/5 border border-white/10 focus:border-accent rounded-2xl pl-14 pr-10 py-5 text-sm font-bold text-white transition-all outline-none appearance-none cursor-pointer">
                                <option value="">Select member when needed</option>
                                <?php foreach (($staff['members'] ?? []) as $member): ?>
                                    <option value="<?php echo (int)$member['id']; ?>"><?php echo htmlspecialchars(($member['first_name'] ?? '') . ' ' . ($member['last_name'] ?? '')); ?></option>
                                <?php endforeach; ?>
                            </select>
                            <i class="fas fa-chevron-down absolute right-5 top-1/2 -translate-y-1/2 text-slate-600 text-[10px] pointer-events-none"></i>
                        </div>
                    </div>

                    <div class="space-y-3">
                        <label class="block text-[9px] font-black text-slate-500 uppercase tracking-widest ml-1">Description / Notes</label>
                        <div class="relative group">
                            <i class="fas fa-align-left absolute left-5 top-6 text-slate-600 group-focus-within:text-accent transition-colors"></i>
                            <textarea name="description" rows="4" class="w-full bg-white/5 border border-white/10 focus:border-accent rounded-3xl pl-14 pr-6 py-6 text-sm font-bold text-white transition-all outline-none resize-none" placeholder="Optional notes..."></textarea>
                        </div>
                    </div>

                    <div id="quick-sms-notify-wrap" class="hidden space-y-4 pt-4">
                        <div class="flex items-center gap-4 bg-accent/5 border border-accent/20 rounded-2xl p-5">
                            <div class="w-10 h-10 bg-accent/10 rounded-xl flex items-center justify-center border border-accent/20">
                                <i class="fas fa-comment-sms text-accent"></i>
                            </div>
                            <div class="flex-1">
                                <div class="flex items-center justify-between">
                                    <label for="quick_send_sms" class="text-[10px] font-black text-white uppercase tracking-widest cursor-pointer">Send SMS Notification</label>
                                    <label class="relative inline-flex items-center cursor-pointer">
                                        <input type="checkbox" name="send_sms" id="quick_send_sms" value="1" class="sr-only peer" checked>
                                        <div class="w-11 h-6 bg-white/10 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-slate-400 after:border-slate-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-accent peer-checked:after:bg-slate-900 peer-checked:after:border-accent"></div>
                                    </label>
                                </div>
                                <p class="text-[9px] font-bold text-slate-500 uppercase tracking-widest mt-1">Send payment confirmation SMS instantly</p>
                            </div>
                        </div>
                    </div>

                    <div class="pt-4">
                        <button type="submit" class="w-full bg-accent text-slate-900 py-6 rounded-2xl font-black text-xs uppercase tracking-[0.3em] hover:scale-[1.02] active:scale-95 transition-all shadow-xl shadow-yellow-500/10">
                            Save Transaction
                        </button>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
    function openQuickEntryModal(type = 'Offering') {
        const modal = document.getElementById('quick-entry-modal');
        const content = document.getElementById('quick-entry-modal-content');
        if (!modal || !content) return;

        const typeInput = document.getElementById('quick_transaction_type');
        if (typeInput) {
            typeInput.value = type;
            // Trigger sync
            const buttons = document.querySelectorAll('[data-quick-income-type="1"]');
            buttons.forEach(btn => {
                if (btn.dataset.value === type) {
                    btn.classList.add('bg-accent', 'text-slate-900', 'border-accent/30', 'shadow-xl', 'shadow-yellow-500/10');
                    btn.classList.remove('bg-white/5', 'text-slate-200', 'border-white/10');
                } else {
                    btn.classList.remove('bg-accent', 'text-slate-900', 'border-accent/30', 'shadow-xl', 'shadow-yellow-500/10');
                    btn.classList.add('bg-white/5', 'text-slate-200', 'border-white/10');
                }
            });
        }

        modal.classList.remove('hidden');
        setTimeout(() => {
            content.classList.remove('scale-95', 'opacity-0');
            content.classList.add('scale-100', 'opacity-100');
        }, 10);
        
        // Initial sync of rules
        if (window.syncQuickRules) window.syncQuickRules();
    }

    function closeQuickEntryModal() {
        const modal = document.getElementById('quick-entry-modal');
        const content = document.getElementById('quick-entry-modal-content');
        if (!modal || !content) return;
        content.classList.remove('scale-100', 'opacity-100');
        content.classList.add('scale-95', 'opacity-0');
        setTimeout(() => modal.classList.add('hidden'), 300);
    }

    (function () {
        const typeInput = document.getElementById('quick_transaction_type');
        const incomeWrap = document.getElementById('quick-income-category-wrap');
        const amountInput = document.getElementById('quick_amount');
        const memberSelect = document.getElementById('quick_member_id');
        const memberSearch = document.getElementById('quick_member_search');
        const memberSearchResults = document.getElementById('quick_member_search_results');
        const departmentSelect = document.getElementById('quick_department_id');
        const smsNotifyWrap = document.getElementById('quick-sms-notify-wrap');
        const offeringSubtypeWrap = document.getElementById('quick-offering-subtype-wrap');
        if (!typeInput || !incomeWrap) return;

        const incomeTypeButtons = Array.from(incomeWrap.querySelectorAll('[data-quick-income-type="1"]'));
        const syncIncomeTypeButtons = function () {
            const current = String(typeInput.value || '');
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

        window.syncQuickRules = () => {
            const selectedType = typeInput.value || '';
            const isOffering = selectedType === 'Offering';
            const isTitheOrWelfare = selectedType === 'Tithe' || selectedType === 'Welfare';
            const requiresDepartment = selectedType === 'Departmental Savings';
            const canHaveMember = isTitheOrWelfare || isOffering;

            if (offeringSubtypeWrap) {
                offeringSubtypeWrap.classList.toggle('hidden', !isOffering);
            }
            if (smsNotifyWrap) {
                smsNotifyWrap.classList.toggle('hidden', !canHaveMember);
            }
            if (departmentSelect) {
                departmentSelect.disabled = !requiresDepartment;
                departmentSelect.classList.toggle('opacity-60', !requiresDepartment);
                if (!requiresDepartment) departmentSelect.value = '';
            }
            if (memberSelect) {
                memberSelect.disabled = !canHaveMember;
                memberSelect.classList.toggle('opacity-60', !canHaveMember);
                if (!canHaveMember) memberSelect.value = '';
            }
            if (memberSearch) {
                memberSearch.disabled = !canHaveMember;
                memberSearch.classList.toggle('opacity-60', !canHaveMember);
                if (!canHaveMember) memberSearch.value = '';
            }
        };

        incomeTypeButtons.forEach((btn) => {
            btn.addEventListener('click', function () {
                typeInput.value = btn.dataset.value;
                syncIncomeTypeButtons();
                window.syncQuickRules();
            });
        });

        const memberOptions = memberSelect ? Array.from(memberSelect.options).filter(o => o.value !== '').map(o => ({ value: o.value, label: o.textContent })) : [];

        const hideMemberSuggestions = () => {
            if (!memberSearchResults) return;
            memberSearchResults.classList.add('hidden');
            memberSearchResults.innerHTML = '';
        };

        const chooseMember = (value) => {
            if (!memberSelect) return;
            memberSelect.value = value;
            const selected = memberOptions.find(o => o.value === value);
            if (memberSearch && selected) memberSearch.value = selected.label;
            hideMemberSuggestions();
        };

        if (memberSearch) {
            memberSearch.addEventListener('input', () => {
                const val = memberSearch.value.trim().toLowerCase();
                if (!val) { hideMemberSuggestions(); return; }
                const filtered = memberOptions.filter(o => o.label.toLowerCase().includes(val)).slice(0, 8);
                if (!filtered.length) {
                    memberSearchResults.innerHTML = '<div class="px-5 py-4 text-xs font-bold text-slate-500">No match.</div>';
                } else {
                    memberSearchResults.innerHTML = filtered.map(o => `
                        <button type="button" class="quick-member-suggestion w-full text-left px-5 py-4 text-sm font-bold text-slate-200 hover:bg-white/5 transition-all border-b border-white/5 last:border-b-0" data-value="${o.value}">${o.label}</button>
                    `).join('');
                }
                memberSearchResults.classList.remove('hidden');
                memberSearchResults.querySelectorAll('.quick-member-suggestion').forEach(b => {
                    b.addEventListener('mousedown', (e) => { e.preventDefault(); chooseMember(b.dataset.value); });
                });
            });
            memberSearch.addEventListener('blur', () => setTimeout(hideMemberSuggestions, 150));
        }

        if (amountInput) {
            document.querySelectorAll('.quick-calc-quick').forEach(b => {
                b.addEventListener('click', () => { amountInput.value = b.dataset.value; amountInput.focus(); });
            });
            document.querySelectorAll('.quick-calc-key').forEach(b => {
                b.addEventListener('click', () => {
                    const key = b.dataset.key;
                    if (key === '.' && amountInput.value.includes('.')) return;
                    amountInput.value += key;
                    amountInput.focus();
                });
            });
            const clear = document.getElementById('quick-calc-clear');
            if (clear) clear.addEventListener('click', () => { amountInput.value = ''; amountInput.focus(); });
        }
    })();
</script>
<?php endif; ?>
