<?php 
    
    $this->db->where('can_delete !=', 'trash');
    $unpaid_invoices = $this->db->get_where('invoice', array('student_id' => $student_id, 'status' => 'unpaid', 'due >' => '0'))->result_array();
    $total_count = 0;

    $currency = $this->db->get_where('settings', array('type' => 'currency'))->row()->description;
    $fmt = new NumberFormatter('en_US', NumberFormatter::CURRENCY);


    $child_name = $this->db->get_where('student', array('student_id' => $student_id, 'parent_id' => $this->session->userdata('parent_id')))->row()->name;

    $system_phone = $this->db->get_where('settings', array('type' => 'phone'))->row()->description;
    $system_mail = $this->db->get_where('settings', array('type' => 'system_email'))->row()->description;
?>
<!DOCTYPE html>
<html>
<head>
    <title>Payment Status</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <link rel="stylesheet" href="<?php echo base_url('assets/css/font-icons/font-awesomenew/css/fontawesome.min.css');?>">
    <link rel="stylesheet" href="<?php echo base_url('assets/css/bootstrap.min.css');?>">

</head>
<body>

<div class="container" id="top">
    <div class="row">
        <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
            <center><h3 id="pTitle"></h3></center>
             <!--payment status modal alert here-->
            <div class="modal fade modal-lg modal-sm" id="modal_payment" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true" style="width: 100%" >
                <div class="modal-dialog">
                    <div class="modal-content modal-lg modal-sm" style="width: 100%;">
                        <div class="modal-header" style="background-color: red;">
                            <h2 style="color: #fff;" class="modal-title" id="myModalLabel" align="center">Payment Status!!!</h2>
                        </div>
                        <div class="modal-body">
                            <center><p><strong><?php echo strtoupper($page_title) ?></strong></p></center>
                            <p>Sorry, you cannot view your child <?php echo ucwords(strtolower($child_name)); ?>'s Report Sheet now because you have not finished paying your child's fees.</p>
                            <p>If you think you are receiving this message by mistake, kindly contact the school administrator on <strong><?php echo $system_phone; ?></strong> or send us a mail via <a href="mailto:<?php echo $system_mail; ?>" target="_blank" title="Send us an email"><strong><?php echo $system_mail; ?></strong></a></p>
                            <p><strong><em>Details about <?php echo $child_name; ?>'S invoice (s) outlined below::</p></em></strong></p>

                            <table class="table table-responsive table-bordered table-striped table-hover table-active">
                                <thead>
                                    <tr style="background-color: #4e4b4c; font-weight: bold; color: #ffffff;">
                                        <td>INVOICE #</td>
                                        <td>INVOICE TITLE</td>
                                        <td>AMOUNT DUE</td>
                                        <td>INVOICE YEAR</td>
                                        <td>INVOICE TERM</td>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($unpaid_invoices as $unpaid): ?>
                                        <tr>
                                            <td><?php echo $unpaid['invoice_code']; ?></td>
                                            <td><?php echo $unpaid['title']; ?></td>
                                            <td style="background-color: #d63030; font-weight: bold; color: #ffffff; text-align: center;"> <?php echo numfmt_format_currency($fmt, $unpaid['due'], $currency); ?></td>
                                            <td><?php echo $unpaid['year']; ?></td>
                                            <td><?php echo $unpaid['term'] ;?></td>
                                            <?php $total_count += $unpaid['due']; ?>
                                        </tr>
                                    <?php endforeach; ?>
                                    <tr style="background-color: #eaeaea;">
                                        <td colspan="2" style="text-align: center; font-weight: bold;">TOTAL AMOUNT</td>
                                        <td style="text-align: center; font-weight: bold;"><u> <?php echo numfmt_format_currency($fmt, $total_count, $currency); ?></u></td>
                                        <td colspan="2" align="right">
                                            <a class="btn btn-default" href="javascript:(void);" onclick="showAjaxModal('<?php echo site_url('modal/popup/modal_mobile_money_payment/'.$unpaid['invoice_code'].'/'.$student_id); ?>')" >
                                                I WANT TO PAY
                                            </a>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>

                            <center><p>Use this button <a href="<?php echo site_url('parents/dashboard'); ?>" class="btn btn-info btn-sm">Dashboard</a> to go back to your dashboard.</p>
                                <h4>Thank You</h4>
                            </center>
                    
                        </div>
                    </div>
                    <!-- /.modal-content -->
                </div>
                <!-- /.modal-dialog -->
            </div>
            <!-- /.modal -->
        </div>
    </div>
</div>
   

    <script type="text/javascript" src="<?php echo base_url('assets/js/jquery-3.3.1.min.js'); ?>"></script>
    <script type="text/javascript" src="<?php echo base_url('assets/js/bootstrap.min.js'); ?>"></script>


    <?php include VIEWPATH.'backend/modal.php';?>

</body>
</html>

<script type="text/javascript">
    $(document).ready(function() {

        $('#modal_payment').modal(
            {
                backdrop: 'static', 
                keyboard: false,
                show: true,
            }
            );
        /*$('.').click(function() {
            $('#pTitle').text('Please wait... Page is redirecting you to your dashboard');
            window.location.href = "<?php //echo site_url('parents/dashboard') ?>";
        });*/
    });
</script>