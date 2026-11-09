<?php
/**
 * CASHIER STUDENT VIEW MODAL
 * Enterprise-grade modal for cashiers to view student details and daily fee payment status
 */

$student_id = $param2;
$student = $this->db->get_where('student', array('student_id' => $student_id))->row();
$enrollment = $this->crud_model->getStudentCurrentEnrollmentStatusRow($student_id);
$class_info = $this->db->get_where('class', array('class_id' => $enrollment->class_id))->row();
$section_info = $this->db->get_where('section', array('section_id' => $enrollment->section_id))->row();

// Get parent information
$parent_id = $student->parent_id;
$guardian_name = 'N/A';
$guardian_phone = 'N/A';
$father_name = 'N/A';
$father_phone = 'N/A';
$mother_name = 'N/A';
$mother_phone = 'N/A';

if($parent_id) {
    $parent = $this->db->get_where('parent', array('parent_id' => $parent_id))->row();
    if($parent) {
        $guardian_name = $parent->name ?: 'N/A';
        $guardian_phone = $parent->phone ?: 'N/A';
        $father_name = $parent->father_name ?: 'N/A';
        $father_phone = $parent->father_phone ?: 'N/A';
        $mother_name = $parent->mother_name ?: 'N/A';
        $mother_phone = $parent->mother_phone ?: 'N/A';
    }
}

// Get daily fee modules that are enabled
$enabled_modules = [];
$fee_types = ['feeding', 'classes', 'water', 'breakfast', 'transport'];
foreach($fee_types as $type) {
    if(is_fee_module_enabled($type)) {
        $enabled_modules[] = $type;
    }
}

// Get daily fee wallet balances for each enabled module
$daily_fee_arrears = [];
$daily_fee_prepaid = [];
$total_arrears = 0;
$total_prepaid = 0;

// Get wallet record for student
$CI =& get_instance();
$CI->db->select('feeding_balance, classes_balance, water_balance, breakfast_balance, transport_balance');
$CI->db->where('student_id', $student_id);
$wallet = $CI->db->get('daily_fee_wallet');

if($wallet && $wallet->num_rows() > 0) {
    $row = $wallet->row();
    
    // Map fee types to their wallet balance columns
    $balance_map = [
        'feeding' => $row->feeding_balance,
        'classes' => $row->classes_balance,
        'water' => $row->water_balance,
        'breakfast' => $row->breakfast_balance,
        'transport' => $row->transport_balance
    ];
    
    foreach($enabled_modules as $module) {
        if(isset($balance_map[$module])) {
            $balance = $balance_map[$module];
            if($balance < 0) {
                // Negative balance = Arrears (student owes)
                $daily_fee_arrears[$module] = abs($balance);
                $total_arrears += abs($balance);
            } elseif($balance > 0) {
                // Positive balance = Prepaid (student has credit)
                $daily_fee_prepaid[$module] = $balance;
                $total_prepaid += $balance;
            }
        }
    }
}

$currency = $this->db->get_where('settings', array('type' => 'currency'))->row()->description;
?>

<!-- Student Basic Info -->
<div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 25px; border-radius: 12px; margin-bottom: 25px; color: white;">
    <div style="display: flex; align-items: center; gap: 20px;">
        <div style="flex-shrink: 0;">
            <img src="<?php echo $this->crud_model->get_image_url('student', $student_id, $student->sex); ?>" 
                 style="width: 100px; height: 100px; border-radius: 50%; border: 4px solid white; object-fit: cover;">
        </div>
        <div style="flex: 1;">
            <h3 style="margin: 0 0 10px 0; color: white; font-size: 24px; font-weight: 700;"><?php echo $student->name; ?></h3>
            <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 10px; font-size: 14px;">
                <div><i class="fa fa-id-card"></i> <strong>ID:</strong> <?php echo $student->student_code; ?></div>
                <div><i class="fa fa-venus-mars"></i> <strong>Gender:</strong> <?php echo ucfirst($student->sex); ?></div>
                <div><i class="fa fa-school"></i> <strong>Class:</strong> <?php echo $class_info->name . ' ' . $class_info->name_numeric . ' ' . $section_info->name; ?></div>
                <div><i class="fa fa-home"></i> <strong>Type:</strong> <?php echo ucfirst($enrollment->residence_type); ?></div>
            </div>
        </div>
    </div>
</div>

