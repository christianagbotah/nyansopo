<?php
  
  $num_rows = $this->graphassessment_model->getPortfolioAssessmentRow($start_week, $end_week, $class_id, $subject_id);//getting the number of times the assessment was conducted within the specified time interval

  $start_week = substr(explode('-', $start_week)[1], 1).'-'.explode('-', $start_week)[0];
  $end_week = substr(explode('-', $end_week)[1], 1). '-'.explode('-', $end_week)[0];


  $student_id_change_tracker = 0;
  $student_name_change_tracker = '';
  $student_name = [];
  $student_total_score = [];
  $student_score = 0;

  $arraySize = count($data);
  $loopCounter = 0;

  foreach($data as $row) {

    if($student_id_change_tracker != 0 && $student_id_change_tracker != $row['student_id']) {
      //this is a another student
      $student_name[] = $student_name_change_tracker;

      //find the average
      if($student_score > 0) {
        $avg = $student_score / $num_rows;
        $perc_score = $avg * 10; //making it 100%

      } else {
        $perc_score = 0;
      }

      if($perc_score > 100) {
        $perc_score = 100;
      }
      $student_total_score[] = round($perc_score, 2);
      $student_score = 0; //reset
    }

    
    $student_score += $row['strand_score'];

    $student_id_change_tracker = $row['student_id'];
    $student_name_change_tracker = $row['name'];
    $loopCounter++;

    //if this is the final item in the array
    if($loopCounter == $arraySize) {
      $student_name[] = $student_name_change_tracker;
      
      //find the average
      if($student_score > 0) {
        $avg = $student_score / $num_rows;
        $perc_score = $avg * 10; //making it 100%
        
      } else {
        $perc_score = 0;
      }

      if($perc_score > 100) {
        $perc_score = 100;
      }
      $student_total_score[] = round($perc_score, 2);
      $student_score = 0; //reset
    }
  }

  $subject_name = $this->graphassessment_model->getSubjectNamebyId($subject_id);
  $subject_name = ucwords(strtolower($subject_name));

  $class_name = $this->crud_model->getFullClassName($class_id);

  //var_dump($student_total_score);
?>

<div id="chart"></div>

<script type="text/javascript">
	 $(function(e) {

      let student_marks = [];
      let student_names = [];
      let subject_name;
      let class_name;
      let graphType;
     // student_marks = [28, 29, 33, 36, 32, 32, 38, 39];

      student_marks = <?php echo json_encode($student_total_score); ?>;
      student_names = <?php echo json_encode($student_name); ?>;
      subject_name = '<?php echo $subject_name; ?>';
      class_name = '<?php echo $class_name; ?>'; 
      graphType = '<?php echo $graph_type; ?>';


      //$('#chart2').html(student_marks);

       var options = {
          series: [
          {
            name: "Total Score",
            data: student_marks
          }
          /*{
            name: "Low - Marks",
            data: [12, 11, 14, 18, 17, 13, 13]
          }*/
        ],
        chart: {
          height: 500,
          type: graphType,
          dropShadow: {
            enabled: true,
            color: '#000',
            top: 18,
            left: 7,
            blur: 10,
            opacity: 0.2
          },
          toolbar: {
            show: true
          }
        },
        colors: ['#77B6EA'],
        dataLabels: {
          enabled: true,
        },
        stroke: {
          curve: 'smooth'
        },
        title: {
          text: class_name + ' Students\' Portfolio Assessment Average Performances In ' + subject_name + ' - Duration: From Week <?=$start_week;?> To Week <?=$end_week;?>',
          align: 'center'
        },
        subtitle: {
          text: graphType.toUpperCase() + ' CHART',
          align: 'right',
        },
        grid: {
          borderColor: '#e7e7e7',
          row: {
            colors: ['#f3f3f3', 'transparent'], // takes an array which will be repeated on columns
            opacity: 0.5
          },
        },
        markers: {
          size: 1
        },
        xaxis: {
          categories: student_names,
          title: {
            text: 'Students'
          }
        },
        yaxis: {
          title: {
            text: 'Assessment Scores'
          },
          min: 0,
          max: 100,
          labels: {
            formatter: (val) => {return val + '%'}
          }
        },
        legend: {
          position: 'top',
          horizontalAlign: 'right',
          floating: true,
          offsetY: -25,
          offsetX: -5
        }
        };

        var chart = new ApexCharts(document.querySelector("#chart"), options);
        chart.render();
  });
</script>