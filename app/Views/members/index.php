<?php
    $churchName = AppConfig::getSetting('church_name', 'Church Management');
    $isDeptHead = (Session::get('user_role') === 'dept_head');
    $myDeptId = $isDeptHead ? (int)(Session::get('user_department_id') ?? 0) : 0;
    $canEditMember = Auth::isAdmin();
    $canAddMember = Auth::isAdmin() || ($isDeptHead && Auth::hasPermission('members/store'));
?>
<div class="flex flex-col sm:flex-row justify-between items-start mb-10 gap-4">
    <div>
        <h2 class="text-4xl sm:text-5xl font-black text-white tracking-tighter">Members</h2>
        <p class="text-slate-400 font-bold mt-2 uppercase tracking-widest text-xs">Directory of <span class="text-accent"><?php echo $churchName; ?></span></p>
    </div>
    <div class="flex w-full sm:w-auto flex-col sm:flex-row gap-3">
        <?php if (!$isDeptHead): ?>
            <a href="<?php echo BASE_URL; ?>/members/export" class="glass-card flex items-center justify-center px-6 py-3.5 rounded-2xl border-white/10 text-slate-400 font-black text-xs uppercase tracking-widest hover:bg-white/5 transition-all">
                <i class="fas fa-file-download mr-2"></i> Export All
            </a>
        <?php endif; ?>
        <?php if ($canAddMember): ?>
            <button onclick="showModal('add-modal', 'add-modal-content')" class="glass-card flex items-center justify-center px-6 py-3.5 rounded-2xl bg-accent text-slate-900 font-black text-xs uppercase tracking-widest hover:scale-[1.05] transition-all">
                <i class="fas fa-plus mr-2"></i> Add Member
            </button>
        <?php endif; ?>
    </div>
</div>

<!-- Stat Cards -->
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6 mb-12">
    <div class="glass-card rounded-[2rem] p-6 flex items-center group border-white/5">
        <div class="w-14 h-14 bg-white/5 rounded-2xl flex items-center justify-center mr-5 border border-white/10 group-hover:bg-accent transition-all">
            <i class="fas fa-users text-slate-400 group-hover:text-slate-900 text-xl"></i>
        </div>
        <div>
            <h3 class="text-3xl font-black text-white"><?php echo number_format($stats['total']); ?></h3>
            <p class="text-[10px] font-black text-slate-500 uppercase tracking-[0.2em]">Total Members</p>
        </div>
    </div>

    <div class="glass-card rounded-[2rem] p-6 flex items-center group border-white/5">
        <div class="w-14 h-14 bg-white/5 rounded-2xl flex items-center justify-center mr-5 border border-white/10 group-hover:bg-accent transition-all">
            <i class="fas fa-user-check text-slate-400 group-hover:text-slate-900 text-xl"></i>
        </div>
        <div>
            <h3 class="text-3xl font-black text-white"><?php echo number_format($stats['active']); ?></h3>
            <p class="text-[10px] font-black text-slate-500 uppercase tracking-[0.2em]">Active Accounts</p>
        </div>
    </div>

    <div class="glass-card rounded-[2rem] p-6 flex items-center group border-white/5">
        <div class="w-14 h-14 bg-white/5 rounded-2xl flex items-center justify-center mr-5 border border-white/10 group-hover:bg-accent transition-all">
            <i class="fas fa-user-plus text-slate-400 group-hover:text-slate-900 text-xl"></i>
        </div>
        <div>
            <h3 class="text-3xl font-black text-white"><?php echo number_format($stats['new']); ?></h3>
            <p class="text-[10px] font-black text-slate-500 uppercase tracking-[0.2em]">New Registrations Today</p>
        </div>
    </div>
</div>

<!-- Search & Filters -->
<div class="glass-card rounded-[2.5rem] p-5 sm:p-6 lg:p-8 mb-12 border-white/5">
    <div class="flex items-center mb-8">
        <i class="fas fa-filter text-accent mr-3 text-sm"></i>
        <h4 class="text-xs font-black text-slate-400 uppercase tracking-[0.3em]">Advanced Filters</h4>
    </div>
    <form id="members-filter-form" action="<?php echo BASE_URL; ?>/members" method="GET" data-loader="top" class="grid grid-cols-1 md:grid-cols-5 gap-6">
        <div class="md:col-span-2 relative group">
            <i class="fas fa-search absolute left-5 top-1/2 -translate-y-1/2 text-slate-500 group-focus-within:text-accent transition-colors"></i>
            <input type="text" name="search" value="<?php echo $filters['search']; ?>" placeholder="Search by name, code or phone..." 
                class="w-full pl-14 pr-6 py-4 bg-white/5 border border-white/10 rounded-2xl text-sm font-bold text-white focus:ring-2 focus:ring-accent focus:bg-white/10 outline-none transition-all">
        </div>
        
        <div class="relative group">
            <i class="fas fa-building absolute left-5 top-1/2 -translate-y-1/2 text-slate-500"></i>
            <?php if ($isDeptHead): ?>
                <select class="w-full pl-14 pr-10 py-4 bg-white/5 border border-white/10 rounded-2xl text-[10px] font-black text-slate-400 uppercase tracking-widest outline-none appearance-none cursor-not-allowed opacity-70" disabled>
                    <?php foreach ($departments as $dept): ?>
                        <?php if ((int)$dept['id'] === $myDeptId): ?>
                            <option value="<?php echo $dept['id']; ?>" selected><?php echo $dept['name']; ?></option>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </select>
                <input type="hidden" name="department" value="<?php echo (int)$myDeptId; ?>">
            <?php else: ?>
            <select id="members-filter-department" name="department" class="w-full pl-14 pr-10 py-4 bg-white/5 border border-white/10 rounded-2xl text-[10px] font-black text-slate-400 uppercase tracking-widest focus:ring-2 focus:ring-accent outline-none appearance-none cursor-pointer">
                    <option value="">Departments</option>
                    <?php foreach ($departments as $dept): ?>
                        <option value="<?php echo $dept['id']; ?>" <?php echo $filters['department'] == $dept['id'] ? 'selected' : ''; ?>>
                            <?php echo $dept['name']; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            <?php endif; ?>
            <i class="fas fa-chevron-down absolute right-5 top-1/2 -translate-y-1/2 text-slate-600 text-[10px] pointer-events-none"></i>
        </div>

        <div class="relative group">
            <i class="fas fa-arrow-down-a-z absolute left-5 top-1/2 -translate-y-1/2 text-slate-500"></i>
            <select id="members-filter-sort" name="sort" class="w-full pl-14 pr-10 py-4 bg-white/5 border border-white/10 rounded-2xl text-[10px] font-black text-slate-400 uppercase tracking-widest focus:ring-2 focus:ring-accent outline-none appearance-none cursor-pointer">
                <?php $sort = (string)($filters['sort'] ?? ''); ?>
                <option value="name" <?php echo ($sort === '' || $sort === 'name') ? 'selected' : ''; ?>>Name (A–Z)</option>
                <option value="first_name" <?php echo $sort === 'first_name' ? 'selected' : ''; ?>>First Name (A–Z)</option>
                <option value="last_name" <?php echo $sort === 'last_name' ? 'selected' : ''; ?>>Last Name (A–Z)</option>
                <option value="member_code" <?php echo $sort === 'member_code' ? 'selected' : ''; ?>>Member Code (A–Z)</option>
                <option value="bio_id" <?php echo $sort === 'bio_id' ? 'selected' : ''; ?>>Bio ID (A–Z)</option>
                <option value="newest" <?php echo $sort === 'newest' ? 'selected' : ''; ?>>Newest First</option>
            </select>
            <i class="fas fa-chevron-down absolute right-5 top-1/2 -translate-y-1/2 text-slate-600 text-[10px] pointer-events-none"></i>
        </div>

        <div class="flex flex-col sm:flex-row gap-3">
            <div class="flex-1 relative">
                <select id="members-filter-status" name="status" class="w-full pl-6 pr-10 py-4 bg-white/5 border border-white/10 rounded-2xl text-[10px] font-black text-slate-400 uppercase tracking-widest focus:ring-2 focus:ring-accent outline-none appearance-none cursor-pointer">
                    <option value="">Status</option>
                    <option value="Active" <?php echo $filters['status'] === 'Active' ? 'selected' : ''; ?>>Active</option>
                </select>
                <i class="fas fa-chevron-down absolute right-5 top-1/2 -translate-y-1/2 text-slate-600 text-[10px] pointer-events-none"></i>
            </div>
            <div class="flex-1 relative">
                <?php $added = (string)($filters['added'] ?? ''); ?>
                <select id="members-filter-added" name="added" class="w-full pl-6 pr-10 py-4 bg-white/5 border border-white/10 rounded-2xl text-[10px] font-black text-slate-400 uppercase tracking-widest focus:ring-2 focus:ring-accent outline-none appearance-none cursor-pointer">
                    <option value="" <?php echo $added === '' ? 'selected' : ''; ?>>Added</option>
                    <option value="today" <?php echo $added === 'today' ? 'selected' : ''; ?>>Today</option>
                </select>
                <i class="fas fa-chevron-down absolute right-5 top-1/2 -translate-y-1/2 text-slate-600 text-[10px] pointer-events-none"></i>
            </div>
            <button type="submit" class="bg-accent text-slate-900 p-4 rounded-2xl hover-glow-yellow transition-all shadow-lg shadow-yellow-500/20">
                <i class="fas fa-arrow-right"></i>
            </button>
        </div>
    </form>
</div>

<script>
    (function () {
        const form = document.getElementById('members-filter-form');
        const sort = document.getElementById('members-filter-sort');
        const dept = document.getElementById('members-filter-department');
        const status = document.getElementById('members-filter-status');
        const added = document.getElementById('members-filter-added');
        if (!form) return;
        const submit = () => (form.requestSubmit ? form.requestSubmit() : form.submit());
        sort?.addEventListener('change', submit);
        dept?.addEventListener('change', submit);
        status?.addEventListener('change', submit);
        added?.addEventListener('change', submit);
    })();
</script>

