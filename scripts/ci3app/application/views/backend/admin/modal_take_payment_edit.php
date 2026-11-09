<?php
$receipt_code = $param2;

// Get payment details for this receipt code
$this->db->where('receipt_code', $receipt_code);
$payment_data = $this->db->get('payment')->row_array();

if (!$payment_data) {
    echo '<div class="alert alert-danger">Payment record not found.</div>';
    return;
}


$student_id = $payment_data['student_id'];
$currency = $this->db->get_where('settings', array('type' => 'currency'))->row()->description;
$fmt = new NumberFormatter('en_US', NumberFormatter::CURRENCY);

$running_year = get_settings('running_year');
$enroll_data = $this->db->get_where('enroll', array('student_id' => $student_id, 'year' => $running_year))->row();
$class_id = $enroll_data ? $enroll_data->class_id : 0;


$student_info = $this->crud_model->getStudentInfoById($student_id);
$class_name = $class_id ? getFullClassName($class_id) : 'N/A';
$total_fee_owes = $this->financial_report_model->getFeeOwedByStudentId($student_id);
$student_name = $student_info->name;

// Get total amount for this receipt code
$this->db->select_sum('amount');
$this->db->where('receipt_code', $receipt_code);
$total_amount = $this->db->get('payment')->row()->amount;


?>

<div class="grid grid-cols-1">
    <section class="px-4 md:px-15">
        <div class="font-extrabold text-2xl text-gray-500 text-right">EDIT PAYMENT</div>
        <div class="flex flex-col p-5 w-full max-w-full border-t-8 bg-white shadow-md border border-t-orange-500 rounded-xl">
            <div class="flex flex-col gap-6 w-full max-w-full p-4">
                
                <div class="grid grid-cols-2 gap-4">
                    <div class="col-span-2 text-gray-600 font-mono font-bold text-xl border-b border-b-gray-400 pb-3">STUDENT'S DETAILS</div>
                    <div class="text-xl font-semibold text-gray-400 dark:text-white">NAME:</div>
                    <div class="text-xl font-semibold text-gray-400 dark:text-white uppercase"><?=$student_name;?></div>

                    <div class="text-xl font-semibold text-gray-400 dark:text-white">CLASS:</div>
                    <div class="text-xl font-semibold text-gray-400 dark:text-white uppercase"><?=$class_name;?></div>

                    <div class="text-xl font-semibold text-gray-500 dark:text-white">TOTAL PAYABLE:</div>
                    <div class="text-xl font-semibold text-gray-500 dark:text-white uppercase"><?=numfmt_format_currency($fmt, $total_fee_owes, $currency);?></div>
                </div><hr>
                <div class="grid grid-cols-1 gap-4">
                    <?php echo form_open(site_url('admin/invoice/update_payment'), array('class' => 'validate flex flex-col gap-5', 'id' => 'payment_edit_form')); ?>
                        <div class="flex items-center w-full">
                            <label for="payment_method" class="block mb-2 font-bold text-gray-700 dark:text-white w-full max-w-full">PAYMENT MODE</label>
                            <select id="payment_method" name="payment_method" data-validate="required" data-message-required="<?php echo get_phrase('field_required');?>" class="bg-gray-50 border border-gray-300 text-gray-900 rounded-lg focus:ring-primary-500 text-xl focus:border-primary-500 block w-full h-16 p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-primary-500 dark:focus:border-primary-500" required="true" onchange="showMthodDetails($(this).val())">
                                <option value="cash" <?=$payment_data['payment_method'] == 'cash' ? 'selected' : '';?>>CASH</option>
                                <option value="momo" <?=$payment_data['payment_method'] == 'momo' ? 'selected' : '';?>>MOBILE MONEY</option>
                                <option value="cheque" <?=$payment_data['payment_method'] == 'cheque' ? 'selected' : '';?>>CHEQUE</option>
                            </select>
                        </div>
                        <div class="flex items-center w-full">
                            <label for="amount" class="block mb-2 font-bold text-gray-700 dark:text-white w-full max-w-full">AMOUNT</label>
                            <input type="number" name="amount" min="1" id="amount" value="<?=$total_amount;?>" data-validate="required" data-message-required="<?php echo get_phrase('amount_required');?>" class="bg-gray-50 border border-gray-300 text-gray-900 rounded-lg focus:ring-primary-600 focus:border-primary-600 block w-full p-2.5 h-16 max-h-16 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white text-xl dark:focus:ring-primary-500 dark:focus:border-primary-500" required="true">
                        </div>
                        <div class="flex items-center w-full">
                            <label for="receipt_code" class="block mb-2 font-bold text-gray-700 dark:text-white w-full max-w-full">RECEIPT No.</label>
                            <input type="text" name="receipt_code" minlength="3" id="receipt_code" value="<?=$receipt_code;?>" readonly class="bg-gray-200 border border-gray-300 text-gray-900 rounded-lg block w-full p-2.5 h-16 max-h-16 text-xl tracking-wider">
                        </div>

                        <div class="flex items-center w-full momo_transaction_id" style="display: <?=$payment_data['payment_method'] == 'momo' ? 'flex' : 'none';?>">
                            <label for="momo_transaction_id" class="block mb-2 font-bold text-gray-700 dark:text-white w-full max-w-full">TRANSACTION ID</label>
                            <input type="text" name="momo_transaction_id" id="momo_transaction_id" value="<?=isset($payment_data['momo_transaction_id']) ? $payment_data['momo_transaction_id'] : '';?>" minlength="10" class="bg-gray-50 border border-gray-300 text-gray-900 rounded-lg focus:ring-primary-600 focus:border-primary-600 block w-full p-2.5 h-16 max-h-16 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white text-xl tracking-wider dark:focus:ring-primary-500 dark:focus:border-primary-500">
                        </div>

                        <div class="flex items-center w-full cheque_number" style="display: <?=$payment_data['payment_method'] == 'cheque' ? 'flex' : 'none';?>">
                            <label for="bank_name" class="block mb-2 font-bold text-gray-700 dark:text-white w-full max-w-full">BANK NAME</label>
                            <input type="text" name="bank_name" id="bank_name" value="<?=isset($payment_data['bank_name']) ? $payment_data['bank_name'] : '';?>" minlength="3" class="bg-gray-50 border border-gray-300 text-gray-900 rounded-lg focus:ring-primary-600 focus:border-primary-600 block w-full p-2.5 h-16 max-h-16 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white text-xl tracking-wider dark:focus:ring-primary-500 dark:focus:border-primary-500">
                        </div>
                        <div class="flex items-center w-full cheque_number" style="display: <?=$payment_data['payment_method'] == 'cheque' ? 'flex' : 'none';?>">
                            <label for="cheque_number" class="block mb-2 font-bold text-gray-700 dark:text-white w-full max-w-full">CHEQUE No.</label>
                            <input type="number" name="cheque_number" id="cheque_number" value="<?=isset($payment_data['cheque_number']) ? $payment_data['cheque_number'] : '';?>" minlength="10" class="bg-gray-50 border border-gray-300 text-gray-900 rounded-lg focus:ring-primary-600 focus:border-primary-600 block w-full p-2.5 h-16 max-h-16 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white text-xl tracking-wider dark:focus:ring-primary-500 dark:focus:border-primary-500">
                        </div>

                        <input type="hidden" name="receipt_code_original" value="<?=$receipt_code;?>">
                        <input type="hidden" name="student_id" value="<?=$student_id;?>">
                        <input type="hidden" name="class_id" value="<?=$class_id;?>">

                        <div class="flex gap-4 justify-end mt-14">
                            <?php echo get_button('submit', 'UPDATE PAYMENT', 'font-semibold'); ?>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </section>
