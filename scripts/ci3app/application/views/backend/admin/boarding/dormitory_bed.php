<style>
.modern-card{background:#fff;border-radius:12px;box-shadow:0 2px 8px rgba(0,0,0,.08);padding:24px;margin-bottom:24px}
.modern-table{width:100%;border-collapse:separate;border-spacing:0}
.modern-table thead th{background:#f8fafc;padding:16px;text-align:left;font-weight:600;color:#475569;border-bottom:2px solid #e2e8f0}
.modern-table tbody td{padding:16px;border-bottom:1px solid #f1f5f9}
.modern-table tbody tr:hover{background:#f8fafc}
.btn-modern{padding:10px 20px;border-radius:8px;border:none;cursor:pointer;font-weight:500;transition:all .3s}
.btn-primary{background:#3b82f6;color:#fff}
.btn-primary:hover{background:#2563eb}
.form-group{margin-bottom:20px}
.form-group label{display:block;margin-bottom:8px;font-weight:500;color:#334155}
.form-control{width:100%;padding:14px;border:1px solid #e2e8f0;border-radius:8px;font-size:14px;min-height:48px}
.form-control:focus{outline:none;border-color:#3b82f6}
.select2-container{z-index:10050!important}
.select2-dropdown{z-index:10051!important}
.badge{padding:6px 12px;border-radius:6px;font-size:12px;font-weight:500}
.badge-success{background:#dcfce7;color:#166534}
.badge-danger{background:#fee2e2;color:#991b1b}
</style>

<div class="modern-card">
<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:24px">
<h2 style="margin:0;color:#1e293b">Dormitory Beds</h2>
<button class="btn-modern btn-primary" onclick="openAddModal()"><i class="fa fa-plus"></i> Add Bed</button>
</div>

<table class="modern-table">
<thead>
<tr>
<th>Bed Code</th>
<th>Dormitory</th>
<th>Bed Number</th>
<th>Type</th>
<th>Status</th>
<th>Actions</th>
</tr>
</thead>
<tbody>
<?php 
$beds = $this->db->get('boarding_bed')->result_array();
foreach($beds as $bed):
?>
<tr>
<td><strong><?php echo $bed['bed_code'];?></strong></td>
<td><?php 
$dorm = $this->db->get_where('boarding_dormitory',['dormitory_id'=>$bed['dormitory_id']])->row();
echo $dorm ? $dorm->dormitory_name : 'N/A';
?></td>
<td><?php echo $bed['bed_number'];?></td>
<td><?php echo $bed['bed_type'];?></td>
<td><span class="badge <?php echo $bed['bed_status']=='available'?'badge-success':'badge-danger';?>"><?php echo ucfirst($bed['bed_status']);?></span></td>
<td>
<button class="btn-modern" style="background:#f59e0b;color:#fff;padding:8px 16px" onclick='editBed(<?php echo json_encode($bed);?>)'><i class="fa fa-edit"></i></button>
<button class="btn-modern" style="background:#ef4444;color:#fff;padding:8px 16px" onclick="deleteBed(<?php echo $bed['bed_id'];?>)"><i class="fa fa-trash"></i></button>
</td>
</tr>
<?php endforeach;?>
</tbody>
</table>
</div>



<link rel="stylesheet" href="<?php echo base_url('assets/select2/css/select2.min.css');?>">
<script src="<?php echo base_url('assets/select2/js/select2.min.js');?>"></script>

<script>
function openAddModal(){
showBedModal();
}

function editBed(bed){
showBedModal(bed);
}

function showBedModal(bed){
const isEdit=!!bed;
const formHtml=`
<?php echo form_open('', array('id'=>'bedForm'));?>
<input type="hidden" name="bed_id" id="bed_id" value="${isEdit?bed.bed_id:''}">
<div class="form-group">
<label>Dormitory *</label>
<select name="dormitory_id" id="dormitory_id" class="form-control select2" required>
<option value="">Select Dormitory</option>
<?php $dorms=$this->db->get('boarding_dormitory')->result_array();foreach($dorms as $d):?>
<option value="<?php echo $d['dormitory_id'];?>"><?php echo $d['dormitory_name'];?></option>
<?php endforeach;?>
</select>
</div>
<div class="form-group">
<label>Bed Code *</label>
<input type="text" name="bed_code" id="bed_code" class="form-control" required value="${isEdit?bed.bed_code:''}">
</div>
<div class="form-group">
<label>Bed Number</label>
<input type="text" name="bed_number" id="bed_number" class="form-control" value="${isEdit?bed.bed_number:''}">
</div>
<div class="form-group">
<label>Bed Type</label>
<select name="bed_type" id="bed_type" class="form-control">
<option value="Single">Single</option>
<option value="Bunk">Bunk</option>
</select>
</div>
<div class="form-group">
<label>Status</label>
<select name="bed_status" id="bed_status" class="form-control">
<option value="available">Available</option>
<option value="occupied">Occupied</option>
<option value="maintenance">Maintenance</option>
</select>
</div>
<div class="form-group">
<label>Description</label>
<textarea name="bed_description" id="bed_description" class="form-control" rows="3">${isEdit?bed.bed_description:''}</textarea>
</div>
<div class="modal-footer" style="border-top:1px solid #ecf0f1;padding:20px 30px;background:#f8f9fa;">
<button type="button" class="btn modern-btn modern-btn-cancel" data-dismiss="modal">Cancel</button>
<button type="submit" class="btn modern-btn modern-btn-primary">Save</button>
</div>
<?php echo form_close();?>
`;
$('#modal_ajax .modal-title').text(isEdit?'Edit Bed':'Add Bed');
$('#modal_ajax .modal-body').html(formHtml);
if(isEdit){
$('#dormitory_id').val(bed.dormitory_id);
$('#bed_type').val(bed.bed_type);
$('#bed_status').val(bed.bed_status);
}
$('#modal_ajax').modal('show');
setTimeout(function(){
$('.select2').select2({dropdownParent:$('#modal_ajax'),width:'100%'});
},100);
}

$(document).on('submit','#bedForm',function(e){
e.preventDefault();
showAjaxModal_alert('Saving...','loading');
const bedId=$('#bed_id').val();
const url=bedId?'<?php echo site_url("admin/manageDormitoryBed/update/");?>'+bedId:'<?php echo site_url("admin/manageDormitoryBed/create");?>';
const formData=$(this).serialize();
$.ajax({url:url,type:'POST',data:formData,success:function(res){
$('#modal_ajax').modal('hide');
showAjaxModal_alert('Bed saved successfully!','success');
setTimeout(function(){$('#modal_alert').modal('hide');location.reload();},1500);
},error:function(xhr){
console.log(xhr.responseText);
showAjaxModal_alert('Failed to save bed. Please try again.','error');
}});
});

$('#modal_ajax').on('hidden.bs.modal',function(){
$('.select2').select2('destroy');
});

function deleteBed(id){
showConfirmModal('Delete Bed','Are you sure you want to delete this bed?',function(){
showAjaxModal_alert('Deleting...','loading');
$.post('<?php echo site_url("admin/manageDormitoryBed/delete/");?>'+id,function(res){
showAjaxModal_alert('Bed deleted successfully!','success');
setTimeout(function(){$('#modal_alert').modal('hide');location.reload();},1500);
}).fail(function(){
showAjaxModal_alert('Failed to delete bed.','error');
});
},'Delete','danger');
}
</script>
