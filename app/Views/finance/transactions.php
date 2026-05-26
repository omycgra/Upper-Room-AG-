<?php
    $churchName = AppConfig::getSetting('church_name', 'Church Management');
    $currency = strtoupper(trim(($bank['currency'] ?? 'GHS')));
    if (!preg_match('/^[A-Z]{2,5}$/', $currency)) $currency = 'GHS';
    $isStaff = !empty($isStaff);
    $activeChangeRequestMap = $active_change_request_map ?? [];
    $receiptData = $receipt_data ?? null;
?>

<div class="flex flex-col sm:flex-row justify-between items-start mb-10 gap-4">
    <div>
        <h2 class="text-4xl sm:text-5xl font-black text-white tracking-tighter">Transactions</h2>
        <p class="text-slate-400 font-bold mt-2 uppercase tracking-widest text-xs">Quick access ledger for <span class="text-accent"><?php echo htmlspecialchars($churchName); ?></span></p>
    </div>
    <div class="flex flex-wrap gap-3">
        <a href="<?php echo BASE_URL; ?>/finance" class="glass-card flex items-center px-6 py-3.5 rounded-2xl border-white/10 text-slate-300 font-black text-xs uppercase tracking-widest hover:bg-white/5 transition-all">
            <i class="fas fa-arrow-left mr-2"></i> Finance
        </a>
        <?php if (empty($isDeptHead) && !Auth::isAdmin()): ?>
            <a href="<?php echo BASE_URL; ?>/finance/add" class="glass-card flex items-center px-6 py-3.5 rounded-2xl bg-accent text-slate-900 font-black text-xs uppercase tracking-widest hover:scale-[1.03] transition-all shadow-xl shadow-yellow-500/20">
                <i class="fas fa-plus mr-2"></i> <?php echo $isStaff ? 'Record Transaction' : 'Add Transaction'; ?>
            </a>
        <?php endif; ?>
    </div>
</div>

