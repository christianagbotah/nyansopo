<?php 
$running_year = $this->db->get_where('settings', array('type' => 'running_year'))->row()->description;
$running_term = $this->db->get_where('settings', array('type' => 'running_term'))->row()->description;
$subjects = $this->db->get_where('subject', array('class_id' => $class_id, 'teacher_id' => $this->session->userdata('teacher_id'),  'year' => $running_year, 'term' => $running_term))->result_array(); ?>
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