<!-- Guardian Information -->
<div style="background: #f8fafc; padding: 20px; border-radius: 12px; margin-bottom: 25px; border-left: 4px solid #667eea;">
    <h5 style="color: #1e293b; font-size: 18px; font-weight: 700; margin: 0 0 15px 0;">
        <i class="fa fa-user-shield"></i> Guardian Information
    </h5>
    <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 15px;">
        <div style="background: white; padding: 15px; border-radius: 8px;">
            <p style="color: #64748b; font-size: 12px; margin: 0 0 5px 0;">Guardian Name</p>
            <p style="color: #1e293b; font-size: 15px; font-weight: 600; margin: 0;"><?php echo $guardian_name; ?></p>
        </div>
        <div style="background: white; padding: 15px; border-radius: 8px;">
            <p style="color: #64748b; font-size: 12px; margin: 0 0 5px 0;">Guardian Phone</p>
            <p style="color: #1e293b; font-size: 15px; font-weight: 600; margin: 0;">
                <a href="tel:<?php echo $guardian_phone; ?>" style="color: #667eea; text-decoration: none;">
                    <i class="fa fa-phone"></i> <?php echo $guardian_phone; ?>
                </a>
            </p>
        </div>
    </div>
</div>

<!-- Father & Mother Information -->
<div style="background: #f8fafc; padding: 20px; border-radius: 12px; margin-bottom: 25px; border-left: 4px solid #8b5cf6;">
    <h5 style="color: #1e293b; font-size: 18px; font-weight: 700; margin: 0 0 15px 0;">
        <i class="fa fa-users"></i> Parents Information
    </h5>
    <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 15px;">
        <div style="background: white; padding: 15px; border-radius: 8px;">
            <p style="color: #64748b; font-size: 12px; margin: 0 0 5px 0;">Father Name</p>
            <p style="color: #1e293b; font-size: 15px; font-weight: 600; margin: 0;"><?php echo $father_name; ?></p>
        </div>
        <div style="background: white; padding: 15px; border-radius: 8px;">
            <p style="color: #64748b; font-size: 12px; margin: 0 0 5px 0;">Father Phone</p>
            <p style="color: #1e293b; font-size: 15px; font-weight: 600; margin: 0;">
                <a href="tel:<?php echo $father_phone; ?>" style="color: #667eea; text-decoration: none;">
                    <i class="fa fa-phone"></i> <?php echo $father_phone; ?>
                </a>
            </p>
        </div>
        <div style="background: white; padding: 15px; border-radius: 8px;">
            <p style="color: #64748b; font-size: 12px; margin: 0 0 5px 0;">Mother Name</p>
            <p style="color: #1e293b; font-size: 15px; font-weight: 600; margin: 0;"><?php echo $mother_name; ?></p>
        </div>
        <div style="background: white; padding: 15px; border-radius: 8px;">
            <p style="color: #64748b; font-size: 12px; margin: 0 0 5px 0;">Mother Phone</p>
            <p style="color: #1e293b; font-size: 15px; font-weight: 600; margin: 0;">
                <a href="tel:<?php echo $mother_phone; ?>" style="color: #667eea; text-decoration: none;">
                    <i class="fa fa-phone"></i> <?php echo $mother_phone; ?>
                </a>
            </p>
        </div>
    </div>
