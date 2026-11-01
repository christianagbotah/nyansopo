<!DOCTYPE html>
<html>
<head>
    <title><?php echo $label; ?> - Payments Report</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .header { text-align: center; margin-bottom: 30px; }
        .header h2 { margin: 5px 0; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #dbeafe; font-weight: bold; color: #1e40af; }
        .text-right { text-align: right; }
        .total-row { font-weight: bold; background-color: #eff6ff; }
        @media print {
            .no-print { display: none; }
        }
    </style>
</head>
<body>
    <div class="no-print" style="margin-bottom: 20px;">
        <button onclick="window.print()" style="padding: 10px 20px; background: #007bff; color: white; border: none; border-radius: 5px; cursor: pointer;">
            Print
        </button>
        <button onclick="window.close()" style="padding: 10px 20px; background: #6c757d; color: white; border: none; border-radius: 5px; cursor: pointer; margin-left: 10px;">
            Close
        </button>
    </div>

    <div class="header">
        <h2><?php echo $school_name; ?></h2>
        <h3><?php echo $label; ?> - Payments Collected</h3>
        <p><strong>Date:</strong> <?php echo $date; ?></p>
        <p><strong>Total Payments:</strong> <?php echo count($payments); ?></p>
    </div>

    <table>
        <thead>
            <tr>
                <th style="width: 5%;">#</th>
                <th style="width: 12%;">Student Code</th>
                <th style="width: 28%;">Student Name</th>
                <th style="width: 15%;">Class</th>
                <th style="width: 25%;">Collected By</th>
                <th style="width: 15%;" class="text-right">Amount</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            $total = 0;
            foreach ($payments as $index => $row): 
                $student = $this->db->get_where('student', ['student_id' => $row['student_id']])->row();
                $enroll = $this->db->get_where('enroll', ['student_id' => $row['student_id'], 'mute' => '0'])->row();
                $class = $this->db->get_where('class', ['class_id' => $enroll->class_id])->row();
                
                if (substr($row['issuer_id'], 0, 1) == 't') {
                    $tid = substr($row['issuer_id'], 1);
                    $issuer = $this->db->get_where('teacher', ['teacher_id' => $tid])->row();
                    $issuer_name = $issuer->name . ' (Teacher)';
                } else {
                    $issuer = $this->db->get_where('admin', ['admin_id' => $row['issuer_id']])->row();
                    $issuer_name = $issuer->name . ' (Admin)';
                }
                
                $amount = $row['amount'];
                $total += $amount;
            ?>
            <tr>
                <td><?php echo $index + 1; ?></td>
                <td><?php echo $student->student_code; ?></td>
                <td><?php echo $student->name; ?></td>
                <td><?php echo $class->name . ' ' . $class->name_numeric; ?></td>
                <td><?php echo $issuer_name; ?></td>
                <td class="text-right" style="color: #3b82f6;"><?php echo $currency . number_format($amount, 2); ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
        <tfoot>
            <tr class="total-row">
                <td colspan="5" class="text-right">TOTAL:</td>
                <td class="text-right" style="color: #3b82f6;"><?php echo $currency . number_format($total, 2); ?></td>
            </tr>
        </tfoot>
    </table>

    <div style="margin-top: 50px;">
        <p>Generated on: <?php echo date('l, F d, Y h:i A'); ?></p>
    </div>
</body>
</html>