<div class="glass-card rounded-[2.5rem] sm:rounded-[3rem] border-white/5 overflow-hidden card-interaction">
    <div class="px-6 sm:px-8 lg:px-10 py-6 sm:py-8 border-b border-white/5 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 bg-white/[0.02]">
        <div class="flex items-center">
            <div class="w-10 h-10 bg-accent/10 rounded-xl flex items-center justify-center mr-4 border border-accent/20">
                <i class="fas fa-receipt text-accent text-sm"></i>
            </div>
            <h4 class="text-xl font-black text-white tracking-tight">Ledger</h4>
        </div>
        <div class="text-[10px] font-black text-slate-500 uppercase tracking-widest"><?php echo count($recent_transactions ?? []); ?> records</div>
    </div>

    <div class="md:hidden p-4 sm:p-6 space-y-4">
        <?php if (empty($recent_transactions)): ?>
            <div class="px-4 py-12 text-center text-slate-500 italic font-bold">No transactions recorded yet.</div>
        <?php else: ?>
            <?php foreach (($recent_transactions ?? []) as $tx): ?>
                <?php $isExpense = ($tx['transaction_type'] ?? '') === 'Expense'; ?>
                <div class="glass-card rounded-[2rem] p-4 sm:p-5 border-white/10">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <p class="text-sm font-black text-slate-200"><?php echo !empty($tx['transaction_date']) ? date('M d, Y', strtotime((string)$tx['transaction_date'])) : 'N/A'; ?></p>
                            <div class="mt-2">
                                <span class="px-3 py-1.5 text-[9px] font-black rounded-full uppercase tracking-widest border <?php echo $isExpense ? 'bg-rose-500/10 text-rose-400 border-rose-500/20' : 'bg-emerald-500/10 text-emerald-400 border-emerald-500/20'; ?>">
                                    <?php echo htmlspecialchars($tx['transaction_type'] ?? ''); ?>
                                </span>
                            </div>
                        </div>
                        <p class="text-base font-black <?php echo $isExpense ? 'text-rose-400' : 'text-emerald-400'; ?>">
                            <?php echo ($isExpense ? '-' : '') . $currency . ' ' . number_format((float)($tx['amount'] ?? 0), 2); ?>
                        </p>
                    </div>
                    <div class="mt-4 space-y-2 text-[10px] font-black uppercase tracking-widest text-slate-500">
                        <p>Method: <span class="text-slate-300"><?php echo htmlspecialchars($tx['payment_method'] ?? ''); ?></span></p>
                        <p>Reference: <span class="text-slate-300"><?php echo htmlspecialchars(($tx['reference_no'] ?? '') !== '' ? $tx['reference_no'] : 'N/A'); ?></span></p>
                    </div>
                    <div class="mt-4 flex flex-wrap gap-2">
                        <button type="button" onclick="openTransactionModal(<?php echo (int)$tx['id']; ?>)" class="h-10 px-4 inline-flex items-center justify-center rounded-xl bg-white/5 text-slate-400 hover:bg-white/10 hover:text-accent transition-all border border-white/5">
                            <i class="fas fa-eye text-xs mr-2"></i><span class="text-[10px] font-black uppercase tracking-widest">View</span>
                        </button>
                        <?php if ($isStaff && (int)($tx['member_id'] ?? 0) > 0): ?>
                            <button type="button" onclick="openReceiptModal(<?php echo (int)$tx['id']; ?>)" class="h-10 px-4 inline-flex items-center justify-center rounded-xl bg-white/5 text-slate-400 hover:bg-white/10 hover:text-accent transition-all border border-white/5">
                                <i class="fas fa-print text-xs mr-2"></i><span class="text-[10px] font-black uppercase tracking-widest">Receipt</span>
                            </button>
                        <?php endif; ?>
                        <?php if ($isStaff): ?>
                            <?php $isOwnTx = ((int)($tx['recorded_by'] ?? (int)Session::get('user_id')) === (int)Session::get('user_id')); ?>
                            <?php if ($isOwnTx): ?>
                                <?php $req = $activeChangeRequestMap[(int)($tx['id'] ?? 0)] ?? null; ?>
                                <?php if ($req): ?>
                                    <?php $reqStatus = strtolower(trim((string)($req['status'] ?? 'pending'))); ?>
                                    <span class="h-10 px-4 inline-flex items-center justify-center rounded-xl border text-[10px] font-black uppercase tracking-widest <?php echo $reqStatus === 'approved' ? 'bg-emerald-500/10 text-emerald-400 border-emerald-500/20' : 'bg-white/5 text-slate-300 border-white/10'; ?>">
                                        <i class="fas fa-clock text-xs mr-2"></i><?php echo htmlspecialchars($reqStatus); ?>
                                    </span>
                                    <?php if ($reqStatus === 'approved'): ?>
                                        <button type="button" onclick="openEditTransactionModal(<?php echo (int)$tx['id']; ?>)" class="h-10 px-4 inline-flex items-center justify-center rounded-xl bg-accent text-slate-900 hover:scale-[1.02] transition-all font-black text-[10px] uppercase tracking-widest">
                                            <i class="fas fa-pen-nib text-xs mr-2"></i>Edit
                                        </button>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <button type="button" onclick="openRequestChangeModal(<?php echo (int)$tx['id']; ?>)" class="h-10 px-4 inline-flex items-center justify-center rounded-xl bg-white/5 text-slate-400 hover:bg-white/10 hover:text-accent transition-all border border-white/5">
                                        <i class="fas fa-clipboard-list text-xs mr-2"></i><span class="text-[10px] font-black uppercase tracking-widest">Request Edit</span>
                                    </button>
                                <?php endif; ?>
                            <?php endif; ?>
                        <?php endif; ?>
                        <?php if (!empty($isDeptHead)): ?>
                            <button type="button" onclick="openEditTransactionModal(<?php echo (int)$tx['id']; ?>)" class="h-10 px-4 inline-flex items-center justify-center rounded-xl bg-white/5 text-slate-400 hover:bg-white/10 hover:text-accent transition-all border border-white/5">
                                <i class="fas fa-pen-nib text-xs mr-2"></i><span class="text-[10px] font-black uppercase tracking-widest">Edit</span>
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <div class="hidden md:block overflow-x-auto custom-scrollbar">
        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="text-[10px] font-black text-slate-500 uppercase tracking-[0.3em] border-b border-white/5 bg-white/[0.01]">
                    <th class="px-10 py-6">Date</th>
                    <th class="px-10 py-6">Transaction Type</th>
                    <th class="px-10 py-6">Amount</th>
                    <th class="px-10 py-6 hidden md:table-cell">Method</th>
                    <th class="px-10 py-6 hidden lg:table-cell">Reference</th>
                    <th class="px-10 py-6 text-right">Action</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-white/[0.02]">
                <?php if (empty($recent_transactions)): ?>
                    <tr>
                        <td colspan="6" class="px-10 py-16 text-center text-slate-500 italic font-bold">No transactions recorded yet.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach (($recent_transactions ?? []) as $tx): ?>
                        <?php $isExpense = ($tx['transaction_type'] ?? '') === 'Expense'; ?>
                        <tr class="hover:bg-white/[0.03] transition-all duration-300">
                            <td class="px-10 py-6">
                                <p class="text-sm font-black text-slate-200"><?php echo !empty($tx['transaction_date']) ? date('M d, Y', strtotime((string)$tx['transaction_date'])) : 'N/A'; ?></p>
                            </td>
                            <td class="px-10 py-6">
                                <span class="px-4 py-1.5 text-[9px] font-black rounded-full uppercase tracking-widest border <?php echo $isExpense ? 'bg-rose-500/10 text-rose-400 border-rose-500/20' : 'bg-emerald-500/10 text-emerald-400 border-emerald-500/20'; ?>">
                                    <?php echo htmlspecialchars($tx['transaction_type'] ?? ''); ?>
                                </span>
                            </td>
                            <td class="px-10 py-6">
                                <p class="text-lg font-black <?php echo $isExpense ? 'text-rose-400' : 'text-emerald-400'; ?>">
                                    <?php echo ($isExpense ? '-' : '') . $currency . ' ' . number_format((float)($tx['amount'] ?? 0), 2); ?>
                                </p>
                            </td>
                            <td class="px-10 py-6 hidden md:table-cell">
                                <div class="flex items-center text-xs font-bold text-slate-400 uppercase tracking-tighter">
                                    <i class="fas fa-credit-card mr-2 opacity-30"></i> <?php echo htmlspecialchars($tx['payment_method'] ?? ''); ?>
                                </div>
                            </td>
                            <td class="px-10 py-6 hidden lg:table-cell">
                                <p class="text-xs font-bold text-slate-400"><?php echo htmlspecialchars(($tx['reference_no'] ?? '') !== '' ? $tx['reference_no'] : 'N/A'); ?></p>
                            </td>
                            <td class="px-10 py-6 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <button type="button" onclick="openTransactionModal(<?php echo (int)$tx['id']; ?>)" class="w-10 h-10 inline-flex items-center justify-center rounded-xl bg-white/5 text-slate-400 hover:bg-white/10 hover:text-accent transition-all border border-white/5">
                                        <i class="fas fa-eye text-xs"></i>
                                    </button>
                                    <?php if ($isStaff && (int)($tx['member_id'] ?? 0) > 0): ?>
                                        <button type="button" onclick="openReceiptModal(<?php echo (int)$tx['id']; ?>)" class="w-10 h-10 inline-flex items-center justify-center rounded-xl bg-white/5 text-slate-400 hover:bg-white/10 hover:text-accent transition-all border border-white/5">
                                            <i class="fas fa-print text-xs"></i>
                                        </button>
                                    <?php endif; ?>
                                    <?php if ($isStaff): ?>
                                        <?php $isOwnTx = ((int)($tx['recorded_by'] ?? (int)Session::get('user_id')) === (int)Session::get('user_id')); ?>
                                        <?php if ($isOwnTx): ?>
                                            <?php $req = $activeChangeRequestMap[(int)($tx['id'] ?? 0)] ?? null; ?>
                                            <?php if ($req): ?>
                                                <?php $reqStatus = strtolower(trim((string)($req['status'] ?? 'pending'))); ?>
                                                <?php if ($reqStatus === 'approved'): ?>
                                                    <button type="button" onclick="openEditTransactionModal(<?php echo (int)$tx['id']; ?>)" class="w-10 h-10 inline-flex items-center justify-center rounded-xl bg-accent text-slate-900 hover:scale-[1.02] transition-all border border-accent/30" title="Approved: Edit">
                                                        <i class="fas fa-pen-nib text-xs"></i>
                                                    </button>
                                                <?php else: ?>
                                                    <span class="w-10 h-10 inline-flex items-center justify-center rounded-xl bg-white/5 text-slate-300 border border-white/10" title="Pending request">
                                                        <i class="fas fa-clock text-xs"></i>
                                                    </span>
                                                <?php endif; ?>
                                            <?php else: ?>
                                                <button type="button" onclick="openRequestChangeModal(<?php echo (int)$tx['id']; ?>)" class="w-10 h-10 inline-flex items-center justify-center rounded-xl bg-white/5 text-slate-400 hover:bg-white/10 hover:text-accent transition-all border border-white/5" title="Request Edit">
                                                    <i class="fas fa-clipboard-list text-xs"></i>
                                                </button>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                    <?php if (!empty($isDeptHead)): ?>
                                        <button type="button" onclick="openEditTransactionModal(<?php echo (int)$tx['id']; ?>)" class="w-10 h-10 inline-flex items-center justify-center rounded-xl bg-white/5 text-slate-400 hover:bg-white/10 hover:text-accent transition-all border border-white/5">
                                            <i class="fas fa-pen-nib text-xs"></i>
                                        </button>
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

