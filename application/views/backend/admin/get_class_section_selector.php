<?php $sections = $this->db->get_where('section', array('class_id' => $class_id))->result_array(); ?>
<select class="form-control select2" name="section_id">
    <?php foreach ($sections as $section): ?>
        <option value="<?php echo $section['section_id'] ?>"><?php echo $section['name']; ?></option>
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
