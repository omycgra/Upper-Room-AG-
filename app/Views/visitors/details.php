<div class="mb-8 flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
    <div>
        <p class="text-[10px] font-black uppercase tracking-[0.35em] text-accent">Visitor Details</p>
        <h2 class="text-3xl sm:text-4xl font-black text-white tracking-tight mt-3"><?php echo htmlspecialchars((string)($visitor['first_name'] ?? '')); ?> <?php echo htmlspecialchars((string)($visitor['last_name'] ?? '')); ?></h2>
        <p class="text-slate-400 text-sm font-bold mt-2">Full visitor profile and follow-up information.</p>
    </div>
    <a href="<?php echo BASE_URL; ?>/visitors" class="inline-flex items-center justify-center gap-3 bg-white/10 text-slate-200 px-6 py-4 rounded-2xl font-black text-xs uppercase tracking-[0.24em] border border-white/10 hover:bg-white/15 transition-all">
        <i class="fas fa-arrow-left text-sm"></i> Back
    </a>
</div>

<div class="grid grid-cols-1 xl:grid-cols-3 gap-6 lg:gap-8">
    <div class="xl:col-span-2 space-y-6">
        <div class="glass-card rounded-[2.5rem] border-white/10 overflow-hidden">
            <div class="px-6 sm:px-8 py-6 border-b border-white/10 bg-white/[0.03]">
                <p class="text-[10px] font-black uppercase tracking-[0.32em] text-slate-500">Visit Info</p>
            </div>
            <div class="p-6 sm:p-8 grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div class="rounded-2xl bg-white/5 border border-white/10 p-5">
                    <p class="text-[10px] font-black uppercase tracking-[0.24em] text-slate-500">Visit Date</p>
                    <p class="text-white font-black mt-3"><?php echo !empty($visitor['visit_date']) ? date('M d, Y', strtotime((string)$visitor['visit_date'])) : 'Not set'; ?></p>
                </div>
                <div class="rounded-2xl bg-white/5 border border-white/10 p-5">
                    <p class="text-[10px] font-black uppercase tracking-[0.24em] text-slate-500">Service Attended</p>
                    <p class="text-white font-black mt-3"><?php echo !empty($visitor['service_attended']) ? htmlspecialchars((string)$visitor['service_attended']) : 'Not specified'; ?></p>
                </div>
                <div class="rounded-2xl bg-white/5 border border-white/10 p-5">
                    <p class="text-[10px] font-black uppercase tracking-[0.24em] text-slate-500">First-Time</p>
                    <p class="text-white font-black mt-3"><?php echo !empty($visitor['is_first_time']) ? 'Yes' : 'No'; ?></p>
                </div>
                <div class="rounded-2xl bg-white/5 border border-white/10 p-5">
                    <p class="text-[10px] font-black uppercase tracking-[0.24em] text-slate-500">Invited By</p>
                    <p class="text-white font-black mt-3"><?php echo !empty($visitor['invited_by']) ? htmlspecialchars((string)$visitor['invited_by']) : 'Walk-in'; ?></p>
                </div>
            </div>
        </div>

        <div class="glass-card rounded-[2.5rem] border-white/10 overflow-hidden">
            <div class="px-6 sm:px-8 py-6 border-b border-white/10 bg-white/[0.03]">
                <p class="text-[10px] font-black uppercase tracking-[0.32em] text-slate-500">Contact</p>
            </div>
            <div class="p-6 sm:p-8 grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div class="rounded-2xl bg-white/5 border border-white/10 p-5">
                    <p class="text-[10px] font-black uppercase tracking-[0.24em] text-slate-500">Phone</p>
                    <p class="text-white font-black mt-3"><?php echo !empty($visitor['phone']) ? htmlspecialchars((string)$visitor['phone']) : 'Not provided'; ?></p>
                </div>
                <div class="rounded-2xl bg-white/5 border border-white/10 p-5">
                    <p class="text-[10px] font-black uppercase tracking-[0.24em] text-slate-500">Email</p>
                    <p class="text-white font-black mt-3"><?php echo !empty($visitor['email']) ? htmlspecialchars((string)$visitor['email']) : 'Not provided'; ?></p>
                </div>
                <div class="rounded-2xl bg-white/5 border border-white/10 p-5">
                    <p class="text-[10px] font-black uppercase tracking-[0.24em] text-slate-500">Preferred Contact</p>
                    <p class="text-white font-black mt-3"><?php echo !empty($visitor['preferred_contact_method']) ? htmlspecialchars((string)$visitor['preferred_contact_method']) : 'Not set'; ?></p>
                </div>
                <div class="rounded-2xl bg-white/5 border border-white/10 p-5">
                    <p class="text-[10px] font-black uppercase tracking-[0.24em] text-slate-500">Gender</p>
                    <p class="text-white font-black mt-3"><?php echo !empty($visitor['gender']) ? htmlspecialchars((string)$visitor['gender']) : 'Not set'; ?></p>
                </div>
                <div class="sm:col-span-2 rounded-2xl bg-white/5 border border-white/10 p-5">
                    <p class="text-[10px] font-black uppercase tracking-[0.24em] text-slate-500">Address</p>
                    <p class="text-white font-bold mt-3 whitespace-pre-wrap"><?php echo !empty($visitor['address']) ? htmlspecialchars((string)$visitor['address']) : 'Not provided'; ?></p>
                </div>
            </div>
        </div>
    </div>

    <div class="space-y-6">
        <div class="glass-card rounded-[2.5rem] border-white/10 overflow-hidden">
            <div class="px-6 sm:px-8 py-6 border-b border-white/10 bg-white/[0.03]">
                <p class="text-[10px] font-black uppercase tracking-[0.32em] text-slate-500">Follow-Up</p>
            </div>
            <div class="p-6 sm:p-8 space-y-4">
                <div class="rounded-2xl bg-white/5 border border-white/10 p-5">
                    <p class="text-[10px] font-black uppercase tracking-[0.24em] text-slate-500">Status</p>
                    <p class="text-white font-black mt-3"><?php echo htmlspecialchars((string)($visitor['follow_up_status'] ?? '')); ?></p>
                </div>
                <div class="rounded-2xl bg-white/5 border border-white/10 p-5">
                    <p class="text-[10px] font-black uppercase tracking-[0.24em] text-slate-500">Next Follow-Up</p>
                    <p class="text-white font-black mt-3"><?php echo !empty($visitor['follow_up_date']) ? date('M d, Y', strtotime((string)$visitor['follow_up_date'])) : 'Not scheduled'; ?></p>
                </div>
                <div class="rounded-2xl bg-white/5 border border-white/10 p-5">
                    <p class="text-[10px] font-black uppercase tracking-[0.24em] text-slate-500">Assigned To</p>
                    <p class="text-white font-black mt-3"><?php echo !empty($visitor['assigned_to_name']) ? htmlspecialchars((string)$visitor['assigned_to_name']) : 'Unassigned'; ?></p>
                    <p class="text-[10px] font-black uppercase tracking-widest text-slate-500 mt-2"><?php echo !empty($visitor['assigned_department_name']) ? htmlspecialchars((string)$visitor['assigned_department_name']) : ''; ?></p>
                </div>
                <div class="rounded-2xl bg-white/5 border border-white/10 p-5">
                    <p class="text-[10px] font-black uppercase tracking-[0.24em] text-slate-500">Approval</p>
                    <p class="text-white font-black mt-3"><?php echo !empty($visitor['approved_at']) ? date('M d, Y H:i', strtotime((string)$visitor['approved_at'])) : 'Not approved'; ?></p>
                    <p class="text-[10px] font-black uppercase tracking-widest text-slate-500 mt-2"><?php echo !empty($visitor['approved_by_name']) ? htmlspecialchars((string)$visitor['approved_by_name']) : ''; ?></p>
                </div>
            </div>
        </div>

        <div class="glass-card rounded-[2.5rem] border-white/10 overflow-hidden">
            <div class="px-6 sm:px-8 py-6 border-b border-white/10 bg-white/[0.03]">
                <p class="text-[10px] font-black uppercase tracking-[0.32em] text-slate-500">Notes</p>
            </div>
            <div class="p-6 sm:p-8 space-y-4">
                <div class="rounded-2xl bg-white/5 border border-white/10 p-5">
                    <p class="text-[10px] font-black uppercase tracking-[0.24em] text-slate-500">Follow-Up Notes</p>
                    <p class="text-white font-bold mt-3 whitespace-pre-wrap"><?php echo !empty($visitor['follow_up_notes']) ? htmlspecialchars((string)$visitor['follow_up_notes']) : 'None'; ?></p>
                </div>
                <div class="rounded-2xl bg-white/5 border border-white/10 p-5">
                    <p class="text-[10px] font-black uppercase tracking-[0.24em] text-slate-500">Prayer Request</p>
                    <p class="text-white font-bold mt-3 whitespace-pre-wrap"><?php echo !empty($visitor['prayer_request']) ? htmlspecialchars((string)$visitor['prayer_request']) : 'None'; ?></p>
                </div>
            </div>
        </div>
    </div>
</div>