<!-- Members List -->
<div class="glass-card rounded-[2.5rem] sm:rounded-[3rem] border-white/5 overflow-hidden card-interaction">
    <div class="px-6 sm:px-8 lg:px-10 py-6 sm:py-8 border-b border-white/5 flex flex-col sm:flex-row justify-between items-start sm:items-center bg-white/[0.02] gap-4">
        <div class="flex items-center">
            <div class="w-10 h-10 bg-accent/10 rounded-xl flex items-center justify-center mr-4 border border-accent/20">
                <i class="fas fa-users-viewfinder text-accent text-sm"></i>
            </div>
            <h4 class="text-xl font-black text-white tracking-tight">Active Members</h4>
        </div>
        <div class="flex items-center gap-6">
            <span class="text-[10px] font-black text-slate-500 uppercase tracking-[0.2em] italic"><?php echo count($members); ?> Records Found</span>
        </div>
    </div>
    <div class="md:hidden p-4 sm:p-6 space-y-4">
        <?php if (empty($members)): ?>
            <div class="px-6 py-16 text-center">
                <div class="flex flex-col items-center opacity-20">
                    <i class="fas fa-users-slash text-6xl mb-6"></i>
                    <p class="text-sm font-black uppercase tracking-[0.3em]">No Member Profiles Found</p>
                </div>
            </div>
        <?php else: ?>
            <?php foreach ($members as $member): ?>
                <?php
                    $memberPhotoUrl = Branding::mediaUrl((string)($member['photo_path'] ?? ''));
                ?>
                <div class="glass-card rounded-[2rem] p-4 sm:p-5 border-white/10">
                    <div class="flex items-start gap-4">
                        <div class="relative flex-shrink-0">
                            <?php if ($memberPhotoUrl !== ''): ?>
                                <img src="<?php echo htmlspecialchars($memberPhotoUrl); ?>" class="w-14 h-14 rounded-2xl object-cover border-2 border-white/10">
                            <?php else: ?>
                                <div class="w-14 h-14 bg-slate-800 border border-white/10 rounded-2xl flex items-center justify-center">
                                    <span class="text-accent font-black text-xl"><?php echo strtoupper(substr($member['first_name'], 0, 1)); ?></span>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="min-w-0 flex-1">
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <div class="text-sm font-black text-slate-200 tracking-tight"><?php echo $member['first_name'] . ' ' . $member['last_name']; ?></div>
                                    <div class="text-[10px] font-black text-slate-500 uppercase tracking-widest mt-1">System ID: <?php echo htmlspecialchars($member['member_code']); ?></div>
                                    <div class="text-[10px] font-black text-accent/80 uppercase tracking-widest mt-1"><?php echo !empty($member['bio_id']) ? 'Bio ID: ' . htmlspecialchars($member['bio_id']) : 'Bio ID: Not Set'; ?></div>
                                </div>
                                <span class="px-3 py-1.5 text-[9px] font-black rounded-full uppercase tracking-[0.2em] bg-accent/10 text-accent border border-accent/20 shadow-[0_0_15px_rgba(251,191,36,0.1)] shrink-0">
                                    Active
                                </span>
                            </div>
                            <div class="mt-3 space-y-1.5">
                                <div class="flex items-center text-xs font-bold text-slate-400">
                                    <i class="fas fa-phone text-[10px] text-accent/40 mr-3"></i> <?php echo $member['phone']; ?>
                                </div>
                                <div class="flex items-center text-[10px] text-slate-500 font-bold uppercase tracking-tighter">
                                    <i class="fas fa-envelope text-[10px] text-accent/40 mr-3"></i> <?php echo $member['email'] ?: 'No Email'; ?>
                                </div>
                                <div class="flex items-center text-[10px] text-slate-500 font-bold uppercase tracking-tighter">
                                    <i class="fas fa-building text-[10px] text-accent/40 mr-3"></i> <?php echo $member['department_names'] ?: 'General'; ?>      
                                </div>
                            </div>
                            <div class="mt-4 flex flex-wrap gap-2">
                                <button onclick="viewMember(<?php echo $member['id']; ?>)" class="h-10 px-4 inline-flex items-center justify-center rounded-xl bg-white/5 text-slate-500 hover-glow-blue transition-all duration-500 border border-white/5">
                                    <i class="fas fa-eye text-xs mr-2"></i><span class="text-[10px] font-black uppercase tracking-widest">View</span>
                                </button>
                                <?php if ($canEditMember): ?>
                                    <button onclick="editMember(<?php echo $member['id']; ?>)" class="h-10 px-4 inline-flex items-center justify-center rounded-xl bg-white/5 text-slate-500 hover-glow-yellow transition-all duration-500 border border-white/5">
                                        <i class="fas fa-pen-nib text-xs mr-2"></i><span class="text-[10px] font-black uppercase tracking-widest">Edit</span>
                                    </button>
                                    <a href="<?php echo BASE_URL; ?>/members/delete?id=<?php echo $member['id']; ?>" 
                                       onclick="return confirm('Security Check: Permanent removal of this member profile?')"
                                       class="h-10 px-4 inline-flex items-center justify-center rounded-xl bg-white/5 text-slate-500 hover-glow-red transition-all duration-500 border border-white/5">
                                        <i class="fas fa-trash-alt text-xs mr-2"></i><span class="text-[10px] font-black uppercase tracking-widest">Delete</span>
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
    <div class="hidden md:block overflow-x-auto custom-scrollbar">
        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="text-[10px] font-black text-slate-500 uppercase tracking-[0.3em] border-b border-white/5 bg-white/[0.01]">
                    <th class="px-10 py-6">Member Profile</th>
                    <th class="px-10 py-6 hidden sm:table-cell">Contact Details</th>
                    <th class="px-10 py-6 hidden lg:table-cell">Department</th>
                    <th class="px-10 py-6 text-center">Status</th>
                    <th class="px-10 py-6 text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-white/[0.02]">
                <?php if (empty($members)): ?>
                    <tr>
                        <td colspan="5" class="px-10 py-24 text-center">
                            <div class="flex flex-col items-center opacity-20">
                                <i class="fas fa-users-slash text-6xl mb-6"></i>
                                <p class="text-sm font-black uppercase tracking-[0.3em]">No Member Profiles Found</p>
                            </div>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($members as $member): ?>
                        <?php
                            $memberPhotoUrl = Branding::mediaUrl((string)($member['photo_path'] ?? ''));
                        ?>
                        <tr class="hover:bg-white/[0.03] transition-all duration-300 group">
                            <td class="px-10 py-6">
                                <div class="flex items-center">
                                    <div class="relative flex-shrink-0">
                                        <?php if ($memberPhotoUrl !== ''): ?>
                                            <img src="<?php echo htmlspecialchars($memberPhotoUrl); ?>" class="w-14 h-14 rounded-2xl object-cover border-2 border-white/10 group-hover:border-accent transition-colors">
                                        <?php else: ?>
                                            <div class="w-14 h-14 bg-slate-800 border border-white/10 rounded-2xl flex items-center justify-center group-hover:bg-accent transition-all duration-500">
                                                <span class="text-accent group-hover:text-slate-900 font-black text-xl transition-colors"><?php echo strtoupper(substr($member['first_name'], 0, 1)); ?></span>
                                            </div>
                                        <?php endif; ?>
                                        <div class="absolute -bottom-1 -right-1 w-5 h-5 bg-slate-900 rounded-full flex items-center justify-center border-2 border-slate-900">
                                            <div class="w-2 h-2 rounded-full bg-accent animate-pulse"></div>
                                        </div>
                                    </div>
                                    <div class="ml-5">
                                        <div class="text-sm font-black text-slate-200 group-hover:text-white transition-colors tracking-tight"><?php echo $member['first_name'] . ' ' . $member['last_name']; ?></div>
                                        <div class="text-[10px] font-black text-slate-500 uppercase tracking-widest mt-1">System ID: <?php echo htmlspecialchars($member['member_code']); ?></div>
                                        <div class="text-[10px] font-black text-accent/80 uppercase tracking-widest mt-1"><?php echo !empty($member['bio_id']) ? 'Bio ID: ' . htmlspecialchars($member['bio_id']) : 'Bio ID: Not Set'; ?></div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-10 py-6 hidden sm:table-cell">
                                <div class="space-y-1.5">
                                    <div class="flex items-center text-xs font-bold text-slate-400 group-hover:text-slate-200 transition-colors">
                                        <i class="fas fa-phone text-[10px] text-accent/40 mr-3"></i> <?php echo $member['phone']; ?>
                                    </div>
                            <div class="flex items-center text-[10px] text-slate-500 font-bold uppercase tracking-tighter">
                                <i class="fas fa-flag text-[10px] text-accent/40 mr-3"></i> <?php echo $member['nationality'] ?: 'No Nationality'; ?>
                            </div>
                                    <div class="flex items-center text-[10px] text-slate-500 font-bold uppercase tracking-tighter">
                                        <i class="fas fa-building text-[10px] text-accent/40 mr-3"></i> <?php echo $member['department_names'] ?: 'General'; ?>
                                    </div>
                                </div>
                            </td>
                            <td class="px-10 py-6 hidden lg:table-cell">
                                <span class="px-4 py-1.5 bg-white/5 border border-white/10 rounded-xl text-[10px] font-black text-slate-400 uppercase tracking-widest group-hover:text-accent group-hover:border-accent/20 transition-all">
                                    <?php echo $member['department_names'] ?: 'General'; ?>
                                </span>
                            </td>
                            <td class="px-10 py-6 text-center">
                                <span class="px-4 py-2 text-[9px] font-black rounded-full uppercase tracking-[0.2em] bg-accent/10 text-accent border border-accent/20 shadow-[0_0_15px_rgba(251,191,36,0.1)]">
                                    ACTIVE
                                </span>
                            </td>
                            <td class="px-10 py-6 text-right">
                                <div class="flex items-center justify-end space-x-3">
                                    <button onclick="viewMember(<?php echo $member['id']; ?>)" class="w-10 h-10 flex items-center justify-center rounded-xl bg-white/5 text-slate-500 hover-glow-blue transition-all duration-500 border border-white/5">
                                        <i class="fas fa-eye text-xs"></i>
                                    </button>
                                    <?php if ($canEditMember): ?>
                                        <button onclick="editMember(<?php echo $member['id']; ?>)" class="w-10 h-10 flex items-center justify-center rounded-xl bg-white/5 text-slate-500 hover-glow-yellow transition-all duration-500 border border-white/5">
                                            <i class="fas fa-pen-nib text-xs"></i>
                                        </button>
                                        <a href="<?php echo BASE_URL; ?>/members/delete?id=<?php echo $member['id']; ?>" 
                                           onclick="return confirm('Security Check: Permanent removal of this member profile?')"
                                           class="w-10 h-10 flex items-center justify-center rounded-xl bg-white/5 text-slate-500 hover-glow-red transition-all duration-500 border border-white/5">
                                            <i class="fas fa-trash-alt text-xs"></i>
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Member Details Modal -->
<div id="member-modal" class="fixed inset-0 bg-slate-950/80 backdrop-blur-xl z-50 hidden flex items-center justify-center p-4">
    <div class="glass-card w-full max-w-2xl rounded-[3rem] overflow-hidden shadow-2xl transform transition-all duration-500 scale-95 opacity-0 max-h-[90vh] border-white/10 flex flex-col" id="modal-content">
        <!-- Modal Header -->
        <div class="relative h-32 sm:h-40 bg-gradient-to-br from-slate-900 to-slate-800 border-b border-white/10 flex-shrink-0">
            <button onclick="closeModal('member-modal', 'modal-content')" class="absolute top-4 right-4 sm:top-6 sm:right-6 w-10 h-10 bg-white/5 hover:bg-accent hover:text-slate-900 text-slate-400 rounded-2xl flex items-center justify-center transition-all z-20 border border-white/10">
                <i class="fas fa-times text-sm"></i>
            </button>
            <div class="absolute -bottom-10 sm:-bottom-12 left-6 sm:left-10">
                <div id="modal-avatar" class="w-20 h-20 sm:w-28 sm:h-28 bg-slate-900 rounded-[1.75rem] sm:rounded-[2rem] p-1 shadow-2xl border-4 border-slate-900">
                    <div class="w-full h-full bg-accent rounded-[1.5rem] flex items-center justify-center shadow-inner">
                        <span id="modal-initial" class="text-slate-900 font-black text-3xl sm:text-4xl"></span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal Body -->
        <div class="px-6 sm:px-10 pt-16 sm:pt-20 pb-10 sm:pb-12 overflow-y-auto custom-scrollbar flex-1">
            <div class="flex flex-col sm:flex-row sm:justify-between sm:items-start gap-6 mb-12">
                <div>
                    <h3 id="modal-name" class="text-3xl font-black text-white tracking-tighter"></h3>
                    <p id="modal-code" class="text-[10px] font-black text-accent uppercase tracking-[0.3em] mt-2"></p>
                    <p id="modal-system-code" class="text-[10px] font-black text-slate-500 uppercase tracking-[0.3em] mt-2"></p>
                </div>
                <span id="modal-status" class="self-start px-5 py-2 rounded-full text-[9px] font-black uppercase tracking-[0.2em] bg-accent/10 text-accent border border-accent/20"></span>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-10">
                <div class="space-y-8">
                    <div>
                        <h4 class="text-[10px] font-black text-slate-500 uppercase tracking-[0.4em] mb-6 flex items-center">
                            <i class="fas fa-id-card mr-3 text-accent"></i> Contact Details
                        </h4>
                        <div class="space-y-5">
                            <div class="flex items-center text-sm font-bold text-slate-300 glass-card p-4 rounded-2xl border-white/5">
                                <i class="fas fa-phone-alt w-10 text-accent"></i>
                                <span id="modal-phone"></span>
                            </div>
                            <div class="flex items-center text-sm font-bold text-slate-300 glass-card p-4 rounded-2xl border-white/5">
                                <i class="fas fa-flag w-10 text-accent"></i>
                                <span id="modal-nationality"></span>
                            </div>
                            <div class="flex items-center text-sm font-bold text-slate-300 glass-card p-4 rounded-2xl border-white/5">
                                <i class="fas fa-envelope w-10 text-accent"></i>
                                <span id="modal-email" class="truncate"></span>
                            </div>
                            <div class="flex items-start text-sm font-bold text-slate-300 glass-card p-4 rounded-2xl border-white/5">
                                <i class="fas fa-map-marker-alt w-10 mt-1 text-accent"></i>
                                <span id="modal-address" class="flex-1 leading-relaxed"></span>
                            </div>
                            <div class="flex items-center text-sm font-bold text-slate-300 glass-card p-4 rounded-2xl border-white/5">
                                <i class="fas fa-location-dot w-10 text-accent"></i>
                                <span id="modal-stays-at"></span>
                            </div>
                            <div class="flex items-center text-sm font-bold text-slate-300 glass-card p-4 rounded-2xl border-white/5">
                                <i class="fas fa-house-flag w-10 text-accent"></i>
                                <span id="modal-home-town"></span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="space-y-8">
                    <div>
                        <h4 class="text-[10px] font-black text-slate-500 uppercase tracking-[0.4em] mb-6 flex items-center">
                            <i class="fas fa-church mr-3 text-accent"></i> Membership
                        </h4>
                        <div class="space-y-5">
                            <div class="flex items-center text-sm font-bold text-slate-300 glass-card p-4 rounded-2xl border-white/5">
                                <i class="fas fa-birthday-cake w-10 text-accent"></i>
                                <span id="modal-dob"></span>
                            </div>
                            <div class="flex items-start text-sm font-bold text-slate-300 glass-card p-4 rounded-2xl border-white/5">
                                <i class="fas fa-building w-10 text-accent"></i>
                                <span id="modal-dept"></span>
                            </div>
                            <div class="flex items-center text-sm font-bold text-slate-300 glass-card p-4 rounded-2xl border-white/5">
                                <i class="fas fa-layer-group w-10 text-accent"></i>
                                <span id="modal-cluster"></span>
                            </div>
                            <div class="flex items-center text-sm font-bold text-slate-300 glass-card p-4 rounded-2xl border-white/5">
                                <i class="fas fa-calendar-plus w-10 text-accent"></i>
                                <span id="modal-join-date"></span>
                            </div>
                            <div class="flex items-center text-sm font-bold text-slate-300 glass-card p-4 rounded-2xl border-white/5">
                                <i class="fas fa-ring w-10 text-accent"></i>
                                <span id="modal-spouse-name"></span>
                            </div>
                            <div class="flex items-center text-sm font-bold text-slate-300 glass-card p-4 rounded-2xl border-white/5">
                                <i class="fas fa-water w-10 text-accent"></i>
                                <span id="modal-baptism"></span>
                            </div>
                            <div class="flex items-center text-sm font-bold text-slate-300 glass-card p-4 rounded-2xl border-white/5">
                                <i class="fas fa-briefcase w-10 text-accent"></i>
                                <span id="modal-work-status"></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-10 mt-10">
                <div>
                    <h4 class="text-[10px] font-black text-slate-500 uppercase tracking-[0.4em] mb-6 flex items-center">
                        <i class="fas fa-people-roof mr-3 text-accent"></i> Family
                    </h4>
                    <div class="space-y-5">
                        <div class="flex items-center text-sm font-bold text-slate-300 glass-card p-4 rounded-2xl border-white/5">
                            <i class="fas fa-person-dress w-10 text-accent"></i>
                            <span id="modal-mother-name"></span>
                        </div>
                        <div class="flex items-center text-sm font-bold text-slate-300 glass-card p-4 rounded-2xl border-white/5">
                            <i class="fas fa-person w-10 text-accent"></i>
                            <span id="modal-father-name"></span>
                        </div>
                    </div>
                </div>

                <div>
                    <h4 class="text-[10px] font-black text-slate-500 uppercase tracking-[0.4em] mb-6 flex items-center">
                        <i class="fas fa-briefcase mr-3 text-accent"></i> Work And Faith
                    </h4>
                    <div class="space-y-5">
                        <div class="flex items-start text-sm font-bold text-slate-300 glass-card p-4 rounded-2xl border-white/5">
                            <i class="fas fa-cross w-10 mt-1 text-accent"></i>
                            <span id="modal-baptism-pastor" class="flex-1 leading-relaxed"></span>
                        </div>
                        <div class="flex items-center text-sm font-bold text-slate-300 glass-card p-4 rounded-2xl border-white/5">
                            <i class="fas fa-building-user w-10 text-accent"></i>
                            <span id="modal-work-name"></span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="mt-12 pt-10 border-t border-white/5 flex flex-col sm:flex-row gap-4">
                <?php if ($canEditMember): ?>
                    <button id="modal-edit-btn" class="flex-1 bg-accent text-slate-900 py-5 rounded-[1.5rem] text-center font-black text-xs uppercase tracking-[0.2em] hover-glow-yellow active:scale-95 transition-all shadow-xl shadow-yellow-500/10">
                        Modify Profile
                    </button>
                <?php endif; ?>
                <button onclick="closeModal('member-modal', 'modal-content')" class="px-10 bg-white/5 text-slate-400 py-5 rounded-[1.5rem] font-black text-xs uppercase tracking-[0.2em] hover:bg-white/10 transition-all">
                    Dismiss
                </button>
            </div>
        </div>
    </div>
