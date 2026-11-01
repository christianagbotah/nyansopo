<!DOCTYPE html>
<html>
<head>
<script src="<?php echo base_url(); ?>assets/sheetjs-master/xlsx.full.min.js"></script>
</head>
<body>
<script>
var data = <?php echo json_encode($data); ?>;
var ws_data = [['#', 'Student Name', 'Student Code', 'Profile', 'Category', 'Original Amount (GHS)', 'Discount', 'Discount Amount (GHS)', 'Final Amount (GHS)']];

data.forEach(function(item, index) {
	var discountDisplay = item.discount_method === 'percentage' 
		? parseFloat(item.discount_percentage).toFixed(1) + '%' 
		: 'GHS ' + parseFloat(item.discount_value_display).toFixed(2);
	
	ws_data.push([
		index + 1,
		item.student_name,
		item.student_code,
		item.profile_name || 'N/A',
		item.discount_category.charAt(0).toUpperCase() + item.discount_category.slice(1),
		parseFloat(item.original_amount),
		discountDisplay,
		parseFloat(item.discount_amount),
		parseFloat(item.final_amount)
	]);
});

var wb = XLSX.utils.book_new();
var ws = XLSX.utils.aoa_to_sheet(ws_data);

ws['!cols'] = [
	{wch: 5}, {wch: 25}, {wch: 15}, {wch: 20}, {wch: 15}, {wch: 20}, {wch: 15}, {wch: 22}, {wch: 20}
];

XLSX.utils.book_append_sheet(wb, ws, 'Discount Report');
XLSX.writeFile(wb, 'Discount_Amount_Report_<?php echo date('Y-m-d'); ?>.xlsx');
window.close();
</script>
</body>
</html>
