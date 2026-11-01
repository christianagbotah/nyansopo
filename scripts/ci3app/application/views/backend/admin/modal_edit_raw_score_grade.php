<?php 
$edit_data		=	$this->db->get_where('raw_score_grade' , array('grade_id' => $param2) )->result_array();
foreach ( $edit_data as $row):
?>

<div class="row">
	<div class="col-md-12">
		<div class="panel" data-collapsed="0" style="background: white; border-radius: 20px; padding: 0; border: none; box-shadow: 0 20px 60px rgba(0,0,0,0.15);">
        	<div class="panel-heading" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 2rem; border-radius: 20px 20px 0 0; border: none; margin: 0;">
            	<div class="panel-title" style="font-size: 1.5rem; font-weight: 700; margin: 0; display: flex; align-items: center; gap: 1rem;">
            		<i class="fa fa-edit" style="font-size: 1.8rem;"></i>
					Edit Raw Score Grade
            	</div>
            </div>
			<div class="panel-body" style="padding: 3rem; background: white; border-radius: 0 0 20px 20px;">
				
                <?php echo form_open(site_url('admin/grade/do_update_raw/'.$row['grade_id']) , array('id' => 'editRawGradeForm', 'target'=>'_top'));?>
            
                <div style="margin-bottom: 2rem; display: flex; flex-direction: column;">
                    <label style="font-weight: 700; color: #374151; margin-bottom: 0.75rem; font-size: 1rem; display: flex; align-items: center; gap: 0.5rem;">
                        <i class="fa fa-tag" style="color: #667eea; font-size: 1.1rem; width: 20px;"></i>
                        Grade Name
                    </label>
                    <input type="text" name="name" value="<?php echo $row['name'];?>" placeholder="e.g., EXCELLENT, VERY GOOD" required style="width: 100%; padding: 1rem 1.25rem; border: 2px solid #e5e7eb; border-radius: 12px; font-size: 1rem; transition: all 0.3s ease; background: #fafbfc; font-weight: 500; box-sizing: border-box;" onfocus="this.style.borderColor='#667eea'; this.style.boxShadow='0 0 0 4px rgba(102, 126, 234, 0.1)'; this.style.background='white'; this.style.transform='translateY(-2px)';" onblur="this.style.borderColor='#e5e7eb'; this.style.boxShadow='none'; this.style.background='#fafbfc'; this.style.transform='translateY(0)';" />
                </div>
                
                <div style="margin-bottom: 2rem; display: flex; flex-direction: column;">
                    <label style="font-weight: 700; color: #374151; margin-bottom: 0.75rem; font-size: 1rem; display: flex; align-items: center; gap: 0.5rem;">
                        <i class="fa fa-certificate" style="color: #667eea; font-size: 1.1rem; width: 20px;"></i>
                        Grade Symbol
                    </label>
                    <input type="text" name="grade_point" value="<?php echo $row['grade_point'];?>" placeholder="e.g., A+, A, B+, 1, 2" required style="width: 100%; padding: 1rem 1.25rem; border: 2px solid #e5e7eb; border-radius: 12px; font-size: 1rem; transition: all 0.3s ease; background: #fafbfc; font-weight: 500; box-sizing: border-box;" onfocus="this.style.borderColor='#667eea'; this.style.boxShadow='0 0 0 4px rgba(102, 126, 234, 0.1)'; this.style.background='white'; this.style.transform='translateY(-2px)';" onblur="this.style.borderColor='#e5e7eb'; this.style.boxShadow='none'; this.style.background='#fafbfc'; this.style.transform='translateY(0)';" />
                </div>
                
                <div style="margin-bottom: 2rem; display: flex; flex-direction: column;">
                    <label style="font-weight: 700; color: #374151; margin-bottom: 0.75rem; font-size: 1rem; display: flex; align-items: center; gap: 0.5rem;">
                        <i class="fa fa-arrow-up" style="color: #667eea; font-size: 1.1rem; width: 20px;"></i>
                        Minimum Mark (%)
                    </label>
                    <input type="number" name="mark_from" value="<?php echo $row['mark_from'];?>" placeholder="e.g., 80.5" min="0" max="100" step="0.1" required style="width: 100%; padding: 1rem 1.25rem; border: 2px solid #e5e7eb; border-radius: 12px; font-size: 1rem; transition: all 0.3s ease; background: #fafbfc; font-weight: 500; box-sizing: border-box;" onfocus="this.style.borderColor='#667eea'; this.style.boxShadow='0 0 0 4px rgba(102, 126, 234, 0.1)'; this.style.background='white'; this.style.transform='translateY(-2px)';" onblur="this.style.borderColor='#e5e7eb'; this.style.boxShadow='none'; this.style.background='#fafbfc'; this.style.transform='translateY(0)';" />
                </div>
                
                <div style="margin-bottom: 2rem; display: flex; flex-direction: column;">
                    <label style="font-weight: 700; color: #374151; margin-bottom: 0.75rem; font-size: 1rem; display: flex; align-items: center; gap: 0.5rem;">
                        <i class="fa fa-arrow-down" style="color: #667eea; font-size: 1.1rem; width: 20px;"></i>
                        Maximum Mark (%)
                    </label>
                    <input type="number" name="mark_upto" value="<?php echo $row['mark_upto'];?>" placeholder="e.g., 100" min="0" max="100" step="0.1" required style="width: 100%; padding: 1rem 1.25rem; border: 2px solid #e5e7eb; border-radius: 12px; font-size: 1rem; transition: all 0.3s ease; background: #fafbfc; font-weight: 500; box-sizing: border-box;" onfocus="this.style.borderColor='#667eea'; this.style.boxShadow='0 0 0 4px rgba(102, 126, 234, 0.1)'; this.style.background='white'; this.style.transform='translateY(-2px)';" onblur="this.style.borderColor='#e5e7eb'; this.style.boxShadow='none'; this.style.background='#fafbfc'; this.style.transform='translateY(0)';" />
                </div>
                
                <div style="margin-bottom: 2rem; display: flex; flex-direction: column;">
                    <label style="font-weight: 700; color: #374151; margin-bottom: 0.75rem; font-size: 1rem; display: flex; align-items: center; gap: 0.5rem;">
                        <i class="fa fa-star" style="color: #667eea; font-size: 1.1rem; width: 20px;"></i>
                        GPA Points
                    </label>
                    <input type="number" name="gpa" value="<?php echo $row['grade_point_numeric'] ?? ''; ?>" placeholder="e.g., 4.0, 3.5, 3.0" step="0.1" min="0" max="5" required style="width: 100%; padding: 1rem 1.25rem; border: 2px solid #e5e7eb; border-radius: 12px; font-size: 1rem; transition: all 0.3s ease; background: #fafbfc; font-weight: 500; box-sizing: border-box;" onfocus="this.style.borderColor='#667eea'; this.style.boxShadow='0 0 0 4px rgba(102, 126, 234, 0.1)'; this.style.background='white'; this.style.transform='translateY(-2px)';" onblur="this.style.borderColor='#e5e7eb'; this.style.boxShadow='none'; this.style.background='#fafbfc'; this.style.transform='translateY(0)';" />
                </div>
                
                <div style="display: flex; gap: 1rem; justify-content: center; padding-top: 2rem; border-top: 2px solid #f3f4f6; margin-top: 2rem;">
                    <button type="submit" style="background: linear-gradient(135deg, #10b981 0%, #059669 100%); color: white; border: none; padding: 1rem 2.5rem; border-radius: 12px; font-weight: 700; font-size: 1rem; cursor: pointer; transition: all 0.3s ease; display: flex; align-items: center; gap: 0.75rem; min-width: 160px; justify-content: center;" onmouseover="this.style.transform='translateY(-3px)'; this.style.boxShadow='0 12px 35px rgba(16, 185, 129, 0.4)';" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='none';">
                        <i class="fa fa-save"></i>
                        Update Grade
                    </button>
                </div>
                
                </form>
                
                <script>
                $('#editRawGradeForm').submit(function(e) {
                    e.preventDefault();
                    $('.close')[0].click();
                    showAjaxModal_alert('Updating grade...', 'loading');
                    
                    $.ajax({
                        url: $(this).attr('action'),
                        type: 'POST',
                        data: new FormData(this),
                        cache: false,
                        contentType: false,
                        processData: false,
                        dataType: 'json'
                    }).done(function(response) {
                        if(response.status === 'success') {
                            showAjaxModal_alert(response.message, 'success');
                            setTimeout(() => location.reload(), 2000);
                        } else {
                            showAjaxModal_alert(response.message, 'error');
                        }
                    }).fail(function() {
                        showAjaxModal_alert('An error occurred', 'error');
                    });
                });
                </script>
        </div>
    </div>
</div>

<?php
endforeach;
?>