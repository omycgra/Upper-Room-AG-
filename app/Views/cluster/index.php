<div class="flex flex-col sm:flex-row justify-between items-start mb-10 gap-4">
    <div>
        <h2 class="text-4xl sm:text-5xl font-black text-white tracking-tighter">Groups</h2>
        <p class="text-slate-400 font-bold mt-2 uppercase tracking-widest text-xs">Cell & Fellowship Management</p>
    </div>
    <button onclick="openGroupModal()" class="glass-card flex items-center px-6 py-3.5 bg-accent text-slate-900 rounded-2xl font-black text-xs uppercase tracking-widest hover:scale-[1.05] transition-all shadow-xl shadow-yellow-500/20">
        <i class="fas fa-plus mr-3"></i> Create Group
    </button>
</div>

<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
    <?php if (empty($clusters)): ?>
        <div class="col-span-full glass-card p-24 rounded-[3rem] border-white/5 text-center card-interaction">
            <div class="w-24 h-24 bg-white/5 rounded-[2rem] flex items-center justify-center mx-auto mb-8 border border-white/10">
                <i class="fas fa-layer-group text-slate-700 text-4xl"></i>
            </div>
            <h4 class="text-2xl font-black text-white mb-3">No Groups Active</h4>
            <p class="text-slate-500 font-bold uppercase tracking-widest text-xs mb-10">Start by organizing your first fellowship cell</p>
            <button onclick="openGroupModal()" class="inline-flex items-center px-10 py-5 bg-accent text-slate-900 rounded-2xl font-black text-xs uppercase tracking-widest hover-glow-yellow transition-all shadow-xl shadow-yellow-500/10">
                Initialize First Group
            </button>
        </div>
    <?php else: ?>
        <?php foreach ($clusters as $cluster): ?>
            <div class="glass-card rounded-[2.5rem] p-8 border-white/5 hover:bg-white/[0.03] transition-all duration-500 group relative overflow-hidden card-interaction">
                <div class="absolute top-0 right-0 -mr-10 -mt-10 w-40 h-40 bg-accent/5 rounded-full blur-3xl group-hover:bg-accent/10 transition-all duration-700"></div>
                
                <div class="relative z-10">
                    <div class="flex justify-between items-start mb-10">
                        <div class="w-16 h-16 bg-slate-800 rounded-2xl flex items-center justify-center border border-white/10 group-hover:bg-accent transition-all duration-500">
                            <i class="fas fa-location-dot text-accent group-hover:text-slate-900 text-2xl transition-colors"></i>
                        </div>
                        <div class="flex gap-3 opacity-0 group-hover:opacity-100 transform translate-x-4 group-hover:translate-x-0 transition-all duration-500">
                            <button onclick="editGroup(<?php echo $cluster['id']; ?>)" class="w-10 h-10 flex items-center justify-center rounded-xl bg-white/5 text-slate-400 hover-glow-yellow transition-all border border-white/5 shadow-lg">
                                <i class="fas fa-pen-nib text-xs"></i>
                            </button>
                            <a href="<?php echo BASE_URL; ?>/cluster/delete?id=<?php echo $cluster['id']; ?>" 
                               onclick="return confirm('Security Check: Permanent removal of this group?')"
                               class="w-10 h-10 flex items-center justify-center rounded-xl bg-white/5 text-slate-400 hover-glow-red transition-all border border-white/5 shadow-lg">
                                <i class="fas fa-trash-alt text-xs"></i>
                            </a>
                        </div>
                    </div>

                    <h4 class="text-2xl font-black text-white mb-3 tracking-tight"><?php echo $cluster['name']; ?></h4>
                    <div class="flex items-center space-x-2 text-[10px] font-black text-slate-500 uppercase tracking-widest mb-10">
                        <i class="fas fa-map-pin text-accent/60"></i> 
                        <span class="group-hover:text-slate-300 transition-colors"><?php echo $cluster['location'] ?: 'Global Location'; ?></span>
                    </div>

                    <div class="pt-8 border-t border-white/5 flex justify-between items-end">
                        <div class="flex-1">
                            <p class="text-[9px] font-black text-accent uppercase tracking-[0.3em] mb-2">Description</p>
                            <p class="text-xs font-bold text-slate-400 line-clamp-2 italic leading-relaxed pr-4 group-hover:text-slate-300 transition-colors">
                                <?php echo $cluster['description'] ?: 'No operational description provided for this group.'; ?>
                            </p>
                        </div>
                        <div class="w-12 h-12 bg-white/5 rounded-2xl flex items-center justify-center text-slate-600 group-hover:text-accent group-hover:bg-accent/10 border border-white/5 transition-all duration-500">
                            <i class="fas fa-arrow-right-long"></i>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<!-- Group Modal -->
