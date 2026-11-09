
        <?php
            $class_id = $param2;
        ?>
        <div class="flex flex-col gap-5">
            <div class="flex gap-10 items-center w-full whitespace-nowrap">

                <label for="to_class_id" class="block mb-2 font-bold text-2xl text-gray-900 dark:text-white w-3/12 max-w-3/12 uppercase">Move Students To:</label>
                <select id="to_class_id" name="to_class_id" data-message-required="<?php echo get_phrase('value_required');?>" class="select2 bg-gray-50 border border-gray-300 text-gray-900 rounded-lg focus:ring-primary-500 focus:border-primary-500 text-xl font-bold block w-3/4 max-w-3/4 h-20 p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-primary-500 dark:focus:border-primary-500 uppercase">
                    <option value=""><?php echo get_phrase('select');?></option>
                       <?php

                        getFullClassList();

                    ?>
                    <option value="graduate"><?php echo get_phrase('Graduate Students');?></option>
                </select>

            </div>

            <div class="flex gap-10 items-center w-full whitespace-nowrap" id="year_batch_holder" style="display: none">
                <label for="year_batch" class="block mb-2 font-bold text-2xl text-gray-900 dark:text-white w-3/12 max-w-3/12">YEAR BATCH</label>
                <select id="year_batch" name="year_batch" class="select2 bg-gray-50 border border-gray-300 text-gray-900 rounded-lg focus:ring-primary-500 focus:border-primary-500 text-xl font-bold block w-3/4 max-w-3/4 h-20 p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-primary-500 dark:focus:border-primary-500 uppercase">

                    <?php
                        populate_academic_year('yes', get_settings('running_year'));
                    ?>
                </select>
        </div>
    </div>

    <script type="text/javascript">

        $('#to_class_id').change(function(ev) {

            if($(this).val() == 'graduate') {

                $('#year_batch_holder').slideDown('slow');

            } else {

                $('#year_batch_holder').slideUp('slow');
            }
        })

        $('#modal_move_student_move').click(function(e) {

                showAjaxModal_alert('<center><div style="font-size: 20px; font-weight: bolder;">Processing Data...<br><i class="fa fa-3x fa-spinner fa-pulse"></i></div></center>', 'Loading');


                let from_class_id = '<?php echo $class_id; ?>';
                let to_class_id = $('#to_class_id').val();
                let students_ids = '<?php echo $param3; ?>';



                if(to_class_id == 'graduate') { /*graduating students*/

                    let year_batch = $('#year_batch').val();

                    $.ajax({
                        url: '<?php echo site_url('admin/graduate_students/') ?>' + from_class_id + '/' + year_batch + '/' + students_ids,
                        type: 'POST',
                        dataType: 'text',
                        cache: false,
                    })
                    .done(function(response) {
                        
                        //========start
                        if(response == 'graduated') {
                            $('#modal_move_student_cancel').click();

                            showAjaxModal_alert('Selected students graduated successfully', 'Success');

                           // navigation('<?php echo site_url('admin/student_information/'); ?>' + from_class_id);

                            setTimeout(() => {
                                window.location.reload();
                                /*$('.modal').removeClass('modal-backdrop');
                                $('.modal').removeClass('fade');
                                $('.modal').removeClass('in');
                                $('.close').click();*/
                            }, 3000);

                        } else {
                            showAjaxModal_alert('Something happened. Sorry we are unable to graduate the selected student(s). Please try again later!', 'Error');
                        }
                        ///=======end
                    })
                    .fail(function(err) {
                        showAjaxModal_alert('Error: ' + err.responseText, 'Error');
                    });

                } else { /*normal moving to another class*/

                    if(from_class_id == to_class_id) {

                        showAjaxModal_alert('Same class selected. Please select a different class and try again!', 'Error');
                        return false;

                    } else if(to_class_id == '') {

                        showAjaxModal_alert('No class was selected. Please select a class and try again!', 'Error');
                        return false;
                    }

                    $.ajax({
                        url: '<?php echo site_url('admin/student_move_to_another_class/') ?>' + from_class_id + '/' + to_class_id + '/' + students_ids,
                        type: 'POST',
                        dataType: 'text',
                        cache: false,
                    })
                    .done(function(response) {
                        
                        //========start
                        if(response == 'moved') {
                            $('#modal_move_student_cancel').click();

                            showAjaxModal_alert('Operation successfully executed', 'Success');

                           // navigation('<?php //echo site_url('admin/student_information/'); ?>' + from_class_id);
                           window.location.reload();
                            setTimeout(() => {
                                
                                /*$('.modal').removeClass('modal-backdrop');
                                $('.modal').removeClass('fade');
                                $('.modal').removeClass('in');
                                $('.close').click();*/
                                $('.close').click();
                            }, 3000);

                        } else {
                            showAjaxModal_alert('Something happened. Sorry we are unable to move the selected student(s). Please try again later! ' + response, 'Error');
                        }
                        ///=======end
                    })
                    .fail(function(err) {
                        showAjaxModal_alert('Error: ' + err.responseText, 'Error');
                    });
                }
            });
    </script>