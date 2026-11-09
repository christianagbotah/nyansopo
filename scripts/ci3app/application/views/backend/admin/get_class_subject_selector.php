<?php 
$running_year = $this->db->get_where('settings', array('type' => 'running_year'))->row()->description;
$running_term = $this->db->get_where('settings', array('type' => 'running_term'))->row()->description;
$running_sem = $this->db->get_where('settings', array('type' => 'running_sem'))->row()->description;

$account_type     = $this->session->userdata('login_type');

//class teacher id
$class_teacher = $this->db->get_where('class', array('class_id' => $class_id))->row()->teacher_id;

$class_name = $this->crud_model->get_class_name($class_id);

if($account_type == 'teacher') {

    if($class_name == 'JHSS') { 

        //if this is a class master or class teacher, grant full access to all the subjects
        if($class_teacher == $this->session->userdata('teacher_id')) {
            $subjects = $this->db->get_where('subject' , array(
            'class_id' => $class_id , 'year' => $this->db->get_where('settings' , array('type' => 'running_year'))->row()->description, 'sem' => $this->db->get_where('settings' , array('type' => 'running_sem'))->row()->description))->result_array();
        } else {
            $subjects = $this->db->get_where('subject' , array(
            'class_id' => $class_id , 'year' => $this->db->get_where('settings' , array('type' => 'running_year'))->row()->description, 'sem' => $this->db->get_where('settings' , array('type' => 'running_sem'))->row()->description, 'teacher_id' => $this->session->userdata('teacher_id')
            ))->result_array();
        }

    } else {
        //if this is a class master or class teacher, grant full access to all the subjects
        if($class_teacher == $this->session->userdata('teacher_id')) {
            $subjects = $this->db->get_where('subject' , array(
            'class_id' => $class_id , 'year' => $this->db->get_where('settings' , array('type' => 'running_year'))->row()->description, 'term' => $this->db->get_where('settings' , array('type' => 'running_term'))->row()->description))->result_array();
        } else {
            $subjects = $this->db->get_where('subject' , array(
            'class_id' => $class_id , 'year' => $this->db->get_where('settings' , array('type' => 'running_year'))->row()->description, 'term' => $this->db->get_where('settings' , array('type' => 'running_term'))->row()->description, 'teacher_id' => $this->session->userdata('teacher_id')
            ))->result_array();
        }

    }

} else {
    if($class_name == 'JHSS') {
        $subjects = $this->db->get_where('subject', array('class_id' => $class_id, 'year' => $running_year, 'sem' => $running_sem))->result_array();
    } else {
        $subjects = $this->db->get_where('subject', array('class_id' => $class_id, 'year' => $running_year, 'term' => $running_term))->result_array();
    }
}


 ?>
<select class="form-control select2" name="subject_id">
    <?php foreach ($subjects as $subject): ?>
        <option value="<?php echo $subject['subject_id'] ?>"><?php echo $subject['name']; ?></option>
    <?php endforeach; ?>
</select>
<script type="text/javascript">

    // ajax form plugin calls at each modal loading,
    $(document).ready(function() {

        // SelectBoxIt Dropdown replacement
        if (typeof $.fn.select2 !== "undefined")
        {
            $("select.select2").each(function(i, el)
            {
                var $this = $(el),
                    opts = {
                        showFirstOption: attrDefault($this, 'first-option', true),
                        'native': attrDefault($this, 'native', false),
                        defaultText: attrDefault($this, 'text', ''),
                    };

                $this.addClass('visible');
                $this.select2({ placeholder: "Select an option", allowClear: true, width: "100%" });
            });
        }
    });

</script>
