
<?php 
  //currency
        $currency = $this->db->get_where('settings', array('type' => 'currency'))->row()->description;
        $fmt = new NumberFormatter('en_US', NumberFormatter::CURRENCY);

        $table_row = '<tr><td></td><td></td></tr>';
        $year_found = 1;


        
        //select all the items title in the payment table distinctly for income
        $query1 =  $this->db
                            ->select('title')
                            ->distinct()
                            ->where('payment_type', 'income')
                            ->where('year', $year)
                            ->order_by('title', 'asc')
                            ->where('can_delete !=', 'trash')
                            ->get('payment');

                  if($query1->num_rows() > 0) {
                    $items_title = $query1->result_array();

                    $item_array = array();
                    $item_counter = 0;
                    foreach($items_title as $item) {
                      $item_array[$item_counter] = $item['title'];
                      $item_counter++;
                    }

                  } else {
                    $year_found = 0;
                  }

      //select all the items title in the payment table distinctly for expenditure
        $query11 =  $this->db
                            ->select('title')
                            ->distinct()
                            ->where('payment_type', 'expense')
                            ->where('year', $year)
                            ->where('can_delete !=', 'trash')
                            ->order_by('title', 'asc')
                            ->get('payment');

                  if($query11->num_rows() > 0) {
                    $items_title_exp = $query11->result_array();

                    $item_array_exp = array();
                    $item_counter_exp = 0;
                    foreach($items_title_exp as $item2) {
                      $item_array_exp[$item_counter_exp] = $item2['title'];
                      $item_counter_exp++;
                    }

                  }
    
?>


        <style type="text/css">
          #button {
            position: fixed;
            background-color: #337ab7;
            padding: 5px 10px 5px 10px;
            font-weight: bold;
            border-radius: 9px;
          }

          #button a {
            text-decoration: none;
            color: #fff;
          }

             @media Print
             {
                page {
                
                background: #ffffff;
                display: block;
                margin: 0 auto;
                margin-bottom: 0.5cm;
                box-shadow: 0;

                }


                page[size="A6"]{
                    width: 80mm;
                    min-height: 297mm;
        
                }

                body, page {
                    margin: 0;
                    box-shadow: 0;
                }

                table {
                    width: 100%; 
                    border-collapse: collapse; 
                    
                }
            }
        </style>

      <div class="row col-md-12" id="print_annual">
        <table class="table table-bordered" style="width:100%; border-collapse:collapse;border: 1px solid #000; font-size: 14px" border="1">
          <caption><h2 style="text-align: center"><u>INCOME AND EXPENDITURE ACCOUNT FOR THE <?php echo explode('-', $year)[1]; ?> ACADEMIC YEAR</u></h2></caption>
          <thead>
            <tr>
              <th><h2>INCOME</h2></th>
              <th><h2>EXPENDITURE</h2></th>
            </tr>
          </thead>
          <tbody>
            <?php if($year_found == 1) { ?>
            <tr>
              <td valign="top">
                <table class="table table-hover table-striped" style="width:100%; border-collapse:collapse;">
                  <thead>
                    <tr>
                      <th style="text-align: left;">ITEM</th>
                      <th style="text-align: right">AMOUNT (<?= $currency; ?>)</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php 
                      $total_income = 0;
                      $income_rows_counter = 0;
                      for($i = 0; $i < sizeof($item_array); $i++) {
                        //let's find the sum of each of the items title in the table
                        $query2 = $this->db
                                          ->select_sum('amount')
                                          ->where('title', $item_array[$i])
                                          ->where('can_delete !=', 'trash')
                                          ->get('payment')->result_array();
                        foreach($query2 as $asw) {
                          ?>

                            <tr>
                              <td><?= strtoupper($item_array[$i]); ?></td>
                              <td align="right"><?= number_format($asw['amount'], 2, '.', ','); ?></td>
                            </tr>
                          <?php
                          $total_income += $asw['amount']; 

                          $income_rows_counter++;
                        }
                     }
                    ?>

                    <tr>
                      <td><strong>GRAND TOTAL INCOME</strong></td>
                      <td align="right"><strong><?= number_format($total_income, 2, '.', ','); ?></strong></td>
                    </tr>

                  </tbody>
                </table>
              </td>

              <td valign="top">
                <table class="table table-hover table-striped" style="width:100%; border-collapse:collapse;">
                  <thead>
                    <tr>
                      <th style="text-align: left;">ITEM</th>
                      <th style="text-align: right">AMOUNT (<?= $currency; ?>)</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php 
                      $total_expenditure = 0;
                      $exp_rows_counter = 0;
                        for($j = 0; $j < sizeof($item_array_exp); $j++) {
                          //let's find the sum of each of the items title in the table
                          $query22 = $this->db
                                            ->select_sum('amount')
                                            ->where('title', $item_array_exp[$j])
                                            ->where('can_delete !=', 'trash')
                                            ->get('payment')->result_array();
                          foreach($query22 as $asw2) {
                            ?>

                              <tr style="border-bottom: 1px;">
                                <td><?= strtoupper($item_array_exp[$j]); ?></td>
                                <td align="right"><?= number_format($asw2['amount'], 2, '.', ','); ?></td>
                              </tr>
                            <?php
                            $total_expenditure += $asw2['amount']; 

                            $exp_rows_counter++;
                          }
                       }

                       if($total_income > $total_expenditure) {
                          echo '<tr>
                                  <td><strong>PROFIT</strong></td>
                                  <td align="right"><strong>'.number_format($total_income - $total_expenditure, 2, '.', ','). '</strong></td>
                                </tr>';
                       } else if($total_income < $total_expenditure) {
                          echo '<tr>
                                  <td><strong>LOSS</strong></td>
                                  <td align="right"><strong>'.number_format($total_income - $total_expenditure, 2, '.', ','). '</strong></td>
                                </tr>';
                         }
                        ?>

                      
                      <tr>
                        <td><strong>GRAND TOTAL EXPENDITURE</strong></td>
                        <td align="right"><strong><?= number_format($total_expenditure, 2, '.', ','); ?></strong></td>
                      </tr>
                  </tbody>
                </table>
              </td>
            </tr>
            <?php 
              } else {
                echo '<tr>
                        <td colspan="2" align="center"><h4 style="color: red;">No Record Found For The Selected Year!</h4></td>
                      </tr>';
              }
            ?>
          </tbody>
        </table>
      </div>

    
        <button onclick="PrintElem('#print_annual')" style="border: 1 solid #0cc; padding: 10px; cursor: pointer; background-color: green; color: #fff;">Print</button>
        <button onclick="load_back()" style="position:relative; border: 1 solid #0cc; cursor: pointer; padding: 10px; background-color: red; color: #fff;">Back</button>
        </center>
    

