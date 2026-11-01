<style>
.modern-card{background:#fff;border-radius:12px;box-shadow:0 2px 8px rgba(0,0,0,.08);padding:24px;margin-bottom:24px}
.modern-table{width:100%;border-collapse:separate;border-spacing:0}
.modern-table thead th{background:#f8fafc;padding:16px;text-align:left;font-weight:600;color:#475569;border-bottom:2px solid #e2e8f0}
.modern-table tbody td{padding:16px;border-bottom:1px solid #f1f5f9}
.modern-table tbody tr:hover{background:#f8fafc}
.btn-modern{padding:10px 20px;border-radius:8px;border:none;cursor:pointer;font-weight:500;transition:all .3s}
.btn-primary{background:#3b82f6;color:#fff}
.btn-primary:hover{background:#2563eb}
.form-grid{display:grid;grid-template-columns:1fr;gap:20px}
@media(min-width:768px){.form-grid{grid-template-columns:1fr 1fr}}
.form-group{margin-bottom:0}
.form-group label{display:block;margin-bottom:8px;font-weight:500;color:#334155}
.form-control{width:100%;padding:14px;border:1px solid #e2e8f0;border-radius:8px;font-size:14px;min-height:48px}
.form-control:focus{outline:none;border-color:#3b82f6}
.form-group-full{grid-column:1/-1}
#modal_dorm .modal-body{max-height:70vh;overflow-y:auto}
.select2-container{z-index:10050!important}
.select2-dropdown{z-index:10051!important}
.badge{padding:6px 12px;border-radius:6px;font-size:12px;font-weight:500}
.badge-success{background:#dcfce7;color:#166534}
</style>

<div class="modern-card">
<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:24px">
<h2 style="margin:0;color:#1e293b">Dormitories</h2>
<button class="btn-modern btn-primary" onclick="openAddModal()"><i class="fa fa-plus"></i> Add Dormitory</button>
</div>

<table class="modern-table">
<thead>
<tr>
<th>Dormitory Name</th>
<th>House</th>
<th>Capacity</th>
<th>Type</th>
<th>Floor</th>
<th>Actions</th>
</tr>
</thead>
<tbody>
<?php 
$dorms = $this->db->get('boarding_dormitory')->result_array();
foreach($dorms as $dorm):
?>
<tr>
<td><strong><?php echo $dorm['dormitory_name'];?></strong></td>
<td><?php 
$house = $this->db->get_where('boarding_house',['house_id'=>$dorm['house_id']])->row();
echo $house ? $house->house_name : 'N/A';
?></td>
<td><?php echo isset($dorm['dormitory_capacity']) ? $dorm['dormitory_capacity'] : 'N/A';?></td>
<td><?php echo $dorm['dormitory_type'];?></td>
<td><?php echo $dorm['dormitory_floor'];?></td>
<td>
<button class="btn-modern" style="background:#f59e0b;color:#fff;padding:8px 16px" onclick='editDorm(<?php echo json_encode($dorm);?>)'><i class="fa fa-edit"></i></button>
<button class="btn-modern" style="background:#ef4444;color:#fff;padding:8px 16px" onclick="deleteDorm(<?php echo $dorm['dormitory_id'];?>)"><i class="fa fa-trash"></i></button>
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
showBoardingDormModal();
}

function editDorm(dorm){
showBoardingDormModal(dorm);
}

function showBoardingDormModal(dorm){
const isEdit=!!dorm;
const formHtml=`
<?php echo form_open('', array('id'=>'dormForm'));?>
<input type="hidden" name="dormitory_id" id="dormitory_id" value="${isEdit?dorm.dormitory_id:''}">
<div class="form-grid">
<div class="form-group form-group-full">
<label>Boarding House *</label>
<select name="house_id" id="house_id" class="form-control select2" required>
<option value="">Select House</option>
<?php $houses=$this->db->get('boarding_house')->result_array();foreach($houses as $h):?>
<option value="<?php echo $h['house_id'];?>"><?php echo $h['house_name'];?></option>
<?php endforeach;?>
</select>
</div>
<div class="form-group">
<label>Dormitory Name *</label>
<input type="text" name="dormitory_name" id="dormitory_name" class="form-control" required value="${isEdit?dorm.dormitory_name:''}">
</div>
<div class="form-group">
<label>Capacity</label>
<input type="number" name="dormitory_capacity" id="dormitory_capacity" class="form-control" value="${isEdit?dorm.dormitory_capacity:''}">
</div>
<div class="form-group">
<label>Type</label>
<select name="dormitory_type" id="dormitory_type" class="form-control">
<option value="Male">Male</option>
<option value="Female">Female</option>
</select>
</div>
<div class="form-group">
<label>Floor</label>
<input type="text" name="dormitory_floor" id="dormitory_floor" class="form-control" value="${isEdit?dorm.dormitory_floor:''}">
</div>
<div class="form-group form-group-full">
<label>Description</label>
<textarea name="dormitory_description" id="dormitory_description" class="form-control" rows="3">${isEdit?dorm.dormitory_description:''}</textarea>
</div>
</div>
<div class="modal-footer" style="border-top:1px solid #ecf0f1;padding:20px 30px;background:#f8f9fa;">
<button type="button" class="btn modern-btn modern-btn-cancel" data-dismiss="modal">Cancel</button>
<button type="submit" class="btn modern-btn modern-btn-primary">Save</button>
</div>
<?php echo form_close();?>
`;
$('#boarding_dormitory_modal_title').text(isEdit?'Edit Dormitory':'Add Dormitory');
$('#boarding_dormitory_modal_body').html(formHtml);
if(isEdit){
$('#house_id').val(dorm.house_id);
$('#dormitory_type').val(dorm.dormitory_type);
}
$('#modal_boarding_dormitory').modal('show');
setTimeout(function(){
$('.select2').select2({dropdownParent:$('#modal_boarding_dormitory'),width:'100%'});
},100);
}

$(document).on('submit','#dormForm',function(e){
e.preventDefault();
showAjaxModal_alert('Saving...','loading');
const dormId=$('#dormitory_id').val();
const url=dormId?'<?php echo site_url("admin/manageBoardingDormitory/update/");?>'+dormId:'<?php echo site_url("admin/manageBoardingDormitory/create");?>';
const formData=$(this).serialize();
$.ajax({url:url,type:'POST',data:formData,success:function(res){
$('#modal_boarding_dormitory').modal('hide');
showAjaxModal_alert('Dormitory saved successfully!','success');
setTimeout(function(){$('#modal_alert').modal('hide');location.reload();},1500);
},error:function(xhr){
console.log(xhr.responseText);
showAjaxModal_alert('Failed to save dormitory. Please try again.','error');
}});
});

$('#modal_boarding_dormitory').on('hidden.bs.modal',function(){
$('.select2').select2('destroy');
});

function deleteDorm(id){
showConfirmModal('Delete Dormitory','Are you sure you want to delete this dormitory?',function(){
showAjaxModal_alert('Deleting...','loading');
$.post('<?php echo site_url("admin/manageBoardingDormitory/delete/");?>'+id,function(res){
showAjaxModal_alert('Dormitory deleted successfully!','success');
setTimeout(function(){$('#modal_alert').modal('hide');location.reload();},1500);
}).fail(function(){
showAjaxModal_alert('Failed to delete dormitory.','error');
});
},'Delete','danger');
}
</script>
