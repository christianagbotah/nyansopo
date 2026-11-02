 <?php
 $this->db->where('can_delete !=', 'trash');
$edit_data = $this->db->get_where('invoice', array('title' => $invoice_title, 'year' => $year, 'term' => $term))->result_array();
$currency = $this->db->get_where('settings', array('type' => 'currency'))->row()->description;
?>

<?php
foreach ($edit_data as $row):
?>

 <br>
    <div id="invoice_print1"></div>
    <div id="invoice_print2">
                <table width="100%" border="0" id="invoice_det">
                    <tr>
                        <td align="left">
                             <img id="logo" src="<?php echo base_url(); ?>uploads/logo.png" style="max-height : 120px;"><br>
                        </td>
                        <td align="right">
                            <h5><?php echo get_phrase('creation_date'); ?> : <?php echo date('d M,Y', $row['creation_timestamp']);?></h5>
                            <h5><?php echo get_phrase('title'); ?> : <?php echo $row['title'];?></h5>
                            <h5><?php echo get_phrase('description'); ?> : <?php echo $row['description'];?></h5>
                            <h5><?php echo get_phrase('status'); ?> : <?php echo $row['status']; ?></h5>
                            <strong style="color: #3b7ded;"><?php echo get_phrase('year'); ?> : <?php echo explode('-', $row['year'])[1]; ?> | <?php echo get_phrase('term'); ?> : <?php echo $row['term']; ?></strong>
                        </td>
                    </tr>
                </table>
        <hr>
        <table width="100%" border="0">    
            <tr>
                <td align="left"><h4><?php echo get_phrase('payment_to'); ?> </h4></td>
                <td align="right"><h4><?php echo get_phrase('bill_to'); ?> </h4></td>
            </tr>

            <tr>
                <td align="left" valign="top">
                    <?php echo $this->db->get_where('settings', array('type' => 'system_name'))->row()->description; ?><br>
                    <?php echo $this->db->get_where('settings', array('type' => 'address'))->row()->description; ?><br>
                    <?php echo $this->db->get_where('settings', array('type' => 'location'))->row()->description; ?><br>
                    <?php echo $this->db->get_where('settings', array('type' => 'phone'))->row()->description; ?><br>            
                </td>
                <td align="right" valign="top">
                    <?php echo $this->db->get_where('student', array('student_id' => $row['student_id'], 'mute' => '0'))->row()->name; ?><br>
                    <?php 
                        $class_id = $this->db->get_where('enroll' , array(
                            'student_id' => $row['student_id'], 'mute' => '0',
                                'year' => $this->db->get_where('settings', array('type' => 'running_year'))->row()->description, 'term' => $this->db->get_where('settings', array('type' => 'running_term'))->row()->description
                        ))->row()->class_id;
                        echo $this->db->get_where('class', array('class_id' => $class_id))->row()->name.' '.$this->db->get_where('class', array('class_id' => $class_id))->row()->name_numeric;
                    ?><br>
                </td>
            </tr>
        </table>
        <hr>

        <table width="100%" border="0">    
            <tr>
                <td align="right" width="80%"><?php echo get_phrase('total_amount'); ?> :</td>
                <td align="right"><?php echo $currency.' '.$row['amount']; ?></td>
            </tr>
            <tr>
                <td align="right" width="80%"><h4><?php echo get_phrase('paid_amount'); ?> :</h4></td>
                <td align="right"><h4><?php echo $currency.' '.$row['amount_paid']; ?></h4></td>
            </tr>
            <?php $due = 'due'; 
                if($row['due'] < 0): $due = 'over_due'; endif; 

            ?>
            <?php if ($row['due'] != 0):?>
            <tr>
                <td align="right" width="80%"><h4><?php echo get_phrase($due); ?> :</h4></td>
                <td align="right"><h4 id="due_amount"><?php echo $currency.' '. $row['due']; ?></h4></td>
            </tr>
            <?php endif;?>
        </table>

        <hr>

        <!-- payment history -->
        <h4><?php echo get_phrase('payment_history'); ?></h4>
        <table class="table table-bordered" width="100%" border="1" style="border-collapse:collapse;">
            <thead>
                <tr>
                    <th><?php echo get_phrase('date'); ?></th>
                    <th><?php echo get_phrase('amount'); ?></th>
                    <th><?php echo get_phrase('method'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php
                $payment_history = $this->db->get_where('payment', array('invoice_id' => $row['invoice_id']))->result_array();
                foreach ($payment_history as $row2):
                    ?>
                    <tr>
                        <td><?php echo date("d M, Y", $row2['timestamp']); ?></td>
                        <td><?php echo $currency.' '.$row2['amount']; ?></td>
                        <td>
                            <?php 
                                if ($row2['method'] == 1)
                                    echo get_phrase('cash');
                                if ($row2['method'] == 2)
                                    echo get_phrase('check');
                                if ($row2['method'] == 3)
                                    echo get_phrase('card');
                                if ($row2['method'] == 4)
                                    echo 'Mobile Money';
                            ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
            <tbody>
        </table>
    </div>
<?php endforeach; ?>


<script type="text/javascript">
    $(document).ready(function() {
        var due = '<?php echo $row['due']; ?>';
        if(due < 10) {
            $('#due_amount').css('color', 'green');
        }else{
            $('#due_amount').css('color', 'red');
        }
        
    });

    //if the year is changed
    function reload_year(year) {
        var invoice_id = '<?php echo $param2; ?>';
        var term = '<?php echo $param4; ?>';
        $.ajax({
            url: '<?php echo site_url('admin/reload_year/'); ?>' + invoice_id + '/' + year + '/' + term,
            success: function(response) {
                $('#invoice_print2').css('display', 'none');
                $('#invoice_print1').html(response);
            }
        });
    }

    //if the term is changed
    function reload_term(term) {
        var invoice_id = '<?php echo $param2; ?>';
        var year = '<?php echo $param3; ?>'; 
        $.ajax({
            url: '<?php echo site_url('admin/reload_term/'); ?>' + invoice_id + '/' + year + '/' + term,
            success: function(response) {
                $('#invoice_print2').css('display', 'none');
                $('#invoice_print1').html(response);
            }
        });
    }

     //if the invoice_title is changed
    function reload_invoice_title(invoice_title) {
        var term = '<?php echo $param4; ?>';
        var year = '<?php echo $param3; ?>'; 
        var student_id = '<?php echo $row['student_id']; ?>';
        $.ajax({
            url: '<?php echo site_url('admin/reload_invoice_title/'); ?>' + student_id + '/' + year + '/' + term + '/' + invoice_title,
            success: function(response) {
                $('#invoice_print2').css('display', 'none');
                $('#invoice_print1').html(response);
            }
        });
    }

    // print invoice function
    function PrintElem(elem)
    {
        Popup($(elem).html());
    }

    function Popup(data)
    {
        var mywindow = window.open('', 'invoice', 'height=400,width=600');
        mywindow.document.write('<html><head><title>Invoice</title>');
        mywindow.document.write('<link rel="stylesheet" href="assets/css/neon-theme.css" type="text/css" />');
        mywindow.document.write('<link rel="stylesheet" href="assets/js/datatables/responsive/css/datatables.responsive.css" type="text/css" />');
        mywindow.document.write('</head><body >');
        mywindow.document.write(data);
        mywindow.document.write('</body></html>');

        var is_chrome = Boolean(mywindow.chrome);
        if (is_chrome) {
            setTimeout(function() {
                mywindow.print();
                mywindow.close();

                return true;
            }, 250);
        }
        else {
            mywindow.print();
            mywindow.close();

            return true;
        }

        return true;
    }

</script>