<script type="text/javascript">
  $(function() {
    //profit_loss();
  })
  //let's calculate the profit and loss
  function profit_loss() {
    $('#profit_tr').css('display', 'none');
    $('#loss_tr').css('display', 'none');

    let total_income = Number(<?php echo $total_income; ?>);
    let total_expenditure = Number(<?php echo $total_expenditure; ?>);

    if(total_income > total_expenditure) { //we have made a profit
      const profit = total_income - total_expenditure;
      const loss = total_expenditure - total_income;

      $('#profit_tr').fadeIn('500');
      $('#profit').html("<strong>" + number_format(profit, 2, '.', ',') + "</strong>");
    } else if(total_income < total_expenditure) { //we have made a loss
      $('#loss_tr').fadeIn('500');
      $('#loss').html("<strong>" + number_format(loss, 2, '.', ',') + "</strong>");
    }
  }
  function load_back() {
    window.location.href = '<?= site_url($this->session->userdata('login_type').'/financial_reports'); ?>';
  }
</script>
    
<script type="text/javascript">
    function PrintElem(elem)
    {
        Popup($(elem).html());
    }

    function Popup(data)
    {
        var mywindow = window.open('', 'Receipt', '');
        mywindow.document.write('<!doctype html><html><head><title></title>');
        mywindow.document.write('<link rel="stylesheet" href="assets/css/neon-theme.css" type="text/css" />');
        mywindow.document.write('<link rel="stylesheet" href="assets/js/datatables/responsive/css/datatables.responsive.css" type="text/css" />');
        mywindow.document.write('<link rel="stylesheet" href="assets/js/jquery-ui/css/no-theme/jquery-ui-1.10.3.custom.min.css">');
        mywindow.document.write('</head><body style="font-size: 14px">');
        mywindow.document.write(data);
        mywindow.document.write('</body></html>');
        mywindow.document.close();

        mywindow.onload=function(){
            mywindow.focus();
            mywindow.print();
            mywindow.close();
        }
        
    }
</script>