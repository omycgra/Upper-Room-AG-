<div class="mb-8 flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
    <?php
        $meId = (int)Session::get('user_id');
        $role = strtolower((string)Session::get('user_role'));
        $isAdmin = in_array($role, ['admin', 'administrator'], true);
        $isVisitationTeam = ($role === 'visitation_team');
        $isPastor = ($role === 'pastor');
        $canAssign = ($isAdmin || $isPastor);
        $assignees = $assignees ?? [];
    ?>
    <div>
        <p class="text-[10px] font-black uppercase tracking-[0.35em] text-accent">Assimilation Desk</p>
        <h2 class="text-3xl sm:text-4xl font-black text-white tracking-tight mt-3">Visitor Log</h2>
        <p class="text-slate-400 text-sm font-bold mt-2">Responsive visitor tracking with richer follow-up information for church care teams.</p>
    </div>
    <?php if (!$isPastor): ?>
        <a href="<?php echo BASE_URL; ?>/visitors/add" class="inline-flex items-center justify-center gap-3 bg-accent text-slate-900 px-6 py-4 rounded-2xl font-black text-xs uppercase tracking-[0.24em] hover-glow-yellow transition-all shadow-xl shadow-yellow-500/10">
            <i class="fas fa-user-plus text-sm"></i> Register Visitor
        </a>
    <?php endif; ?>
</div>

<div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4 mb-8">
    <div class="glass-card rounded-[2rem] p-5 border-white/10">
        <p class="text-[10px] font-black uppercase tracking-[0.32em] text-slate-500">Total Visitors</p>
        <p class="text-3xl font-black text-white mt-4"><?php echo (int)($stats['total'] ?? 0); ?></p>
    </div>
    <div class="glass-card rounded-[2rem] p-5 border-white/10">
        <p class="text-[10px] font-black uppercase tracking-[0.32em] text-slate-500">Pending Follow-Up</p>
        <p class="text-3xl font-black text-accent mt-4"><?php echo (int)($stats['pending'] ?? 0); ?></p>
    </div>
    <div class="glass-card rounded-[2rem] p-5 border-white/10">
        <p class="text-[10px] font-black uppercase tracking-[0.32em] text-slate-500">First-Time Guests</p>
        <p class="text-3xl font-black text-white mt-4"><?php echo (int)($stats['first_time'] ?? 0); ?></p>
    </div>
    <div class="glass-card rounded-[2rem] p-5 border-white/10">
        <p class="text-[10px] font-black uppercase tracking-[0.32em] text-slate-500">Assigned Follow-Up</p>
        <p class="text-3xl font-black text-white mt-4"><?php echo (int)($stats['assigned'] ?? 0); ?></p>
    </div>
</div>

<?php if (empty($visitors)): ?>
    <div class="glass-card rounded-[2.5rem] p-10 border-white/10 text-center">
        <div class="w-20 h-20 rounded-[2rem] bg-white/5 border border-white/10 text-accent flex items-center justify-center mx-auto mb-5">
            <i class="fas fa-user-friends text-2xl"></i>
        </div>
        <h3 class="text-2xl font-black text-white">No visitors recorded yet</h3>
        <p class="text-slate-400 text-sm font-bold mt-3">Start by capturing new guest details, service attended, and follow-up plans.</p>
    </div>
