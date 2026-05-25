<div class="mb-6">
    <a href="attendance" class="text-purple-600 hover:text-purple-700 font-medium">
        <i class="fas fa-arrow-left mr-2"></i> Back to Dashboard
    </a>
    <h2 class="text-2xl font-bold text-gray-800 mt-2">Mark Service Attendance</h2>
</div>

<div class="bg-white rounded-xl shadow-sm p-8">
    <form action="attendance/store" method="POST">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
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
        </div>

        <div class="mb-6">
            <h4 class="font-bold text-gray-800 mb-4">Select Members Present</h4>
            <div class="max-h-96 overflow-y-auto border rounded-lg">
                <table class="w-full text-left">
                    <thead class="bg-gray-50 sticky top-0">
                        <tr>
                            <th class="px-6 py-3 border-b"><input type="checkbox" id="select-all" class="rounded text-purple-600"></th>
                            <th class="px-6 py-3 border-b text-xs font-bold text-gray-500 uppercase">Member Name</th>
                            <th class="px-6 py-3 border-b text-xs font-bold text-gray-500 uppercase">Code</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        <?php foreach ($members as $member): ?>
                            <tr class="hover:bg-gray-50 cursor-pointer" onclick="this.querySelector('input').click()">
                                <td class="px-6 py-4">
                                    <input type="checkbox" name="member_ids[]" value="<?php echo $member['id']; ?>" class="rounded text-purple-600 member-check" onclick="event.stopPropagation()">
                                </td>
                                <td class="px-6 py-4 text-sm font-medium text-gray-900"><?php echo $member['first_name'] . ' ' . $member['last_name']; ?></td>
                                <td class="px-6 py-4 text-sm text-gray-500"><?php echo $member['member_code']; ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="flex justify-end">
            <button type="submit" class="bg-purple-600 text-white px-8 py-3 rounded-lg font-bold hover:bg-purple-700 transition-all shadow-lg shadow-purple-900/10">
                Submit Attendance
            </button>
        </div>
    </form>
</div>

<script>
    document.getElementById('select-all').addEventListener('change', function() {
        const checks = document.querySelectorAll('.member-check');
        checks.forEach(check => check.checked = this.checked);
    });
</script>
