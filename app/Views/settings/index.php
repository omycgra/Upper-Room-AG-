<?php $isAdmin = (Session::get('user_role') === 'admin'); ?>
<div class="flex flex-col sm:flex-row justify-between items-start mb-10 gap-4">
    <div>
        <h2 class="text-4xl sm:text-5xl font-black text-white tracking-tighter">Settings</h2>
        <p class="text-slate-400 font-bold mt-2 uppercase tracking-widest text-xs">Global Configuration & User Management</p>
    </div>
</div>

<div class="grid grid-cols-1 xl:grid-cols-3 gap-6 lg:gap-8">
    <!-- User Management Section -->
    <div class="xl:col-span-2 space-y-8">
        <div class="glass-card rounded-[2.5rem] sm:rounded-[3rem] border-white/5 overflow-hidden card-interaction">
            <div class="px-6 sm:px-8 lg:px-10 py-6 sm:py-8 border-b border-white/5 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 bg-white/[0.02]">
                <div class="flex items-center">
                    <div class="w-10 h-10 bg-accent/10 rounded-xl flex items-center justify-center mr-4 border border-accent/20">
                        <i class="fas fa-user-shield text-accent text-sm"></i>
                    </div>
                    <h4 class="text-xl font-black text-white tracking-tight">System Administrators</h4>
                </div>
                <?php if ($isAdmin): ?>
                <button onclick="document.getElementById('add-user-modal').classList.remove('hidden')" 
                    class="w-full sm:w-auto bg-accent text-slate-900 px-6 py-3 rounded-xl text-[10px] font-black uppercase tracking-widest hover-glow-yellow transition-all shadow-lg shadow-yellow-500/20">
                    <i class="fas fa-plus mr-2"></i> New Admin
                </button>
                <?php endif; ?>
            </div>
            <div class="md:hidden p-4 sm:p-6 space-y-4">
                <?php if (!empty($users) && is_array($users)): foreach ($users as $user): ?>
                    <div class="glass-card rounded-[2rem] p-4 sm:p-5 border-white/10">
                        <div class="flex items-start justify-between gap-4">
                            <div class="flex items-center min-w-0">
                                <div class="w-12 h-12 bg-slate-800 rounded-2xl flex items-center justify-center border border-white/10 mr-4 shrink-0">
                                    <span class="text-accent font-black text-lg"><?php echo strtoupper(substr($user['name'], 0, 1)); ?></span>
                                </div>
                                <div class="min-w-0">
                                    <p class="text-sm font-black text-slate-200 truncate"><?php echo $user['name']; ?></p>
                                    <p class="text-[10px] font-bold text-slate-500 uppercase tracking-widest mt-1 break-all"><?php echo $user['email']; ?></p>
                                </div>
                            </div>
                            <span class="px-3 py-1.5 text-[9px] font-black rounded-full uppercase tracking-widest border shrink-0 <?php echo $user['role'] === 'admin' ? 'bg-accent/10 text-accent border-accent/20 shadow-[0_0_15px_rgba(251,191,36,0.1)]' : 'bg-white/5 text-slate-400 border-white/10'; ?>">
                                <?php echo $user['role']; ?>
                            </span>
                        </div>
                        <div class="mt-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                            <div class="flex items-center text-xs font-bold text-slate-400">
                                <i class="far fa-clock text-[10px] text-accent/40 mr-3"></i>
                                <?php echo $user['last_login'] ? date('M d, H:i', strtotime($user['last_login'])) : 'Never active'; ?>
                            </div>
                            <?php if ($user['id'] != Session::get('user_id')): ?>
                                <?php if ($isAdmin): ?>
                                    <div class="flex flex-col sm:flex-row gap-2 w-full sm:w-auto">
                                        <button type="button"
                                            data-reset-password="1"
                                            data-user-id="<?php echo (int)$user['id']; ?>"
                                            data-user-name="<?php echo htmlspecialchars((string)$user['name']); ?>"
                                            data-user-email="<?php echo htmlspecialchars((string)$user['email']); ?>"
                                            class="inline-flex w-full sm:w-auto h-10 items-center justify-center rounded-xl bg-white/5 px-4 text-slate-400 hover:bg-white/10 transition-all duration-500 border border-white/5">
                                            <i class="fas fa-key text-xs mr-2"></i><span class="text-[10px] font-black uppercase tracking-widest">Reset Password</span>
                                        </button>
                                        <a href="settings/user/delete?id=<?php echo $user['id']; ?>" 
                                           onclick="return confirm('Security Check: Permanent deletion of this account?')"
                                           class="inline-flex w-full sm:w-auto h-10 items-center justify-center rounded-xl bg-white/5 px-4 text-slate-500 hover-glow-red transition-all duration-500 border border-white/5">
                                            <i class="fas fa-trash-alt text-xs mr-2"></i><span class="text-[10px] font-black uppercase tracking-widest">Delete</span>
                                        </a>
                                    </div>
                                <?php endif; ?>
                            <?php else: ?>
                                <div class="flex items-center text-emerald-400 text-[10px] font-black uppercase tracking-widest">
                                    <div class="w-2 h-2 rounded-full bg-emerald-400 mr-2 animate-pulse"></div> Current Session
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; endif; ?>
            </div>
            <div class="hidden md:block overflow-x-auto custom-scrollbar">
                <table class="w-full text-left">
                    <thead>
                        <tr class="text-[10px] font-black text-slate-500 uppercase tracking-[0.3em] border-b border-white/5 bg-white/[0.01]">
                            <th class="px-10 py-6">Admin Profile</th>
                            <th class="px-10 py-6">Privileges</th>
                            <th class="px-10 py-6">Last Login</th>
                            <th class="px-10 py-6 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-white/[0.02]">
                        <?php if (!empty($users) && is_array($users)): foreach ($users as $user): ?>
                            <tr class="hover:bg-white/[0.03] transition-all duration-300 group">
                                <td class="px-10 py-6">
                                    <div class="flex items-center">
                                        <div class="w-12 h-12 bg-slate-800 rounded-2xl flex items-center justify-center border border-white/10 group-hover:bg-accent transition-all duration-500 mr-4">
                                            <span class="text-accent group-hover:text-slate-900 font-black text-lg transition-colors"><?php echo strtoupper(substr($user['name'], 0, 1)); ?></span>
                                        </div>
                                        <div>
                                            <p class="text-sm font-black text-slate-200 group-hover:text-white transition-colors tracking-tight"><?php echo $user['name']; ?></p>
                                            <p class="text-[10px] font-bold text-slate-500 uppercase tracking-widest mt-1"><?php echo $user['email']; ?></p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-10 py-6">
                                    <span class="px-4 py-1.5 text-[9px] font-black rounded-full uppercase tracking-widest border <?php echo $user['role'] === 'admin' ? 'bg-accent/10 text-accent border-accent/20 shadow-[0_0_15px_rgba(251,191,36,0.1)]' : 'bg-white/5 text-slate-400 border-white/10'; ?>">
                                        <?php echo $user['role']; ?>
                                    </span>
                                </td>
                                <td class="px-10 py-6">
                                    <div class="flex items-center text-xs font-bold text-slate-400">
                                        <i class="far fa-clock text-[10px] text-accent/40 mr-3"></i>
                                        <?php echo $user['last_login'] ? date('M d, H:i', strtotime($user['last_login'])) : 'Never active'; ?>
                                    </div>
                                </td>
                                <td class="px-10 py-6 text-right">
                                    <?php if ($user['id'] != Session::get('user_id')): ?>
                                        <?php if ($isAdmin): ?>
                                            <div class="inline-flex items-center gap-2 justify-end">
                                                <button type="button"
                                                    data-reset-password="1"
                                                    data-user-id="<?php echo (int)$user['id']; ?>"
                                                    data-user-name="<?php echo htmlspecialchars((string)$user['name']); ?>"
                                                    data-user-email="<?php echo htmlspecialchars((string)$user['email']); ?>"
                                                    class="w-10 h-10 inline-flex items-center justify-center rounded-xl bg-white/5 text-slate-400 hover:bg-white/10 transition-all duration-500 border border-white/5">
                                                    <i class="fas fa-key text-xs"></i>
                                                </button>
                                                <a href="settings/user/delete?id=<?php echo $user['id']; ?>" 
                                                   onclick="return confirm('Security Check: Permanent deletion of this account?')"
                                                   class="w-10 h-10 inline-flex items-center justify-center rounded-xl bg-white/5 text-slate-500 hover-glow-red transition-all duration-500 border border-white/5">
                                                    <i class="fas fa-trash-alt text-xs"></i>
                                                </a>
                                            </div>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <div class="flex items-center justify-end text-emerald-400 text-[10px] font-black uppercase tracking-widest">
                                            <div class="w-2 h-2 rounded-full bg-emerald-400 mr-2 animate-pulse"></div> Current Session
                                        </div>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Global Settings Sidebar -->
    <div class="space-y-8">
        <div class="glass-card rounded-[2.5rem] sm:rounded-[3rem] p-6 sm:p-8 lg:p-10 border-white/5 card-interaction">
            <div class="flex items-center justify-between mb-8 sm:mb-10">
                <div class="flex items-center">
                    <div class="w-12 h-12 bg-accent/10 rounded-2xl flex items-center justify-center mr-4 border border-accent/20 overflow-hidden">
                        <?php if (!empty($me['photo_path'])): ?>
                            <img src="<?php echo BASE_URL . '/' . $me['photo_path']; ?>" alt="Me" class="w-full h-full object-cover">
                        <?php else: ?>
                            <i class="fas fa-user text-accent text-lg"></i>
                        <?php endif; ?>
                    </div>
                    <div>
                        <h4 class="text-xl font-black text-white tracking-tight">My Profile</h4>
                        <p class="text-[10px] font-black text-slate-500 uppercase tracking-widest mt-1"><?php echo $me['role'] ?? 'admin'; ?></p>
                    </div>
                </div>
            </div>

            <form action="<?php echo BASE_URL; ?>/settings/updateProfile" method="POST" enctype="multipart/form-data" class="space-y-6">
                <div class="space-y-3">
                    <label class="block text-[9px] font-black text-slate-500 uppercase tracking-widest ml-1">Full Name</label>
                    <div class="relative group">
                        <i class="fas fa-user absolute left-5 top-1/2 -translate-y-1/2 text-slate-600 group-focus-within:text-accent transition-colors"></i>
                        <input type="text" name="name" value="<?php echo htmlspecialchars($me['name'] ?? ''); ?>" required class="w-full bg-white/5 border border-white/10 focus:border-accent rounded-2xl pl-14 pr-6 py-4 text-sm font-bold text-white transition-all outline-none">
                    </div>
                </div>

                <div class="space-y-3">
                    <label class="block text-[9px] font-black text-slate-500 uppercase tracking-widest ml-1">Username</label>
                    <div class="relative group">
                        <i class="fas fa-at absolute left-5 top-1/2 -translate-y-1/2 text-slate-600 group-focus-within:text-accent transition-colors"></i>
                        <input type="text" name="username" value="<?php echo htmlspecialchars($me['username'] ?? ''); ?>" class="w-full bg-white/5 border border-white/10 focus:border-accent rounded-2xl pl-14 pr-6 py-4 text-sm font-bold text-white transition-all outline-none">
                    </div>
                </div>

                <div class="space-y-3">
                    <label class="block text-[9px] font-black text-slate-500 uppercase tracking-widest ml-1">Email</label>
                    <div class="relative group">
                        <i class="fas fa-envelope absolute left-5 top-1/2 -translate-y-1/2 text-slate-600 group-focus-within:text-accent transition-colors"></i>
                        <input type="email" name="email" value="<?php echo htmlspecialchars($me['email'] ?? ''); ?>" required class="w-full bg-white/5 border border-white/10 focus:border-accent rounded-2xl pl-14 pr-6 py-4 text-sm font-bold text-white transition-all outline-none">
                    </div>
                </div>

                <div class="space-y-3">
                    <label class="block text-[9px] font-black text-slate-500 uppercase tracking-widest ml-1">New Password</label>
                    <div class="relative group">
                        <i class="fas fa-key absolute left-5 top-1/2 -translate-y-1/2 text-slate-600 group-focus-within:text-accent transition-colors"></i>
                        <input type="password" name="password" class="w-full bg-white/5 border border-white/10 focus:border-accent rounded-2xl pl-14 pr-6 py-4 text-sm font-bold text-white transition-all outline-none" placeholder="Leave blank to keep current">
                    </div>
                </div>

                <div class="space-y-3">
                    <label class="block text-[9px] font-black text-slate-500 uppercase tracking-widest ml-1">Profile Photo</label>
                    <div class="relative group">
                        <i class="fas fa-image absolute left-5 top-1/2 -translate-y-1/2 text-slate-600"></i>
                        <input type="file" name="photo" accept="image/*" class="w-full bg-white/5 border border-white/10 rounded-2xl pl-14 pr-6 py-4 text-[10px] font-black text-slate-400 transition-all outline-none file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-[10px] file:font-black file:bg-accent file:text-slate-900 hover:file:bg-white cursor-pointer">
                    </div>
                </div>

                <button type="submit" class="w-full bg-accent text-slate-900 py-5 rounded-2xl font-black text-xs uppercase tracking-[0.2em] hover-glow-yellow active:scale-95 transition-all shadow-xl shadow-yellow-500/10">
                    Save Profile
                </button>
            </form>
        </div>

        <div class="glass-card rounded-[2.5rem] sm:rounded-[3rem] p-6 sm:p-8 lg:p-10 border-white/5 card-interaction">
            <div class="flex items-center mb-10">
                <div class="w-12 h-12 bg-accent/10 rounded-2xl flex items-center justify-center mr-4 border border-accent/20">
                    <i class="fas fa-church text-accent text-lg"></i>
                </div>
                <h4 class="text-xl font-black text-white tracking-tight">Church Identity</h4>
            </div>
            
            <form action="<?php echo BASE_URL; ?>/settings/updateBranding" method="POST" enctype="multipart/form-data" class="space-y-8">
                <div class="space-y-3">
                    <label class="block text-[9px] font-black text-slate-500 uppercase tracking-widest ml-1">Official Name</label>
                    <div class="relative group">
                        <i class="fas fa-signature absolute left-5 top-1/2 -translate-y-1/2 text-slate-600 group-focus-within:text-accent transition-colors"></i>
                        <input type="text" name="church_name" value="<?php echo htmlspecialchars($churchName ?? ''); ?>" required class="w-full bg-white/5 border border-white/10 focus:border-accent rounded-2xl pl-14 pr-6 py-4 text-sm font-bold text-white transition-all outline-none">
                    </div>
                </div>

                <div class="space-y-3">
                    <label class="block text-[9px] font-black text-slate-500 uppercase tracking-widest ml-1">Church Logo</label>
                    <div class="rounded-[2rem] border border-white/10 bg-white/5 p-5">
                        <div class="flex items-center gap-4">
                            <div class="w-16 h-16 rounded-2xl flex items-center justify-center overflow-hidden border border-white/10 bg-slate-900/60 shrink-0">
                                <?php if (!empty($churchLogoPath)): ?>
                                    <img src="<?php echo BASE_URL . '/' . ltrim($churchLogoPath, '/'); ?>" alt="Church Logo" class="w-full h-full object-contain p-1">
                                <?php else: ?>
                                    <i class="fas fa-church text-accent text-xl"></i>
                                <?php endif; ?>
                            </div>
                            <div class="min-w-0">
                                <p class="text-sm font-black text-slate-200">Current branding logo</p>
                                <p class="text-[10px] font-black text-slate-500 uppercase tracking-widest mt-1">Upload a PNG, JPG, GIF, or WEBP image up to 2MB</p>
                            </div>
                        </div>
                    </div>
                    <div class="relative group">
                        <i class="fas fa-image absolute left-5 top-1/2 -translate-y-1/2 text-slate-600"></i>
                        <input type="file" name="church_logo" accept="image/png,image/jpeg,image/gif,image/webp" class="w-full bg-white/5 border border-white/10 rounded-2xl pl-14 pr-6 py-4 text-[10px] font-black text-slate-400 transition-all outline-none file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-[10px] file:font-black file:bg-accent file:text-slate-900 hover:file:bg-white cursor-pointer">
                    </div>
                </div>

                <div class="space-y-3">
                    <label class="block text-[9px] font-black text-slate-500 uppercase tracking-widest ml-1">Default Theme</label>
                    <div class="relative">
                        <i class="fas fa-palette absolute left-5 top-1/2 -translate-y-1/2 text-slate-600"></i>
                        <select name="theme" class="w-full bg-white/5 border border-white/10 focus:border-accent rounded-2xl pl-14 pr-10 py-4 text-sm font-bold text-white transition-all outline-none appearance-none cursor-pointer">
                            <option value="dark" <?php echo ($theme ?? 'dark') === 'dark' ? 'selected' : ''; ?>>Dark Theme</option>
                            <option value="light" <?php echo ($theme ?? 'dark') === 'light' ? 'selected' : ''; ?>>Light Theme</option>
                            <option value="ocean" <?php echo ($theme ?? 'dark') === 'ocean' ? 'selected' : ''; ?>>Ocean Theme</option>
                            <option value="sunset" <?php echo ($theme ?? 'dark') === 'sunset' ? 'selected' : ''; ?>>Sunset Theme</option>
                        </select>
                        <i class="fas fa-chevron-down absolute right-5 top-1/2 -translate-y-1/2 text-slate-600 text-[10px]"></i>
                    </div>
                    <p class="text-[10px] font-black text-slate-500 uppercase tracking-widest">Used on login and the main application layout</p>
                </div>

                <div class="space-y-3 opacity-50">
                    <label class="block text-[9px] font-black text-slate-500 uppercase tracking-widest ml-1">System Timezone</label>
                    <div class="relative">
                        <i class="fas fa-globe absolute left-5 top-1/2 -translate-y-1/2 text-slate-600"></i>
                        <select disabled class="w-full bg-white/5 border border-white/10 rounded-2xl pl-14 pr-10 py-4 text-sm font-bold text-white outline-none appearance-none">
                            <option>Greenwich Mean Time (GMT)</option>
                        </select>
                        <i class="fas fa-lock absolute right-5 top-1/2 -translate-y-1/2 text-slate-700 text-[10px]"></i>
                    </div>
                </div>

                <button type="submit" class="w-full bg-accent text-slate-900 py-5 rounded-2xl font-black text-xs uppercase tracking-[0.2em] hover-glow-yellow active:scale-95 transition-all shadow-xl shadow-yellow-500/10">
                    Save Church Branding
                </button>

                <a href="<?php echo BASE_URL; ?>/setup.php?force_setup=1" target="_blank" rel="noopener noreferrer" class="w-full inline-flex items-center justify-center bg-white/5 text-slate-200 py-5 rounded-2xl font-black text-xs uppercase tracking-[0.2em] border border-white/10 hover:bg-white/10 transition-all">
                    <i class="fas fa-screwdriver-wrench mr-2 text-accent"></i> Re-Run Installer
                </a>
            </form>
        </div>

        <div class="glass-card rounded-[2.5rem] sm:rounded-[3rem] p-6 sm:p-8 lg:p-10 border-white/5 card-interaction <?php echo $isAdmin ? '' : 'opacity-60'; ?>">
            <div class="flex items-center mb-10">
                <div class="w-12 h-12 bg-accent/10 rounded-2xl flex items-center justify-center mr-4 border border-accent/20">
                    <i class="fas fa-database text-accent text-lg"></i>
                </div>
                <div class="min-w-0">
                    <h4 class="text-xl font-black text-white tracking-tight">Supabase Connection</h4>
                    <p class="text-[10px] font-black uppercase tracking-widest mt-1 <?php echo (!empty($dbConfig['pdo_pgsql']) && !empty($dbConfig['pgsql'])) ? 'text-emerald-400' : 'text-rose-400'; ?>">
                        <?php echo (!empty($dbConfig['pdo_pgsql']) && !empty($dbConfig['pgsql'])) ? 'POSTGRES EXTENSIONS READY' : 'POSTGRES EXTENSIONS MISSING'; ?>
                    </p>
                </div>
            </div>

            <form action="<?php echo BASE_URL; ?>/settings/updateDatabaseConnection" method="POST" class="space-y-8">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="space-y-3">
                        <label class="block text-[9px] font-black text-slate-500 uppercase tracking-widest ml-1">Host</label>
                        <div class="relative group">
                            <i class="fas fa-server absolute left-5 top-1/2 -translate-y-1/2 text-slate-600 group-focus-within:text-accent transition-colors"></i>
                            <input <?php echo $isAdmin ? '' : 'disabled'; ?> type="text" name="db_host" value="<?php echo htmlspecialchars($dbConfig['host'] ?? ''); ?>" class="w-full bg-white/5 border border-white/10 focus:border-accent rounded-2xl pl-14 pr-6 py-4 text-xs font-black text-slate-200 transition-all outline-none" placeholder="aws-0-...pooler.supabase.com">
                        </div>
                    </div>

                    <div class="space-y-3">
                        <label class="block text-[9px] font-black text-slate-500 uppercase tracking-widest ml-1">Port</label>
                        <div class="relative group">
                            <i class="fas fa-network-wired absolute left-5 top-1/2 -translate-y-1/2 text-slate-600 group-focus-within:text-accent transition-colors"></i>
                            <input <?php echo $isAdmin ? '' : 'disabled'; ?> type="text" name="db_port" value="<?php echo htmlspecialchars($dbConfig['port'] ?? '5432'); ?>" class="w-full bg-white/5 border border-white/10 focus:border-accent rounded-2xl pl-14 pr-6 py-4 text-xs font-black text-slate-200 transition-all outline-none" placeholder="5432">
                        </div>
                    </div>

                    <div class="space-y-3">
                        <label class="block text-[9px] font-black text-slate-500 uppercase tracking-widest ml-1">Database</label>
                        <div class="relative group">
                            <i class="fas fa-folder-tree absolute left-5 top-1/2 -translate-y-1/2 text-slate-600 group-focus-within:text-accent transition-colors"></i>
                            <input <?php echo $isAdmin ? '' : 'disabled'; ?> type="text" name="db_name" value="<?php echo htmlspecialchars($dbConfig['name'] ?? 'postgres'); ?>" class="w-full bg-white/5 border border-white/10 focus:border-accent rounded-2xl pl-14 pr-6 py-4 text-xs font-black text-slate-200 transition-all outline-none" placeholder="postgres">
                        </div>
                    </div>

                    <div class="space-y-3">
                        <label class="block text-[9px] font-black text-slate-500 uppercase tracking-widest ml-1">User</label>
                        <div class="relative group">
                            <i class="fas fa-user-shield absolute left-5 top-1/2 -translate-y-1/2 text-slate-600 group-focus-within:text-accent transition-colors"></i>
                            <input <?php echo $isAdmin ? '' : 'disabled'; ?> type="text" name="db_user" value="<?php echo htmlspecialchars($dbConfig['user'] ?? ''); ?>" class="w-full bg-white/5 border border-white/10 focus:border-accent rounded-2xl pl-14 pr-6 py-4 text-xs font-black text-slate-200 transition-all outline-none" placeholder="postgres.xxxxx">
                        </div>
                    </div>

                    <div class="space-y-3">
                        <label class="block text-[9px] font-black text-slate-500 uppercase tracking-widest ml-1">Password</label>
                        <div class="relative group">
                            <i class="fas fa-key absolute left-5 top-1/2 -translate-y-1/2 text-slate-600 group-focus-within:text-accent transition-colors"></i>
                            <input <?php echo $isAdmin ? '' : 'disabled'; ?> type="password" name="db_pass" value="" class="w-full bg-white/5 border border-white/10 focus:border-accent rounded-2xl pl-14 pr-6 py-4 text-xs font-black text-slate-200 transition-all outline-none" placeholder="<?php echo !empty($dbConfig['has_pass']) ? 'Leave blank to keep current' : 'Enter Supabase database password'; ?>">
                        </div>
                    </div>

                    <div class="space-y-3">
                        <label class="block text-[9px] font-black text-slate-500 uppercase tracking-widest ml-1">Schema</label>
                        <div class="relative group">
                            <i class="fas fa-layer-group absolute left-5 top-1/2 -translate-y-1/2 text-slate-600 group-focus-within:text-accent transition-colors"></i>
                            <input <?php echo $isAdmin ? '' : 'disabled'; ?> type="text" name="db_schema" value="<?php echo htmlspecialchars($dbConfig['schema'] ?? 'public'); ?>" class="w-full bg-white/5 border border-white/10 focus:border-accent rounded-2xl pl-14 pr-6 py-4 text-xs font-black text-slate-200 transition-all outline-none" placeholder="public">
                        </div>
                    </div>
                </div>

                <div class="space-y-3">
                    <label class="block text-[9px] font-black text-slate-500 uppercase tracking-widest ml-1">SSL Mode</label>
                    <div class="relative group">
                        <i class="fas fa-shield-halved absolute left-5 top-1/2 -translate-y-1/2 text-slate-600 group-focus-within:text-accent transition-colors"></i>
                        <select <?php echo $isAdmin ? '' : 'disabled'; ?> name="db_sslmode" class="w-full bg-white/5 border border-white/10 focus:border-accent rounded-2xl pl-14 pr-10 py-4 text-xs font-black text-slate-200 transition-all outline-none appearance-none cursor-pointer">
                            <?php $ssl = strtolower((string)($dbConfig['sslmode'] ?? 'require')); ?>
                            <option value="require" <?php echo $ssl === 'require' ? 'selected' : ''; ?>>require</option>
                            <option value="prefer" <?php echo $ssl === 'prefer' ? 'selected' : ''; ?>>prefer</option>
                            <option value="disable" <?php echo $ssl === 'disable' ? 'selected' : ''; ?>>disable</option>
                            <option value="verify-full" <?php echo $ssl === 'verify-full' ? 'selected' : ''; ?>>verify-full</option>
                        </select>
                        <i class="fas fa-chevron-down absolute right-5 top-1/2 -translate-y-1/2 text-slate-600 text-[10px] pointer-events-none"></i>
                    </div>
                    <p class="text-[10px] font-black text-slate-500 uppercase tracking-widest">This saves directly into .env for multi-PC deployment</p>
                </div>

                <button <?php echo $isAdmin ? '' : 'disabled'; ?> type="submit" class="w-full bg-accent text-slate-900 py-5 rounded-2xl font-black text-xs uppercase tracking-[0.2em] hover-glow-yellow active:scale-95 transition-all shadow-xl shadow-yellow-500/10 disabled:opacity-60 disabled:cursor-not-allowed">
                    Test & Save Connection
                </button>
                <?php if (!$isAdmin): ?>
                    <p class="text-center text-[10px] font-black uppercase tracking-widest text-slate-500">Admin only</p>
                <?php endif; ?>
            </form>
        </div>

        <div class="glass-card rounded-[2.5rem] sm:rounded-[3rem] p-6 sm:p-8 lg:p-10 border-white/5 card-interaction">
            <div class="flex items-center mb-10">
                <div class="w-12 h-12 bg-accent/10 rounded-2xl flex items-center justify-center mr-4 border border-accent/20">
                    <i class="fas fa-message text-accent text-lg"></i>
                </div>
                <h4 class="text-xl font-black text-white tracking-tight">SMS Configuration</h4>
            </div>

            <form action="<?php echo BASE_URL; ?>/settings/updateSmsConfig" method="POST" class="space-y-8">
                <div class="space-y-3">
                    <label class="block text-[9px] font-black text-slate-500 uppercase tracking-widest ml-1">Provider</label>
                    <div class="relative group">
                        <i class="fas fa-plug absolute left-5 top-1/2 -translate-y-1/2 text-slate-600 group-focus-within:text-accent transition-colors"></i>
                        <select id="sms-provider" name="sms_provider" class="w-full bg-white/5 border border-white/10 focus:border-accent rounded-2xl pl-14 pr-10 py-4 text-sm font-bold text-white transition-all outline-none appearance-none">
                            <option value="nalo" <?php echo ($smsProvider ?? 'nalo') === 'nalo' ? 'selected' : ''; ?>>Nalo</option>
                            <option value="mnotify" <?php echo ($smsProvider ?? 'nalo') === 'mnotify' ? 'selected' : ''; ?>>MNotify</option>
                            <option value="infobip" <?php echo ($smsProvider ?? 'nalo') === 'infobip' ? 'selected' : ''; ?>>Infobip</option>
                            <option value="twilio" <?php echo ($smsProvider ?? 'nalo') === 'twilio' ? 'selected' : ''; ?>>Twilio</option>
                        </select>
                        <i class="fas fa-chevron-down absolute right-5 top-1/2 -translate-y-1/2 text-slate-600 text-[10px] pointer-events-none"></i>
                    </div>
                </div>

                <div id="sms-provider-nalo" class="space-y-8">
                <div class="space-y-3">
                    <label class="block text-[9px] font-black text-slate-500 uppercase tracking-widest ml-1">API Key</label>
                    <div class="relative group">
                        <i class="fas fa-key absolute left-5 top-1/2 -translate-y-1/2 text-slate-600 group-focus-within:text-accent transition-colors"></i>
                        <input type="password" name="sms_api_key" value="<?php echo htmlspecialchars($smsApiKey ?? ''); ?>" class="w-full bg-white/5 border border-white/10 focus:border-accent rounded-2xl pl-14 pr-6 py-4 text-sm font-bold text-white transition-all outline-none" placeholder="Paste your SMS API key">
                    </div>
                    <p class="text-[10px] font-black text-slate-500 uppercase tracking-widest">Stored in settings</p>
                </div>

                <div class="space-y-3">
                    <label class="block text-[9px] font-black text-slate-500 uppercase tracking-widest ml-1">Sender ID</label>
                    <div class="relative group">
                        <i class="fas fa-signature absolute left-5 top-1/2 -translate-y-1/2 text-slate-600 group-focus-within:text-accent transition-colors"></i>
                        <input type="text" name="sms_sender_id" value="<?php echo htmlspecialchars($smsSenderId ?? ''); ?>" class="w-full bg-white/5 border border-white/10 focus:border-accent rounded-2xl pl-14 pr-6 py-4 text-sm font-bold text-white transition-all outline-none" placeholder="Max 11 characters (e.g., UPPERROOM)">
                    </div>
                </div>

                <div class="space-y-3">
                    <label class="block text-[9px] font-black text-slate-500 uppercase tracking-widest ml-1">Provider Prefix</label>
                    <div class="relative group">
                        <i class="fas fa-tag absolute left-5 top-1/2 -translate-y-1/2 text-slate-600 group-focus-within:text-accent transition-colors"></i>
                        <input type="text" name="sms_nalo_prefix" value="<?php echo htmlspecialchars($smsPrefix ?? 'Resl_Nalo'); ?>" class="w-full bg-white/5 border border-white/10 focus:border-accent rounded-2xl pl-14 pr-6 py-4 text-sm font-bold text-white transition-all outline-none" placeholder="e.g., Resl_Nalo">
                    </div>
                </div>

                <div class="space-y-3">
                    <label class="block text-[9px] font-black text-slate-500 uppercase tracking-widest ml-1">Base URL</label>
                    <div class="relative group">
                        <i class="fas fa-link absolute left-5 top-1/2 -translate-y-1/2 text-slate-600 group-focus-within:text-accent transition-colors"></i>
                        <input type="text" name="sms_base_url" value="<?php echo htmlspecialchars($smsBaseUrl ?? 'https://sms.nalosolutions.com/smsbackend/clientapi/{prefix}/send-message/'); ?>" class="w-full bg-white/5 border border-white/10 focus:border-accent rounded-2xl pl-14 pr-6 py-4 text-xs font-black text-slate-200 transition-all outline-none">
                    </div>
                    <p class="text-[10px] font-black text-slate-500 uppercase tracking-widest">Use {prefix} placeholder</p>
                </div>
                </div>

                <div id="sms-provider-mnotify" class="space-y-8">
                <div class="space-y-3">
                    <label class="block text-[9px] font-black text-slate-500 uppercase tracking-widest ml-1">API Key</label>
                    <div class="relative group">
                        <i class="fas fa-key absolute left-5 top-1/2 -translate-y-1/2 text-slate-600 group-focus-within:text-accent transition-colors"></i>
                        <input type="password" name="sms_api_key" value="<?php echo htmlspecialchars($smsApiKey ?? ''); ?>" class="w-full bg-white/5 border border-white/10 focus:border-accent rounded-2xl pl-14 pr-6 py-4 text-sm font-bold text-white transition-all outline-none" placeholder="Paste your MNotify API key">
                    </div>
                    <p class="text-[10px] font-black text-slate-500 uppercase tracking-widest">Uses the MNotify bulk SMS gateway</p>
                </div>

                <div class="space-y-3">
                    <label class="block text-[9px] font-black text-slate-500 uppercase tracking-widest ml-1">Sender ID</label>
                    <div class="relative group">
                        <i class="fas fa-signature absolute left-5 top-1/2 -translate-y-1/2 text-slate-600 group-focus-within:text-accent transition-colors"></i>
                        <input type="text" name="sms_sender_id" value="<?php echo htmlspecialchars($smsSenderId ?? ''); ?>" class="w-full bg-white/5 border border-white/10 focus:border-accent rounded-2xl pl-14 pr-6 py-4 text-sm font-bold text-white transition-all outline-none" placeholder="Approved MNotify sender ID (max 11 chars)">
                    </div>
                    <p class="text-[10px] font-black text-slate-500 uppercase tracking-widest">MNotify requires an approved sender ID</p>
                </div>
                </div>

                <div id="sms-provider-infobip" class="space-y-8">
                <div class="space-y-3">
                    <label class="block text-[9px] font-black text-slate-500 uppercase tracking-widest ml-1">API Key</label>
                    <div class="relative group">
                        <i class="fas fa-key absolute left-5 top-1/2 -translate-y-1/2 text-slate-600 group-focus-within:text-accent transition-colors"></i>
                        <input type="password" name="sms_api_key" value="<?php echo htmlspecialchars($smsApiKey ?? ''); ?>" class="w-full bg-white/5 border border-white/10 focus:border-accent rounded-2xl pl-14 pr-6 py-4 text-sm font-bold text-white transition-all outline-none" placeholder="Paste your Infobip API key">
                    </div>
                    <p class="text-[10px] font-black text-slate-500 uppercase tracking-widest">Authorization header uses App API key</p>
                </div>

                <div class="space-y-3">
                    <label class="block text-[9px] font-black text-slate-500 uppercase tracking-widest ml-1">Sender ID</label>
                    <div class="relative group">
                        <i class="fas fa-signature absolute left-5 top-1/2 -translate-y-1/2 text-slate-600 group-focus-within:text-accent transition-colors"></i>
                        <input type="text" name="sms_sender_id" value="<?php echo htmlspecialchars($smsSenderId ?? ''); ?>" class="w-full bg-white/5 border border-white/10 focus:border-accent rounded-2xl pl-14 pr-6 py-4 text-sm font-bold text-white transition-all outline-none" placeholder="Example: ServiceSMS or your approved sender">
                    </div>
                </div>

                <div class="space-y-3">
                    <label class="block text-[9px] font-black text-slate-500 uppercase tracking-widest ml-1">Base URL</label>
                    <div class="relative group">
                        <i class="fas fa-link absolute left-5 top-1/2 -translate-y-1/2 text-slate-600 group-focus-within:text-accent transition-colors"></i>
                        <input type="text" name="sms_infobip_base_url" value="<?php echo htmlspecialchars($smsInfobipBaseUrl ?? 'https://api.infobip.com'); ?>" class="w-full bg-white/5 border border-white/10 focus:border-accent rounded-2xl pl-14 pr-6 py-4 text-xs font-black text-slate-200 transition-all outline-none" placeholder="https://your-domain.api.infobip.com">
                    </div>
                    <p class="text-[10px] font-black text-slate-500 uppercase tracking-widest">Use your Infobip account base URL if provided</p>
                </div>
                </div>

                <div id="sms-provider-twilio" class="space-y-8">
                    <div class="space-y-3">
                        <label class="block text-[9px] font-black text-slate-500 uppercase tracking-widest ml-1">Account SID</label>
                        <div class="relative group">
                            <i class="fas fa-id-card absolute left-5 top-1/2 -translate-y-1/2 text-slate-600 group-focus-within:text-accent transition-colors"></i>
                            <input type="text" name="sms_twilio_account_sid" value="<?php echo htmlspecialchars($smsTwilioAccountSid ?? ''); ?>" class="w-full bg-white/5 border border-white/10 focus:border-accent rounded-2xl pl-14 pr-6 py-4 text-xs font-black text-slate-200 transition-all outline-none" placeholder="ACxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx">
                        </div>
                    </div>

                    <div class="space-y-3">
                        <label class="block text-[9px] font-black text-slate-500 uppercase tracking-widest ml-1">Auth Token</label>
                        <div class="relative group">
                            <i class="fas fa-key absolute left-5 top-1/2 -translate-y-1/2 text-slate-600 group-focus-within:text-accent transition-colors"></i>
                            <input type="password" name="sms_twilio_auth_token" value="<?php echo htmlspecialchars($smsTwilioAuthToken ?? ''); ?>" class="w-full bg-white/5 border border-white/10 focus:border-accent rounded-2xl pl-14 pr-6 py-4 text-sm font-bold text-white transition-all outline-none" placeholder="Paste your Twilio auth token">
                        </div>
                    </div>

                    <div class="space-y-3">
                        <label class="block text-[9px] font-black text-slate-500 uppercase tracking-widest ml-1">From (Twilio Number)</label>
                        <div class="relative group">
                            <i class="fas fa-phone absolute left-5 top-1/2 -translate-y-1/2 text-slate-600 group-focus-within:text-accent transition-colors"></i>
                            <input type="text" name="sms_twilio_from" value="<?php echo htmlspecialchars($smsTwilioFrom ?? ''); ?>" class="w-full bg-white/5 border border-white/10 focus:border-accent rounded-2xl pl-14 pr-6 py-4 text-sm font-bold text-white transition-all outline-none" placeholder="+1XXXXXXXXXX or approved sender">
                        </div>
                        <p class="text-[10px] font-black text-slate-500 uppercase tracking-widest">Twilio requires To, From, Body</p>
                    </div>
                </div>

                <button type="submit" class="w-full bg-accent text-slate-900 py-5 rounded-2xl font-black text-xs uppercase tracking-[0.2em] hover-glow-yellow active:scale-95 transition-all shadow-xl shadow-yellow-500/10">
                    Save SMS Settings
                </button>
            </form>
        </div>

        <script>
            (function () {
                const select = document.getElementById('sms-provider');
                const nalo = document.getElementById('sms-provider-nalo');
                const mnotify = document.getElementById('sms-provider-mnotify');
                const infobip = document.getElementById('sms-provider-infobip');
                const twilio = document.getElementById('sms-provider-twilio');
                if (!select || !nalo || !mnotify || !infobip || !twilio) return;

                const apply = () => {
                    const v = (select.value || 'nalo').toLowerCase();
                    if (v === 'twilio') {
                        nalo.classList.add('hidden');
                        mnotify.classList.add('hidden');
                        infobip.classList.add('hidden');
                        twilio.classList.remove('hidden');
                    } else if (v === 'mnotify') {
                        nalo.classList.add('hidden');
                        infobip.classList.add('hidden');
                        twilio.classList.add('hidden');
                        mnotify.classList.remove('hidden');
                    } else if (v === 'infobip') {
                        nalo.classList.add('hidden');
                        mnotify.classList.add('hidden');
                        twilio.classList.add('hidden');
                        infobip.classList.remove('hidden');
                    } else {
                        mnotify.classList.add('hidden');
                        twilio.classList.add('hidden');
                        infobip.classList.add('hidden');
                        nalo.classList.remove('hidden');
                    }
                };

                select.addEventListener('change', apply);
                apply();
            })();
        </script>

        <div class="glass-card rounded-[3rem] p-10 border-white/5 card-interaction">
            <div class="flex items-center mb-10">
                <div class="w-12 h-12 bg-accent/10 rounded-2xl flex items-center justify-center mr-4 border border-accent/20">
                    <i class="fas fa-palette text-accent text-lg"></i>
                </div>
                <h4 class="text-xl font-black text-white tracking-tight">Theme</h4>
            </div>

            <form id="theme-form" action="<?php echo BASE_URL; ?>/settings/updateTheme" method="POST" class="space-y-8">
                <div class="space-y-3">
                    <label class="block text-[9px] font-black text-slate-500 uppercase tracking-widest ml-1">Interface Theme</label>
                    <div class="relative">
                        <i class="fas fa-palette absolute left-5 top-1/2 -translate-y-1/2 text-slate-600"></i>
                        <select id="theme-select" name="theme" class="w-full bg-white/5 border border-white/10 focus:border-accent rounded-2xl pl-14 pr-10 py-4 text-sm font-bold text-white transition-all outline-none appearance-none cursor-pointer">
                            <option value="dark" <?php echo ($theme ?? 'dark') === 'dark' ? 'selected' : ''; ?>>Dark Theme</option>
                            <option value="light" <?php echo ($theme ?? 'dark') === 'light' ? 'selected' : ''; ?>>Light Theme</option>
                            <option value="ocean" <?php echo ($theme ?? 'dark') === 'ocean' ? 'selected' : ''; ?>>Ocean Theme</option>
                            <option value="sunset" <?php echo ($theme ?? 'dark') === 'sunset' ? 'selected' : ''; ?>>Sunset Theme</option>
                        </select>
                        <i class="fas fa-chevron-down absolute right-5 top-1/2 -translate-y-1/2 text-slate-600 text-[10px]"></i>
                    </div>
                    <p class="text-[10px] font-black text-slate-500 uppercase tracking-widest">This becomes the default theme for everyone</p>
                </div>

                <button type="submit" class="w-full bg-accent text-slate-900 py-5 rounded-2xl font-black text-xs uppercase tracking-[0.2em] hover-glow-yellow active:scale-95 transition-all shadow-xl shadow-yellow-500/10">
                    Apply Theme
                </button>
            </form>
        </div>

        <div class="glass-card rounded-[3rem] p-10 border-white/5 bg-slate-900/50 card-interaction">
            <div class="flex items-center mb-8">
                <div class="w-12 h-12 bg-white/5 rounded-2xl flex items-center justify-center mr-4 border border-white/10">
                    <i class="fas fa-shield-halved text-accent"></i>
                </div>
                <h4 class="text-xl font-black text-white tracking-tight">System Node</h4>
            </div>
            <div class="space-y-6">
                <div class="flex justify-between items-center p-4 rounded-2xl bg-white/[0.02] border border-white/5 hover:bg-white/5 transition-all cursor-default">
                    <span class="text-[10px] font-black text-slate-500 uppercase tracking-widest">DB Integrity</span>
                    <span class="text-[9px] font-black text-emerald-400 bg-emerald-500/10 px-3 py-1 rounded-full border border-emerald-500/20 uppercase">Encrypted</span>
                </div>
                <div class="flex justify-between items-center p-4 rounded-2xl bg-white/[0.02] border border-white/5 hover:bg-white/5 transition-all cursor-default">
                    <span class="text-[10px] font-black text-slate-500 uppercase tracking-widest">Gateway</span>
                    <span class="text-[9px] font-black text-emerald-400 bg-emerald-500/10 px-3 py-1 rounded-full border border-emerald-500/20 uppercase">Connected</span>
                </div>
                <div class="flex justify-between items-center p-4 rounded-2xl bg-white/[0.02] border border-white/5 hover:bg-white/5 transition-all cursor-default">
                    <span class="text-[10px] font-black text-slate-500 uppercase tracking-widest">Backup</span>
                    <span class="text-[9px] font-black text-accent bg-accent/10 px-3 py-1 rounded-full border border-accent/20 uppercase">Synchronized</span>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    (function () {
        const select = document.getElementById('theme-select');
        const form = document.getElementById('theme-form');
        if (!select || !form) return;
        select.addEventListener('change', () => {
            try { localStorage.setItem('uiTheme', select.value); } catch (e) {}
            form.submit();
        });
    })();