<div id="group-modal" class="fixed inset-0 bg-slate-950/80 backdrop-blur-xl z-50 hidden flex items-center justify-center p-4">
    <div class="glass-card w-full max-w-lg rounded-[3.5rem] overflow-hidden shadow-2xl transform transition-all duration-500 scale-95 opacity-0 border-white/10 max-h-[90vh] flex flex-col" id="group-modal-content">
        <div class="px-10 py-10 bg-slate-900 relative overflow-hidden border-b border-white/5">
            <div class="relative z-10 flex justify-between items-center text-white">
                <div>
                    <h3 id="modal-title" class="text-3xl font-black tracking-tighter">Create Group</h3>
                    <p class="text-accent text-[10px] font-black mt-2 tracking-[0.3em] uppercase">Fellowship Infrastructure</p>
                </div>
                <button onclick="closeGroupModal()" class="w-12 h-12 bg-white/5 hover:bg-accent hover:text-slate-900 rounded-2xl flex items-center justify-center transition-all border border-white/10">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        </div>

        <form id="group-form" action="<?php echo BASE_URL; ?>/cluster/store" method="POST" class="p-6 sm:p-10 bg-slate-900/50 overflow-y-auto custom-scrollbar flex-1">
            <input type="hidden" name="id" id="group-id">
            <div class="space-y-8">
                <div class="space-y-3">
                    <label class="block text-[9px] font-black text-slate-500 uppercase tracking-widest ml-1">Group Name</label>
                    <div class="relative group">
                        <i class="fas fa-layer-group absolute left-5 top-1/2 -translate-y-1/2 text-slate-600 group-focus-within:text-accent transition-colors"></i>
                        <input type="text" name="name" id="group-name" required placeholder="e.g. Calvary Fellowship" class="w-full bg-white/5 border border-white/10 focus:border-accent rounded-2xl pl-14 pr-6 py-5 text-sm font-bold text-white transition-all outline-none">
                    </div>
                </div>

                <div class="space-y-3">
                    <label class="block text-[9px] font-black text-slate-500 uppercase tracking-widest ml-1">Operational Area</label>
                    <div class="relative group">
                        <i class="fas fa-location-dot absolute left-5 top-1/2 -translate-y-1/2 text-slate-600 group-focus-within:text-accent transition-colors"></i>
                        <input type="text" name="location" id="group-location" placeholder="e.g. North Ridge District" class="w-full bg-white/5 border border-white/10 focus:border-accent rounded-2xl pl-14 pr-6 py-5 text-sm font-bold text-white transition-all outline-none">
                    </div>
                </div>

                <div class="space-y-3">
                    <label class="block text-[9px] font-black text-slate-500 uppercase tracking-widest ml-1">Strategic Description</label>
                    <div class="relative group">
                        <i class="fas fa-align-left absolute left-5 top-6 text-slate-600 group-focus-within:text-accent transition-colors"></i>
                        <textarea name="description" id="group-description" rows="4" class="w-full bg-white/5 border border-white/10 focus:border-accent rounded-3xl pl-14 pr-6 py-6 text-sm font-bold text-white transition-all outline-none resize-none" placeholder="Purpose and mission of this fellowship group..."></textarea>
                    </div>
                </div>
            </div>

            <div class="mt-12 flex flex-col sm:flex-row gap-4">
                <button type="submit" class="flex-1 bg-accent text-slate-900 py-6 rounded-2xl font-black text-xs uppercase tracking-[0.3em] hover:scale-[1.02] active:scale-95 transition-all shadow-xl shadow-yellow-500/10">
                    Confirm & Initialize
                </button>
                <button type="button" onclick="closeGroupModal()" class="px-10 py-6 bg-white/5 text-slate-400 rounded-2xl font-black text-xs uppercase tracking-[0.3em] hover:bg-white/10 transition-all">
                    Discard
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    function openGroupModal() {
        document.getElementById('modal-title').textContent = 'Create Group';
        document.getElementById('group-form').action = '<?php echo BASE_URL; ?>/cluster/store';
        document.getElementById('group-id').value = '';
        document.getElementById('group-name').value = '';
        document.getElementById('group-location').value = '';
        document.getElementById('group-description').value = '';
        
        showModal('group-modal', 'group-modal-content');
    }

    async function editGroup(id) {
        try {
            const response = await fetch(`<?php echo BASE_URL; ?>/cluster/viewAjax?id=${id}`);
            const data = await response.json();

            if (data.error) {
                alert(data.error);
                return;
            }

            document.getElementById('modal-title').textContent = 'Edit Group';
            document.getElementById('group-form').action = '<?php echo BASE_URL; ?>/cluster/update';
            document.getElementById('group-id').value = data.id;
            document.getElementById('group-name').value = data.name;
            document.getElementById('group-location').value = data.location || '';
            document.getElementById('group-description').value = data.description || '';

            showModal('group-modal', 'group-modal-content');
        } catch (error) {
            console.error('Error:', error);
            alert('Failed to load group details.');
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

    function closeGroupModal() {
        const modal = document.getElementById('group-modal');
        const content = document.getElementById('group-modal-content');
        content.classList.remove('scale-100', 'opacity-100');
        content.classList.add('scale-95', 'opacity-0');
        setTimeout(() => {
            modal.classList.add('hidden');
        }, 300);
    }

    // Close on backdrop click
    document.getElementById('group-modal').addEventListener('click', (e) => {
        if (e.target === document.getElementById('group-modal')) closeGroupModal();
    });
</script>