</div>

<script type="text/javascript">
    function showMthodDetails(val) {
        if(val == 'cash') {
            $('.momo_transaction_id').slideUp('slow');
            $('.cheque_number').slideUp('slow');
            $('#momo_transaction_id').removeAttr('required');
            $('#bank_name').removeAttr('required');
            $('#cheque_number').removeAttr('required');
        } else if(val == 'momo') {
            $('.momo_transaction_id').slideDown('slow');
            $('.cheque_number').slideUp('slow');
            $('#momo_transaction_id').attr('required', 'required');
            $('#bank_name').removeAttr('required');
            $('#cheque_number').removeAttr('required');
        } else if(val == 'cheque') {
            $('.cheque_number').slideDown('slow');
            $('.momo_transaction_id').slideUp('slow');
            $('#bank_name').attr('required', 'required');
            $('#cheque_number').attr('required', 'required');
            $('#momo_transaction_id').removeAttr('required');
        }
    }

    $('#payment_edit_form').submit(function(event) {
        event.preventDefault();
        showAjaxModal_alert('<center><div style="font-size: 20px; font-weight: bolder; margin-top: 0px; ">Processing Data...<br><i class="fa fa-3x fa-spinner fa-pulse"></i></div></center>', 'Loading');
        $.ajax({
            url: '<?php echo site_url('admin/invoice/update_payment'); ?>',
            type: 'POST',
            dataType: 'json',
            data: new FormData(this),
            cache: false,
            contentType: false,
            processData: false
        })
        .done(function(data) {
            if(data.message == 1) {
                $('.in').click();
                showAjaxModal_alert('Payment updated successfully.', 'Success');
                sessionStorage.setItem('return_to_receipts', 'true');
                setTimeout(() => {
                    $('#modal_alert').modal('hide');
                    $('.close').click();
                    location.reload();
                }, 2000);
            } else {
                showAjaxModal_alert('<ul>' + data.error + '</ul>', 'Error');
            }
        })
        .fail(function(err) {
            showAjaxModal_alert(err.responseText, 'Error');
        });
    });
</script>
