<?php

  $subject_id_change_tracker = 0;
  $subject_name_change_tracker = '';
  $subject_name = [];
  $subject_total_score = [];
  $subject_score = 0;


  /*if($graph_type != 'bar') {
  	$subject_total_score = [0];
  }*/

 // var_dump($data);

  $arraySize = count($data);
  $loopCounter = 0;

  foreach($data as $row) {


    if($subject_id_change_tracker != 0 && $subject_id_change_tracker != $row['subject_id']) {
      //this is a another student
      $subject_name[] = $subject_name_change_tracker;
      $num_rows = $this->graphassessment_model->getPortfolioAssessmentRow($start_week, $end_week, $class_id, $subject_id_change_tracker);//getting the number of times the assessment was conducted within the specified time interval

      //find the average
      if($subject_score > 0) {
        $avg = $subject_score / $num_rows;
        $perc_score = $avg * 10; //making it 100%

      } else {
        $perc_score = 0;
      }
      
      if($perc_score > 100) {
        $perc_score = 100;
      }
      $subject_total_score[] = round($perc_score, 2);
      $subject_score = 0; //reset
    }

    
    $subject_score += $row['strand_score'];

    $subject_id_change_tracker = $row['subject_id'];
    $subject_name_change_tracker = $row['name'];
    $loopCounter++;

    //if this is the final item in the array
    if($loopCounter == $arraySize) {
      $subject_name[] = $subject_name_change_tracker;
      $num_rows = $this->graphassessment_model->getPortfolioAssessmentRow($start_week, $end_week, $class_id, $subject_id_change_tracker);//getting the number of times the assessment was conducted within the specified time interval
      
      //find the average
      if($subject_score > 0) {
        $avg = $subject_score / $num_rows;
        $perc_score = $avg * 10; //making it 100%
        
      } else {
        $perc_score = 0;
      }
      
      if($perc_score > 100) {
        $perc_score = 100;
      }
      $subject_total_score[] = round($perc_score, 2);
      $subject_score = 0; //reset
    }
  }

  $student_name = $this->crud_model->getStudentInfoById($student_id)->name;
  $student_name = ucwords(strtolower($student_name));

  $class_name = $this->crud_model->getFullClassName($class_id);

//for updating the graph title
  $start_week = substr(explode('-', $start_week)[1], 1).'-'.explode('-', $start_week)[0];
  $end_week = substr(explode('-', $end_week)[1], 1). '-'.explode('-', $end_week)[0];
?>

<div id="chart"></div>

<script type="text/javascript">
	 $(function(e) {

      let subject_marks = [];
      let subject_names = [];
      let subject_name;
      let class_name;
      let graphType;
     // subject_marks = [28, 29, 33, 36, 32, 32, 38, 39];

      subject_marks = <?php echo json_encode($subject_total_score); ?>;
      subject_names = <?php echo json_encode($subject_name); ?>;
      student_name = '<?php echo $student_name; ?>';
      class_name = '<?php echo $class_name; ?>'; 
      graphType = '<?php echo $graph_type; ?>';


      //$('#chart2').html(subject_marks);

       var options = {
          series: [
          {
            name: "Total Score",
            data: subject_marks
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
          text: student_name + '\'s Portfolio Assessment Average Performances In All Subjects - Duration: From Week <?=$start_week;?> To Week <?=$end_week;?>',
          align: 'center'
        },
        subtitle: {
          text: graphType.toUpperCase() + ' CHART - CLASS: ' + class_name.toUpperCase(),
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
          categories: subject_names,
          title: {
            text: 'Subjects'
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