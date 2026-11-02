<div class="overflow-x-auto">
    <table class="w-full text-base">
        <thead class="bg-gray-50">
            <tr>
                <th class="px-4 py-3 text-left font-semibold text-gray-700"><?php echo get_phrase('date'); ?></th>
                <th class="px-4 py-3 text-left font-semibold text-gray-700"><?php echo get_phrase('recipient'); ?></th>
                <th class="px-4 py-3 text-left font-semibold text-gray-700"><?php echo get_phrase('phone'); ?></th>
                <th class="px-4 py-3 text-left font-semibold text-gray-700"><?php echo get_phrase('status'); ?></th>
                <th class="px-4 py-3 text-left font-semibold text-gray-700"><?php echo get_phrase('message'); ?></th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-200">
            <?php if(empty($logs)): ?>
                <tr>
                    <td colspan="5" class="px-4 py-8 text-center text-gray-500">
                        <?php echo get_phrase('no_logs_found'); ?>
                    </td>
                </tr>
            <?php else: ?>
                <?php foreach($logs as $log): ?>
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3 text-gray-700"><?php echo date('M d, Y H:i', strtotime($log['sent_at'])); ?></td>
                        <td class="px-4 py-3 text-gray-700"><?php echo $log['recipient_name']; ?></td>
                        <td class="px-4 py-3 text-gray-700"><?php echo $log['recipient_phone']; ?></td>
                        <td class="px-4 py-3">
                            <?php
                            $status_class = 'bg-gray-100 text-gray-700';
                            if($log['status'] == 'sent') $status_class = 'bg-green-100 text-green-700';
                            if($log['status'] == 'failed') $status_class = 'bg-red-100 text-red-700';
                            if($log['status'] == 'pending') $status_class = 'bg-yellow-100 text-yellow-700';
                            ?>
                            <span class="px-3 py-1 rounded-full text-sm font-semibold <?php echo $status_class; ?>">
                                <?php echo ucfirst($log['status']); ?>
                            </span>
                        </td>
                        <td class="px-4 py-3 text-gray-700"><?php echo substr($log['message'], 0, 50) . '...'; ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<div class="mt-4 pt-4 border-t">
    <button type="button" class="w-full px-6 py-3 bg-gray-100 hover:bg-gray-200 text-gray-700 text-lg font-semibold rounded-lg transition" data-dismiss="modal">
        Close
    </button>
</div>
