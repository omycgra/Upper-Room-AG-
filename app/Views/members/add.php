<div class="mb-8 flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
    <div>
        <a href="<?php echo BASE_URL; ?>/members" class="inline-flex items-center text-accent hover:text-white font-black text-xs uppercase tracking-[0.28em] mb-4 transition-colors">
            <i class="fas fa-arrow-left mr-3 text-[10px]"></i> Back To Members
        </a>
        <h2 class="text-3xl sm:text-4xl font-black text-white tracking-tight">Register Member</h2>
        <p class="text-slate-400 text-sm font-bold mt-2">Updated dashboard entry form with the current church theme and cleaner responsive spacing.</p>
    </div>
    <div class="glass-card rounded-[2rem] border-white/10 px-5 py-4 max-w-xl">
        <p class="text-[10px] font-black uppercase tracking-[0.35em] text-accent">Age Insights</p>
        <p class="text-xs text-slate-300 font-bold mt-2">Adding the birth date helps the dashboard age brackets stay useful and accurate.</p>
    </div>
</div>

<div class="glass-card rounded-[2.5rem] overflow-hidden border-white/10 mb-10">
    <div class="px-6 py-6 sm:px-8 sm:py-8 lg:px-10 bg-slate-950/80 border-b border-white/5 flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
        <div>
            <p class="text-[10px] font-black uppercase tracking-[0.35em] text-accent">Member Enrollment</p>
            <h3 class="text-2xl sm:text-3xl font-black text-white tracking-tight mt-3">Church Bio And Placement</h3>
        </div>
        <div class="inline-flex items-center gap-3 rounded-2xl bg-accent/10 border border-accent/20 px-4 py-3">
            <span class="w-10 h-10 rounded-2xl bg-accent text-slate-900 flex items-center justify-center shadow-lg shadow-yellow-500/20">
                <i class="fas fa-user-plus text-sm"></i>
            </span>
            <div>
                <p class="text-[10px] font-black uppercase tracking-[0.24em] text-accent">Quick Tip</p>
                <p class="text-xs text-slate-300 font-bold mt-1">Photo, birth date, group, and department keep reports more complete.</p>
            </div>
        </div>
    </div>

    <form action="<?php echo BASE_URL; ?>/members/store" method="POST" enctype="multipart/form-data" class="p-6 sm:p-8 lg:p-10">
        <div class="grid grid-cols-1 xl:grid-cols-3 gap-6 lg:gap-8">
            <div class="xl:col-span-1">
                <div class="glass-card rounded-[2rem] p-6 border-white/10 h-full">
                    <div class="flex items-center justify-between mb-6">
                        <div>
                            <p class="text-[10px] font-black uppercase tracking-[0.35em] text-accent">Profile Photo</p>
                            <p class="text-xs text-slate-400 font-bold mt-2">Use a clear member portrait for directories and birthday popups.</p>
                        </div>
                        <span class="w-12 h-12 rounded-2xl bg-white/5 border border-white/10 flex items-center justify-center text-accent">
                            <i class="fas fa-camera"></i>
                        </span>
                    </div>

                    <div class="flex flex-col items-center text-center">
                        <div class="relative group">
                            <div id="photo-preview" class="w-40 h-40 sm:w-48 sm:h-48 bg-slate-950/80 rounded-[2.5rem] overflow-hidden flex items-center justify-center border border-white/10 shadow-2xl shadow-slate-950/40 transition-all duration-300 group-hover:border-accent">
                                <i class="fas fa-camera text-slate-700 text-4xl group-hover:text-accent transition-colors"></i>
                            </div>
                            <label for="photo-upload" class="absolute -bottom-2 -right-2 w-14 h-14 bg-accent text-slate-900 rounded-2xl flex items-center justify-center cursor-pointer hover-glow-yellow transition-all shadow-xl shadow-yellow-500/20">
                                <i class="fas fa-plus text-sm"></i>
                            </label>
                            <input type="file" name="photo" id="photo-upload" class="hidden" accept="image/*" onchange="previewImage(this)">
                        </div>
                        <p class="text-[10px] font-black text-slate-500 uppercase tracking-[0.35em] mt-6">Tap To Upload</p>
                    </div>

                    <div class="space-y-3 mt-8">
                        <label class="block text-[10px] font-black text-slate-500 uppercase tracking-[0.28em]">Bio ID</label>
                        <div class="relative group">
                            <i class="fas fa-id-badge absolute left-5 top-1/2 -translate-y-1/2 text-slate-600 group-focus-within:text-accent transition-colors"></i>
                            <input type="text" name="bio_id" placeholder="Enter Bio ID manually" class="w-full bg-white/5 border border-white/10 rounded-2xl pl-14 pr-5 py-4 text-sm font-bold text-white placeholder:text-slate-600 focus:border-accent outline-none transition-all">
                        </div>
                        <p class="text-[11px] text-slate-500 font-bold">System ID is generated automatically after save. Bio ID is the one you type here manually.</p>
                    </div>
                </div>
            </div>

            <div class="xl:col-span-2 space-y-6">
                <div class="glass-card rounded-[2rem] p-6 sm:p-8 border-white/10">
                    <div class="flex items-center gap-4 mb-6">
                        <span class="text-[10px] font-black uppercase tracking-[0.45em] text-accent">Identity</span>
                        <div class="h-px flex-1 bg-white/5"></div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                        <div class="space-y-3">
                            <label class="block text-[10px] font-black text-slate-500 uppercase tracking-[0.28em]">First Name</label>
                            <div class="relative group">
                                <i class="fas fa-user absolute left-5 top-1/2 -translate-y-1/2 text-slate-600 group-focus-within:text-accent transition-colors"></i>
                                <input type="text" name="first_name" required placeholder="e.g. John" class="w-full bg-white/5 border border-white/10 rounded-2xl pl-14 pr-5 py-4 text-sm font-bold text-white placeholder:text-slate-600 focus:border-accent outline-none transition-all">
                            </div>
                        </div>

                        <div class="space-y-3">
                            <label class="block text-[10px] font-black text-slate-500 uppercase tracking-[0.28em]">Last Name</label>
                            <div class="relative group">
                                <i class="fas fa-user absolute left-5 top-1/2 -translate-y-1/2 text-slate-600 group-focus-within:text-accent transition-colors"></i>
                                <input type="text" name="last_name" required placeholder="e.g. Doe" class="w-full bg-white/5 border border-white/10 rounded-2xl pl-14 pr-5 py-4 text-sm font-bold text-white placeholder:text-slate-600 focus:border-accent outline-none transition-all">
                            </div>
                        </div>

                        <div class="space-y-3">
                            <label class="block text-[10px] font-black text-slate-500 uppercase tracking-[0.28em]">Phone Number</label>
                            <div class="relative group">
                                <i class="fas fa-phone absolute left-5 top-1/2 -translate-y-1/2 text-slate-600 group-focus-within:text-accent transition-colors"></i>
                                <input type="text" name="phone" required placeholder="e.g. +233 24 000 0000" class="w-full bg-white/5 border border-white/10 rounded-2xl pl-14 pr-5 py-4 text-sm font-bold text-white placeholder:text-slate-600 focus:border-accent outline-none transition-all">
                            </div>
                        </div>

                        <div class="space-y-3">
                            <label class="block text-[10px] font-black text-slate-500 uppercase tracking-[0.28em]">Nationality</label>
                            <div class="relative group">
                                <i class="fas fa-flag absolute left-5 top-1/2 -translate-y-1/2 text-slate-600 group-focus-within:text-accent transition-colors"></i>
                                <input type="text" name="nationality" placeholder="e.g. Ghanaian" class="w-full bg-white/5 border border-white/10 rounded-2xl pl-14 pr-5 py-4 text-sm font-bold text-white placeholder:text-slate-600 focus:border-accent outline-none transition-all">
                            </div>
                        </div>

                        <div class="space-y-3">
                            <label class="block text-[10px] font-black text-slate-500 uppercase tracking-[0.28em]">Email Address</label>
                            <div class="relative group">
                                <i class="fas fa-envelope absolute left-5 top-1/2 -translate-y-1/2 text-slate-600 group-focus-within:text-accent transition-colors"></i>
                                <input type="email" name="email" placeholder="john.doe@example.com" class="w-full bg-white/5 border border-white/10 rounded-2xl pl-14 pr-5 py-4 text-sm font-bold text-white placeholder:text-slate-600 focus:border-accent outline-none transition-all">
                            </div>
                        </div>

                        <div class="space-y-3">
                            <label class="block text-[10px] font-black text-slate-500 uppercase tracking-[0.28em]">Date Of Birth</label>
                            <div class="relative group">
                                <i class="fas fa-calendar-alt absolute left-5 top-1/2 -translate-y-1/2 text-accent"></i>
                                <input type="date" name="date_of_birth" class="w-full bg-accent/5 border border-accent/20 rounded-2xl pl-14 pr-5 py-4 text-sm font-bold text-white outline-none transition-all [color-scheme:dark] focus:border-accent">
                            </div>
                            <p class="text-[11px] text-slate-500 font-bold">Recommended for birthdays and age bracket summaries.</p>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                            <div class="space-y-3">
                                <label class="block text-[10px] font-black text-slate-500 uppercase tracking-[0.28em]">Gender</label>
                                <div class="relative group">
                                    <i class="fas fa-venus-mars absolute left-5 top-1/2 -translate-y-1/2 text-slate-600"></i>
                                    <select name="gender" required class="w-full bg-white/5 border border-white/10 rounded-2xl pl-14 pr-10 py-4 text-sm font-bold text-white outline-none appearance-none focus:border-accent transition-all">
                                        <option value="male">Male</option>
                                        <option value="female">Female</option>
                                        <option value="other">Other</option>
                                    </select>
                                    <i class="fas fa-chevron-down absolute right-5 top-1/2 -translate-y-1/2 text-slate-600 text-[10px] pointer-events-none"></i>
                                </div>
                            </div>

                            <div class="space-y-3">
                                <label class="block text-[10px] font-black text-slate-500 uppercase tracking-[0.28em]">Marital Status</label>
                                <div class="relative group">
                                    <i class="fas fa-heart absolute left-5 top-1/2 -translate-y-1/2 text-slate-600"></i>
                                    <select name="marital_status" required class="w-full bg-white/5 border border-white/10 rounded-2xl pl-14 pr-10 py-4 text-sm font-bold text-white outline-none appearance-none focus:border-accent transition-all">
                                        <option value="single">Single</option>
                                        <option value="married">Married</option>
                                        <option value="widowed">Widowed</option>
                                        <option value="divorced">Divorced</option>
                                    </select>
                                    <i class="fas fa-chevron-down absolute right-5 top-1/2 -translate-y-1/2 text-slate-600 text-[10px] pointer-events-none"></i>
                                </div>
                            </div>
                        </div>

                        <div class="space-y-3">
                            <label class="block text-[10px] font-black text-slate-500 uppercase tracking-[0.28em]">Stays At</label>
                            <div class="relative group">
                                <i class="fas fa-location-dot absolute left-5 top-1/2 -translate-y-1/2 text-slate-600 group-focus-within:text-accent transition-colors"></i>
                                <input type="text" name="stays_at" placeholder="Current place of stay" class="w-full bg-white/5 border border-white/10 rounded-2xl pl-14 pr-5 py-4 text-sm font-bold text-white placeholder:text-slate-600 focus:border-accent outline-none transition-all">
                            </div>
                        </div>

                        <div class="space-y-3">
                            <label class="block text-[10px] font-black text-slate-500 uppercase tracking-[0.28em]">Home Town</label>
                            <div class="relative group">
                                <i class="fas fa-house-flag absolute left-5 top-1/2 -translate-y-1/2 text-slate-600 group-focus-within:text-accent transition-colors"></i>
                                <input type="text" name="home_town" placeholder="Town or village of origin" class="w-full bg-white/5 border border-white/10 rounded-2xl pl-14 pr-5 py-4 text-sm font-bold text-white placeholder:text-slate-600 focus:border-accent outline-none transition-all">
                            </div>
                        </div>
                    </div>
                </div>

                <div class="glass-card rounded-[2rem] p-6 sm:p-8 border-white/10">
                    <div class="flex items-center gap-4 mb-6">
                        <span class="text-[10px] font-black uppercase tracking-[0.45em] text-accent">Church Placement</span>
                        <div class="h-px flex-1 bg-white/5"></div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                        <div class="space-y-3">
                            <label class="block text-[10px] font-black text-slate-500 uppercase tracking-[0.28em]">Cluster / Group</label>
                            <div class="relative group">
                                <i class="fas fa-layer-group absolute left-5 top-1/2 -translate-y-1/2 text-slate-600"></i>
                                <select name="cluster_id" class="w-full bg-white/5 border border-white/10 rounded-2xl pl-14 pr-10 py-4 text-sm font-bold text-white outline-none appearance-none focus:border-accent transition-all">
                                    <option value="">None / Not Assigned</option>
                                    <?php foreach ($clusters as $cluster): ?>
                                        <option value="<?php echo $cluster['id']; ?>"><?php echo $cluster['name']; ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <i class="fas fa-chevron-down absolute right-5 top-1/2 -translate-y-1/2 text-slate-600 text-[10px] pointer-events-none"></i>
                            </div>
                        </div>

                        <div class="space-y-3">
                            <label class="block text-[10px] font-black text-slate-500 uppercase tracking-[0.28em]">Primary Department</label>
                            <div class="relative group">
                                <i class="fas fa-building absolute left-5 top-1/2 -translate-y-1/2 text-slate-600"></i>
                                <?php if (!empty($isDeptHead)): ?>
                                    <select name="department_id" class="w-full bg-white/5 border border-white/10 rounded-2xl pl-14 pr-10 py-4 text-sm font-bold text-white outline-none appearance-none cursor-not-allowed opacity-70" disabled>
                                        <?php foreach ($departments as $dept): ?>
                                            <?php if ((int)$dept['id'] === (int)$myDeptId): ?>
                                                <option value="<?php echo $dept['id']; ?>" selected><?php echo $dept['name']; ?></option>
                                            <?php endif; ?>
                                        <?php endforeach; ?>
                                    </select>
                                    <input type="hidden" name="department_id" value="<?php echo (int)$myDeptId; ?>">
                                <?php else: ?>
                                    <select name="department_id" class="w-full bg-white/5 border border-white/10 rounded-2xl pl-14 pr-10 py-4 text-sm font-bold text-white outline-none appearance-none focus:border-accent transition-all">
                                        <option value="">None / Not Assigned</option>
                                        <?php foreach ($departments as $dept): ?>
                                            <option value="<?php echo $dept['id']; ?>"><?php echo $dept['name']; ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                <?php endif; ?>
                                <i class="fas fa-chevron-down absolute right-5 top-1/2 -translate-y-1/2 text-slate-600 text-[10px] pointer-events-none"></i>
                            </div>
                            <p class="text-[11px] text-slate-500 font-bold">Department heads can manage only this primary department.</p>
                        </div>

                        <div class="space-y-3">
                            <label class="block text-[10px] font-black text-slate-500 uppercase tracking-[0.28em]">Additional Departments</label>
                            <?php if (!empty($isDeptHead)): ?>
                                <div class="w-full bg-white/5 border border-white/10 rounded-2xl px-5 py-4 text-sm font-bold text-slate-500 opacity-70">
                                    Additional departments can be assigned by admins only.
                                </div>
                            <?php else: ?>
                                <select name="additional_department_ids[]" multiple size="5" class="w-full bg-white/5 border border-white/10 rounded-2xl px-5 py-4 text-sm font-bold text-white outline-none focus:border-accent transition-all custom-scrollbar">
                                    <?php foreach ($departments as $dept): ?>
                                        <option value="<?php echo $dept['id']; ?>"><?php echo $dept['name']; ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <p class="text-[11px] text-slate-500 font-bold">Hold `Ctrl` or `Cmd` to select more than one extra department.</p>
                            <?php endif; ?>
                        </div>

                        <div class="md:col-span-2 space-y-3">
                            <label class="block text-[10px] font-black text-slate-500 uppercase tracking-[0.28em]">Home Address</label>
                            <div class="relative group">
                                <i class="fas fa-map-location-dot absolute left-5 top-6 text-slate-600 group-focus-within:text-accent transition-colors"></i>
                                <textarea name="address" rows="4" placeholder="Enter residential address, landmark, or community..." class="w-full bg-white/5 border border-white/10 rounded-[1.75rem] pl-14 pr-5 py-5 text-sm font-bold text-white placeholder:text-slate-600 outline-none resize-none focus:border-accent transition-all"></textarea>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="glass-card rounded-[2rem] p-6 sm:p-8 border-white/10">
                    <div class="flex items-center gap-4 mb-6">
                        <span class="text-[10px] font-black uppercase tracking-[0.45em] text-accent">Family And Work</span>
                        <div class="h-px flex-1 bg-white/5"></div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                        <div class="space-y-3">
                            <label class="block text-[10px] font-black text-slate-500 uppercase tracking-[0.28em]">Name Of Spouse</label>
                            <div class="relative group">
                                <i class="fas fa-ring absolute left-5 top-1/2 -translate-y-1/2 text-slate-600 group-focus-within:text-accent transition-colors"></i>
                                <input type="text" name="spouse_name" placeholder="If married" class="w-full bg-white/5 border border-white/10 rounded-2xl pl-14 pr-5 py-4 text-sm font-bold text-white placeholder:text-slate-600 focus:border-accent outline-none transition-all">
                            </div>
                        </div>

                        <div class="space-y-3">
                            <label class="block text-[10px] font-black text-slate-500 uppercase tracking-[0.28em]">Mother's Name</label>
                            <div class="relative group">
                                <i class="fas fa-person-dress absolute left-5 top-1/2 -translate-y-1/2 text-slate-600 group-focus-within:text-accent transition-colors"></i>
                                <input type="text" name="mother_name" class="w-full bg-white/5 border border-white/10 rounded-2xl pl-14 pr-5 py-4 text-sm font-bold text-white placeholder:text-slate-600 focus:border-accent outline-none transition-all">
                            </div>
                        </div>

                        <div class="space-y-3">
                            <label class="block text-[10px] font-black text-slate-500 uppercase tracking-[0.28em]">Father's Name</label>
                            <div class="relative group">
                                <i class="fas fa-person absolute left-5 top-1/2 -translate-y-1/2 text-slate-600 group-focus-within:text-accent transition-colors"></i>
                                <input type="text" name="father_name" class="w-full bg-white/5 border border-white/10 rounded-2xl pl-14 pr-5 py-4 text-sm font-bold text-white placeholder:text-slate-600 focus:border-accent outline-none transition-all">
                            </div>
                        </div>

                        <div class="space-y-3">
                            <label class="block text-[10px] font-black text-slate-500 uppercase tracking-[0.28em]">Have You Been Baptized?</label>
                            <div class="relative group">
                                <i class="fas fa-water absolute left-5 top-1/2 -translate-y-1/2 text-slate-600"></i>
                                <select name="is_baptized" class="w-full bg-white/5 border border-white/10 rounded-2xl pl-14 pr-10 py-4 text-sm font-bold text-white outline-none appearance-none focus:border-accent transition-all">
                                    <option value="0">No</option>
                                    <option value="1">Yes</option>
                                </select>
                                <i class="fas fa-chevron-down absolute right-5 top-1/2 -translate-y-1/2 text-slate-600 text-[10px] pointer-events-none"></i>
                            </div>
                        </div>

                        <div class="md:col-span-2 space-y-3">
                            <label class="block text-[10px] font-black text-slate-500 uppercase tracking-[0.28em]">Pastor Who Baptized You And Church</label>
                            <div class="relative group">
                                <i class="fas fa-cross absolute left-5 top-1/2 -translate-y-1/2 text-slate-600 group-focus-within:text-accent transition-colors"></i>
                                <input type="text" name="baptism_pastor_church" placeholder="Pastor name and church" class="w-full bg-white/5 border border-white/10 rounded-2xl pl-14 pr-5 py-4 text-sm font-bold text-white placeholder:text-slate-600 focus:border-accent outline-none transition-all">
                            </div>
                        </div>

                        <div class="space-y-3">
                            <label class="block text-[10px] font-black text-slate-500 uppercase tracking-[0.28em]">Are You Currently Working?</label>
                            <div class="relative group">
                                <i class="fas fa-briefcase absolute left-5 top-1/2 -translate-y-1/2 text-slate-600"></i>
                                <select name="currently_working" class="w-full bg-white/5 border border-white/10 rounded-2xl pl-14 pr-10 py-4 text-sm font-bold text-white outline-none appearance-none focus:border-accent transition-all">
                                    <option value="0">No</option>
                                    <option value="1">Yes</option>
                                </select>
                                <i class="fas fa-chevron-down absolute right-5 top-1/2 -translate-y-1/2 text-slate-600 text-[10px] pointer-events-none"></i>
                            </div>
                        </div>

                        <div class="space-y-3">
                            <label class="block text-[10px] font-black text-slate-500 uppercase tracking-[0.28em]">Name Of Work</label>
                            <div class="relative group">
                                <i class="fas fa-building-user absolute left-5 top-1/2 -translate-y-1/2 text-slate-600 group-focus-within:text-accent transition-colors"></i>
                                <input type="text" name="work_name" placeholder="If currently working" class="w-full bg-white/5 border border-white/10 rounded-2xl pl-14 pr-5 py-4 text-sm font-bold text-white placeholder:text-slate-600 focus:border-accent outline-none transition-all">
                            </div>
                        </div>
                    </div>
                </div>

                <div class="flex flex-col sm:flex-row gap-4">
                    <button type="submit" class="flex-1 bg-accent text-slate-900 px-8 py-5 rounded-2xl font-black text-xs uppercase tracking-[0.3em] hover-glow-yellow transition-all shadow-xl shadow-yellow-500/10">
                        Register Member Profile
                    </button>
                    <a href="<?php echo BASE_URL; ?>/members" class="sm:w-auto text-center px-8 py-5 bg-white/5 border border-white/10 text-slate-300 rounded-2xl font-black text-xs uppercase tracking-[0.3em] hover:bg-white/10 transition-all">
                        Cancel
                    </a>
                </div>
            </div>
        </div>
    </form>
</div>

<script>
    function previewImage(input) {
        const preview = document.getElementById('photo-preview');
        if (!preview || !input.files || !input.files[0]) {
            return;
        }

        const reader = new FileReader();
        reader.onload = function (e) {
            preview.innerHTML = '<img src="' + e.target.result + '" class="w-full h-full object-cover" alt="Member photo preview">';
        };
        reader.readAsDataURL(input.files[0]);
    }
</script>