<?php else: ?>
    <div class="grid grid-cols-1 lg:hidden gap-4 mb-8">
        <?php foreach ($visitors as $visitor): ?>
            <?php
                $isCompleted = ($visitor['follow_up_status'] ?? '') === 'Completed';
                $badgeClass = $isCompleted
                    ? 'bg-green-500/15 text-green-200 border-green-400/20'
                    : 'bg-amber-500/15 text-amber-100 border-amber-400/20';
                $isApproved = !empty($visitor['approved_at']);
                $isAssignedToMe = (int)($visitor['assigned_to'] ?? 0) === $meId;
            ?>
            <div class="glass-card rounded-[2rem] p-5 border-white/10">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <h3 class="text-lg font-black text-white"><?php echo $visitor['first_name'] . ' ' . $visitor['last_name']; ?></h3>
                        <p class="text-xs font-bold text-slate-400 mt-2"><?php echo !empty($visitor['service_attended']) ? $visitor['service_attended'] : 'Service not specified'; ?></p>
                    </div>
                    <span class="inline-flex items-center rounded-full border px-3 py-1 text-[10px] font-black uppercase tracking-[0.2em] <?php echo $badgeClass; ?>">
                        <?php echo $visitor['follow_up_status']; ?>
                    </span>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 mt-5 text-sm">
                    <div class="rounded-2xl bg-white/5 border border-white/5 px-4 py-3">
                        <p class="text-[10px] font-black uppercase tracking-[0.24em] text-slate-500">Visit Date</p>
                        <p class="text-white font-bold mt-2"><?php echo !empty($visitor['visit_date']) ? date('M d, Y', strtotime($visitor['visit_date'])) : 'Not set'; ?></p>
                    </div>
                    <div class="rounded-2xl bg-white/5 border border-white/5 px-4 py-3">
                        <p class="text-[10px] font-black uppercase tracking-[0.24em] text-slate-500">First-Time</p>
                        <p class="text-white font-bold mt-2"><?php echo !empty($visitor['is_first_time']) ? 'Yes' : 'Returning'; ?></p>
                    </div>
                    <div class="rounded-2xl bg-white/5 border border-white/5 px-4 py-3">
                        <p class="text-[10px] font-black uppercase tracking-[0.24em] text-slate-500">Contact</p>
                        <p class="text-white font-bold mt-2">
                            <?php if ($isVisitationTeam && !$isApproved): ?>
                                Hidden until approved
                            <?php else: ?>
                                <?php echo !empty($visitor['phone']) ? $visitor['phone'] : (!empty($visitor['email']) ? $visitor['email'] : 'Not provided'); ?>
                            <?php endif; ?>
                        </p>
                    </div>
                    <div class="rounded-2xl bg-white/5 border border-white/5 px-4 py-3">
                        <p class="text-[10px] font-black uppercase tracking-[0.24em] text-slate-500">Assigned To</p>
                        <div class="mt-2 flex flex-col gap-2">
                            <p class="text-white font-bold"><?php echo !empty($visitor['assigned_to_name']) ? $visitor['assigned_to_name'] : 'Unassigned'; ?></p>
                            <?php if ($isVisitationTeam): ?>
                                <?php if ($isAssignedToMe && !$isApproved): ?>
                                    <form action="<?php echo BASE_URL; ?>/visitors/approve" method="POST" data-loader="top">
                                        <input type="hidden" name="visitor_id" value="<?php echo (int)$visitor['id']; ?>">
                                        <button type="submit" class="w-full h-10 inline-flex items-center justify-center rounded-xl bg-accent text-slate-900 font-black text-[10px] uppercase tracking-widest">
                                            Approve
                                        </button>
                                    </form>
                                <?php elseif ($isApproved): ?>
                                    <a href="<?php echo BASE_URL; ?>/visitors/details?id=<?php echo (int)$visitor['id']; ?>" class="inline-flex h-10 items-center justify-center rounded-xl bg-white/10 text-slate-200 font-black text-[10px] uppercase tracking-widest border border-white/10">
                                        View Details
                                    </a>
                                <?php endif; ?>
                            <?php else: ?>
                                <div class="flex flex-col sm:flex-row gap-2">
                                    <a href="<?php echo BASE_URL; ?>/visitors/details?id=<?php echo (int)$visitor['id']; ?>" class="inline-flex h-10 items-center justify-center rounded-xl bg-white/10 text-slate-200 font-black text-[10px] uppercase tracking-widest border border-white/10 px-4">
                                        View Details
                                    </a>
                                    <?php if ($canAssign): ?>
                                        <button type="button"
                                            data-assign-visitor="1"
                                            data-visitor-id="<?php echo (int)$visitor['id']; ?>"
                                            data-visitor-name="<?php echo htmlspecialchars(trim(($visitor['first_name'] ?? '') . ' ' . ($visitor['last_name'] ?? ''))); ?>"
                                            data-assigned-to="<?php echo (int)($visitor['assigned_to'] ?? 0); ?>"
                                            data-is-approved="<?php echo $isApproved ? '1' : '0'; ?>"
                                            class="inline-flex h-10 items-center justify-center rounded-xl bg-accent text-slate-900 font-black text-[10px] uppercase tracking-widest px-4">
                                            <?php echo $isApproved ? 'Reassign' : 'Assign'; ?>
                                        </button>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <div class="mt-4 space-y-2 text-xs font-bold text-slate-300">
                    <p><span class="text-slate-500 uppercase tracking-[0.2em] mr-2">Invited</span><?php echo !empty($visitor['invited_by']) ? $visitor['invited_by'] : 'Walk-in'; ?></p>
                    <p><span class="text-slate-500 uppercase tracking-[0.2em] mr-2">Follow-Up</span><?php echo !empty($visitor['follow_up_date']) ? date('M d, Y', strtotime($visitor['follow_up_date'])) : 'Not scheduled'; ?></p>
                    <p><span class="text-slate-500 uppercase tracking-[0.2em] mr-2">Method</span><?php echo !empty($visitor['preferred_contact_method']) ? $visitor['preferred_contact_method'] : 'Not set'; ?></p>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <div class="hidden lg:block glass-card rounded-[2.5rem] border-white/10 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left min-w-[1100px]">
                <thead class="bg-white/5 border-b border-white/5">
                    <tr>
                        <th class="px-6 py-5 text-[10px] font-black text-slate-500 uppercase tracking-[0.24em]">Visitor</th>
                        <th class="px-6 py-5 text-[10px] font-black text-slate-500 uppercase tracking-[0.24em]">Visit</th>
                        <th class="px-6 py-5 text-[10px] font-black text-slate-500 uppercase tracking-[0.24em]">Contact</th>
                        <th class="px-6 py-5 text-[10px] font-black text-slate-500 uppercase tracking-[0.24em]">Status</th>
                        <th class="px-6 py-5 text-[10px] font-black text-slate-500 uppercase tracking-[0.24em]">Invited By</th>
                        <th class="px-6 py-5 text-[10px] font-black text-slate-500 uppercase tracking-[0.24em]">Assigned To</th>
                        <th class="px-6 py-5 text-[10px] font-black text-slate-500 uppercase tracking-[0.24em]">Next Follow-Up</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-white/5">
                    <?php foreach ($visitors as $visitor): ?>
                        <?php
                            $isCompleted = ($visitor['follow_up_status'] ?? '') === 'Completed';
                            $badgeClass = $isCompleted
                                ? 'bg-green-500/15 text-green-200 border-green-400/20'
                                : 'bg-amber-500/15 text-amber-100 border-amber-400/20';
                            $isApproved = !empty($visitor['approved_at']);
                            $isAssignedToMe = (int)($visitor['assigned_to'] ?? 0) === $meId;
                        ?>
                        <tr class="hover:bg-white/[0.03] transition-colors">
                            <td class="px-6 py-5">
                                <div class="flex flex-col">
                                    <span class="text-sm font-black text-white"><?php echo $visitor['first_name'] . ' ' . $visitor['last_name']; ?></span>
                                    <span class="text-xs font-bold text-slate-400 mt-2"><?php echo !empty($visitor['service_attended']) ? $visitor['service_attended'] : 'Service not specified'; ?></span>
                                </div>
                            </td>
                            <td class="px-6 py-5 text-sm font-bold text-slate-300">
                                <div><?php echo !empty($visitor['visit_date']) ? date('M d, Y', strtotime($visitor['visit_date'])) : 'Not set'; ?></div>
                                <div class="text-xs text-slate-500 mt-2"><?php echo !empty($visitor['is_first_time']) ? 'First-time visitor' : 'Returning visitor'; ?></div>
                            </td>
                            <td class="px-6 py-5 text-sm font-bold text-slate-300">
                                <?php if ($isVisitationTeam && !$isApproved): ?>
                                    <div class="text-slate-400">Hidden until approved</div>
                                    <div class="text-xs text-slate-600 mt-2">Approve to view contact</div>
                                <?php else: ?>
                                    <div><?php echo !empty($visitor['phone']) ? $visitor['phone'] : 'No phone'; ?></div>
                                    <div class="text-xs text-slate-500 mt-2"><?php echo !empty($visitor['preferred_contact_method']) ? $visitor['preferred_contact_method'] : (!empty($visitor['email']) ? $visitor['email'] : 'No preferred method'); ?></div>
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-5">
                                <span class="inline-flex items-center rounded-full border px-3 py-1 text-[10px] font-black uppercase tracking-[0.2em] <?php echo $badgeClass; ?>">
                                    <?php echo $visitor['follow_up_status']; ?>
                                </span>
                            </td>
                            <td class="px-6 py-5 text-sm font-bold text-slate-300">
                                <?php echo !empty($visitor['invited_by']) ? $visitor['invited_by'] : 'Walk-in'; ?>
                            </td>
                            <td class="px-6 py-5 text-sm font-bold text-slate-300">
                                <div class="flex items-center justify-between gap-3">
                                    <span><?php echo !empty($visitor['assigned_to_name']) ? $visitor['assigned_to_name'] : 'Unassigned'; ?></span>
                                    <?php if ($isVisitationTeam): ?>
                                        <?php if ($isAssignedToMe && !$isApproved): ?>
                                            <form action="<?php echo BASE_URL; ?>/visitors/approve" method="POST" data-loader="top">
                                                <input type="hidden" name="visitor_id" value="<?php echo (int)$visitor['id']; ?>">
                                                <button type="submit" class="h-9 px-4 rounded-xl bg-accent text-slate-900 font-black text-[10px] uppercase tracking-widest">
                                                    Approve
                                                </button>
                                            </form>
                                        <?php elseif ($isApproved): ?>
                                            <a href="<?php echo BASE_URL; ?>/visitors/details?id=<?php echo (int)$visitor['id']; ?>" class="h-9 px-4 inline-flex items-center justify-center rounded-xl bg-white/10 text-slate-200 font-black text-[10px] uppercase tracking-widest border border-white/10">
                                                Details
                                            </a>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <div class="inline-flex items-center gap-2 justify-end">
                                            <a href="<?php echo BASE_URL; ?>/visitors/details?id=<?php echo (int)$visitor['id']; ?>" class="h-9 px-4 inline-flex items-center justify-center rounded-xl bg-white/10 text-slate-200 font-black text-[10px] uppercase tracking-widest border border-white/10">
                                                Details
                                            </a>
                                            <?php if ($canAssign): ?>
                                                <button type="button"
                                                    data-assign-visitor="1"
                                                    data-visitor-id="<?php echo (int)$visitor['id']; ?>"
                                                    data-visitor-name="<?php echo htmlspecialchars(trim(($visitor['first_name'] ?? '') . ' ' . ($visitor['last_name'] ?? ''))); ?>"
                                                    data-assigned-to="<?php echo (int)($visitor['assigned_to'] ?? 0); ?>"
                                                    data-is-approved="<?php echo $isApproved ? '1' : '0'; ?>"
                                                    class="h-9 px-4 rounded-xl bg-accent text-slate-900 font-black text-[10px] uppercase tracking-widest">
                                                    <?php echo $isApproved ? 'Reassign' : 'Assign'; ?>
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td class="px-6 py-5 text-sm font-bold text-slate-300">
                                <?php echo !empty($visitor['follow_up_date']) ? date('M d, Y', strtotime($visitor['follow_up_date'])) : 'Not scheduled'; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php endif; ?>

