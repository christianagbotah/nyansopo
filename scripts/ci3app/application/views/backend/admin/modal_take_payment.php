<?php
$readonly = 'readonly';
$form_random_id = substr(sha1(md5(mt_rand(1004200000, 1009999999))), 0, 10);

//currency
$currency = $this->db->get_where('settings', array('type' => 'currency'))->row()->description;
$currency_len = strlen($currency);
$fmt = new NumberFormatter('en_US', NumberFormatter::CURRENCY);

$receipt_style = $this->db->get_where('settings', array('type' => 'receipt_style'))->row()->description;

$running_year = get_settings('running_year');
$running_term = get_settings('running_term');
$running_sem = get_settings('running_sem');

$student_id = $param2; 
$bulk_mode = isset($param3) && $param3 === 'bulk_mode'; 

// Only fetch student data if a valid student ID is provided
if (!empty($student_id) && $student_id > 0) {
    $this->db->where('can_delete !=', 'trash');
    $edit_data_array = $this->db->get_where('invoice', array('student_id' => $student_id, 'due !=' => 0));
    
    // Get the latest enrollment record for the student (highest enroll_id)
    $class_id_query = $this->db->query("
        SELECT class_id 
        FROM enroll 
        WHERE student_id = ? 
        AND year = ? 
        AND mute = '0'
        AND enroll_id = (
            SELECT MAX(enroll_id) 
            FROM enroll 
            WHERE student_id = ? 
            AND year = ? 
            AND mute = '0'
        )
    ", array($student_id, $running_year, $student_id, $running_year));
    
    // Check if enrollment exists
    $enrollment_row = $class_id_query->row();
    $class_id = $enrollment_row ? $enrollment_row->class_id : null;
    $edit_data = $edit_data_array->result_array();
    
    $student_info = $this->crud_model->getStudentInfoById($student_id);
    $class_name = $class_id ? getFullClassName($class_id) : 'No Class Assigned';
    $total_fee_owes = $this->financial_report_model->getFeeOwedByStudentId($student_id);
    $student_name = $student_info ? $student_info->name : 'Unknown Student';
} else {
    // No student selected yet - set default values
    $class_id = null;
    $edit_data = array();
    $student_info = null;
    $class_name = 'No Student Selected';
    $total_fee_owes = 0;
    $student_name = 'No Student Selected';
}
?>

<div class="grid grid-cols-1">
    <section class="px-4 md:px-15">
        <div class="font-extrabold text-2xl text-gray-500 text-right">TAKE PAYMENT</div>
        <div class="flex flex-col p-5 w-full max-w-full border-t-8 bg-white shadow-md border border-t-green-500 rounded-xl">
            <div class="flex flex-col gap-6 w-full max-w-full p-4">
                
                <div class="grid grid-cols-1 gap-4 border-b border-b-gray-300 pb-4">
                    <div class="text-gray-600 font-mono font-bold text-xl">SELECT STUDENT</div>
                    <div class="flex items-center w-full relative">
                        <div class="relative w-full">
                            <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                                <i class="fas fa-search text-gray-400 text-xl"></i>
                            </div>
                            <input type="text" 
                                   id="student_search_input" 
                                   class="bg-gray-50 border border-gray-300 text-gray-900 rounded-lg focus:ring-primary-500 text-xl focus:border-primary-500 block w-full h-16 pl-12 pr-4 py-2.5" 
                                   placeholder="Type student name or code to search..."
                                   autocomplete="off">
                            <input type="hidden" id="selected_student_id" value="<?=$student_id;?>">
                        </div>
                        <div id="student_search_results" 
                             class="absolute top-full left-0 right-0 bg-white border border-gray-300 rounded-lg shadow-lg mt-1 max-h-96 overflow-y-auto z-50" 
                             style="display: none;">
                        </div>
                    </div>
                </div>
                
                <div class="grid grid-cols-2 gap-4">
                    <div class="col-span-2 text-gray-600 font-mono font-bold text-xl border-b border-b-gray-400 pb-3">STUDENT'S DETAILS</div>
                    <div class="text-xl font-semibold text-gray-400 dark:text-white">NAME:</div>
                    <div class="text-xl font-semibold text-gray-400 dark:text-white uppercase"><?=$student_name;?></div>

                    <div class="text-xl font-semibold text-gray-400 dark:text-white">CLASS:</div>
                    <div class="text-xl font-semibold text-gray-400 dark:text-white uppercase"><?=$class_name;?></div>

                    <div class="text-xl font-semibold text-gray-500 dark:text-white">TOTAL PAYABLE:</div>
                    <div class="text-xl font-semibold text-gray-500 dark:text-white uppercase"><?=numfmt_format_currency($fmt, $total_fee_owes, $currency);?></div>
                </div>
                
                <!-- ✅ Credit Widget -->
                <?php if(!empty($student_id) && $student_id > 0): ?>
                    <?php 
                    // Load Credit_model with safety check
                    $total_credit = 0;
                    if (file_exists(APPPATH . 'models/Credit_model.php')) {
                        try {
                            $this->load->model('Credit_model');
                            if (isset($this->Credit_model) && method_exists($this->Credit_model, 'get_student_total_credit')) {
                                $total_credit = $this->Credit_model->get_student_total_credit($student_id);
                            }
                        } catch (Exception $e) {
                            // Silently fail - credits are optional
                            $total_credit = 0;
                        }
                    }
                    
                    if($total_credit > 0): 
                    ?>
                    <div class="bg-gradient-to-r from-green-50 to-emerald-50 border-l-4 border-green-500 p-4 rounded-lg shadow-sm">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <div class="bg-green-500 text-white rounded-full p-3">
                                    <i class="fa fa-gift text-2xl"></i>
                                </div>
                                <div>
                                    <div class="text-sm font-medium text-gray-600">Available Credit Balance</div>
                                    <div class="text-2xl font-bold text-green-600">
                                        <?=numfmt_format_currency($fmt, $total_credit, $currency);?>
                                    </div>
                                </div>
                            </div>
                            <button type="button" 
                                    onclick="showCreditHistory(<?=$student_id;?>)"
                                    class="bg-green-500 hover:bg-green-600 text-white font-semibold py-2 px-4 rounded-lg transition-colors duration-200">
                                <i class="fa fa-history mr-2"></i>View History
                            </button>
                        </div>
                        <div class="mt-2 text-sm text-gray-600">
                            <i class="fa fa-info-circle mr-1"></i>
                            This credit will be automatically applied to future invoices
                        </div>
                    </div>
                    <?php endif; ?>
                <?php endif; ?>
                <!-- End Credit Widget -->
                
                <hr>
                <div class="grid grid-cols-1 gap-4">
                    <?php echo form_open(site_url('admin/invoice/take_payment'), array('class' => 'validate flex flex-col gap-5', 'id' => 'payment_form')); ?>
                        <div class="flex items-center w-full">
                            <label for="payment_method" class="block mb-2 font-bold text-gray-700 dark:text-white w-full max-w-full">PAYMENT MODE</label>
                           
                            <select id="payment_method" name="payment_method" data-validate="required" data-message-required="<?php echo get_phrase('field_required');?>" class="bg-gray-50 border border-gray-300 text-gray-900 rounded-lg focus:ring-primary-500 text-xl focus:border-primary-500 block w-full h-16 p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-primary-500 dark:focus:border-primary-500" required="true" onchange="showMthodDetails($(this).val())">
                                <!-- <option value="0">All Payment Methods</option> -->
                                <?php
                                    $payment_methods = get_payment_methods();
                                    if(!empty($payment_methods)) {
                                        foreach($payment_methods as $method) {
                                            echo '<option value="' . $method->id . '">' . $method->name . '</option>';
                                        }
                                    } else {
                                        // Fallback if database table doesn't exist
                                        echo '<option value="1">Cash</option>';
                                        echo '<option value="2">Cheque</option>';
                                        echo '<option value="3">Mobile Money</option>';
                                        echo '<option value="4">Bank Transfer</option>';
                                    }
                                ?>
                            </select>
                        </div>
                        <div class="flex items-center w-full">
                            <label for="amount" class="block mb-2 font-bold text-gray-700 dark:text-white w-full max-w-full">AMOUNT</label>
                            <input type="number" name="amount" min="1" id="amount" data-validate="required" data-message-required="<?php echo get_phrase('amount_required');?>" class="bg-gray-50 border border-gray-300 text-gray-900 rounded-lg focus:ring-primary-600 focus:border-primary-600 block w-full p-2.5 h-16 max-h-16 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white text-xl dark:focus:ring-primary-500 dark:focus:border-primary-500" placeholder="600" autofocus required="true">
                        </div>
                        <div class="flex items-center w-full">
                            <label for="receipt_code" class="block mb-2 font-bold text-gray-700 dark:text-white w-full max-w-full">RECEIPT No.</label>
                            <input type="text" name="receipt_code" minlength="3" id="receipt_code" class="bg-gray-50 border border-gray-300 text-gray-900 rounded-lg focus:ring-primary-600 focus:border-primary-600 block w-full p-2.5 h-16 max-h-16 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white text-xl tracking-wider dark:focus:ring-primary-500 dark:focus:border-primary-500" placeholder="4012252">
                        </div>

                        <!-- if user selects mobile money, let's show these further info fields-->
                        <div class="flex items-center w-full momo_transaction_id" style="display: none">
                            <label for="momo_transaction_id" class="block mb-2 font-bold text-gray-700 dark:text-white w-full max-w-full">
                                TRANSACTION ID
                                <div class="text-sm font-normal text-gray-500 mt-1">(Optional - Auto-generated if empty)</div>
                            </label>
                            <input type="text" name="momo_transaction_id" id="momo_transaction_id" minlength="10" class="bg-gray-50 border border-gray-300 text-gray-900 rounded-lg focus:ring-primary-600 focus:border-primary-600 block w-full p-2.5 h-16 max-h-16 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white text-xl tracking-wider dark:focus:ring-primary-500 dark:focus:border-primary-500" placeholder="Leave empty to auto-generate">
                        </div>

                        <!-- if user selects cheque, let's show these further info fields-->
                        <div class="flex items-center w-full cheque_number" style="display: none">
                            <label for="bank_name" class="block mb-2 font-bold text-gray-700 dark:text-white w-full max-w-full">BANK NAME</label>
                            <input type="text" name="bank_name" id="bank_name" minlength="3" class="bg-gray-50 border border-gray-300 text-gray-900 rounded-lg focus:ring-primary-600 focus:border-primary-600 block w-full p-2.5 h-16 max-h-16 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white text-xl tracking-wider dark:focus:ring-primary-500 dark:focus:border-primary-500" placeholder="Ecobank">
                        </div>
                        <div class="flex items-center w-full cheque_number" style="display: none">
                            <label for="cheque_number" class="block mb-2 font-bold text-gray-700 dark:text-white w-full max-w-full">CHEQUE No.</label>
                            <input type="number" name="cheque_number" id="cheque_number" minlength="10" class="bg-gray-50 border border-gray-300 text-gray-900 rounded-lg focus:ring-primary-600 focus:border-primary-600 block w-full p-2.5 h-16 max-h-16 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white text-xl tracking-wider dark:focus:ring-primary-500 dark:focus:border-primary-500" placeholder="00112345678123456789123">
                        </div>

                        <input type="hidden" name="student_id" value="<?=$student_id;?>">
                        <input type="hidden" name="class_id" value="<?=$class_id;?>">
                        <input type="hidden" name="total_fees_owe" value="<?=$total_fee_owes;?>">
                        <input type="hidden" name="bulk_mode" value="<?=$bulk_mode ? '1' : '0';?>">
                        

                        <div class="flex gap-4 justify-between  mt-14">
                            <div class="flex gap-3 items-center text-left justify-start pull-left border-b border-b-blue-700">
                                <label class="block mb-2 font-bold text-gray-500 dark:text-white w-full max-w-full uppercase text-2xl">Print Receipt?</label>

                                <div class="switch-button  showcase-switch-button">
                                    <input id="print_receipt"  type="checkbox"  value="1" checked name="print_receipt" onchange="value_change(this.value)">
                                    <label for="print_receipt" ></label>
                                </div>
                            </div>
                            <?php
                                echo get_button('submit', 'TAKE PAYMENT', 'font-semibold');
                            ?>
                        </div>

                    </form>
                    
                </div>
            </div>
        </div>
    </section><!-- end of payment side -->
</div>





<script type="text/javascript">
    $(function(e) {

        // Prevent modal from closing on ESC key or backdrop click
        $('#modal_ajax').modal({
            backdrop: 'static',
            keyboard: false
        });

        let total_fee_owes = Number(<?=$total_fee_owes;?>);

        if(total_fee_owes < 1) {
            $('#payment_form select').attr('disabled', 'disabled');
            $('#payment_form button').attr('disabled', 'disabled');
            $('#payment_form input').attr('disabled', 'disabled');
            $('#payment_form select').addClass('cursor-not-allowed');
            $('#payment_form input').addClass('cursor-not-allowed');
            $('#payment_form button').addClass('cursor-not-allowed bg-gray-300 text-gray-400');
        }

        // AJAX Student Search Implementation
        let searchTimeout;
        let currentRequest = null;

        $('#student_search_input').on('input', function() {
            const searchTerm = $(this).val().trim();
            
            // Clear previous timeout
            clearTimeout(searchTimeout);
            
            // Hide results if search term is less than 2 characters
            if(searchTerm.length < 2) {
                $('#student_search_results').hide().empty();
                return;
            }
            
            // Debounce search - wait 300ms after user stops typing
            searchTimeout = setTimeout(function() {
                // Cancel previous request if still running
                if(currentRequest) {
                    currentRequest.abort();
                }
                
                // Show loading state
                $('#student_search_results').show().html(
                    '<div class="p-4 text-center text-gray-500">' +
                    '<i class="fas fa-spinner fa-spin mr-2"></i>Searching...' +
                    '</div>'
                );
                
                // Make AJAX request
                currentRequest = $.ajax({
                    url: '<?php echo site_url('admin/search_student_for_fees'); ?>',
                    type: 'POST',
                    dataType: 'json',
                    data: { 
                        search_term: searchTerm,
                        <?php echo $this->security->get_csrf_token_name(); ?>: '<?php echo $this->security->get_csrf_hash(); ?>'
                    },
                    success: function(response) {
                        currentRequest = null;
                        
                        if(response.success && response.students && response.students.length > 0) {
                            let html = '';
                            response.students.forEach(function(student) {
                                html += '<div class="student-result-item p-4 border-b border-gray-200 hover:bg-gray-50 cursor-pointer transition-colors" ' +
                                       'data-student-id="' + student.student_id + '" ' +
                                       'data-student-name="' + student.name + '" ' +
                                       'data-class-name="' + student.class_name + '" ' +
                                       'data-total-due="' + student.total_due + '">' +
                                       '<div class="flex justify-between items-center">' +
                                       '<div>' +
                                       '<div class="font-semibold text-gray-800 text-lg">' + student.name.toUpperCase() + '</div>' +
                                       '<div class="text-sm text-gray-600">' +
                                       '<i class="fas fa-id-card mr-1"></i>' + student.student_code + ' | ' +
                                       '<i class="fas fa-graduation-cap mr-1"></i>' + student.class_name +
                                       '</div>' +
                                       '</div>' +
                                       '<div class="text-right">' +
                                       '<div class="text-sm text-gray-500">Owes</div>' +
                                       '<div class="font-bold text-red-600 text-lg"><?=$currency;?>' + parseFloat(student.total_due).toFixed(2) + '</div>' +
                                       '</div>' +
                                       '</div>' +
                                       '</div>';
                            });
                            $('#student_search_results').html(html);
                        } else {
                            $('#student_search_results').html(
                                '<div class="p-4 text-center text-gray-500">' +
                                '<i class="fas fa-info-circle mr-2"></i>No students found with outstanding fees' +
                                '</div>'
                            );
                        }
                    },
                    error: function(xhr, status, error) {
                        if(status !== 'abort') {
                            currentRequest = null;
                            $('#student_search_results').html(
                                '<div class="p-4 text-center text-red-500">' +
                                '<i class="fas fa-exclamation-triangle mr-2"></i>Error searching students' +
                                '</div>'
                            );
                        }
                    }
                });
            }, 300);
        });

        // Handle student selection from search results
        $(document).on('click', '.student-result-item', function() {
            const studentId = $(this).data('student-id');
            const studentName = $(this).data('student-name');
            
            // Update hidden field and search input
            $('#selected_student_id').val(studentId);
            $('#student_search_input').val(studentName.toUpperCase());
            
            // Hide search results
            $('#student_search_results').hide().empty();
            
            // Reload modal with selected student
            showAjaxModal('<?php echo site_url('modal/popup/modal_take_payment/'); ?>' + studentId, 'take_payment');
        });

        // Hide search results when clicking outside
        $(document).on('click', function(e) {
            if(!$(e.target).closest('#student_search_input, #student_search_results').length) {
                $('#student_search_results').hide();
            }
        });

        // Show results again when focusing on search input (if there are results)
        $('#student_search_input').on('focus', function() {
            if($('#student_search_results').children().length > 0) {
                $('#student_search_results').show();
            }
        });
    })
    function showMthodDetails(val) {

        if(val == 1) {

            /*we close momo and cheque more info fields*/
            $('.momo_transaction_id').slideUp('slow');
            $('.cheque_number').slideUp('slow');

            /*remove the rquired attributes*/
            $('#momo_transaction_id').removeAttr('required');
            $('#bank_name').removeAttr('required');
            $('#cheque_number').removeAttr('required');

        } else if(val == 3) {

            /*we open momo and close cheque more info fields*/
            $('.momo_transaction_id').slideDown('slow');
            $('.cheque_number').slideUp('slow');

            /*remove the required attributes - transaction ID is now optional*/
            $('#momo_transaction_id').removeAttr('required');
            $('#bank_name').removeAttr('required');
            $('#cheque_number').removeAttr('required');

        } else if(val == 2) {

            /*we open cheque and close momo more info fields*/
            $('.cheque_number').slideDown('slow');
            $('.momo_transaction_id').slideUp('slow');

            /*add required attribute to cheque*/
            $('#bank_name').attr('required', 'required');
            $('#cheque_number').attr('required', 'required');

            /*remove the rquired attributes*/
            $('#momo_transaction_id').removeAttr('required');

        }
    }

    /*form submitted*/
    $('#payment_form').off('submit').on('submit', function(event) {

        event.preventDefault();
        event.stopImmediatePropagation(); // Stop other handlers from running
        
        // Prevent duplicate submissions
        if ($(this).data('submitting') === true) {
            console.log('Form already submitting, ignoring duplicate submit');
            return false;
        }
        $(this).data('submitting', true);
        
        const isBulkMode = $('input[name="bulk_mode"]').val() === '1';
        const isStudentProfile = window.location.href.includes('student_profile');
        
        // BUTTON LOCKING - Disable submit button to prevent double submissions
        const $submitButton = $('#payment_form button[type="submit"]');
        const originalButtonHtml = $submitButton.html();
        
        // Function to re-enable button - can be called from anywhere
        window.reEnablePaymentButton = function() {
            $('#payment_form').data('submitting', false); // Reset flag
            $submitButton.prop('disabled', false)
                         .removeClass('opacity-50 cursor-not-allowed bg-gray-400')
                         .html(originalButtonHtml);
        };
        
        $submitButton.prop('disabled', true)
                     .addClass('opacity-50 cursor-not-allowed bg-gray-400')
                     .html('<i class="fas fa-spinner fa-spin mr-2"></i>Processing...');

        showAjaxModal_alert('<center><div style="font-size: 20px; font-weight: bolder; margin-top: 0px; ">Processing Data...<br><i class="fa fa-3x fa-spinner fa-pulse"></i></div></center>', 'Loading');
        
        $.ajax({
          url: '<?php echo site_url('admin/invoice/take_payment'); ?>',
          type: 'POST',
          dataType: 'json',
          data: new FormData(this),
          cache: false,
          contentType: false,
          processData: false
      })
      .always(function() {
        // ALWAYS reset the flag and clean up, no matter what
        $('#payment_form').data('submitting', false);
        
        // ALWAYS hide the loading modal after request completes (success or error)
        // This ensures we don't get stuck with a loading modal
        setTimeout(() => {
            $('#modal_alert').modal('hide');
            // Clean up any stray backdrops
            $('.modal-backdrop').not(':last').remove();
        }, 100);
      })
      .done(function(data) {

        console.log('Payment Response:', data); // Debug log
        console.log('Response type:', typeof data); // Check if it's an object or string
        console.log('Message value:', data.message); // Debug the message specifically
        console.log('Is message == 1?', data.message == 1); // Debug the condition
        
        // If data is a string, try to parse it
        if(typeof data === 'string') {
            try {
                data = JSON.parse(data);
                console.log('Parsed JSON:', data);
            } catch(e) {
                console.error('JSON parse error:', e);
                console.error('Raw response:', data);
                
                if(typeof window.reEnablePaymentButton === 'function') {
                    window.reEnablePaymentButton();
                }
                
                $('#modal_alert').modal('hide');
                setTimeout(() => {
                    showAjaxModal_alert('Invalid server response: ' + data.substring(0, 500), 'Error');
                }, 300);
                return;
            }
        }
        
        if(data.message == 1) {
            // SUCCESS - flag will be reset when modal reloads or page refreshes
            $('#payment_form').data('submitting', false);
            //success
            let successMessage = 'Payment made successfully.';
            
            // Check if credit was created
            if(data.credit_created && data.credit_amount > 0) {
                successMessage += '<br><br><div style="background: linear-gradient(135deg, #10b981, #059669); color: white; padding: 15px; border-radius: 8px; margin-top: 10px;"><i class="fa fa-gift"></i> <strong>Credit Created!</strong><br>' + data.credit_message + '</div>';
            }
            
            showAjaxModal_alert(successMessage, 'success', false);

            if(data.print_receipt == 1) { //wants to print receipt
                setTimeout(() => {
                    window.open(data.url, '_blank');
                    
                    setTimeout(() => {
                        $('#modal_alert').modal('hide');
                        
                        if(isStudentProfile) {
                            location.reload();
                        } else if(isBulkMode) {
                            $('#modal_ajax').modal('hide');
                            if(typeof refreshBulkInvoices === 'function') {
                                refreshBulkInvoices();
                            }
                        } else {
                            showAjaxModal('<?php echo site_url('modal/popup/modal_take_payment/0'); ?>', 'take_payment');
                            setTimeout(() => {
                                $('#modal_ajax').modal({backdrop: 'static', keyboard: false});
                                $('body').addClass('modal-open');
                            }, 400);
                        }
                    }, 500);
                }, 2500);

            } else {
                setTimeout(() => {
                    $('#modal_alert').modal('hide');
                    $('.modal-backdrop').not(':last').remove();
                    
                    if(isStudentProfile) {
                        location.reload();
                    } else if(isBulkMode) {
                        $('#modal_ajax').modal('hide');
                        if(typeof refreshBulkInvoices === 'function') {
                            refreshBulkInvoices();
                        }
                    } else {
                        showAjaxModal('<?php echo site_url('modal/popup/modal_take_payment/0'); ?>', 'take_payment');
                        setTimeout(() => {
                            $('#modal_ajax').modal({backdrop: 'static', keyboard: false});
                            
                            if(typeof class_selected_load === 'function') {
                                class_selected_load(<?=$class_id;?>); //load this class list
                            }
                        }, 400);
                    }
                }, 3000);
            }


           // navigation('<?php //echo site_url('admin/invoices_show'); ?>');

        } else {
            //error - RE-ENABLE BUTTON
            if(typeof window.reEnablePaymentButton === 'function') {
                window.reEnablePaymentButton();
            }
            
            //$('#modal_alert').modal('hide');
            setTimeout(() => {
                let errorList = '<ul id="list_err_payment_create">';
                $.each(data, function(index, val) {
                    // Skip the 'message' key - only show actual error messages
                    if(index !== 'message') {
                        errorList += '<li style="font-size: 16px">' + val + '</li>';
                    }
                });
                errorList += '</ul>';
                showAjaxModal_alert(errorList, 'Error');
            }, 300);
        }
      })
      .fail(function(err) {
        // ERROR - RE-ENABLE BUTTON
        if(typeof window.reEnablePaymentButton === 'function') {
            window.reEnablePaymentButton();
        }
        
        //$('#modal_alert').modal('hide');
        setTimeout(() => {
            showAjaxModal_alert(err.responseText, 'Error');
        }, 300);

      });
      
      return false; // Prevent any other submit handlers or default form submission
    });

    function value_change(val) {
       let print_receipt = $('#print_receipt').filter(':checked').length;
       if(print_receipt > 0) {
            $('#print_receipt').val(1);
            $('#yes_no').text('YES');
            $('#yes_no').css('color', '#000000');
       } else {
        $('#print_receipt').val(0);
        $('#yes_no').text('NO');
        $('#yes_no').css('color', '#b3afaf');
       }
    }
    
    // Show credit history modal
    function showCreditHistory(studentId) {
        $.get('<?=site_url("admin/get_student_credit_info")?>' + '/' + studentId, function(response) {
            const data = JSON.parse(response);
            
            if(data.status === 'success') {
                let historyHtml = '<div class="table-responsive"><table class="table table-striped table-bordered">' +
                    '<thead style="background: linear-gradient(135deg, #667eea, #764ba2); color: white;">' +
                    '<tr>' +
                    '<th>Date</th>' +
                    '<th>Source</th>' +
                    '<th>Credit Amount</th>' +
                    '<th>Applied</th>' +
                    '<th>Remaining</th>' +
                    '<th>Status</th>' +
                    '<th>Notes</th>' +
                    '</tr>' +
                    '</thead><tbody>';
                
                if(data.history && data.history.length > 0) {
                    data.history.forEach(function(item) {
                        const statusBadge = item.status === 'active' ? 
                            '<span class="badge badge-success">Active</span>' : 
                            '<span class="badge badge-secondary">Fully Applied</span>';
                            
                        historyHtml += '<tr>' +
                            '<td>' + new Date(item.created_at).toLocaleDateString() + '</td>' +
                            '<td>' + (item.source_receipt_code || 'Manual') + '</td>' +
                            '<td>GH₵ ' + parseFloat(item.credit_amount).toFixed(2) + '</td>' +
                            '<td>GH₵ ' + parseFloat(item.applied_amount).toFixed(2) + '</td>' +
                            '<td>GH₵ ' + parseFloat(item.remaining_amount).toFixed(2) + '</td>' +
                            '<td>' + statusBadge + '</td>' +
                            '<td>' + (item.notes || '') + '</td>' +
                            '</tr>';
                    });
                } else {
                    historyHtml += '<tr><td colspan="7" class="text-center">No credit history found</td></tr>';
                }
                
                historyHtml += '</tbody></table></div>';
                
                // Show in a modal
                showAjaxModal_content('Credit History', historyHtml);
            } else {
                showAjaxModal_alert('Failed to load credit history', 'error');
            }
        }).fail(function() {
            showAjaxModal_alert('Error loading credit history', 'error');
        });
    }
</script>