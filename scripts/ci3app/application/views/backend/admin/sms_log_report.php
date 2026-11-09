<!-- SMS Log Report -->
<div class="row">
    <div class="col-md-12">
        <div class="panel panel-primary">
            <div class="panel-heading">
                <h3 class="panel-title"><?php echo get_phrase('sms_log_report'); ?></h3>
            </div>
            <div class="panel-body">
                <table id="smsLogTable" class="table table-striped table-bordered">
                    <thead>
                        <tr>
                            <th><?php echo get_phrase('date'); ?></th>
                            <th><?php echo get_phrase('student'); ?></th>
                            <th><?php echo get_phrase('phone'); ?></th>
                            <th><?php echo get_phrase('type'); ?></th>
                            <th><?php echo get_phrase('message'); ?></th>
                            <th><?php echo get_phrase('status'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($sms_logs as $log): ?>
                        <tr>
                            <td><?php echo date('Y-m-d H:i', $log['sent_at']); ?></td>
                            <td>
                                <?php 
                                $student = $this->db->get_where('student', ['student_id' => $log['student_id']])->row();
                                echo $student->name;
                                ?>
                            </td>
                            <td><?php echo $log['phone']; ?></td>
                            <td>
                                <span class="label label-info">
                                    <?php echo str_replace('_', ' ', ucfirst($log['type'])); ?>
                                </span>
                            </td>
                            <td><?php echo substr($log['message'], 0, 50) . '...'; ?></td>
                            <td>
                                <?php if($log['status'] == 'sent'): ?>
                                    <span class="label label-success"><?php echo get_phrase('sent'); ?></span>
                                <?php elseif($log['status'] == 'failed'): ?>
                                    <span class="label label-danger"><?php echo get_phrase('failed'); ?></span>
                                <?php else: ?>
                                    <span class="label label-warning"><?php echo get_phrase('pending'); ?></span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    $('#smsLogTable').DataTable({
        "order": [[0, "desc"]],
        "pageLength": 25
    });
});
</script>