<?php if ($canAssign): ?>
<div id="assign-visitor-modal" class="hidden fixed inset-0 bg-slate-950/80 backdrop-blur-xl z-50 flex items-center justify-center p-4">
    <div class="glass-card w-full max-w-md rounded-[3rem] p-6 sm:p-12 shadow-2xl border-white/10 transform transition-all scale-100 max-h-[90vh] overflow-y-auto custom-scrollbar">
        <div class="flex justify-between items-center mb-10">
            <div>
                <h3 id="assign-visitor-title" class="text-3xl font-black text-white tracking-tighter">Assign Visitor</h3>
                <p id="assign-visitor-target" class="text-[10px] font-black uppercase tracking-widest text-slate-500 mt-2"></p>
            </div>
            <button type="button" onclick="document.getElementById('assign-visitor-modal').classList.add('hidden')" class="w-10 h-10 bg-white/5 hover:bg-accent hover:text-slate-900 text-slate-400 rounded-xl flex items-center justify-center transition-all border border-white/10">
                <i class="fas fa-times text-sm"></i>
            </button>
        </div>
        <form action="<?php echo BASE_URL; ?>/visitors/assign" method="POST" data-loader="top" class="space-y-8">
            <input type="hidden" name="visitor_id" id="assign-visitor-id" value="">
            <div class="space-y-3">
                <label class="block text-[9px] font-black text-slate-500 uppercase tracking-widest ml-1">Visitation Member</label>
                <div class="relative">
                    <i class="fas fa-user-check absolute left-5 top-1/2 -translate-y-1/2 text-slate-600"></i>
                    <select id="assign-visitor-select" name="assigned_to" required class="w-full bg-white/5 border border-white/10 focus:border-accent rounded-2xl pl-14 pr-10 py-4 text-sm font-bold text-white transition-all outline-none appearance-none cursor-pointer">
                        <option value="">Select visitation assignee</option>
                        <?php foreach (($assignees ?? []) as $u): ?>
                            <option value="<?php echo (int)$u['id']; ?>">
                                <?php echo htmlspecialchars((string)($u['display_name'] ?? '')); ?><?php echo !empty($u['department_name']) ? ' - ' . htmlspecialchars((string)$u['department_name']) : ''; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <i class="fas fa-chevron-down absolute right-5 top-1/2 -translate-y-1/2 text-slate-600 text-[10px]"></i>
                </div>
                <p class="text-[10px] font-black text-slate-500 uppercase tracking-widest">This will reset approval for this visitor</p>
            </div>
            <button type="submit" class="w-full bg-accent text-slate-900 py-5 rounded-2xl font-black text-xs uppercase tracking-[0.2em] hover:scale-[1.02] active:scale-95 transition-all shadow-xl shadow-yellow-500/10">
                Save Assignment
            </button>
        </form>
    </div>
</div>

<script>
    (function () {
        const modal = document.getElementById('assign-visitor-modal');
        const idInput = document.getElementById('assign-visitor-id');
        const select = document.getElementById('assign-visitor-select');
        const label = document.getElementById('assign-visitor-target');
        const title = document.getElementById('assign-visitor-title');
        if (!modal || !idInput || !select || !label) return;

        const open = (visitorId, visitorName, assignedTo, isApproved) => {
            idInput.value = String(visitorId || '');
            label.textContent = String(visitorName || '').trim();
            const v = String(assignedTo || '');
            if (v !== '') select.value = v;
            const approved = String(isApproved || '') === '1';
            if (title) title.textContent = approved ? 'Reassign Visitor' : 'Assign Visitor';
            modal.classList.remove('hidden');
        };

        document.addEventListener('click', (e) => {
            const btn = e.target.closest('[data-assign-visitor="1"]');
            if (!btn) return;
            open(btn.dataset.visitorId, btn.dataset.visitorName, btn.dataset.assignedTo, btn.dataset.isApproved);
        });

        modal.addEventListener('click', (e) => {
            if (e.target === modal) modal.classList.add('hidden');
        });
    })();
</script>
<?php endif; ?>
