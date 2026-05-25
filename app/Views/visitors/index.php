<div class="mb-8 flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
    <div>
        <p class="text-[10px] font-black uppercase tracking-[0.35em] text-accent">Assimilation Desk</p>
        <h2 class="text-3xl sm:text-4xl font-black text-white tracking-tight mt-3">Visitor Log</h2>
        <p class="text-slate-400 text-sm font-bold mt-2">Responsive visitor tracking with richer follow-up information for church care teams.</p>
    </div>
    <a href="<?php echo BASE_URL; ?>/visitors/add" class="inline-flex items-center justify-center gap-3 bg-accent text-slate-900 px-6 py-4 rounded-2xl font-black text-xs uppercase tracking-[0.24em] hover-glow-yellow transition-all shadow-xl shadow-yellow-500/10">
        <i class="fas fa-user-plus text-sm"></i> Register Visitor
    </a>
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
                        <p class="text-white font-bold mt-2"><?php echo !empty($visitor['phone']) ? $visitor['phone'] : (!empty($visitor['email']) ? $visitor['email'] : 'Not provided'); ?></p>
                    </div>
                    <div class="rounded-2xl bg-white/5 border border-white/5 px-4 py-3">
                        <p class="text-[10px] font-black uppercase tracking-[0.24em] text-slate-500">Assigned To</p>
                        <p class="text-white font-bold mt-2"><?php echo !empty($visitor['assigned_to_name']) ? $visitor['assigned_to_name'] : 'Unassigned'; ?></p>
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
                                <div><?php echo !empty($visitor['phone']) ? $visitor['phone'] : 'No phone'; ?></div>
                                <div class="text-xs text-slate-500 mt-2"><?php echo !empty($visitor['preferred_contact_method']) ? $visitor['preferred_contact_method'] : (!empty($visitor['email']) ? $visitor['email'] : 'No preferred method'); ?></div>
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
                                <?php echo !empty($visitor['assigned_to_name']) ? $visitor['assigned_to_name'] : 'Unassigned'; ?>
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
