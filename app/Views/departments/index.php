<div class="flex flex-col sm:flex-row justify-between items-start mb-10 gap-4">
    <div>
        <h2 class="text-4xl sm:text-5xl font-black text-white tracking-tighter">Departments</h2>
        <p class="text-slate-400 font-bold mt-2 uppercase tracking-widest text-xs">Structure, leadership & oversight</p>
    </div>
    <button type="button" onclick="openDepartmentModal()" class="glass-card flex items-center px-6 py-3.5 bg-accent text-slate-900 rounded-2xl font-black text-xs uppercase tracking-widest hover:scale-[1.05] transition-all shadow-xl shadow-yellow-500/20">
        <i class="fas fa-plus mr-3"></i> Create Department
    </button>
</div>

<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
    <?php if (empty($departments)): ?>
        <div class="col-span-full glass-card p-24 rounded-[3rem] border-white/5 text-center card-interaction">
            <div class="w-24 h-24 bg-white/5 rounded-[2rem] flex items-center justify-center mx-auto mb-8 border border-white/10">
                <i class="fas fa-sitemap text-slate-700 text-4xl"></i>
            </div>
            <h4 class="text-2xl font-black text-white mb-3">No Departments Created</h4>
            <p class="text-slate-500 font-bold uppercase tracking-widest text-xs mb-10">Create departments and assign leadership</p>
            <button type="button" onclick="openDepartmentModal()" class="inline-flex items-center px-10 py-5 bg-accent text-slate-900 rounded-2xl font-black text-xs uppercase tracking-widest hover-glow-yellow transition-all shadow-xl shadow-yellow-500/10">
                Initialize First Department
            </button>
        </div>
    <?php else: ?>
        <?php foreach ($departments as $dept): ?>
            <div class="glass-card rounded-[2.5rem] p-8 border-white/5 hover:bg-white/[0.03] transition-all duration-500 group relative overflow-hidden card-interaction">
                <div class="absolute top-0 right-0 -mr-10 -mt-10 w-40 h-40 bg-accent/5 rounded-full blur-3xl group-hover:bg-accent/10 transition-all duration-700"></div>
                <div class="relative z-10">
                    <div class="flex justify-between items-start mb-10">
                        <div class="w-16 h-16 bg-slate-800 rounded-2xl flex items-center justify-center border border-white/10 group-hover:bg-accent transition-all duration-500">
                            <i class="fas fa-sitemap text-accent group-hover:text-slate-900 text-2xl transition-colors"></i>
                        </div>
                        <div class="flex gap-3 opacity-0 group-hover:opacity-100 transform translate-x-4 group-hover:translate-x-0 transition-all duration-500">
                            <button type="button" onclick="openDepartmentModal(<?php echo (int)$dept['id']; ?>)" class="w-10 h-10 flex items-center justify-center rounded-xl bg-white/5 text-slate-400 hover-glow-yellow transition-all border border-white/5 shadow-lg">
                                <i class="fas fa-pen-nib text-xs"></i>
                            </button>
                            <a href="<?php echo BASE_URL; ?>/departments/delete?id=<?php echo (int)$dept['id']; ?>"
                               onclick="return confirm('Security Check: Permanent removal of this department?')"
                               class="w-10 h-10 flex items-center justify-center rounded-xl bg-white/5 text-slate-400 hover-glow-red transition-all border border-white/5 shadow-lg">
                                <i class="fas fa-trash-alt text-xs"></i>
                            </a>
                        </div>
                    </div>

                    <h4 class="text-2xl font-black text-white mb-4 tracking-tight"><?php echo htmlspecialchars($dept['name']); ?></h4>

                    <div class="space-y-3 mb-10">
                        <div class="flex items-center text-[10px] font-black text-slate-500 uppercase tracking-widest">
                            <i class="fas fa-user-shield text-accent/60 mr-3"></i>
                            <span class="group-hover:text-slate-300 transition-colors">
                                <?php
                                    $headName = trim(($dept['head_first_name'] ?? '') . ' ' . ($dept['head_last_name'] ?? ''));
                                    echo $headName !== '' ? htmlspecialchars($headName) . ' ' . '<span class="text-accent/60">(' . htmlspecialchars($dept['head_member_code'] ?? '') . ')</span>' : 'No Head Assigned';
                                ?>
                            </span>
                        </div>
                    </div>

                    <div class="pt-8 border-t border-white/5">
                        <p class="text-[9px] font-black text-accent uppercase tracking-[0.3em] mb-2">Description</p>
                        <p class="text-xs font-bold text-slate-400 line-clamp-3 italic leading-relaxed group-hover:text-slate-300 transition-colors">
                            <?php echo htmlspecialchars($dept['description'] ?: 'No departmental description provided.'); ?>
                        </p>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<div id="department-modal" class="fixed inset-0 bg-slate-950/80 backdrop-blur-xl z-50 hidden flex items-center justify-center p-4">
    <div class="glass-card w-full max-w-lg rounded-[3.5rem] overflow-hidden shadow-2xl transform transition-all duration-500 scale-95 opacity-0 border-white/10 max-h-[90vh] flex flex-col" id="department-modal-content">
        <div class="px-10 py-10 bg-slate-900 relative overflow-hidden border-b border-white/5">
            <div class="relative z-10 flex justify-between items-center text-white">
                <div>
                    <h3 id="department-modal-title" class="text-3xl font-black tracking-tighter">Create Department</h3>
                    <p class="text-accent text-[10px] font-black mt-2 tracking-[0.3em] uppercase">Organizational Control</p>
                </div>
                <button type="button" onclick="closeDepartmentModal()" class="w-12 h-12 bg-white/5 hover:bg-accent hover:text-slate-900 rounded-2xl flex items-center justify-center transition-all border border-white/10">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        </div>

        <form id="department-form" action="<?php echo BASE_URL; ?>/departments/store" method="POST" class="p-6 sm:p-10 bg-slate-900/50 overflow-y-auto custom-scrollbar flex-1">
            <input type="hidden" name="id" id="department-id">
            <div class="space-y-8">
                <div class="space-y-3">
                    <label class="block text-[9px] font-black text-slate-500 uppercase tracking-widest ml-1">Department Name</label>
                    <div class="relative group">
                        <i class="fas fa-sitemap absolute left-5 top-1/2 -translate-y-1/2 text-slate-600 group-focus-within:text-accent transition-colors"></i>
                        <input type="text" name="name" id="department-name" required placeholder="e.g. Youth Ministry" class="w-full bg-white/5 border border-white/10 focus:border-accent rounded-2xl pl-14 pr-6 py-5 text-sm font-bold text-white transition-all outline-none">
                    </div>
                </div>

                <div class="space-y-3">
                    <label class="block text-[9px] font-black text-slate-500 uppercase tracking-widest ml-1">Department Head</label>
                    <div class="relative group">
                        <i class="fas fa-user-shield absolute left-5 top-1/2 -translate-y-1/2 text-slate-600"></i>
                        <select name="head_member_id" id="department-head" class="w-full bg-white/5 border border-white/10 focus:border-accent rounded-2xl pl-14 pr-10 py-5 text-sm font-bold text-white transition-all outline-none appearance-none cursor-pointer">
                            <option value="">No Head Assigned</option>
                            <?php foreach ($members as $m): ?>
                                <option value="<?php echo (int)$m['id']; ?>">
                                    <?php echo htmlspecialchars($m['first_name'] . ' ' . $m['last_name'] . ' (' . $m['member_code'] . ')'); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <i class="fas fa-chevron-down absolute right-5 top-1/2 -translate-y-1/2 text-slate-600 text-[10px] pointer-events-none"></i>
                    </div>
                </div>

                <div class="space-y-3">
                    <label class="block text-[9px] font-black text-slate-500 uppercase tracking-widest ml-1">Strategic Description</label>
                    <div class="relative group">
                        <i class="fas fa-align-left absolute left-5 top-6 text-slate-600 group-focus-within:text-accent transition-colors"></i>
                        <textarea name="description" id="department-description" rows="4" class="w-full bg-white/5 border border-white/10 focus:border-accent rounded-3xl pl-14 pr-6 py-6 text-sm font-bold text-white transition-all outline-none resize-none" placeholder="Purpose and responsibility of this department..."></textarea>
                    </div>
                </div>
            </div>

            <div class="mt-12 flex flex-col sm:flex-row gap-4">
                <button type="submit" class="flex-1 bg-accent text-slate-900 py-6 rounded-2xl font-black text-xs uppercase tracking-[0.3em] hover:scale-[1.02] active:scale-95 transition-all shadow-xl shadow-yellow-500/10">
                    Confirm & Save
                </button>
                <button type="button" onclick="closeDepartmentModal()" class="px-10 py-6 bg-white/5 text-slate-400 rounded-2xl font-black text-xs uppercase tracking-[0.3em] hover:bg-white/10 transition-all">
                    Discard
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    const departments = <?php echo json_encode($departments ?? []); ?>;

    function showDepartmentModal() {
        const modal = document.getElementById('department-modal');
        const content = document.getElementById('department-modal-content');
        modal.classList.remove('hidden');
        setTimeout(() => {
            content.classList.remove('scale-95', 'opacity-0');
            content.classList.add('scale-100', 'opacity-100');
        }, 10);
    }

    function closeDepartmentModal() {
        const modal = document.getElementById('department-modal');
        const content = document.getElementById('department-modal-content');
        content.classList.remove('scale-100', 'opacity-100');
        content.classList.add('scale-95', 'opacity-0');
        setTimeout(() => {
            modal.classList.add('hidden');
        }, 300);
    }

    function openDepartmentModal(id = null) {
        const title = document.getElementById('department-modal-title');
        const form = document.getElementById('department-form');

        const idInput = document.getElementById('department-id');
        const nameInput = document.getElementById('department-name');
        const headInput = document.getElementById('department-head');
        const descInput = document.getElementById('department-description');

        if (!id) {
            title.textContent = 'Create Department';
            form.action = '<?php echo BASE_URL; ?>/departments/store';
            idInput.value = '';
            nameInput.value = '';
            headInput.value = '';
            descInput.value = '';
            showDepartmentModal();
            return;
        }

        const dept = (departments || []).find(d => String(d.id) === String(id));
        if (!dept) {
            alert('Department not found');
            return;
        }

        title.textContent = 'Edit Department';
        form.action = '<?php echo BASE_URL; ?>/departments/update';
        idInput.value = dept.id;
        nameInput.value = dept.name || '';
        headInput.value = dept.head_member_id || '';
        descInput.value = dept.description || '';
        showDepartmentModal();
    }

    document.getElementById('department-modal').addEventListener('click', (e) => {
        if (e.target === document.getElementById('department-modal')) closeDepartmentModal();
    });
</script>