</div>

<?php if ($canEditMember): ?>
<!-- Edit Member Modal -->
<div id="edit-modal" class="fixed inset-0 bg-slate-950/90 backdrop-blur-2xl z-50 hidden flex items-center justify-center p-4">
    <div class="glass-card w-full max-w-2xl rounded-[3.5rem] overflow-hidden shadow-2xl transform transition-all duration-500 scale-95 opacity-0 max-h-[90vh] flex flex-col border-white/10" id="edit-modal-content">
        <!-- Modern Header -->
        <div class="px-10 py-10 bg-[linear-gradient(135deg,#020617_0%,#0f172a_45%,rgba(251,191,36,0.16)_100%)] relative overflow-hidden flex-shrink-0 border-b border-white/5">
            <div class="relative z-10 flex justify-between items-center text-white">
                <div>
                    <h3 class="text-3xl font-black tracking-tighter">Edit Member</h3>
                    <p class="text-accent text-[10px] font-black mt-2 tracking-[0.3em] uppercase">Security Level: Administrative</p>
                </div>
                <button onclick="closeModal('edit-modal', 'edit-modal-content')" class="w-12 h-12 bg-white/5 hover:bg-accent hover:text-slate-900 rounded-2xl flex items-center justify-center transition-all border border-white/10">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        </div>
 
        <form action="<?php echo BASE_URL; ?>/members/update" method="POST" enctype="multipart/form-data" class="flex-1 overflow-y-auto custom-scrollbar bg-slate-900/50">
            <div class="p-10">
                <input type="hidden" name="id" id="edit-id">
                
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-8">
                    <!-- Photo Section -->
                    <div class="sm:col-span-2 flex flex-col items-center mb-8">
                        <div class="relative group">
                            <div id="edit-photo-preview" class="w-36 h-36 bg-slate-800 rounded-[2.5rem] overflow-hidden flex items-center justify-center border-4 border-slate-800 shadow-2xl group-hover:border-accent transition-all duration-500">
                                <i class="fas fa-user-edit text-slate-700 text-4xl group-hover:text-accent"></i>
                            </div>
                            <label for="edit-photo" class="absolute -bottom-2 -right-2 w-12 h-12 bg-accent text-slate-900 rounded-2xl flex items-center justify-center cursor-pointer hover:scale-110 transition-all shadow-xl z-20">
                                <i class="fas fa-camera text-sm"></i>
                            </label>
                            <input type="file" name="photo" id="edit-photo" class="hidden" accept="image/*" capture="environment" onchange="previewImage(this, 'edit-photo-preview')">
                        </div>
                        <p class="text-[9px] font-black text-slate-500 uppercase tracking-[0.3em] mt-6">Upload New Profile Image</p>
                    </div>

                    <div class="sm:col-span-2 space-y-3">
                        <label class="block text-[9px] font-black text-slate-500 uppercase tracking-widest ml-1">Bio ID</label>
                        <div class="relative group">
                            <i class="fas fa-id-badge absolute left-5 top-1/2 -translate-y-1/2 text-slate-600 group-focus-within:text-accent transition-colors"></i>
                            <input type="text" name="bio_id" id="edit-member-code" class="w-full bg-white/5 border border-white/10 focus:border-accent rounded-2xl pl-14 pr-6 py-4 text-sm font-bold text-white transition-all outline-none" placeholder="Enter Bio ID manually">
                        </div>
                        <p class="text-[10px] font-bold text-slate-500 ml-1">System ID stays automatic. Change only the Bio ID here.</p>
                    </div>

                    <!-- Personal Information -->
                    <div class="sm:col-span-2 flex items-center space-x-6 mb-4">
                        <span class="text-[10px] font-black text-accent uppercase tracking-[0.5em]">Identity</span>
                        <div class="h-[1px] flex-1 bg-white/5"></div>
                    </div>

                    <div class="space-y-3">
                        <label class="block text-[9px] font-black text-slate-500 uppercase tracking-widest ml-1">First Name</label>
                        <div class="relative group">
                            <i class="fas fa-user absolute left-5 top-1/2 -translate-y-1/2 text-slate-600 group-focus-within:text-accent transition-colors"></i>
                            <input type="text" name="first_name" id="edit-first-name" required class="w-full bg-white/5 border border-white/10 focus:border-accent rounded-2xl pl-14 pr-6 py-4 text-sm font-bold text-white transition-all outline-none">
                        </div>
                    </div>

                    <div class="space-y-3">
                        <label class="block text-[9px] font-black text-slate-500 uppercase tracking-widest ml-1">Last Name</label>
                        <div class="relative group">
                            <i class="fas fa-user absolute left-5 top-1/2 -translate-y-1/2 text-slate-600 group-focus-within:text-accent transition-colors"></i>
                            <input type="text" name="last_name" id="edit-last-name" required class="w-full bg-white/5 border border-white/10 focus:border-accent rounded-2xl pl-14 pr-6 py-4 text-sm font-bold text-white transition-all outline-none">
                        </div>
                    </div>

                    <div class="space-y-3">
                        <label class="block text-[9px] font-black text-slate-500 uppercase tracking-widest ml-1">Email Address</label>
                        <div class="relative group">
                            <i class="fas fa-envelope absolute left-5 top-1/2 -translate-y-1/2 text-slate-600 group-focus-within:text-accent transition-colors"></i>
                            <input type="email" name="email" id="edit-email" class="w-full bg-white/5 border border-white/10 focus:border-accent rounded-2xl pl-14 pr-6 py-4 text-sm font-bold text-white transition-all outline-none">
                        </div>
                    </div>

                    <div class="space-y-3">
                        <label class="block text-[9px] font-black text-slate-500 uppercase tracking-widest ml-1">Phone Number</label>
                        <div class="relative group">
                            <i class="fas fa-phone absolute left-5 top-1/2 -translate-y-1/2 text-slate-600 group-focus-within:text-accent transition-colors"></i>
                            <input type="text" name="phone" id="edit-phone" required class="w-full bg-white/5 border border-white/10 focus:border-accent rounded-2xl pl-14 pr-6 py-4 text-sm font-bold text-white transition-all outline-none">
                        </div>
                    </div>

                    <div class="space-y-3">
                        <label class="block text-[9px] font-black text-slate-500 uppercase tracking-widest ml-1">Nationality</label>
                        <div class="relative group">
                            <i class="fas fa-flag absolute left-5 top-1/2 -translate-y-1/2 text-slate-600 group-focus-within:text-accent transition-colors"></i>
                            <input type="text" name="nationality" id="edit-nationality" class="w-full bg-white/5 border border-white/10 focus:border-accent rounded-2xl pl-14 pr-6 py-4 text-sm font-bold text-white transition-all outline-none">
                        </div>
                    </div>

                    <div class="space-y-3">
                        <label class="block text-[9px] font-black text-slate-500 uppercase tracking-widest ml-1">Birth Date</label>
                        <div class="relative group">
                            <i class="fas fa-calendar-alt absolute left-5 top-1/2 -translate-y-1/2 text-slate-600 group-focus-within:text-accent transition-colors"></i>
                            <input type="date" name="date_of_birth" id="edit-dob" class="w-full bg-white/5 border border-white/10 focus:border-accent rounded-2xl pl-14 pr-6 py-4 text-sm font-bold text-white transition-all outline-none [color-scheme:dark]">
                        </div>
                    </div>

                    <div class="space-y-3">
                        <label class="block text-[9px] font-black text-slate-500 uppercase tracking-widest ml-1">Gender</label>
                        <div class="relative group">
                            <i class="fas fa-venus-mars absolute left-5 top-1/2 -translate-y-1/2 text-slate-600"></i>
                            <select name="gender" id="edit-gender" class="w-full bg-white/5 border border-white/10 focus:border-accent rounded-2xl pl-14 pr-10 py-4 text-sm font-bold text-white transition-all outline-none appearance-none cursor-pointer">
                                <option value="male">Male</option>
                                <option value="female">Female</option>
                                <option value="other">Other</option>
                            </select>
                            <i class="fas fa-chevron-down absolute right-5 top-1/2 -translate-y-1/2 text-slate-600 text-[10px] pointer-events-none"></i>
                        </div>
                    </div>

                    <div class="space-y-3">
                        <label class="block text-[9px] font-black text-slate-500 uppercase tracking-widest ml-1">Stays At</label>
                        <div class="relative group">
                            <i class="fas fa-location-dot absolute left-5 top-1/2 -translate-y-1/2 text-slate-600 group-focus-within:text-accent transition-colors"></i>
                            <input type="text" name="stays_at" id="edit-stays-at" class="w-full bg-white/5 border border-white/10 focus:border-accent rounded-2xl pl-14 pr-6 py-4 text-sm font-bold text-white transition-all outline-none">
                        </div>
                    </div>

                    <div class="space-y-3">
                        <label class="block text-[9px] font-black text-slate-500 uppercase tracking-widest ml-1">Home Town</label>
                        <div class="relative group">
                            <i class="fas fa-house-flag absolute left-5 top-1/2 -translate-y-1/2 text-slate-600 group-focus-within:text-accent transition-colors"></i>
                            <input type="text" name="home_town" id="edit-home-town" class="w-full bg-white/5 border border-white/10 focus:border-accent rounded-2xl pl-14 pr-6 py-4 text-sm font-bold text-white transition-all outline-none">
                        </div>
                    </div>

                    <!-- Church Section -->
                    <div class="sm:col-span-2 flex items-center space-x-6 mb-4 mt-8">
                        <span class="text-[10px] font-black text-accent uppercase tracking-[0.5em]">Church</span>
                        <div class="h-[1px] flex-1 bg-white/5"></div>
                    </div>

                    <div class="space-y-3">
                        <label class="block text-[9px] font-black text-slate-500 uppercase tracking-widest ml-1">Current Status</label>
                        <div class="relative group">
                            <i class="fas fa-shield-check absolute left-5 top-1/2 -translate-y-1/2 text-accent"></i>
                            <select name="membership_status" id="edit-status" class="w-full bg-accent/5 border border-accent/20 rounded-2xl pl-14 pr-10 py-4 text-sm font-black text-accent outline-none appearance-none">
                                <option value="Active">ACTIVE</option>
                            </select>
                            <i class="fas fa-lock absolute right-5 top-1/2 -translate-y-1/2 text-accent/30 text-[10px] pointer-events-none"></i>
                        </div>
                    </div>

                    <div class="space-y-3">
                        <label class="block text-[9px] font-black text-slate-500 uppercase tracking-widest ml-1">Primary Department</label>
                        <div class="relative group">
                            <i class="fas fa-building absolute left-5 top-1/2 -translate-y-1/2 text-slate-600"></i>
                            <select name="department_id" id="edit-dept" class="w-full bg-white/5 border border-white/10 focus:border-accent rounded-2xl pl-14 pr-10 py-4 text-sm font-bold text-white transition-all outline-none appearance-none cursor-pointer">
                                <option value="">None Assigned</option>
                                <?php foreach ($departments as $dept): ?>
                                    <option value="<?php echo $dept['id']; ?>"><?php echo $dept['name']; ?></option>
                                <?php endforeach; ?>
                            </select>
                            <i class="fas fa-chevron-down absolute right-5 top-1/2 -translate-y-1/2 text-slate-600 text-[10px] pointer-events-none"></i>
                        </div>
                        <p class="text-[10px] font-bold text-slate-500 ml-1">Department heads manage only the member's primary department.</p>
                    </div>

                    <div class="space-y-3">
                        <label class="block text-[9px] font-black text-slate-500 uppercase tracking-widest ml-1">Additional Departments</label>
                        <select name="additional_department_ids[]" id="edit-additional-depts" multiple size="5" class="w-full bg-white/5 border border-white/10 focus:border-accent rounded-2xl px-4 py-4 text-sm font-bold text-white transition-all outline-none custom-scrollbar">
                            <?php foreach ($departments as $dept): ?>
                                <option value="<?php echo $dept['id']; ?>"><?php echo $dept['name']; ?></option>
                            <?php endforeach; ?>
                        </select>
                        <p class="text-[10px] font-bold text-slate-500 ml-1">Hold `Ctrl` or `Cmd` to select more than one extra department.</p>
                    </div>

                    <div class="space-y-3">
                        <label class="block text-[9px] font-black text-slate-500 uppercase tracking-widest ml-1">Position</label>
                        <div class="relative group">
                            <i class="fas fa-user-tag absolute left-5 top-1/2 -translate-y-1/2 text-slate-600 group-focus-within:text-accent transition-colors"></i>
                            <input type="text" name="position" id="edit-position" class="w-full bg-white/5 border border-white/10 focus:border-accent rounded-2xl pl-14 pr-6 py-4 text-sm font-bold text-white transition-all outline-none" placeholder="e.g. Department Head, Secretary...">
                        </div>
                    </div>

                    <div class="space-y-3">
                        <label class="block text-[9px] font-black text-slate-500 uppercase tracking-widest ml-1">Cluster / Group</label>
                        <div class="relative group">
                            <i class="fas fa-layer-group absolute left-5 top-1/2 -translate-y-1/2 text-slate-600"></i>
                            <select name="cluster_id" id="edit-cluster" class="w-full bg-white/5 border border-white/10 focus:border-accent rounded-2xl pl-14 pr-10 py-4 text-sm font-bold text-white transition-all outline-none appearance-none cursor-pointer">
                                <option value="">None Assigned</option>
                                <?php foreach ($clusters as $cluster): ?>
                                    <option value="<?php echo $cluster['id']; ?>"><?php echo $cluster['name']; ?></option>
                                <?php endforeach; ?>
                            </select>
                            <i class="fas fa-chevron-down absolute right-5 top-1/2 -translate-y-1/2 text-slate-600 text-[10px] pointer-events-none"></i>
                        </div>
                    </div>

                    <div class="space-y-3">
                        <label class="block text-[9px] font-black text-slate-500 uppercase tracking-widest ml-1">Marital Status</label>
                        <div class="relative group">
                            <i class="fas fa-heart absolute left-5 top-1/2 -translate-y-1/2 text-slate-600"></i>
                            <select name="marital_status" id="edit-marital-status" class="w-full bg-white/5 border border-white/10 focus:border-accent rounded-2xl pl-14 pr-10 py-4 text-sm font-bold text-white transition-all outline-none appearance-none cursor-pointer">
                                <option value="single">Single</option>
                                <option value="married">Married</option>
                                <option value="widowed">Widowed</option>
                                <option value="divorced">Divorced</option>
                            </select>
                            <i class="fas fa-chevron-down absolute right-5 top-1/2 -translate-y-1/2 text-slate-600 text-[10px] pointer-events-none"></i>
                        </div>
                    </div>

                    <div class="sm:col-span-2 space-y-3 mt-4">
                        <label class="block text-[9px] font-black text-slate-500 uppercase tracking-widest ml-1">Residential Address</label>
                        <div class="relative group">
                            <i class="fas fa-map-location-dot absolute left-5 top-6 text-slate-600"></i>
                            <textarea name="address" id="edit-address" rows="3" class="w-full bg-white/5 border border-white/10 focus:border-accent rounded-3xl pl-14 pr-6 py-5 text-sm font-bold text-white transition-all outline-none resize-none" placeholder="Enter full address details..."></textarea>
                        </div>
                    </div>

                    <div class="sm:col-span-2 flex items-center space-x-6 mb-4 mt-8">
                        <span class="text-[10px] font-black text-accent uppercase tracking-[0.5em]">Family And Work</span>
                        <div class="h-[1px] flex-1 bg-white/5"></div>
                    </div>

                    <div class="space-y-3">
                        <label class="block text-[9px] font-black text-slate-500 uppercase tracking-widest ml-1">Name Of Spouse</label>
                        <div class="relative group">
                            <i class="fas fa-ring absolute left-5 top-1/2 -translate-y-1/2 text-slate-600 group-focus-within:text-accent transition-colors"></i>
                            <input type="text" name="spouse_name" id="edit-spouse-name" class="w-full bg-white/5 border border-white/10 focus:border-accent rounded-2xl pl-14 pr-6 py-4 text-sm font-bold text-white transition-all outline-none">
                        </div>
                    </div>

                    <div class="space-y-3">
                        <label class="block text-[9px] font-black text-slate-500 uppercase tracking-widest ml-1">Mother's Name</label>
                        <div class="relative group">
                            <i class="fas fa-person-dress absolute left-5 top-1/2 -translate-y-1/2 text-slate-600 group-focus-within:text-accent transition-colors"></i>
                            <input type="text" name="mother_name" id="edit-mother-name" class="w-full bg-white/5 border border-white/10 focus:border-accent rounded-2xl pl-14 pr-6 py-4 text-sm font-bold text-white transition-all outline-none">
                        </div>
                    </div>

                    <div class="space-y-3">
                        <label class="block text-[9px] font-black text-slate-500 uppercase tracking-widest ml-1">Father's Name</label>
                        <div class="relative group">
                            <i class="fas fa-person absolute left-5 top-1/2 -translate-y-1/2 text-slate-600 group-focus-within:text-accent transition-colors"></i>
                            <input type="text" name="father_name" id="edit-father-name" class="w-full bg-white/5 border border-white/10 focus:border-accent rounded-2xl pl-14 pr-6 py-4 text-sm font-bold text-white transition-all outline-none">
                        </div>
                    </div>

                    <div class="space-y-3">
                        <label class="block text-[9px] font-black text-slate-500 uppercase tracking-widest ml-1">Have You Been Baptized?</label>
                        <div class="relative group">
                            <i class="fas fa-water absolute left-5 top-1/2 -translate-y-1/2 text-slate-600"></i>
                            <select name="is_baptized" id="edit-is-baptized" class="w-full bg-white/5 border border-white/10 focus:border-accent rounded-2xl pl-14 pr-10 py-4 text-sm font-bold text-white transition-all outline-none appearance-none cursor-pointer">
                                <option value="0">No</option>
                                <option value="1">Yes</option>
                            </select>
                            <i class="fas fa-chevron-down absolute right-5 top-1/2 -translate-y-1/2 text-slate-600 text-[10px] pointer-events-none"></i>
                        </div>
                    </div>

                    <div class="sm:col-span-2 space-y-3">
                        <label class="block text-[9px] font-black text-slate-500 uppercase tracking-widest ml-1">Name Of Pastor Who Baptized You And The Church</label>
                        <div class="relative group">
                            <i class="fas fa-cross absolute left-5 top-1/2 -translate-y-1/2 text-slate-600 group-focus-within:text-accent transition-colors"></i>
                            <input type="text" name="baptism_pastor_church" id="edit-baptism-pastor-church" class="w-full bg-white/5 border border-white/10 focus:border-accent rounded-2xl pl-14 pr-6 py-4 text-sm font-bold text-white transition-all outline-none">
                        </div>
                    </div>

                    <div class="space-y-3">
                        <label class="block text-[9px] font-black text-slate-500 uppercase tracking-widest ml-1">Are You Currently Working?</label>
                        <div class="relative group">
                            <i class="fas fa-briefcase absolute left-5 top-1/2 -translate-y-1/2 text-slate-600"></i>
                            <select name="currently_working" id="edit-currently-working" class="w-full bg-white/5 border border-white/10 focus:border-accent rounded-2xl pl-14 pr-10 py-4 text-sm font-bold text-white transition-all outline-none appearance-none cursor-pointer">
                                <option value="0">No</option>
                                <option value="1">Yes</option>
                            </select>
                            <i class="fas fa-chevron-down absolute right-5 top-1/2 -translate-y-1/2 text-slate-600 text-[10px] pointer-events-none"></i>
                        </div>
                    </div>

                    <div class="space-y-3">
                        <label class="block text-[9px] font-black text-slate-500 uppercase tracking-widest ml-1">Name Of Work</label>
                        <div class="relative group">
                            <i class="fas fa-building-user absolute left-5 top-1/2 -translate-y-1/2 text-slate-600 group-focus-within:text-accent transition-colors"></i>
                            <input type="text" name="work_name" id="edit-work-name" class="w-full bg-white/5 border border-white/10 focus:border-accent rounded-2xl pl-14 pr-6 py-4 text-sm font-bold text-white transition-all outline-none">
                        </div>
                    </div>
                </div>

                <div class="mt-12 flex flex-col sm:flex-row gap-4">
                    <button type="submit" class="flex-1 bg-accent text-slate-900 py-5 rounded-2xl font-black text-xs uppercase tracking-[0.3em] hover-glow-yellow active:scale-95 transition-all shadow-xl shadow-yellow-500/10">
                        Commit Changes
                    </button>
                    <button type="button" onclick="closeModal('edit-modal', 'edit-modal-content')" class="px-10 py-5 bg-white/5 text-slate-400 rounded-2xl font-black text-xs uppercase tracking-[0.3em] hover:bg-white/10 transition-all">
                        Cancel
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<?php if ($canAddMember): ?>
<!-- Add Member Modal -->
<div id="add-modal" class="fixed inset-0 bg-slate-950/90 backdrop-blur-2xl z-50 hidden flex items-center justify-center p-4">
    <div class="glass-card w-full max-w-2xl rounded-[3.5rem] overflow-hidden shadow-2xl transform transition-all duration-500 scale-95 opacity-0 max-h-[90vh] flex flex-col border-white/10" id="add-modal-content">
        <!-- Modern Header -->
        <div class="px-10 py-10 bg-slate-900 relative overflow-hidden flex-shrink-0 border-b border-white/5">
            <div class="relative z-10 flex justify-between items-center text-white">
                <div>
                    <h3 class="text-3xl font-black tracking-tighter">Register Member</h3>
                    <p class="text-accent text-[10px] font-black mt-2 tracking-[0.3em] uppercase">Security Level: Administrative</p>
                </div>
                <button onclick="closeModal('add-modal', 'add-modal-content')" class="w-12 h-12 bg-white/5 hover:bg-accent hover:text-slate-900 rounded-2xl flex items-center justify-center transition-all border border-white/10">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        </div>

        <div class="flex-1 overflow-y-auto custom-scrollbar bg-slate-900/50">
            <!-- Excel Import Section (Condensed) -->
            <?php if (!$isDeptHead): ?>
            <div class="px-10 pt-8 pb-4">
                <div class="glass-card rounded-3xl p-6 border-accent/20 bg-accent/5">
                    <div class="flex items-center justify-between mb-4">
                        <div class="flex items-center">
                            <i class="fas fa-file-excel text-accent mr-3"></i>
                            <h4 class="text-[10px] font-black text-white uppercase tracking-widest">Bulk Import via Excel</h4>
                        </div>
                        <a href="<?php echo BASE_URL; ?>/members/template" class="text-[9px] font-black text-accent hover:text-white transition-colors uppercase tracking-widest flex items-center">
                            <i class="fas fa-download mr-1.5"></i> Download Template
                        </a>
                    </div>
                    <form action="<?php echo BASE_URL; ?>/members/import" method="POST" enctype="multipart/form-data" class="flex gap-3">
                        <input type="file" name="excel_file" accept=".csv, application/vnd.openxmlformats-officedocument.spreadsheetml.sheet, application/vnd.ms-excel" required 
                            class="flex-1 text-[10px] font-bold text-slate-400 file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-[10px] file:font-black file:bg-accent file:text-slate-900 hover:file:bg-white transition-all cursor-pointer">
                        <button type="submit" class="px-5 py-2 bg-white/10 hover:bg-white/20 text-white rounded-xl text-[10px] font-black uppercase tracking-widest transition-all">
                            Import
                        </button>
                    </form>
                </div>
            </div>
            <?php endif; ?>

            <div class="px-10 py-6 flex items-center space-x-6">
                <span class="text-[10px] font-black text-slate-500 uppercase tracking-[0.5em]">OR MANUAL ENTRY</span>
                <div class="h-[1px] flex-1 bg-white/5"></div>
            </div>

            <form action="<?php echo BASE_URL; ?>/members/store" method="POST" enctype="multipart/form-data" class="p-10 pt-0">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-8">
                    <!-- Photo Section -->
                    <div class="sm:col-span-2 flex flex-col items-center mb-8">
                        <div class="relative group">
                            <div id="add-photo-preview" class="w-36 h-36 bg-slate-800 rounded-[2.5rem] overflow-hidden flex items-center justify-center border-4 border-slate-800 shadow-2xl group-hover:border-accent transition-all duration-500">
                                <i class="fas fa-camera text-slate-700 text-4xl group-hover:text-accent"></i>
                            </div>
                            <label for="add-photo" class="absolute -bottom-2 -right-2 w-12 h-12 bg-accent text-slate-900 rounded-2xl flex items-center justify-center cursor-pointer hover:scale-110 transition-all shadow-xl z-20">
                                <i class="fas fa-plus text-sm"></i>
                            </label>
                            <input type="file" name="photo" id="add-photo" class="hidden" accept="image/*" capture="environment" onchange="previewImage(this, 'add-photo-preview')">
                        </div>
                        <p class="text-[9px] font-black text-slate-500 uppercase tracking-[0.3em] mt-6">Upload Profile Image</p>
                    </div>

                    <div class="sm:col-span-2 space-y-3">
                        <label class="block text-[9px] font-black text-slate-500 uppercase tracking-widest ml-1">Bio ID</label>
                        <div class="relative group">
                            <i class="fas fa-id-badge absolute left-5 top-1/2 -translate-y-1/2 text-slate-600 group-focus-within:text-accent transition-colors"></i>
                            <input type="text" name="bio_id" class="w-full bg-white/5 border border-white/10 focus:border-accent rounded-2xl pl-14 pr-6 py-4 text-sm font-bold text-white transition-all outline-none" placeholder="Enter Bio ID manually">
                        </div>
                        <p class="text-[10px] font-bold text-slate-500 ml-1">System ID is generated automatically. Bio ID is entered manually here.</p>
                    </div>

                    <!-- Personal Information -->
                    <div class="sm:col-span-2 flex items-center space-x-6 mb-4">
                        <span class="text-[10px] font-black text-accent uppercase tracking-[0.5em]">Identity</span>
                        <div class="h-[1px] flex-1 bg-white/5"></div>
                    </div>

                    <div class="space-y-3">
                        <label class="block text-[9px] font-black text-slate-500 uppercase tracking-widest ml-1">First Name</label>
                        <div class="relative group">
                            <i class="fas fa-user absolute left-5 top-1/2 -translate-y-1/2 text-slate-600 group-focus-within:text-accent transition-colors"></i>
                            <input type="text" name="first_name" required class="w-full bg-white/5 border border-white/10 focus:border-accent rounded-2xl pl-14 pr-6 py-4 text-sm font-bold text-white transition-all outline-none" placeholder="e.g. John">
                        </div>
                    </div>

                    <div class="space-y-3">
                        <label class="block text-[9px] font-black text-slate-500 uppercase tracking-widest ml-1">Last Name</label>
                        <div class="relative group">
                            <i class="fas fa-user absolute left-5 top-1/2 -translate-y-1/2 text-slate-600 group-focus-within:text-accent transition-colors"></i>
                            <input type="text" name="last_name" required class="w-full bg-white/5 border border-white/10 focus:border-accent rounded-2xl pl-14 pr-6 py-4 text-sm font-bold text-white transition-all outline-none" placeholder="e.g. Doe">
                        </div>
                    </div>

                    <div class="space-y-3">
                        <label class="block text-[9px] font-black text-slate-500 uppercase tracking-widest ml-1">Email Address</label>
                        <div class="relative group">
                            <i class="fas fa-envelope absolute left-5 top-1/2 -translate-y-1/2 text-slate-600 group-focus-within:text-accent transition-colors"></i>
                            <input type="email" name="email" class="w-full bg-white/5 border border-white/10 focus:border-accent rounded-2xl pl-14 pr-6 py-4 text-sm font-bold text-white transition-all outline-none" placeholder="john@example.com">
                        </div>
                    </div>

                    <div class="space-y-3">
                        <label class="block text-[9px] font-black text-slate-500 uppercase tracking-widest ml-1">Phone Number</label>
                        <div class="relative group">
                            <i class="fas fa-phone absolute left-5 top-1/2 -translate-y-1/2 text-slate-600 group-focus-within:text-accent transition-colors"></i>
                            <input type="text" name="phone" required class="w-full bg-white/5 border border-white/10 focus:border-accent rounded-2xl pl-14 pr-6 py-4 text-sm font-bold text-white transition-all outline-none" placeholder="e.g. 0240000000">
                        </div>
                    </div>

                    <div class="space-y-3">
                        <label class="block text-[9px] font-black text-slate-500 uppercase tracking-widest ml-1">Nationality</label>
                        <div class="relative group">
                            <i class="fas fa-flag absolute left-5 top-1/2 -translate-y-1/2 text-slate-600 group-focus-within:text-accent transition-colors"></i>
                            <input type="text" name="nationality" class="w-full bg-white/5 border border-white/10 focus:border-accent rounded-2xl pl-14 pr-6 py-4 text-sm font-bold text-white transition-all outline-none" placeholder="e.g. Ghanaian">
                        </div>
                    </div>

                    <div class="space-y-3">
                        <label class="block text-[9px] font-black text-slate-500 uppercase tracking-widest ml-1">Birth Date</label>
                        <div class="relative group">
                            <i class="fas fa-calendar-alt absolute left-5 top-1/2 -translate-y-1/2 text-accent"></i>
                            <input type="date" name="date_of_birth" class="w-full bg-accent/5 border border-accent/20 focus:border-accent rounded-2xl pl-14 pr-6 py-4 text-sm font-bold text-white transition-all outline-none [color-scheme:dark]">
                        </div>
                        <p class="text-[10px] font-bold text-slate-500 ml-1">Recommended for birthdays and age brackets.</p>
                    </div>

                    <div class="space-y-3">
                        <label class="block text-[9px] font-black text-slate-500 uppercase tracking-widest ml-1">Gender</label>
                        <div class="relative group">
                            <i class="fas fa-venus-mars absolute left-5 top-1/2 -translate-y-1/2 text-slate-600"></i>
                            <select name="gender" class="w-full bg-white/5 border border-white/10 focus:border-accent rounded-2xl pl-14 pr-10 py-4 text-sm font-bold text-white transition-all outline-none appearance-none cursor-pointer">
                                <option value="male">Male</option>
                                <option value="female">Female</option>
                                <option value="other">Other</option>
                            </select>
                            <i class="fas fa-chevron-down absolute right-5 top-1/2 -translate-y-1/2 text-slate-600 text-[10px] pointer-events-none"></i>
                        </div>
                    </div>

                    <!-- Church Section -->
                    <div class="sm:col-span-2 flex items-center space-x-6 mb-4 mt-8">
                        <span class="text-[10px] font-black text-accent uppercase tracking-[0.5em]">Church</span>
                        <div class="h-[1px] flex-1 bg-white/5"></div>
                    </div>

                    <div class="space-y-3">
                        <label class="block text-[9px] font-black text-slate-500 uppercase tracking-widest ml-1">Initial Status</label>
                        <div class="relative group">
                            <i class="fas fa-shield-check absolute left-5 top-1/2 -translate-y-1/2 text-accent"></i>
                            <select name="membership_status" class="w-full bg-accent/5 border border-accent/20 rounded-2xl pl-14 pr-10 py-4 text-sm font-black text-accent outline-none appearance-none">
                                <option value="Active">ACTIVE</option>
                            </select>
                            <i class="fas fa-lock absolute right-5 top-1/2 -translate-y-1/2 text-accent/30 text-[10px] pointer-events-none"></i>
                        </div>
                    </div>

                    <div class="space-y-3">
                        <label class="block text-[9px] font-black text-slate-500 uppercase tracking-widest ml-1">Primary Department</label>
                        <div class="relative group">
                            <i class="fas fa-building absolute left-5 top-1/2 -translate-y-1/2 text-slate-600"></i>
                            <?php if ($isDeptHead): ?>
                                <select name="department_id" class="w-full bg-white/5 border border-white/10 focus:border-accent rounded-2xl pl-14 pr-10 py-4 text-sm font-bold text-white transition-all outline-none appearance-none cursor-not-allowed opacity-70" disabled>
                                    <?php foreach ($departments as $dept): ?>
                                        <?php if ((int)$dept['id'] === $myDeptId): ?>
                                            <option value="<?php echo $dept['id']; ?>" selected><?php echo $dept['name']; ?></option>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                </select>
                                <input type="hidden" name="department_id" value="<?php echo (int)$myDeptId; ?>">
                            <?php else: ?>
                                <select name="department_id" class="w-full bg-white/5 border border-white/10 focus:border-accent rounded-2xl pl-14 pr-10 py-4 text-sm font-bold text-white transition-all outline-none appearance-none cursor-pointer">
                                    <option value="">None Assigned</option>
                                    <?php foreach ($departments as $dept): ?>
                                        <option value="<?php echo $dept['id']; ?>"><?php echo $dept['name']; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            <?php endif; ?>
                            <i class="fas fa-chevron-down absolute right-5 top-1/2 -translate-y-1/2 text-slate-600 text-[10px] pointer-events-none"></i>
                        </div>
                        <p class="text-[10px] font-bold text-slate-500 ml-1">Department heads remain restricted to this primary department only.</p>
                    </div>

                    <div class="space-y-3">
                        <label class="block text-[9px] font-black text-slate-500 uppercase tracking-widest ml-1">Additional Departments</label>
                        <?php if ($isDeptHead): ?>
                            <div class="w-full bg-white/5 border border-white/10 rounded-2xl px-4 py-4 text-sm font-bold text-slate-500 opacity-70">
                                Additional departments can be assigned by admins only.
                            </div>
                        <?php else: ?>
                            <select name="additional_department_ids[]" multiple size="5" class="w-full bg-white/5 border border-white/10 focus:border-accent rounded-2xl px-4 py-4 text-sm font-bold text-white transition-all outline-none custom-scrollbar">
                                <?php foreach ($departments as $dept): ?>
                                    <option value="<?php echo $dept['id']; ?>"><?php echo $dept['name']; ?></option>
                                <?php endforeach; ?>
                            </select>
                            <p class="text-[10px] font-bold text-slate-500 ml-1">Hold `Ctrl` or `Cmd` to select more than one extra department.</p>
                        <?php endif; ?>
                    </div>

                    <div class="space-y-3">
                        <label class="block text-[9px] font-black text-slate-500 uppercase tracking-widest ml-1">Cluster / Group</label>
                        <div class="relative group">
                            <i class="fas fa-layer-group absolute left-5 top-1/2 -translate-y-1/2 text-slate-600"></i>
                            <select name="cluster_id" class="w-full bg-white/5 border border-white/10 focus:border-accent rounded-2xl pl-14 pr-10 py-4 text-sm font-bold text-white transition-all outline-none appearance-none cursor-pointer">
                                <option value="">None Assigned</option>
                                <?php foreach ($clusters as $cluster): ?>
                                    <option value="<?php echo $cluster['id']; ?>"><?php echo $cluster['name']; ?></option>
                                <?php endforeach; ?>
                            </select>
                            <i class="fas fa-chevron-down absolute right-5 top-1/2 -translate-y-1/2 text-slate-600 text-[10px] pointer-events-none"></i>
                        </div>
                    </div>

                    <div class="space-y-3">
                        <label class="block text-[9px] font-black text-slate-500 uppercase tracking-widest ml-1">Marital Status</label>
                        <div class="relative group">
                            <i class="fas fa-heart absolute left-5 top-1/2 -translate-y-1/2 text-slate-600"></i>
                            <select name="marital_status" class="w-full bg-white/5 border border-white/10 focus:border-accent rounded-2xl pl-14 pr-10 py-4 text-sm font-bold text-white transition-all outline-none appearance-none cursor-pointer">
                                <option value="single">Single</option>
                                <option value="married">Married</option>
                                <option value="widowed">Widowed</option>
                                <option value="divorced">Divorced</option>
                            </select>
                            <i class="fas fa-chevron-down absolute right-5 top-1/2 -translate-y-1/2 text-slate-600 text-[10px] pointer-events-none"></i>
                        </div>
                    </div>

                    <div class="sm:col-span-2 space-y-3 mt-4">
                        <label class="block text-[9px] font-black text-slate-500 uppercase tracking-widest ml-1">Residential Address</label>
                        <div class="relative group">
                            <i class="fas fa-map-location-dot absolute left-5 top-6 text-slate-600"></i>
                            <textarea name="address" rows="3" class="w-full bg-white/5 border border-white/10 focus:border-accent rounded-3xl pl-14 pr-6 py-5 text-sm font-bold text-white transition-all outline-none resize-none" placeholder="Enter full address details..."></textarea>
                        </div>
                    </div>

                    <div class="sm:col-span-2 flex items-center space-x-6 mb-4 mt-8">
                        <span class="text-[10px] font-black text-accent uppercase tracking-[0.5em]">Family And Work</span>
                        <div class="h-[1px] flex-1 bg-white/5"></div>
                    </div>

                    <div class="space-y-3">
                        <label class="block text-[9px] font-black text-slate-500 uppercase tracking-widest ml-1">Name Of Spouse</label>
                        <div class="relative group">
                            <i class="fas fa-ring absolute left-5 top-1/2 -translate-y-1/2 text-slate-600 group-focus-within:text-accent transition-colors"></i>
                            <input type="text" name="spouse_name" class="w-full bg-white/5 border border-white/10 focus:border-accent rounded-2xl pl-14 pr-6 py-4 text-sm font-bold text-white transition-all outline-none" placeholder="If married">
                        </div>
                    </div>

                    <div class="space-y-3">
                        <label class="block text-[9px] font-black text-slate-500 uppercase tracking-widest ml-1">Mother's Name</label>
                        <div class="relative group">
                            <i class="fas fa-person-dress absolute left-5 top-1/2 -translate-y-1/2 text-slate-600 group-focus-within:text-accent transition-colors"></i>
                            <input type="text" name="mother_name" class="w-full bg-white/5 border border-white/10 focus:border-accent rounded-2xl pl-14 pr-6 py-4 text-sm font-bold text-white transition-all outline-none">
                        </div>
                    </div>

                    <div class="space-y-3">
                        <label class="block text-[9px] font-black text-slate-500 uppercase tracking-widest ml-1">Father's Name</label>
                        <div class="relative group">
                            <i class="fas fa-person absolute left-5 top-1/2 -translate-y-1/2 text-slate-600 group-focus-within:text-accent transition-colors"></i>
                            <input type="text" name="father_name" class="w-full bg-white/5 border border-white/10 focus:border-accent rounded-2xl pl-14 pr-6 py-4 text-sm font-bold text-white transition-all outline-none">
                        </div>
                    </div>

                    <div class="space-y-3">
                        <label class="block text-[9px] font-black text-slate-500 uppercase tracking-widest ml-1">Have You Been Baptized?</label>
                        <div class="relative group">
                            <i class="fas fa-water absolute left-5 top-1/2 -translate-y-1/2 text-slate-600"></i>
                            <select name="is_baptized" class="w-full bg-white/5 border border-white/10 focus:border-accent rounded-2xl pl-14 pr-10 py-4 text-sm font-bold text-white transition-all outline-none appearance-none cursor-pointer">
                                <option value="0">No</option>
                                <option value="1">Yes</option>
                            </select>
                            <i class="fas fa-chevron-down absolute right-5 top-1/2 -translate-y-1/2 text-slate-600 text-[10px] pointer-events-none"></i>
                        </div>
                    </div>

                    <div class="sm:col-span-2 space-y-3">
                        <label class="block text-[9px] font-black text-slate-500 uppercase tracking-widest ml-1">Name Of Pastor Who Baptized You And The Church</label>
                        <div class="relative group">
                            <i class="fas fa-cross absolute left-5 top-1/2 -translate-y-1/2 text-slate-600 group-focus-within:text-accent transition-colors"></i>
                            <input type="text" name="baptism_pastor_church" class="w-full bg-white/5 border border-white/10 focus:border-accent rounded-2xl pl-14 pr-6 py-4 text-sm font-bold text-white transition-all outline-none" placeholder="Pastor name and church">
                        </div>
                    </div>

                    <div class="space-y-3">
                        <label class="block text-[9px] font-black text-slate-500 uppercase tracking-widest ml-1">Are You Currently Working?</label>
                        <div class="relative group">
                            <i class="fas fa-briefcase absolute left-5 top-1/2 -translate-y-1/2 text-slate-600"></i>
                            <select name="currently_working" class="w-full bg-white/5 border border-white/10 focus:border-accent rounded-2xl pl-14 pr-10 py-4 text-sm font-bold text-white transition-all outline-none appearance-none cursor-pointer">
                                <option value="0">No</option>
                                <option value="1">Yes</option>
                            </select>
                            <i class="fas fa-chevron-down absolute right-5 top-1/2 -translate-y-1/2 text-slate-600 text-[10px] pointer-events-none"></i>
                        </div>
                    </div>

                    <div class="space-y-3">
                        <label class="block text-[9px] font-black text-slate-500 uppercase tracking-widest ml-1">Name Of Work</label>
                        <div class="relative group">
                            <i class="fas fa-building-user absolute left-5 top-1/2 -translate-y-1/2 text-slate-600 group-focus-within:text-accent transition-colors"></i>
                            <input type="text" name="work_name" class="w-full bg-white/5 border border-white/10 focus:border-accent rounded-2xl pl-14 pr-6 py-4 text-sm font-bold text-white transition-all outline-none" placeholder="If currently working">
                        </div>
                    </div>
                </div>

                <div class="mt-12 flex flex-col sm:flex-row gap-4">
                    <button type="submit" class="flex-1 bg-accent text-slate-900 py-5 rounded-2xl font-black text-xs uppercase tracking-[0.3em] hover-glow-yellow active:scale-95 transition-all shadow-xl shadow-yellow-500/10">
                        Register Member
                    </button>
                    <button type="button" onclick="closeModal('add-modal', 'add-modal-content')" class="px-10 py-5 bg-white/5 text-slate-400 rounded-2xl font-black text-xs uppercase tracking-[0.3em] hover:bg-white/10 transition-all">
                        Cancel
                    </button>
                </div>
            </form>
        </div>
    </div>
 </div>
