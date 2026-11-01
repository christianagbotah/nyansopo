<div class="form-group">
<label class="control-label" style="margin-bottom: 5px;"><?php echo get_phrase('section');?></label>
    <select name="section_id" id="section_id" class="form-control select2">
        <?php 
            $sections = $this->db->get_where('section' , array(
                'class_id' => $class_id 
            ))->result_array();
            foreach($sections as $row):
        ?>
        <option value="<?php echo $row['section_id'];?>"><?php echo $row['name'];?></option>
        <?php endforeach;?>
    </select>
</div>

<script type="text/javascript">
   
    $(document).ready(function () {

        // SelectBoxIt Dropdown replacement
        if (typeof $.fn.select2 !== "undefined")
        {
            $("select.select2").each(function (i, el)
            {
                var $this = $(el),
                        opts = {
                            showFirstOption: attrDefault($this, 'first-option', true),
                            'native': attrDefault($t<div class="form-group">
<label class="control-label" style="margin-bottom: 5px;"><?php echo get_phrase('section');?></label>
    <select name="section_id" id="section_id" class="form-control select2">
        <?php 
            $sections = $this->db->get_where('section' , array(
                'class_id' => $class_id 
            ))->result_array();
            foreach($sections as $row):
        ?>
        <option value="<?php echo $row['section_id'];?>"><?php echo $row['name'];?></option>
        <?php endforeach;?>
    </select>
</div>

<script type="text/javascript">
   
    $(document).ready(function () {

        // SelectBoxIt Dropdown replacement
        if (typeof $.fn.select2 !== "undefined")
        {
            $("select.select2").each(function (i, el)
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