</script>

<!-- Add User Modal -->
<?php if ($isAdmin): ?>
<div id="add-user-modal" class="hidden fixed inset-0 bg-slate-950/80 backdrop-blur-xl z-50 flex items-center justify-center p-4">
    <div class="glass-card w-full max-w-md rounded-[3rem] p-6 sm:p-12 shadow-2xl border-white/10 transform transition-all scale-100 max-h-[90vh] overflow-y-auto custom-scrollbar">
        <div class="flex justify-between items-center mb-10">
            <h3 class="text-3xl font-black text-white tracking-tighter">Grant Access</h3>
            <button onclick="document.getElementById('add-user-modal').classList.add('hidden')" class="w-10 h-10 bg-white/5 hover:bg-accent hover:text-slate-900 text-slate-400 rounded-xl flex items-center justify-center transition-all border border-white/10">
                <i class="fas fa-times text-sm"></i>
            </button>
        </div>
        <form action="<?php echo BASE_URL; ?>/settings/addUser" method="POST" enctype="multipart/form-data" class="space-y-8">
            <div class="space-y-3">
                <label class="block text-[9px] font-black text-slate-500 uppercase tracking-widest ml-1">Admin Full Name</label>
                <div class="relative group">
                    <i class="fas fa-user absolute left-5 top-1/2 -translate-y-1/2 text-slate-600 group-focus-within:text-accent transition-colors"></i>
                    <input type="text" name="name" required class="w-full bg-white/5 border border-white/10 focus:border-accent rounded-2xl pl-14 pr-6 py-4 text-sm font-bold text-white transition-all outline-none" placeholder="e.g. Pastor John Doe">
                </div>
            </div>
            <div class="space-y-3">
                <label class="block text-[9px] font-black text-slate-500 uppercase tracking-widest ml-1">Username</label>
                <div class="relative group">
                    <i class="fas fa-at absolute left-5 top-1/2 -translate-y-1/2 text-slate-600 group-focus-within:text-accent transition-colors"></i>
                    <input type="text" name="username" class="w-full bg-white/5 border border-white/10 focus:border-accent rounded-2xl pl-14 pr-6 py-4 text-sm font-bold text-white transition-all outline-none" placeholder="e.g. admin.urampong">
                </div>
            </div>
            <div class="space-y-3">
                <label class="block text-[9px] font-black text-slate-500 uppercase tracking-widest ml-1">Email Identity</label>
                <div class="relative group">
                    <i class="fas fa-envelope absolute left-5 top-1/2 -translate-y-1/2 text-slate-600 group-focus-within:text-accent transition-colors"></i>
                    <input type="email" name="email" required class="w-full bg-white/5 border border-white/10 focus:border-accent rounded-2xl pl-14 pr-6 py-4 text-sm font-bold text-white transition-all outline-none">
                </div>
            </div>
            <div class="space-y-3">
                <label class="block text-[9px] font-black text-slate-500 uppercase tracking-widest ml-1">Profile Photo</label>
                <div class="relative group">
                    <i class="fas fa-image absolute left-5 top-1/2 -translate-y-1/2 text-slate-600"></i>
                    <input type="file" name="photo" accept="image/*" class="w-full bg-white/5 border border-white/10 rounded-2xl pl-14 pr-6 py-4 text-[10px] font-black text-slate-400 transition-all outline-none file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-[10px] file:font-black file:bg-accent file:text-slate-900 hover:file:bg-white cursor-pointer">
                </div>
            </div>
            <div class="space-y-3">
                <label class="block text-[9px] font-black text-slate-500 uppercase tracking-widest ml-1">Administrative Key</label>
                <div class="relative group">
                    <i class="fas fa-key absolute left-5 top-1/2 -translate-y-1/2 text-slate-600 group-focus-within:text-accent transition-colors"></i>
                    <input type="password" name="password" required class="w-full bg-white/5 border border-white/10 focus:border-accent rounded-2xl pl-14 pr-6 py-4 text-sm font-bold text-white transition-all outline-none">
                </div>
            </div>
            <div class="space-y-3">
                <label class="block text-[9px] font-black text-slate-500 uppercase tracking-widest ml-1">Permission Level</label>
                <div class="relative">
                    <i class="fas fa-user-gear absolute left-5 top-1/2 -translate-y-1/2 text-slate-600"></i>
                    <select id="new-user-role" name="role" class="w-full bg-white/5 border border-white/10 focus:border-accent rounded-2xl pl-14 pr-10 py-4 text-sm font-bold text-white transition-all outline-none appearance-none cursor-pointer">
                        <option value="finance_staff">Finance Staff</option>
                        <option value="finance_head">Head Of Finance</option>
                        <option value="dept_head">Department Head</option>
                        <option value="visitation_team">Visitation Team</option>
                        <option value="auditor">Auditor</option>
                        <option value="pastor">Pastor</option>
                        <option value="admin">System Administrator</option>
                    </select>
                    <i class="fas fa-chevron-down absolute right-5 top-1/2 -translate-y-1/2 text-slate-600 text-[10px]"></i>
                </div>
            </div>
            <div id="new-user-department-wrap" class="space-y-3 hidden">
                <label class="block text-[9px] font-black text-slate-500 uppercase tracking-widest ml-1">Department</label>
                <div class="relative">
                    <i class="fas fa-sitemap absolute left-5 top-1/2 -translate-y-1/2 text-slate-600"></i>
                    <select id="new-user-department" name="department_id" class="w-full bg-white/5 border border-white/10 focus:border-accent rounded-2xl pl-14 pr-10 py-4 text-sm font-bold text-white transition-all outline-none appearance-none cursor-pointer">
                        <option value="">Select Department</option>
                        <?php foreach (($departments ?? []) as $d): ?>
                            <option value="<?php echo (int)$d['id']; ?>"><?php echo htmlspecialchars($d['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <i class="fas fa-chevron-down absolute right-5 top-1/2 -translate-y-1/2 text-slate-600 text-[10px]"></i>
                </div>
            </div>
            <button type="submit" class="w-full bg-accent text-slate-900 py-5 rounded-2xl font-black text-xs uppercase tracking-[0.2em] hover:scale-[1.02] active:scale-95 transition-all shadow-xl shadow-yellow-500/10 mt-6">
                Initialize Account
            </button>
        </form>
    </div>
</div>
<?php endif; ?>

<?php if ($isAdmin): ?>
<div id="reset-password-modal" class="hidden fixed inset-0 bg-slate-950/80 backdrop-blur-xl z-50 flex items-center justify-center p-4">
    <div class="glass-card w-full max-w-md rounded-[3rem] p-6 sm:p-12 shadow-2xl border-white/10 transform transition-all scale-100 max-h-[90vh] overflow-y-auto custom-scrollbar">
        <div class="flex justify-between items-center mb-10">
            <div>
                <h3 class="text-3xl font-black text-white tracking-tighter">Reset Password</h3>
                <p id="reset-password-target" class="text-[10px] font-black uppercase tracking-widest text-slate-500 mt-2"></p>
            </div>
            <button type="button" onclick="document.getElementById('reset-password-modal').classList.add('hidden')" class="w-10 h-10 bg-white/5 hover:bg-accent hover:text-slate-900 text-slate-400 rounded-xl flex items-center justify-center transition-all border border-white/10">
                <i class="fas fa-times text-sm"></i>
            </button>
        </div>
        <form action="<?php echo BASE_URL; ?>/settings/user/resetPassword" method="POST" data-loader="top" class="space-y-8">
            <input type="hidden" name="user_id" id="reset-password-user-id" value="">
            <div class="space-y-3">
                <label class="block text-[9px] font-black text-slate-500 uppercase tracking-widest ml-1">New Password</label>
                <div class="relative group">
                    <i class="fas fa-key absolute left-5 top-1/2 -translate-y-1/2 text-slate-600 group-focus-within:text-accent transition-colors"></i>
                    <input type="password" name="new_password" required class="w-full bg-white/5 border border-white/10 focus:border-accent rounded-2xl pl-14 pr-6 py-4 text-sm font-bold text-white transition-all outline-none">
                </div>
            </div>
            <div class="space-y-3">
                <label class="block text-[9px] font-black text-slate-500 uppercase tracking-widest ml-1">Confirm Password</label>
                <div class="relative group">
                    <i class="fas fa-shield-halved absolute left-5 top-1/2 -translate-y-1/2 text-slate-600 group-focus-within:text-accent transition-colors"></i>
                    <input type="password" name="confirm_password" required class="w-full bg-white/5 border border-white/10 focus:border-accent rounded-2xl pl-14 pr-6 py-4 text-sm font-bold text-white transition-all outline-none">
                </div>
            </div>
            <button type="submit" class="w-full bg-accent text-slate-900 py-5 rounded-2xl font-black text-xs uppercase tracking-[0.2em] hover:scale-[1.02] active:scale-95 transition-all shadow-xl shadow-yellow-500/10">
                Update Password
            </button>
        </form>
    </div>
</div>
<?php endif; ?>

<script>
    (function () {
        const modal = document.getElementById('add-user-modal');
        if (!modal) return;
        modal.addEventListener('click', (e) => {
            if (e.target === modal) modal.classList.add('hidden');
        });
    })();
</script>

<script>
    (function () {
        const role = document.getElementById('new-user-role');
        const deptWrap = document.getElementById('new-user-department-wrap');
        const dept = document.getElementById('new-user-department');
        if (!role || !deptWrap || !dept) return;

        const apply = () => {
            const v = (role.value || '').toLowerCase();
            if (v === 'dept_head' || v === 'visitation_team') {
                deptWrap.classList.remove('hidden');
            } else {
                dept.value = '';
                deptWrap.classList.add('hidden');
            }
        };

        role.addEventListener('change', apply);
        apply();
    })();
</script>

<?php if ($isAdmin): ?>
<script>
    (function () {
        const modal = document.getElementById('reset-password-modal');
        const userIdInput = document.getElementById('reset-password-user-id');
        const label = document.getElementById('reset-password-target');
        if (!modal || !userIdInput || !label) return;

        const open = (id, name, email) => {
            userIdInput.value = String(id || '');
            const safeName = (name || '').trim();
            const safeEmail = (email || '').trim();
            label.textContent = safeName !== '' ? `${safeName} • ${safeEmail}` : safeEmail;
            modal.classList.remove('hidden');
        };

        document.addEventListener('click', (e) => {
            const btn = e.target.closest('[data-reset-password="1"]');
            if (!btn) return;
            open(btn.dataset.userId, btn.dataset.userName, btn.dataset.userEmail);
        });

        modal.addEventListener('click', (e) => {
            if (e.target === modal) modal.classList.add('hidden');
        });
    })();
</script>
<?php endif; ?>