<?php endif; ?>

<script>
    function boolValue(value) {
        return value === true || value === 1 || value === '1' || value === 'true' || value === 't';
    }

    function setMultiSelectValues(selectId, values) {
        const element = document.getElementById(selectId);
        if (!element) {
            return;
        }

        const selectedValues = Array.isArray(values)
            ? values.map(String)
            : String(values || '')
                .split(',')
                .map(value => value.trim())
                .filter(Boolean);

        Array.from(element.options).forEach(option => {
            option.selected = selectedValues.includes(option.value);
        });
    }

    function memberPhotoUrl(relative) {
        const baseUrl = <?php echo json_encode(BASE_URL); ?>;
        let p = String(relative || '').trim().replace(/\\/g, '/').replace(/^\/+/, '');
        if (!p) return '';
        if (p.startsWith('http://') || p.startsWith('https://')) return p;
        const publicIdx = p.indexOf('public/uploads/');
        if (publicIdx >= 0) p = p.slice(publicIdx);
        const uploadsIdx = p.indexOf('uploads/');
        if (uploadsIdx >= 0 && publicIdx < 0) p = p.slice(uploadsIdx);
        if (p.startsWith('uploads/')) p = 'public/' + p;
        return baseUrl + '/' + p;
    }

    async function viewMember(id) {
        const modal = document.getElementById('member-modal');
        const modalContent = document.getElementById('modal-content');
        
        try {
            const response = await fetch(`<?php echo BASE_URL; ?>/members/viewAjax?id=${id}`);
            const data = await response.json();

            if (data.error) {
                alert(data.error);
                return;
            }

            document.getElementById('modal-name').textContent = `${data.first_name} ${data.last_name}`;
            document.getElementById('modal-code').textContent = `Bio ID: ${data.bio_id || 'Not Set'}`;
            document.getElementById('modal-system-code').textContent = `System ID: ${data.member_code || 'Pending'}`;
            
            const avatarContainer = document.getElementById('modal-avatar');
            if (data.photo_path) {
                const src = memberPhotoUrl(data.photo_path);
                avatarContainer.innerHTML = src ? `<img src="${src}" class="w-full h-full rounded-[1.25rem] object-cover shadow-inner">` : `
                    <div class="w-full h-full bg-emerald-50 rounded-[1.25rem] flex items-center justify-center">
                        <span class="text-emerald-700 font-black text-3xl">${data.first_name.charAt(0).toUpperCase()}</span>
                    </div>`;
            } else {
                avatarContainer.innerHTML = `
                    <div class="w-full h-full bg-emerald-50 rounded-[1.25rem] flex items-center justify-center">
                        <span class="text-emerald-700 font-black text-3xl">${data.first_name.charAt(0).toUpperCase()}</span>
                    </div>`;
            }

            document.getElementById('modal-phone').textContent = data.phone || 'N/A';
            document.getElementById('modal-nationality').textContent = data.nationality || 'Nationality not provided';
            document.getElementById('modal-email').textContent = data.email || 'No email provided';
            document.getElementById('modal-address').textContent = data.address || 'No address provided';
            document.getElementById('modal-stays-at').textContent = data.stays_at || 'Stay location not provided';
            document.getElementById('modal-home-town').textContent = data.home_town || 'Home town not provided';
            document.getElementById('modal-dob').textContent = data.date_of_birth ? new Date(data.date_of_birth).toLocaleDateString('en-US', { month: 'long', day: 'numeric', year: 'numeric' }) : 'Not provided';
            document.getElementById('modal-dept').textContent = data.department_names || data.department_name || 'No department assigned';
            document.getElementById('modal-cluster').textContent = data.cluster_name || 'No cluster assigned';
            document.getElementById('modal-join-date').textContent = `Joined on ${new Date(data.join_date).toLocaleDateString('en-US', { month: 'long', day: 'numeric', year: 'numeric' })}`;
            document.getElementById('modal-spouse-name').textContent = data.spouse_name || 'No spouse name provided';
            document.getElementById('modal-baptism').textContent = boolValue(data.is_baptized) ? 'Baptized' : 'Not baptized yet';
            document.getElementById('modal-work-status').textContent = boolValue(data.currently_working) ? 'Currently working' : 'Not currently working';
            document.getElementById('modal-mother-name').textContent = data.mother_name || "Mother's name not provided";
            document.getElementById('modal-father-name').textContent = data.father_name || "Father's name not provided";
            document.getElementById('modal-baptism-pastor').textContent = data.baptism_pastor_church || 'Baptism pastor and church not provided';
            document.getElementById('modal-work-name').textContent = data.work_name || 'No work name provided';
            
            const statusBadge = document.getElementById('modal-status');
            statusBadge.textContent = 'ACTIVE';
            statusBadge.className = `px-4 py-1.5 rounded-full text-[10px] font-black uppercase tracking-widest border bg-emerald-50 text-emerald-700 border-emerald-100`;

            const editBtn = document.getElementById('modal-edit-btn');
            if (editBtn) {
                editBtn.onclick = () => {
                    closeModal('member-modal', 'modal-content');
                    setTimeout(() => editMember(data.id), 300);
                };
            }

            showModal('member-modal', 'modal-content');
        } catch (error) {
            console.error('Error:', error);
            alert('Failed to load member details.');
        }
    }

    async function editMember(id) {
        const modal = document.getElementById('edit-modal');
        const modalContent = document.getElementById('edit-modal-content');
        if (!modal || !modalContent) return;

        try {
            const response = await fetch(`<?php echo BASE_URL; ?>/members/viewAjax?id=${id}`);
            const data = await response.json();

            if (data.error) {
                alert(data.error);
                return;
            }

            // Populate Edit Form
            document.getElementById('edit-id').value = data.id;
            document.getElementById('edit-member-code').value = data.bio_id || '';
            document.getElementById('edit-first-name').value = data.first_name;
            document.getElementById('edit-last-name').value = data.last_name;
            document.getElementById('edit-email').value = data.email || '';
            document.getElementById('edit-phone').value = data.phone || '';
            document.getElementById('edit-nationality').value = data.nationality || '';
            document.getElementById('edit-dob').value = data.date_of_birth || '';
            document.getElementById('edit-stays-at').value = data.stays_at || '';
            document.getElementById('edit-home-town').value = data.home_town || '';
            document.getElementById('edit-status').value = data.membership_status;
            document.getElementById('edit-dept').value = data.department_id || '';
            setMultiSelectValues('edit-additional-depts', data.additional_department_ids_csv || '');
            document.getElementById('edit-position').value = data.position || '';
            document.getElementById('edit-cluster').value = data.cluster_id || '';
            document.getElementById('edit-marital-status').value = data.marital_status || 'single';
            document.getElementById('edit-gender').value = data.gender || 'male';
            document.getElementById('edit-address').value = data.address || '';
            document.getElementById('edit-spouse-name').value = data.spouse_name || '';
            document.getElementById('edit-mother-name').value = data.mother_name || '';
            document.getElementById('edit-father-name').value = data.father_name || '';
            document.getElementById('edit-is-baptized').value = boolValue(data.is_baptized) ? '1' : '0';
            document.getElementById('edit-baptism-pastor-church').value = data.baptism_pastor_church || '';
            document.getElementById('edit-currently-working').value = boolValue(data.currently_working) ? '1' : '0';
            document.getElementById('edit-work-name').value = data.work_name || '';

            // Handle Photo Preview
            const preview = document.getElementById('edit-photo-preview');
            if (data.photo_path) {
                const src = memberPhotoUrl(data.photo_path);
                preview.innerHTML = src ? `<img src="${src}" class="w-full h-full object-cover">` : `<i class="fas fa-camera text-gray-300 text-2xl group-hover:text-emerald-500"></i>`;
            } else {
                preview.innerHTML = `<i class="fas fa-camera text-gray-300 text-2xl group-hover:text-emerald-500"></i>`;
            }

            showModal('edit-modal', 'edit-modal-content');
        } catch (error) {
            console.error('Error:', error);
            alert('Failed to load edit form.');
        }
    }

    function previewImage(input, previewId) {
        const preview = document.getElementById(previewId);
        if (input.files && input.files[0]) {
            const reader = new FileReader();
            reader.onload = function(e) {
                preview.innerHTML = `<img src="${e.target.result}" class="w-full h-full object-cover">`;
            }
            reader.readAsDataURL(input.files[0]);
        }
    }

    function showModal(modalId, contentId) {
        const modal = document.getElementById(modalId);
        const content = document.getElementById(contentId);
        modal.classList.remove('hidden');
        setTimeout(() => {
            content.classList.remove('scale-95', 'opacity-0');
            content.classList.add('scale-100', 'opacity-100');
        }, 10);
    }

    function closeModal(modalId, contentId) {
        const modal = document.getElementById(modalId);
        const content = document.getElementById(contentId);
        content.classList.remove('scale-100', 'opacity-100');
        content.classList.add('scale-95', 'opacity-0');
        setTimeout(() => {
            modal.classList.add('hidden');
        }, 300);
    }

    // Close on backdrop click
    [document.getElementById('member-modal'), document.getElementById('edit-modal'), document.getElementById('add-modal')].forEach(m => {
        m.addEventListener('click', (e) => {
            if (e.target === m) closeModal(m.id, m.id === 'member-modal' ? 'modal-content' : (m.id === 'edit-modal' ? 'edit-modal-content' : 'add-modal-content'));
        });
    });
</script>