<script>
    const financeTransactions = <?php echo json_encode($recent_transactions ?? [], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
    const financeReceiptSeed = <?php echo json_encode($receiptData, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
    const receiptLogoUrl = <?php echo json_encode((function () {
        $logoPath = Branding::getLogoPath();
        return $logoPath ? BASE_URL . '/' . ltrim($logoPath, '/') : '';
    })()); ?>;
    let activeReceiptTx = null;

    function openTransactionModal(id) {
        const modal = document.getElementById('transaction-modal');
        const content = document.getElementById('transaction-modal-content');
        const tx = (financeTransactions || []).find(t => String(t.id) === String(id));
        if (!tx || !modal || !content) return;

        const currency = <?php echo json_encode($currency); ?>;
        const dateStr = tx.transaction_date ? new Date(tx.transaction_date).toLocaleDateString(undefined, { year: 'numeric', month: 'short', day: '2-digit' }) : '';
        const isExpense = String(tx.transaction_type || '') === 'Expense';

        document.getElementById('tx-type').textContent = tx.transaction_type || '';
        document.getElementById('tx-type').className = `px-4 py-1.5 text-[9px] font-black rounded-full uppercase tracking-widest border ${isExpense ? 'bg-rose-500/10 text-rose-400 border-rose-500/20' : 'bg-emerald-500/10 text-emerald-400 border-emerald-500/20'}`;
        document.getElementById('tx-amount').textContent = `${isExpense ? '-' : ''}${currency} ${Number(tx.amount || 0).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
        document.getElementById('tx-date').textContent = dateStr || 'N/A';
        document.getElementById('tx-method').textContent = tx.payment_method || 'N/A';
        document.getElementById('tx-ref').textContent = tx.reference_no || 'N/A';
        document.getElementById('tx-member').textContent = (tx.member_name || '').trim() !== '' ? tx.member_name : 'N/A';
        document.getElementById('tx-dept').textContent = (tx.department_name || '').trim() !== '' ? tx.department_name : 'N/A';
        document.getElementById('tx-recorder').textContent = (tx.recorded_by_name || '').trim() !== '' ? tx.recorded_by_name : 'N/A';
        document.getElementById('tx-desc').textContent = (tx.description || '').trim() !== '' ? tx.description : 'N/A';

        modal.classList.remove('hidden');
        setTimeout(() => {
            content.classList.remove('scale-95', 'opacity-0');
            content.classList.add('scale-100', 'opacity-100');
        }, 10);
    }

    function closeTransactionModal() {
        const modal = document.getElementById('transaction-modal');
        const content = document.getElementById('transaction-modal-content');
        if (!modal || !content) return;
        content.classList.remove('scale-100', 'opacity-100');
        content.classList.add('scale-95', 'opacity-0');
        setTimeout(() => modal.classList.add('hidden'), 300);
    }

    function escapeReceiptHtml(text) {
        const div = document.createElement('div');
        div.textContent = String(text || '');
        return div.innerHTML;
    }

    function buildReceiptMarkup(tx, label = 'Official Copy') {
        const currency = <?php echo json_encode($currency); ?>;
        const church = <?php echo json_encode($churchName); ?>;
        const typeLabel = String(tx.transaction_type || 'PAYMENT');
        const amount = Number(tx.amount || 0).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        const date = (tx.transaction_date || '').slice(0, 10);
        const memberName = (tx.member_name || '').trim() || 'N/A';
        const method = (tx.payment_method || '').trim() || 'N/A';
        const receiptNo = (tx.transaction_number || '').trim() || 'N/A';
        const recordedBy = (tx.recorded_by_name || '').trim() || 'N/A';
        return `
            <div style="border-radius:26px;border:1px solid #e5e7eb;overflow:hidden;">
                <div style="padding:18px 22px;background:#0b1220;color:#ffffff;display:flex;align-items:center;justify-content:space-between;gap:12px;">
                    <div style="display:flex;align-items:center;gap:12px;">
                        ${receiptLogoUrl ? `<img src="${escapeReceiptHtml(receiptLogoUrl)}" style="width:44px;height:44px;border-radius:14px;object-fit:cover;border:1px solid rgba(255,255,255,0.18);">` : `<div style="width:44px;height:44px;border-radius:14px;background:rgba(255,255,255,0.08);display:flex;align-items:center;justify-content:center;font-weight:900;">${escapeReceiptHtml(String(church || 'C').slice(0,1).toUpperCase())}</div>`}
                        <div>
                            <div style="font-size:12px;font-weight:900;letter-spacing:0.18em;text-transform:uppercase;color:#fbbf24;">Receipt</div>
                            <div style="font-size:16px;font-weight:900;line-height:1.2;">${escapeReceiptHtml(church)}</div>
                        </div>
                    </div>
                    <div style="text-align:right;">
                        <div style="font-size:11px;font-weight:900;letter-spacing:0.18em;text-transform:uppercase;opacity:0.8;">${escapeReceiptHtml(label)}</div>
                        <div style="font-size:12px;font-weight:800;opacity:0.9;">${escapeReceiptHtml(receiptNo)}</div>
                    </div>
                </div>
                <div style="padding:22px;background:#ffffff;">
                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;">
                        <div style="border:1px solid #e5e7eb;border-radius:18px;padding:14px 16px;">
                            <div style="font-size:11px;font-weight:900;letter-spacing:0.16em;text-transform:uppercase;color:#64748b;">Member</div>
                            <div style="font-size:16px;font-weight:900;color:#0b1220;margin-top:8px;">${escapeReceiptHtml(memberName)}</div>
                            <div style="font-size:12px;font-weight:800;color:#64748b;margin-top:8px;">Date: ${escapeReceiptHtml(date || 'N/A')}</div>
                        </div>
                        <div style="border:1px solid #e5e7eb;border-radius:18px;padding:14px 16px;">
                            <div style="font-size:11px;font-weight:900;letter-spacing:0.16em;text-transform:uppercase;color:#64748b;">Payment</div>
                            <div style="font-size:16px;font-weight:900;color:#0b1220;margin-top:8px;">${escapeReceiptHtml(typeLabel)}</div>
                            <div style="font-size:12px;font-weight:800;color:#64748b;margin-top:8px;">Method: ${escapeReceiptHtml(method)}</div>
                        </div>
                        <div style="grid-column:1 / -1;border:2px solid #fbbf24;border-radius:18px;padding:16px 18px;background:#fff7ed;">
                            <div style="font-size:11px;font-weight:900;letter-spacing:0.16em;text-transform:uppercase;color:#92400e;">Amount Received</div>
                            <div style="font-size:34px;font-weight:900;color:#0b1220;margin-top:6px;">${escapeReceiptHtml(currency)} ${escapeReceiptHtml(amount)}</div>
                            <div style="font-size:12px;font-weight:800;color:#92400e;margin-top:8px;">Received by: ${escapeReceiptHtml(recordedBy)}</div>
                        </div>
                    </div>
                </div>
            </div>
        `;
    }

    function openReceiptModal(id = null) {
        const modal = document.getElementById('receipt-modal');
        const content = document.getElementById('receipt-modal-content');
        const body = document.getElementById('receipt-body');
        if (!modal || !content || !body) return;

        let tx = financeReceiptSeed;
        if (id !== null) {
            tx = (financeTransactions || []).find(t => String(t.id) === String(id));
        }
        if (!tx) return;
        activeReceiptTx = tx;

        body.innerHTML = buildReceiptMarkup(tx, 'Preview Copy');
        modal.classList.remove('hidden');
        setTimeout(() => {
            content.classList.remove('scale-95', 'opacity-0');
            content.classList.add('scale-100', 'opacity-100');
        }, 10);
    }

    function closeReceiptModal() {
        const modal = document.getElementById('receipt-modal');
        const content = document.getElementById('receipt-modal-content');
        if (!modal || !content) return;
        content.classList.remove('scale-100', 'opacity-100');
        content.classList.add('scale-95', 'opacity-0');
        setTimeout(() => modal.classList.add('hidden'), 300);
    }

    function printReceipt() {
        if (!activeReceiptTx) return;
        const receiptWindow = window.open('', '_blank', 'width=1200,height=820');
        if (!receiptWindow) return;
        const printable = buildReceiptMarkup(activeReceiptTx, 'Official Copy');
        receiptWindow.document.write(`
            <html>
            <head>
                <title>Payment Receipt</title>
                <style>
                    * { box-sizing: border-box; }
                    @page { size: A4 portrait; margin: 12mm; }
                    body { font-family: Arial, Helvetica, sans-serif; margin: 0; background: #ffffff; color: #0b1220; }
                    @media print { body { margin: 0; } }
                </style>
            </head>
            <body>${printable}</body>
            </html>
        `);
        receiptWindow.document.close();
        receiptWindow.focus();
        receiptWindow.print();
    }
</script>

<div id="transaction-modal" class="fixed inset-0 bg-slate-950/80 backdrop-blur-xl z-50 hidden flex items-center justify-center p-4">
    <div class="glass-card w-full max-w-2xl rounded-[3.5rem] overflow-hidden shadow-2xl transform transition-all duration-500 scale-95 opacity-0 border-white/10 max-h-[90vh] flex flex-col" id="transaction-modal-content">
        <div class="px-10 py-10 bg-slate-900 relative overflow-hidden border-b border-white/5 flex-shrink-0">
            <div class="relative z-10 flex justify-between items-center text-white">
                <div>
                    <h3 class="text-3xl font-black tracking-tighter">Transaction Details</h3>
                    <p class="text-accent text-[10px] font-black mt-2 tracking-[0.3em] uppercase">Ledger</p>
                </div>
                <button type="button" onclick="closeTransactionModal()" class="w-12 h-12 bg-white/5 hover:bg-accent hover:text-slate-900 rounded-2xl flex items-center justify-center transition-all border border-white/10">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        </div>

        <div class="p-6 sm:p-10 bg-slate-900/50 overflow-y-auto custom-scrollbar flex-1 space-y-8">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <span id="tx-type" class="px-4 py-1.5 text-[9px] font-black rounded-full uppercase tracking-widest border bg-white/5 text-slate-300 border-white/10"></span>
                <div id="tx-amount" class="text-2xl font-black text-white tracking-tighter"></div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                <div class="bg-white/5 rounded-[2rem] p-6 border border-white/10">
                    <p class="text-[9px] font-black text-slate-500 uppercase tracking-widest">Date</p>
                    <p id="tx-date" class="text-sm font-black text-slate-200 mt-3"></p>
                </div>
                <div class="bg-white/5 rounded-[2rem] p-6 border border-white/10">
                    <p class="text-[9px] font-black text-slate-500 uppercase tracking-widest">Payment Method</p>
                    <p id="tx-method" class="text-sm font-black text-slate-200 mt-3"></p>
                </div>
                <div class="bg-white/5 rounded-[2rem] p-6 border border-white/10">
                    <p class="text-[9px] font-black text-slate-500 uppercase tracking-widest">Reference</p>
                    <p id="tx-ref" class="text-sm font-black text-slate-200 mt-3"></p>
                </div>
                <div class="bg-white/5 rounded-[2rem] p-6 border border-white/10">
                    <p class="text-[9px] font-black text-slate-500 uppercase tracking-widest">Recorded By</p>
                    <p id="tx-recorder" class="text-sm font-black text-slate-200 mt-3"></p>
                </div>
                <div class="bg-white/5 rounded-[2rem] p-6 border border-white/10">
                    <p class="text-[9px] font-black text-slate-500 uppercase tracking-widest">Member</p>
                    <p id="tx-member" class="text-sm font-black text-slate-200 mt-3"></p>
                </div>
                <div class="bg-white/5 rounded-[2rem] p-6 border border-white/10">
                    <p class="text-[9px] font-black text-slate-500 uppercase tracking-widest">Department</p>
                    <p id="tx-dept" class="text-sm font-black text-slate-200 mt-3"></p>
                </div>
            </div>

            <div class="bg-white/5 rounded-[2.5rem] p-6 border border-white/10">
                <p class="text-[9px] font-black text-slate-500 uppercase tracking-widest">Description</p>
                <p id="tx-desc" class="text-sm font-bold text-slate-200 mt-3 whitespace-pre-wrap"></p>
            </div>
        </div>
    </div>
</div>

<div id="receipt-modal" class="fixed inset-0 bg-slate-950/80 backdrop-blur-xl z-50 hidden flex items-center justify-center p-4">
    <div class="glass-card w-full max-w-xl rounded-[3.5rem] overflow-hidden shadow-2xl transform transition-all duration-500 scale-95 opacity-0 border-white/10 max-h-[90vh] flex flex-col" id="receipt-modal-content">
        <div class="px-10 py-10 bg-slate-900 relative overflow-hidden border-b border-white/5 flex-shrink-0">
            <div class="relative z-10 flex justify-between items-center text-white">
                <div>
                    <h3 class="text-3xl font-black tracking-tighter">Receipt Ready</h3>
                    <p class="text-accent text-[10px] font-black mt-2 tracking-[0.3em] uppercase">Print Confirmation</p>
                </div>
                <button type="button" onclick="closeReceiptModal()" class="w-12 h-12 bg-white/5 hover:bg-accent hover:text-slate-900 rounded-2xl flex items-center justify-center transition-all border border-white/10">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        </div>
        <div class="p-6 sm:p-10 bg-slate-900/50 overflow-y-auto custom-scrollbar flex-1">
            <div id="receipt-body" class="bg-white rounded-[2.5rem] p-8"></div>
        </div>
        <div class="px-6 sm:px-10 pb-8 bg-slate-900/50 flex flex-col sm:flex-row gap-4">
            <button type="button" onclick="printReceipt()" class="flex-1 bg-accent text-slate-900 py-5 rounded-2xl font-black text-xs uppercase tracking-[0.3em] hover:scale-[1.02] active:scale-95 transition-all shadow-xl shadow-yellow-500/10">
                Print Receipt
            </button>
            <button type="button" onclick="closeReceiptModal()" class="px-10 py-5 bg-white/5 text-slate-300 rounded-2xl font-black text-xs uppercase tracking-[0.3em] hover:bg-white/10 transition-all">
                Close
            </button>
        </div>
    </div>
</div>

<?php if ($isStaff): ?>
<div id="request-change-modal" class="fixed inset-0 bg-slate-950/80 backdrop-blur-xl z-50 hidden flex items-center justify-center p-4">
    <div class="glass-card w-full max-w-lg rounded-[3.5rem] overflow-hidden shadow-2xl transform transition-all duration-500 scale-95 opacity-0 border-white/10 max-h-[90vh] flex flex-col" id="request-change-modal-content">
        <div class="px-10 py-10 bg-slate-900 relative overflow-hidden border-b border-white/5 flex-shrink-0">
            <div class="relative z-10 flex justify-between items-center text-white">
                <div>
                    <h3 class="text-3xl font-black tracking-tighter">Request Edit</h3>
                    <p class="text-accent text-[10px] font-black mt-2 tracking-[0.3em] uppercase" id="request-change-subtitle">Send to head of finance for approval</p>
                </div>
                <button type="button" onclick="closeRequestChangeModal()" class="w-12 h-12 bg-white/5 hover:bg-accent hover:text-slate-900 rounded-2xl flex items-center justify-center transition-all border border-white/10">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        </div>

        <form action="<?php echo BASE_URL; ?>/finance/requestChange" method="POST" class="p-6 sm:p-10 bg-slate-900/50 overflow-y-auto custom-scrollbar flex-1 space-y-6">
            <input type="hidden" name="finance_id" id="request_change_finance_id">
            <div class="bg-white/5 rounded-[2.5rem] p-6 border border-white/10">
                <p class="text-[9px] font-black text-slate-500 uppercase tracking-widest">Transaction</p>
                <p class="text-sm font-black text-slate-200 mt-3" id="request-change-tx-label"></p>
            </div>

            <div class="space-y-2">
                <label class="block text-[9px] font-black text-slate-500 uppercase tracking-widest ml-1">Reason</label>
                <div class="relative group">
                    <i class="fas fa-message absolute left-5 top-5 text-slate-600 group-focus-within:text-accent transition-colors"></i>
                    <textarea name="reason" id="request_change_reason" rows="4" required class="w-full bg-white/5 border border-white/10 focus:border-accent rounded-3xl pl-14 pr-6 py-5 text-sm font-bold text-white transition-all outline-none resize-none" placeholder="Explain what is wrong and what should be corrected"></textarea>
                </div>
            </div>

            <div class="flex flex-col sm:flex-row gap-4 pt-2">
                <button type="submit" class="flex-1 bg-accent text-slate-900 py-5 rounded-2xl font-black text-xs uppercase tracking-[0.2em] hover:scale-[1.02] active:scale-95 transition-all shadow-xl shadow-yellow-500/10">
                    Send Request
                </button>
                <button type="button" onclick="closeRequestChangeModal()" class="px-10 py-5 bg-white/5 text-slate-400 rounded-2xl font-black text-xs uppercase tracking-[0.3em] hover:bg-white/10 transition-all">
                    Cancel
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    function openRequestChangeModal(id) {
        const tx = (financeTransactions || []).find(t => String(t.id) === String(id));
        const modal = document.getElementById('request-change-modal');
        const content = document.getElementById('request-change-modal-content');
        const idEl = document.getElementById('request_change_finance_id');
        const labelEl = document.getElementById('request-change-tx-label');
        const reasonEl = document.getElementById('request_change_reason');
        if (!tx || !modal || !content || !idEl || !labelEl || !reasonEl) return;

        const type = tx.transaction_type || 'Transaction';
        const amount = Number(tx.amount || 0).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        const date = (tx.transaction_date || '').slice(0, 10);
        const ref = (tx.reference_no || '').trim();

        idEl.value = tx.id || '';
        labelEl.textContent = `${type} • ${date || 'N/A'} • <?php echo $currency; ?> ${amount}${ref ? ' • Ref: ' + ref : ''}`;
        reasonEl.value = '';

        modal.classList.remove('hidden');
        setTimeout(() => {
            content.classList.remove('scale-95', 'opacity-0');
            content.classList.add('scale-100', 'opacity-100');
        }, 10);
    }

    function closeRequestChangeModal() {
        const modal = document.getElementById('request-change-modal');
        const content = document.getElementById('request-change-modal-content');
        if (!modal || !content) return;
        content.classList.remove('scale-100', 'opacity-100');
        content.classList.add('scale-95', 'opacity-0');
        setTimeout(() => modal.classList.add('hidden'), 300);
    }

    document.getElementById('request-change-modal')?.addEventListener('click', (e) => {
        if (e.target === document.getElementById('request-change-modal')) closeRequestChangeModal();
    });
</script>
<?php endif; ?>

<?php if (!empty($isDeptHead) || $isStaff): ?>
<div id="edit-tx-modal" class="fixed inset-0 bg-slate-950/80 backdrop-blur-xl z-50 hidden flex items-center justify-center p-4">
    <div class="glass-card w-full max-w-lg rounded-[3.5rem] overflow-hidden shadow-2xl transform transition-all duration-500 scale-95 opacity-0 border-white/10 max-h-[90vh] flex flex-col" id="edit-tx-modal-content">
        <div class="px-10 py-10 bg-slate-900 relative overflow-hidden border-b border-white/5 flex-shrink-0">
            <div class="relative z-10 flex justify-between items-center text-white">
                <div>
                    <h3 class="text-3xl font-black tracking-tighter">Update Transaction</h3>
                    <p class="text-accent text-[10px] font-black mt-2 tracking-[0.3em] uppercase"><?php echo !empty($isDeptHead) ? 'Department Transaction' : 'Approved Change Request'; ?></p>
                </div>
                <button type="button" onclick="closeEditTransactionModal()" class="w-12 h-12 bg-white/5 hover:bg-accent hover:text-slate-900 rounded-2xl flex items-center justify-center transition-all border border-white/10">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        </div>

        <form action="<?php echo BASE_URL; ?>/finance/updateTransaction" method="POST" class="p-6 sm:p-10 bg-slate-900/50 overflow-y-auto custom-scrollbar flex-1 space-y-6">
            <input type="hidden" name="id" id="edit_tx_id">

            <div class="space-y-2">
                <label class="block text-[9px] font-black text-slate-500 uppercase tracking-widest ml-1">Amount</label>
                <div class="relative group">
                    <i class="fas fa-coins absolute left-5 top-1/2 -translate-y-1/2 text-slate-600 group-focus-within:text-accent transition-colors"></i>
                    <input type="number" step="0.01" min="0" name="amount" id="edit_tx_amount" required class="w-full bg-white/5 border border-white/10 focus:border-accent rounded-2xl pl-14 pr-6 py-4 text-sm font-bold text-white transition-all outline-none">
                </div>
            </div>

            <div class="space-y-2">
                <label class="block text-[9px] font-black text-slate-500 uppercase tracking-widest ml-1">Transaction Date</label>
                <div class="relative group">
                    <i class="fas fa-calendar-alt absolute left-5 top-1/2 -translate-y-1/2 text-slate-600 group-focus-within:text-accent transition-colors"></i>
                    <input type="date" name="transaction_date" id="edit_tx_date" required class="w-full bg-white/5 border border-white/10 focus:border-accent rounded-2xl pl-14 pr-6 py-4 text-sm font-bold text-white transition-all outline-none [color-scheme:dark]">
                </div>
            </div>

            <div class="space-y-2">
                <label class="block text-[9px] font-black text-slate-500 uppercase tracking-widest ml-1">Reference</label>
                <div class="relative group">
                    <i class="fas fa-hashtag absolute left-5 top-1/2 -translate-y-1/2 text-slate-600 group-focus-within:text-accent transition-colors"></i>
                    <input type="text" name="reference_no" id="edit_tx_ref" class="w-full bg-white/5 border border-white/10 focus:border-accent rounded-2xl pl-14 pr-6 py-4 text-sm font-bold text-white transition-all outline-none" placeholder="Optional reference">
                </div>
            </div>

            <div class="space-y-2">
                <label class="block text-[9px] font-black text-slate-500 uppercase tracking-widest ml-1">Description</label>
                <div class="relative group">
                    <i class="fas fa-note-sticky absolute left-5 top-5 text-slate-600 group-focus-within:text-accent transition-colors"></i>
                    <textarea name="description" id="edit_tx_desc" rows="3" class="w-full bg-white/5 border border-white/10 focus:border-accent rounded-3xl pl-14 pr-6 py-5 text-sm font-bold text-white transition-all outline-none resize-none" placeholder="Optional notes"></textarea>
                </div>
            </div>

            <div class="flex flex-col sm:flex-row gap-4 pt-2">
                <button type="submit" class="flex-1 bg-accent text-slate-900 py-5 rounded-2xl font-black text-xs uppercase tracking-[0.2em] hover:scale-[1.02] active:scale-95 transition-all shadow-xl shadow-yellow-500/10">
                    Save Changes
                </button>
                <button type="button" onclick="closeEditTransactionModal()" class="px-10 py-5 bg-white/5 text-slate-400 rounded-2xl font-black text-xs uppercase tracking-[0.3em] hover:bg-white/10 transition-all">
                    Cancel
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    function openEditTransactionModal(id) {
        const tx = (financeTransactions || []).find(t => String(t.id) === String(id));
        const modal = document.getElementById('edit-tx-modal');
        const content = document.getElementById('edit-tx-modal-content');
        if (!tx || !modal || !content) return;

        const idEl = document.getElementById('edit_tx_id');
        const amountEl = document.getElementById('edit_tx_amount');
        const dateEl = document.getElementById('edit_tx_date');
        const refEl = document.getElementById('edit_tx_ref');
        const descEl = document.getElementById('edit_tx_desc');
        if (!idEl || !amountEl || !dateEl || !refEl || !descEl) return;

        idEl.value = tx.id || '';
        amountEl.value = tx.amount || '';
        dateEl.value = (tx.transaction_date || '').slice(0, 10);
        refEl.value = tx.reference_no || '';
        descEl.value = tx.description || '';

        modal.classList.remove('hidden');
        setTimeout(() => {
            content.classList.remove('scale-95', 'opacity-0');
            content.classList.add('scale-100', 'opacity-100');
        }, 10);
    }

    function closeEditTransactionModal() {
        const modal = document.getElementById('edit-tx-modal');
        const content = document.getElementById('edit-tx-modal-content');
        if (!modal || !content) return;
        content.classList.remove('scale-100', 'opacity-100');
        content.classList.add('scale-95', 'opacity-0');
        setTimeout(() => modal.classList.add('hidden'), 300);
    }

    document.getElementById('edit-tx-modal')?.addEventListener('click', (e) => {
        if (e.target === document.getElementById('edit-tx-modal')) closeEditTransactionModal();
    });
</script>
<?php endif; ?>

<script>
    document.getElementById('transaction-modal')?.addEventListener('click', (e) => {
        if (e.target === document.getElementById('transaction-modal')) closeTransactionModal();
    });
    document.getElementById('receipt-modal')?.addEventListener('click', (e) => {
        if (e.target === document.getElementById('receipt-modal')) closeReceiptModal();
    });
    if (financeReceiptSeed && financeReceiptSeed.id) {
        setTimeout(() => openReceiptModal(financeReceiptSeed.id), 200);
    }
</script>