</div>

    <!-- Daily Fee Payment Status -->
    <div style="background: #f8fafc; padding: 20px; border-radius: 12px; border-left: 4px solid <?php echo $total_arrears > 0 ? '#ef4444' : ($total_prepaid > 0 ? '#3b82f6' : '#10b981'); ?>;">
        <h5 style="color: #1e293b; font-size: 18px; font-weight: 700; margin: 0 0 15px 0;">
            <i class="fa fa-money-bill-wave"></i> Daily Fee Payment Status
        </h5>
        
        <?php if(count($daily_fee_prepaid) > 0): ?>
            <div style="background: #dbeafe; padding: 15px; border-radius: 8px; margin-bottom: 15px; border-left: 4px solid #3b82f6;">
                <p style="color: #1e40af; font-size: 14px; font-weight: 600; margin: 0 0 10px 0;">
                    <i class="fa fa-check-circle"></i> Prepaid Balance (Credit)
                </p>
                <div style="display: grid; gap: 10px;">
                    <?php foreach($daily_fee_prepaid as $type => $amount): ?>
                        <div style="display: flex; justify-content: space-between; align-items: center; background: white; padding: 12px; border-radius: 6px;">
                            <span style="color: #1e293b; font-weight: 600; text-transform: capitalize;">
                                <i class="fa fa-<?php echo $type == 'feeding' ? 'utensils' : ($type == 'transport' ? 'bus' : ($type == 'water' ? 'tint' : ($type == 'breakfast' ? 'coffee' : 'book'))); ?>"></i>
                                <?php echo ucfirst($type); ?> Fee
                            </span>
                            <span style="color: #2563eb; font-size: 16px; font-weight: 700;">
                                <?php echo $currency . ' ' . number_format($amount, 2); ?>
                            </span>
                        </div>
                    <?php endforeach; ?>
                </div>
                <div style="margin-top: 15px; padding-top: 15px; border-top: 2px solid #bfdbfe;">
                    <div style="display: flex; justify-content: space-between; align-items: center;">
                        <span style="color: #1e40af; font-size: 16px; font-weight: 700;">Total Prepaid:</span>
                        <span style="color: #2563eb; font-size: 20px; font-weight: 700;">
                            <?php echo $currency . ' ' . number_format($total_prepaid, 2); ?>
                        </span>
                    </div>
                </div>
            </div>
        <?php endif; ?>
        
        <?php if(count($daily_fee_arrears) > 0): ?>
            <div style="background: #fee2e2; padding: 15px; border-radius: 8px; margin-bottom: 15px; border-left: 4px solid #ef4444;">
                <p style="color: #991b1b; font-size: 14px; font-weight: 600; margin: 0 0 10px 0;">
                    <i class="fa fa-exclamation-triangle"></i> Outstanding Arrears
                </p>
                <div style="display: grid; gap: 10px;">
                    <?php foreach($daily_fee_arrears as $type => $amount): ?>
                        <div style="display: flex; justify-content: space-between; align-items: center; background: white; padding: 12px; border-radius: 6px;">
                            <span style="color: #1e293b; font-weight: 600; text-transform: capitalize;">
                                <i class="fa fa-<?php echo $type == 'feeding' ? 'utensils' : ($type == 'transport' ? 'bus' : ($type == 'water' ? 'tint' : ($type == 'breakfast' ? 'coffee' : 'book'))); ?>"></i>
                                <?php echo ucfirst($type); ?> Fee
                            </span>
                            <span style="color: #dc2626; font-size: 16px; font-weight: 700;">
                                <?php echo $currency . ' ' . number_format($amount, 2); ?>
                            </span>
                        </div>
                    <?php endforeach; ?>
                </div>
                <div style="margin-top: 15px; padding-top: 15px; border-top: 2px solid #fecaca;">
                    <div style="display: flex; justify-content: space-between; align-items: center;">
                        <span style="color: #991b1b; font-size: 16px; font-weight: 700;">Total Arrears:</span>
                        <span style="color: #dc2626; font-size: 20px; font-weight: 700;">
                            <?php echo $currency . ' ' . number_format($total_arrears, 2); ?>
                        </span>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <?php if(count($daily_fee_prepaid) == 0): ?>
            <div style="background: #d1fae5; padding: 20px; border-radius: 8px; text-align: center; border-left: 4px solid #10b981;">
                <i class="fa fa-check-circle" style="color: #059669; font-size: 48px; margin-bottom: 10px;"></i>
                <p style="color: #065f46; font-size: 16px; font-weight: 600; margin: 0;">
                    No Outstanding Arrears
                </p>
                <p style="color: #047857; font-size: 14px; margin: 5px 0 0 0;">
                    All daily fees are up to date
                </p>
            </div>
            <?php endif; ?>
        <?php endif; ?>

        <?php if(count($enabled_modules) == 0): ?>
            <div style="background: #fef3c7; padding: 15px; border-radius: 8px; text-align: center;">
                <i class="fa fa-info-circle" style="color: #d97706; font-size: 24px; margin-bottom: 10px;"></i>
                <p style="color: #92400e; font-size: 14px; margin: 0;">
                    No daily fee modules are currently enabled
                </p>
            </div>
        <?php endif; ?>
    </div>

<!-- Collect Payment Button -->
<?php if($total_arrears > 0): ?>
<div style="margin-top: 20px; text-align: center;">
    <button type="button" onclick="collectPayment(<?php echo $student_id; ?>)" class="btn btn-lg" style="background: linear-gradient(135deg, #10b981 0%, #059669 100%); color: white; font-weight: 700; padding: 12px 30px; border: none; border-radius: 8px; box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);">
        <i class="fa fa-money-bill-wave"></i> Collect Payment
    </button>
</div>
<?php endif; ?>

<script>
function collectPayment(studentId) {
    $('#modal_ajax').modal('hide');
    setTimeout(function() {
        window.location.href = '<?php echo site_url('fee_collection'); ?>?student_id=' + studentId;
    }, 300);
}
</script>
