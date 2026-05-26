<div class="flex justify-between items-center mb-6">
    <h2 class="text-2xl font-bold text-gray-800">Attendance Dashboard</h2>
    <div class="flex space-x-3">
        <a href="attendance/mark" class="bg-purple-600 text-white px-4 py-2 rounded-lg hover:bg-purple-700 transition-colors">
            <i class="fas fa-check-double mr-2"></i> Mark Attendance
        </a>
    </div>
</div>

<div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
    <div class="bg-white p-6 rounded-xl shadow-sm border-l-4 border-purple-500">
        <p class="text-sm text-gray-500 uppercase font-bold tracking-wider">Avg. Attendance Rate</p>
        <h3 class="text-3xl font-bold text-gray-800 mt-1"><?php echo $attendance_rate; ?></h3>
    </div>
</div>

<div class="bg-white rounded-xl shadow-sm p-6 mb-8">
    <div class="flex items-start justify-between gap-4 flex-col md:flex-row">
        <div>
            <h4 class="font-bold text-gray-800">BioTime Sync</h4>
            <p class="text-sm text-gray-500 mt-1">Imports device punches and marks members present by matching Member Bio ID to BioTime emp_code.</p>
        </div>
        <?php if (!empty($biotime_configured)): ?>
            <span class="px-3 py-1.5 text-xs font-bold rounded-full bg-emerald-100 text-emerald-800">Connected</span>
        <?php else: ?>
            <span class="px-3 py-1.5 text-xs font-bold rounded-full bg-amber-100 text-amber-800">Not Configured</span>
        <?php endif; ?>
    </div>

    <?php if (!empty($biotime_configured)): ?>
        <form action="attendance/syncBioTime" method="POST" class="mt-6 grid grid-cols-1 md:grid-cols-3 gap-4 items-end">
            <div>
                <label class="block text-sm font-semibold text-gray-600 mb-2">Service Date</label>
                <input type="date" name="service_date" required value="<?php echo date('Y-m-d'); ?>" class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-purple-500 outline-none">
            </div>
            <div>
                <label class="block text-sm font-semibold text-gray-600 mb-2">Service Type</label>
                <select name="service_type" required class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-purple-500 outline-none">
                    <option value="Sunday Service">Sunday Service</option>
                    <option value="Mid-week Service">Mid-week Service</option>
                    <option value="Youth Meeting">Youth Meeting</option>
                    <option value="Special Event">Special Event</option>
                </select>
            </div>
            <div class="flex justify-start md:justify-end">
                <button type="submit" class="bg-gray-900 text-white px-6 py-2.5 rounded-lg font-bold hover:bg-black transition-all">
                    <i class="fas fa-fingerprint mr-2"></i> Sync Now
                </button>
            </div>
        </form>
        <div class="mt-4 text-xs text-gray-500">
            BioTime URL: <span class="font-semibold"><?php echo htmlspecialchars((string)($biotime_url ?? '')); ?></span>
        </div>
    <?php else: ?>
        <div class="mt-6 bg-gray-50 border rounded-lg p-4 text-sm text-gray-700">
            Set these environment variables to enable BioTime sync: <span class="font-semibold">BIOTIME_URL</span> and either <span class="font-semibold">BIOTIME_TOKEN</span> or <span class="font-semibold">BIOTIME_USERNAME</span> + <span class="font-semibold">BIOTIME_PASSWORD</span> (optional: <span class="font-semibold">BIOTIME_TZ</span>).
        </div>
    <?php endif; ?>
</div>

<div class="bg-white rounded-xl shadow-sm overflow-hidden">
    <div class="px-6 py-4 border-b">
        <h4 class="font-bold text-gray-800">Recent Attendance Records</h4>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-left">
            <thead>
                <tr class="bg-gray-50 border-b">
                    <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase">Service Date</th>
                    <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase">Service Type</th>
                    <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase">Member</th>
                    <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase">Bio ID</th>
                    <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase">Source</th>
                    <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase">Device Time</th>
                    <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase">Status</th>
                </tr>
            </thead>
            <tbody class="divide-y">
                <?php if (empty($recent_records)): ?>
                    <tr>
                        <td colspan="7" class="px-6 py-8 text-center text-gray-500">No attendance records found.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($recent_records as $record): ?>
                        <?php
                            $name = trim((string)($record['first_name'] ?? '') . ' ' . (string)($record['last_name'] ?? ''));
                            $code = trim((string)($record['member_code'] ?? ''));
                            $bio = trim((string)($record['bio_id'] ?? ''));
                            $source = trim((string)($record['source'] ?? 'manual'));
                            $deviceTime = trim((string)($record['device_time'] ?? ''));
                        ?>
                        <tr>
                            <td class="px-6 py-4 text-sm text-gray-900"><?php echo !empty($record['service_date']) ? date('M d, Y', strtotime((string)$record['service_date'])) : 'N/A'; ?></td>
                            <td class="px-6 py-4 text-sm text-gray-700"><?php echo htmlspecialchars((string)($record['service_type'] ?? '')); ?></td>
                            <td class="px-6 py-4 text-sm text-gray-700">
                                <div class="font-semibold text-gray-900"><?php echo htmlspecialchars($name !== '' ? $name : ('#' . (int)($record['member_id'] ?? 0))); ?></div>
                                <?php if ($code !== ''): ?>
                                    <div class="text-xs text-gray-500"><?php echo htmlspecialchars($code); ?></div>
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-700"><?php echo htmlspecialchars($bio !== '' ? $bio : '—'); ?></td>
                            <td class="px-6 py-4 text-sm text-gray-700"><?php echo htmlspecialchars($source !== '' ? $source : 'manual'); ?></td>
                            <td class="px-6 py-4 text-sm text-gray-700">
                                <?php echo $deviceTime !== '' ? htmlspecialchars(date('M d, Y H:i', strtotime($deviceTime))) : '—'; ?>
                            </td>
                            <td class="px-6 py-4">
                                <span class="px-2 py-1 text-xs font-bold rounded-full bg-green-100 text-green-700">PRESENT</span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
