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
                    <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase">Member ID</th>
                    <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase">Status</th>
                </tr>
            </thead>
            <tbody class="divide-y">
                <?php if (empty($recent_records)): ?>
                    <tr>
                        <td colspan="4" class="px-6 py-8 text-center text-gray-500">No attendance records found.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($recent_records as $record): ?>
                        <tr>
                            <td class="px-6 py-4 text-sm text-gray-900"><?php echo date('M d, Y', strtotime($record['service_date'])); ?></td>
                            <td class="px-6 py-4 text-sm text-gray-700"><?php echo $record['service_type']; ?></td>
                            <td class="px-6 py-4 text-sm text-gray-700">#<?php echo $record['member_id']; ?></td>
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
