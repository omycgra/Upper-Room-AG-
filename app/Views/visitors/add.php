<div class="mb-8 flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
    <div>
        <a href="<?php echo BASE_URL; ?>/visitors" class="inline-flex items-center text-accent hover:text-white font-black text-xs uppercase tracking-[0.28em] mb-4 transition-colors">
            <i class="fas fa-arrow-left mr-3 text-[10px]"></i> Back To Visitor Log
        </a>
        <h2 class="text-3xl sm:text-4xl font-black text-white tracking-tight">Register New Visitor</h2>
        <p class="text-slate-400 text-sm font-bold mt-2">Capture the right guest information for pastoral care, follow-up, and assimilation.</p>
    </div>
    <div class="glass-card rounded-[2rem] border-white/10 px-5 py-4 max-w-xl">
        <p class="text-[10px] font-black uppercase tracking-[0.35em] text-accent">Visitor Workflow</p>
        <p class="text-xs text-slate-300 font-bold mt-2">Store service details, follow-up owner, contact method, and prayer notes in one place.</p>
    </div>
</div>

<div class="glass-card rounded-[2.5rem] overflow-hidden border-white/10">
    <div class="px-6 py-6 sm:px-8 sm:py-8 lg:px-10 bg-slate-950/80 border-b border-white/5 flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
        <div>
            <p class="text-[10px] font-black uppercase tracking-[0.35em] text-accent">First Impressions</p>
            <h3 class="text-2xl sm:text-3xl font-black text-white tracking-tight mt-3">Visitor Intake And Follow-Up</h3>
        </div>
        <div class="inline-flex items-center gap-3 rounded-2xl bg-accent/10 border border-accent/20 px-4 py-3">
            <span class="w-10 h-10 rounded-2xl bg-accent text-slate-900 flex items-center justify-center shadow-lg shadow-yellow-500/20">
                <i class="fas fa-user-friends text-sm"></i>
            </span>
            <div>
                <p class="text-[10px] font-black uppercase tracking-[0.24em] text-accent">Best Practice</p>
                <p class="text-xs text-slate-300 font-bold mt-1">Assign an owner and next follow-up date before saving.</p>
            </div>
        </div>
    </div>

    <form action="<?php echo BASE_URL; ?>/visitors/store" method="POST" class="p-6 sm:p-8 lg:p-10">
        <div class="space-y-6">
            <div class="glass-card rounded-[2rem] p-6 sm:p-8 border-white/10">
                <div class="flex items-center gap-4 mb-6">
                    <span class="text-[10px] font-black uppercase tracking-[0.45em] text-accent">Visitor Identity</span>
                    <div class="h-px flex-1 bg-white/5"></div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                    <div class="space-y-3">
                        <label class="block text-[10px] font-black text-slate-500 uppercase tracking-[0.28em]">First Name</label>
                        <div class="relative group">
                            <i class="fas fa-user absolute left-5 top-1/2 -translate-y-1/2 text-slate-600 group-focus-within:text-accent transition-colors"></i>
                            <input type="text" name="first_name" required class="w-full bg-white/5 border border-white/10 rounded-2xl pl-14 pr-5 py-4 text-sm font-bold text-white placeholder:text-slate-600 focus:border-accent outline-none transition-all" placeholder="e.g. Grace">
                        </div>
                    </div>

                    <div class="space-y-3">
                        <label class="block text-[10px] font-black text-slate-500 uppercase tracking-[0.28em]">Last Name</label>
                        <div class="relative group">
                            <i class="fas fa-user absolute left-5 top-1/2 -translate-y-1/2 text-slate-600 group-focus-within:text-accent transition-colors"></i>
                            <input type="text" name="last_name" required class="w-full bg-white/5 border border-white/10 rounded-2xl pl-14 pr-5 py-4 text-sm font-bold text-white placeholder:text-slate-600 focus:border-accent outline-none transition-all" placeholder="e.g. Mensah">
                        </div>
                    </div>

                    <div class="space-y-3">
                        <label class="block text-[10px] font-black text-slate-500 uppercase tracking-[0.28em]">Phone Number</label>
                        <div class="relative group">
                            <i class="fas fa-phone absolute left-5 top-1/2 -translate-y-1/2 text-slate-600 group-focus-within:text-accent transition-colors"></i>
                            <input type="text" name="phone" class="w-full bg-white/5 border border-white/10 rounded-2xl pl-14 pr-5 py-4 text-sm font-bold text-white placeholder:text-slate-600 focus:border-accent outline-none transition-all" placeholder="Main contact number">
                        </div>
                    </div>

                    <div class="space-y-3">
                        <label class="block text-[10px] font-black text-slate-500 uppercase tracking-[0.28em]">Email Address</label>
                        <div class="relative group">
                            <i class="fas fa-envelope absolute left-5 top-1/2 -translate-y-1/2 text-slate-600 group-focus-within:text-accent transition-colors"></i>
                            <input type="email" name="email" class="w-full bg-white/5 border border-white/10 rounded-2xl pl-14 pr-5 py-4 text-sm font-bold text-white placeholder:text-slate-600 focus:border-accent outline-none transition-all" placeholder="visitor@example.com">
                        </div>
                    </div>

                    <div class="space-y-3">
                        <label class="block text-[10px] font-black text-slate-500 uppercase tracking-[0.28em]">Gender</label>
                        <div class="relative group">
                            <i class="fas fa-venus-mars absolute left-5 top-1/2 -translate-y-1/2 text-slate-600"></i>
                            <select name="gender" class="w-full bg-white/5 border border-white/10 rounded-2xl pl-14 pr-10 py-4 text-sm font-bold text-white appearance-none outline-none focus:border-accent transition-all">
                                <option value="">Select gender</option>
                                <option value="male">Male</option>
                                <option value="female">Female</option>
                            </select>
                            <i class="fas fa-chevron-down absolute right-5 top-1/2 -translate-y-1/2 text-slate-600 text-[10px] pointer-events-none"></i>
                        </div>
                    </div>

                    <div class="space-y-3">
                        <label class="block text-[10px] font-black text-slate-500 uppercase tracking-[0.28em]">Preferred Contact</label>
                        <div class="relative group">
                            <i class="fas fa-comments absolute left-5 top-1/2 -translate-y-1/2 text-slate-600"></i>
                            <select name="preferred_contact_method" class="w-full bg-white/5 border border-white/10 rounded-2xl pl-14 pr-10 py-4 text-sm font-bold text-white appearance-none outline-none focus:border-accent transition-all">
                                <option value="">Choose method</option>
                                <option value="Call">Call</option>
                                <option value="SMS">SMS</option>
                                <option value="WhatsApp">WhatsApp</option>
                                <option value="Email">Email</option>
                            </select>
                            <i class="fas fa-chevron-down absolute right-5 top-1/2 -translate-y-1/2 text-slate-600 text-[10px] pointer-events-none"></i>
                        </div>
                    </div>

                    <div class="md:col-span-2 space-y-3">
                        <label class="block text-[10px] font-black text-slate-500 uppercase tracking-[0.28em]">Address / Location</label>
                        <div class="relative group">
                            <i class="fas fa-map-location-dot absolute left-5 top-6 text-slate-600 group-focus-within:text-accent transition-colors"></i>
                            <textarea name="address" rows="3" class="w-full bg-white/5 border border-white/10 rounded-[1.75rem] pl-14 pr-5 py-5 text-sm font-bold text-white placeholder:text-slate-600 outline-none resize-none focus:border-accent transition-all" placeholder="Community, landmark, or home address"></textarea>
                        </div>
                    </div>
                </div>
            </div>

            <div class="glass-card rounded-[2rem] p-6 sm:p-8 border-white/10">
                <div class="flex items-center gap-4 mb-6">
                    <span class="text-[10px] font-black uppercase tracking-[0.45em] text-accent">Visit Details</span>
                    <div class="h-px flex-1 bg-white/5"></div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                    <div class="space-y-3">
                        <label class="block text-[10px] font-black text-slate-500 uppercase tracking-[0.28em]">Visit Date</label>
                        <div class="relative group">
                            <i class="fas fa-calendar-day absolute left-5 top-1/2 -translate-y-1/2 text-accent"></i>
                            <input type="date" name="visit_date" required value="<?php echo date('Y-m-d'); ?>" class="w-full bg-accent/5 border border-accent/20 rounded-2xl pl-14 pr-5 py-4 text-sm font-bold text-white outline-none focus:border-accent transition-all [color-scheme:dark]">
                        </div>
                    </div>

                    <div class="space-y-3">
                        <label class="block text-[10px] font-black text-slate-500 uppercase tracking-[0.28em]">Service Attended</label>
                        <div class="relative group">
                            <i class="fas fa-church absolute left-5 top-1/2 -translate-y-1/2 text-slate-600"></i>
                            <select name="service_attended" class="w-full bg-white/5 border border-white/10 rounded-2xl pl-14 pr-10 py-4 text-sm font-bold text-white appearance-none outline-none focus:border-accent transition-all">
                                <option value="">Select service</option>
                                <option value="Sunday Service">Sunday Service</option>
                                <option value="Midweek Service">Midweek Service</option>
                                <option value="Prayer Meeting">Prayer Meeting</option>
                                <option value="All Night">All Night</option>
                                <option value="Special Program">Special Program</option>
                            </select>
                            <i class="fas fa-chevron-down absolute right-5 top-1/2 -translate-y-1/2 text-slate-600 text-[10px] pointer-events-none"></i>
                        </div>
                    </div>

                    <div class="space-y-3">
                        <label class="block text-[10px] font-black text-slate-500 uppercase tracking-[0.28em]">Visitor Type</label>
                        <div class="relative group">
                            <i class="fas fa-sparkles absolute left-5 top-1/2 -translate-y-1/2 text-slate-600"></i>
                            <select name="is_first_time" class="w-full bg-white/5 border border-white/10 rounded-2xl pl-14 pr-10 py-4 text-sm font-bold text-white appearance-none outline-none focus:border-accent transition-all">
                                <option value="1">First-time visitor</option>
                                <option value="0">Returning visitor</option>
                            </select>
                            <i class="fas fa-chevron-down absolute right-5 top-1/2 -translate-y-1/2 text-slate-600 text-[10px] pointer-events-none"></i>
                        </div>
                    </div>

                    <div class="space-y-3">
                        <label class="block text-[10px] font-black text-slate-500 uppercase tracking-[0.28em]">Invited By</label>
                        <div class="relative group">
                            <i class="fas fa-user-group absolute left-5 top-1/2 -translate-y-1/2 text-slate-600 group-focus-within:text-accent transition-colors"></i>
                            <input type="text" name="invited_by" class="w-full bg-white/5 border border-white/10 rounded-2xl pl-14 pr-5 py-4 text-sm font-bold text-white placeholder:text-slate-600 focus:border-accent outline-none transition-all" placeholder="Member, friend, online, or walk-in">
                        </div>
                    </div>

                    <div class="md:col-span-2 space-y-3">
                        <label class="block text-[10px] font-black text-slate-500 uppercase tracking-[0.28em]">Prayer Request / Visitor Notes</label>
                        <div class="relative group">
                            <i class="fas fa-hands-praying absolute left-5 top-6 text-slate-600 group-focus-within:text-accent transition-colors"></i>
                            <textarea name="prayer_request" rows="4" class="w-full bg-white/5 border border-white/10 rounded-[1.75rem] pl-14 pr-5 py-5 text-sm font-bold text-white placeholder:text-slate-600 outline-none resize-none focus:border-accent transition-all" placeholder="Prayer needs, counseling request, special remarks, or family details"></textarea>
                        </div>
                    </div>
                </div>
            </div>

            <div class="glass-card rounded-[2rem] p-6 sm:p-8 border-white/10">
                <div class="flex items-center gap-4 mb-6">
                    <span class="text-[10px] font-black uppercase tracking-[0.45em] text-accent">Follow-Up Plan</span>
                    <div class="h-px flex-1 bg-white/5"></div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                    <div class="space-y-3">
                        <label class="block text-[10px] font-black text-slate-500 uppercase tracking-[0.28em]">Follow-Up Date</label>
                        <div class="relative group">
                            <i class="fas fa-calendar-check absolute left-5 top-1/2 -translate-y-1/2 text-accent"></i>
                            <input type="date" name="follow_up_date" value="<?php echo date('Y-m-d', strtotime('+2 days')); ?>" class="w-full bg-accent/5 border border-accent/20 rounded-2xl pl-14 pr-5 py-4 text-sm font-bold text-white outline-none focus:border-accent transition-all [color-scheme:dark]">
                        </div>
                    </div>

                    <div class="space-y-3">
                        <label class="block text-[10px] font-black text-slate-500 uppercase tracking-[0.28em]">Assign Follow-Up To</label>
                        <div class="relative group">
                            <i class="fas fa-user-check absolute left-5 top-1/2 -translate-y-1/2 text-slate-600"></i>
                            <select name="assigned_to" class="w-full bg-white/5 border border-white/10 rounded-2xl pl-14 pr-10 py-4 text-sm font-bold text-white appearance-none outline-none focus:border-accent transition-all" <?php echo empty($followupUsers) ? 'disabled' : 'required'; ?>>
                                <?php if (empty($followupUsers)): ?>
                                    <option value="">Create a Visitation Member user first</option>
                                <?php else: ?>
                                    <option value="">Select visitation assignee</option>
                                    <?php foreach (($followupUsers ?? []) as $user): ?>
                                        <option value="<?php echo (int)$user['id']; ?>">
                                            <?php echo $user['display_name']; ?><?php echo !empty($user['department_name']) ? ' - ' . $user['department_name'] : ''; ?>
                                        </option>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </select>
                            <i class="fas fa-chevron-down absolute right-5 top-1/2 -translate-y-1/2 text-slate-600 text-[10px] pointer-events-none"></i>
                        </div>
                        <p class="text-[11px] text-slate-500 font-bold">Only users created as a Visitation Member inside the Visitation department can be assigned.</p>
                    </div>

                    <div class="md:col-span-2 space-y-3">
                        <label class="block text-[10px] font-black text-slate-500 uppercase tracking-[0.28em]">Follow-Up Notes</label>
                        <div class="relative group">
                            <i class="fas fa-notes-medical absolute left-5 top-6 text-slate-600 group-focus-within:text-accent transition-colors"></i>
                            <textarea name="follow_up_notes" rows="4" class="w-full bg-white/5 border border-white/10 rounded-[1.75rem] pl-14 pr-5 py-5 text-sm font-bold text-white placeholder:text-slate-600 outline-none resize-none focus:border-accent transition-all" placeholder="Planned next step, who should call, what to emphasize, and any pastoral follow-up instructions"></textarea>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="mt-8 flex flex-col sm:flex-row gap-4">
            <button type="submit" class="flex-1 bg-accent text-slate-900 px-8 py-5 rounded-2xl font-black text-xs uppercase tracking-[0.3em] hover-glow-yellow transition-all shadow-xl shadow-yellow-500/10">
                Register Visitor
            </button>
            <a href="<?php echo BASE_URL; ?>/visitors" class="sm:w-auto text-center px-8 py-5 bg-white/5 border border-white/10 text-slate-300 rounded-2xl font-black text-xs uppercase tracking-[0.3em] hover:bg-white/10 transition-all">
                Cancel
            </a>
        </div>
    </form>
</div>
