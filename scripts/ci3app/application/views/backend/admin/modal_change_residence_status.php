
        <?php
            $class_id = $param2;
            $running_term = get_settings('running_term');
        ?>
        <div class="flex flex-col gap-5">
            <div class="flex gap-5 items-center w-full whitespace-nowrap">

                <label for="to_class_id" class="block mb-2 font-bold text-2xl text-gray-900 dark:text-white w-3/12 max-w-3/12 uppercase">TERM:</label>
                <select id="term" name="term" data-message-required="<?php echo get_phrase('term is required');?>" class="select2 bg-gray-50 border border-gray-300 text-gray-900 rounded-lg focus:ring-primary-500 focus:border-primary-500 text-xl font-bold block w-3/4 max-w-3/4 h-20 p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-primary-500 dark:focus:border-primary-500 uppercase" required>
                    <option value="1" <?=$running_term == 1 ? 'selected' : '';?>><?php echo get_phrase('One');?></option>
                    <option value="2" <?=$running_term == 2 ? 'selected' : '';?>><?php echo get_phrase('Two');?></option>
                    <option value="3" <?=$running_term == 3 ? 'selected' : '';?>><?php echo get_phrase('Three');?></option>

                </select>

            </div>

            <div class="flex gap-5 items-center w-full whitespace-nowrap">
                <label for="year" class="block mb-2 font-bold text-2xl text-gray-900 dark:text-white w-3/12 max-w-3/12">YEAR</label>
                <select id="year" name="year" data-message-required="<?php echo get_phrase('year is required');?>" class="select2 bg-gray-50 border border-gray-300 text-gray-900 rounded-lg focus:ring-primary-500 focus:border-primary-500 text-xl font-bold block w-3/4 max-w-3/4 h-20 p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-primary-500 dark:focus:border-primary-500 uppercase" required>

                    <?php
                        populate_academic_year('yes', get_settings('running_year'));
                    ?>
                </select>
        </div>
    </div>

    <script type="text/javascript">

        $('#modal_change_residence_status_change').click(function(e) {

            showAjaxModal_alert('<center><div style="font-size: 20px; font-weight: bolder;">Processing Data...<br><i class="fa fa-3x fa-spinner fa-pulse"></i></div></center>', 'Loading');


            let term = $('#term').val();
            let year = $('#year').val();
            let students_ids = '<?php echo $param3; ?>';


                $.ajax({
                    url: '<?php echo site_url('admin/update_students_residence_status/'.$class_id.'/') ?>' + term + '/' + year + '/' + students_ids,
                    type: 'POST',
                    dataType: 'text',
                    cache: false,
                })
                .done(function(response) {
                    
                    //========start
                    if(response == 'success') {
                        $('#modal_change_residence_status_cancel').click();

                        showAjaxModal_alert('Selected students\' residential status updated successfully', 'Success');

                       // navigation('<?php echo site_url('admin/student_information/'); ?>' + from_class_id);

                        setTimeout(() => {
                            window.location.reload();
                            /*$('.modal').removeClass('modal-backdrop');
                            $('.modal').removeClass('fade');
                            $('.modal').removeClass('in');
                            $('.close').click();*/
                        }, 3000);

                    } else {
                        showAjaxModal_alert('Something happened. Sorry we are unable to update the residential status for the selected student(s). Please try again later!', 'Error');
                    }
                    ///=======end
                })
                .fail(function(err) {
                    showAjaxModal_alert('Error: ' + err.responseText, 'Error');
                }); 
            });


    </script>