<?php


  $start_week_label = substr(explode('-', $start_week)[1], 1).'-'.explode('-', $start_week)[0];
  $end_week_label = substr(explode('-', $end_week)[1], 1). '-'.explode('-', $end_week)[0];
  $class_name = $this->crud_model->getFullClassName($class_id);

  $all_student_ids = $this->graphassessment_model->getAllStudentsID($class_id, $start_week);
  $all_subject_ids = $this->graphassessment_model->getAllSubjectsID($class_id, $start_week, $end_week);

  $subject_name_array = [];
  $main_score_array = [];
  $student_counter = 0;
  $student_chart_color = [];


  foreach($all_student_ids as $student) {

    $student_chart_color[] = $this->graphassessment_model->generateNewHexColor(); //generate color for the student

    $student_scores = [];
    $student_data = [];

    foreach($all_subject_ids as $subject) {

       //get subject name
      if($student_counter == 0) {
        $subject_name_array[] = $this->crud_model->get_subject_name_by_id($subject['subject_id']);
      }
      

      //all subjects for a single student
      $num_rows = $this->graphassessment_model->getPortfolioAssessmentRow($start_week, $end_week, $class_id, $subject['subject_id']);//getting the number of times the assessment was conducted within the specified time interval
      $total_strand_score = $this->graphassessment_model->getTotalStrandScore(
        $class_id,
        $student['student_id'],
        $subject['subject_id'],
        $start_week,
        $end_week,
      );

      if($total_strand_score > 0) {
        $avg = $total_strand_score / $num_rows;
        $perc = $avg * 10;

        if($perc > 100) {
          $perc = 100;
        }
      } else {
        $perc = 0;
      }

      $student_scores[] = round($perc, 2);

    }

    $student_counter++;
    $student_name = $this->crud_model->getStudentInfoById($student['student_id'])->name;

    $student_data['name'] = $student_name;
    $student_data['data'] = $student_scores;

    //push it
    $main_score_array[] = $student_data;

  }
  
  /*var_dump($subject_name_array);
  return;*/
  
  $student_name_label = ucwords(strtolower($student_name));

  

//for updating the graph title
  $start_week = substr(explode('-', $start_week)[1], 1).'-'.explode('-', $start_week)[0];
  $end_week = substr(explode('-', $end_week)[1], 1). '-'.explode('-', $end_week)[0];
?>

<div id="chart"></div>

<script type="text/javascript">
	 $(function(e) {

      let subject_marks = [];
      let subject_names = [];
      let chartColors = [];
      let subject_name;
      let class_name;
      let graphType;
     // subject_marks = [28, 29, 33, 36, 32, 32, 38, 39];

      subject_marks = $.parseJSON('<?php echo json_encode($main_score_array); ?>');
      subject_names = <?php echo json_encode($subject_name_array); ?>;
      student_name = '<?php echo $student_name_label; ?>';
      class_name = '<?php echo $class_name; ?>'; 
      graphType = '<?php echo $graph_type; ?>';
      chartColors = <?php echo json_encode($student_chart_color); ?>;
console.log(chartColors);
      //$('#chart2').html(subject_marks);
console.log(subject_marks.length);
    let i = 0;
       var options = {
          series: subject_marks
          
          /*{
            name: "Low - Marks",
            data: [12, 11, 14]
          }*/
        ,
        chart: {
          height: 700,
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
        colors: chartColors,
        dataLabels: {
          enabled: true,
        },
        stroke: {
          curve: 'smooth'
        },
        title: {
          text: 'All Students Portfolio Assessment Average Performances In All Subjects - Duration: From Week <?=$start_week_label;?> To Week <?=$end_week_label;?>',
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