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
#modal_house .modal-body{max-height:70vh;overflow-y:auto}
.select2-container{z-index:10050!important}
.select2-dropdown{z-index:10051!important}
.badge{padding:6px 12px;border-radius:6px;font-size:12px;font-weight:500}
.badge-success{background:#dcfce7;color:#166534}
.badge-warning{background:#fef3c7;color:#92400e}
</style>

<div class="modern-card">
<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:24px">
<h2 style="margin:0;color:#1e293b">Boarding Houses</h2>
<button class="btn-modern btn-primary" onclick="openAddModal()"><i class="fa fa-plus"></i> Add House</button>
</div>

<table class="modern-table">
<thead>
<tr>
<th>House Name</th>
<th>Capacity</th>
<th>House Master</th>
<th>Year Established</th>
<th>Status</th>
<th>Actions</th>
</tr>
</thead>
<tbody>
<?php 
$houses = $this->db->get('boarding_house')->result_array();
foreach($houses as $house):
?>
<tr>
<td><strong><?php echo $house['house_name'];?></strong></td>
<td><?php echo $house['house_capacity'];?></td>
<td><?php 
$teacher = $this->db->get_where('teacher',['teacher_id'=>$house['house_master_id']])->row();
echo $teacher ? $teacher->name : 'N/A';
?></td>
<td><?php echo $house['house_year_established'];?></td>
<td><span class="badge badge-success">Active</span></td>
<td>
<button class="btn-modern" style="background:#f59e0b;color:#fff;padding:8px 16px" onclick='editHouse(<?php echo json_encode($house);?>)'><i class="fa fa-edit"></i></button>
<button class="btn-modern" style="background:#ef4444;color:#fff;padding:8px 16px" onclick="deleteHouse(<?php echo $house['house_id'];?>)"><i class="fa fa-trash"></i></button>
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
showBoardingHouseModal();
}

function editHouse(house){
showBoardingHouseModal(house);
}

function showBoardingHouseModal(house){
const isEdit=!!house;
const formHtml=`
<?php echo form_open_multipart('', array('id'=>'houseForm'));?>
<input type="hidden" name="house_id" id="house_id" value="${isEdit?house.house_id:''}">
<div class="form-grid">
<div class="form-group">
<label>House Name *</label>
<input type="text" name="house_name" id="house_name" class="form-control" required value="${isEdit?house.house_name:''}">
</div>
<div class="form-group">
<label>Capacity</label>
<input type="number" name="house_capacity" id="house_capacity" class="form-control" value="${isEdit?house.house_capacity:''}">
</div>
<div class="form-group">
<label>House Master</label>
<select name="house_master_id" id="house_master_id" class="form-control select2">
<option value="">Select Teacher</option>
<?php $teachers=$this->db->get('teacher')->result_array();foreach($teachers as $t):?>
<option value="<?php echo $t['teacher_id'];?>"><?php echo $t['name'];?></option>
<?php endforeach;?>
</select>
</div>
<div class="form-group">
<label>Year Established</label>
<input type="number" name="house_year_established" id="house_year_established" class="form-control" value="${isEdit?house.house_year_established:''}">
</div>
<div class="form-group form-group-full">
<label>GPS Code</label>
<input type="text" name="house_gps_code" id="house_gps_code" class="form-control" value="${isEdit?house.house_gps_code:''}">
</div>
<div class="form-group form-group-full">
<label>Description</label>
<textarea name="house_description" id="house_description" class="form-control" rows="3">${isEdit?house.house_description:''}</textarea>
</div>
<div class="form-group form-group-full">
<label>House Image</label>
<input type="file" name="house_image_link" class="form-control">
</div>
</div>
<div class="modal-footer" style="border-top:1px solid #ecf0f1;padding:20px 30px;background:#f8f9fa;">
<button type="button" class="btn modern-btn modern-btn-cancel" data-dismiss="modal">Cancel</button>
<button type="submit" class="btn modern-btn modern-btn-primary">Save</button>
</div>
<?php echo form_close();?>
`;
$('#boarding_house_modal_title').text(isEdit?'Edit Boarding House':'Add Boarding House');
$('#boarding_house_modal_body').html(formHtml);
if(isEdit){
$('#house_master_id').val(house.house_master_id);
}
$('#modal_boarding_house').modal('show');
setTimeout(function(){
$('.select2').select2({dropdownParent:$('#modal_boarding_house'),width:'100%'});
},100);
}

$(document).on('submit','#houseForm',function(e){
e.preventDefault();
showAjaxModal_alert('Saving...','loading');
const formData=new FormData(this);
const houseId=$('#house_id').val();
const url=houseId?'<?php echo site_url("admin/manageBoardingHouse/update/");?>'+houseId:'<?php echo site_url("admin/manageBoardingHouse/create");?>';
$.ajax({url:url,type:'POST',data:formData,processData:false,contentType:false,success:function(res){
$('#modal_boarding_house').modal('hide');
showAjaxModal_alert('Boarding house saved successfully!','success');
setTimeout(function(){$('#modal_alert').modal('hide');location.reload();},1500);
},error:function(){
showAjaxModal_alert('Failed to save boarding house. Please try again.','error');
}});
});

$('#modal_boarding_house').on('hidden.bs.modal',function(){
$('.select2').select2('destroy');
});

function deleteHouse(id){
showConfirmModal('Delete Boarding House','Are you sure you want to delete this boarding house?',function(){
showAjaxModal_alert('Deleting...','loading');
$.post('<?php echo site_url("admin/manageBoardingHouse/delete/");?>'+id,function(res){
showAjaxModal_alert('Boarding house deleted successfully!','success');
setTimeout(function(){$('#modal_alert').modal('hide');location.reload();},1500);
}).fail(function(){
showAjaxModal_alert('Failed to delete boarding house.','error');
});
},'Delete','danger');
}
</script>
