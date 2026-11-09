function generate_bulk_student_excel() {
	require_once APPPATH . 'third_party/PhpSpreadsheet/vendor/autoload.php';
	
	use PhpOffice\PhpSpreadsheet\Spreadsheet;
	use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
	use PhpOffice\PhpSpreadsheet\Style\Fill;
	use PhpOffice\PhpSpreadsheet\Style\Alignment;
	use PhpOffice\PhpSpreadsheet\Style\Border;
	
	$spreadsheet = new Spreadsheet();
	$sheet = $spreadsheet->getActiveSheet();
	
	// Define headers
	$headers = [
		'First Name', 'Middle Name', 'Last Name', 'Student ID', 'Gender', 'Date of Birth', 
		'Blood Group', 'Nationality', 'Ghana Card ID', 'Student Phone', 'Former School', 
		'Admission Date', 'Allergies', 'Medical Conditions', 'Emergency Contact', 
		'Special Diet', 'Religion', 'Address', 'Guardian Name', 'Guardian Phone', 
		'Guardian Email', 'Password'
	];
	
	// Set headers
	$col = 'A';
	foreach ($headers as $header) {
		$sheet->setCellValue($col . '1', $header);
		$sheet->getColumnDimension($col)->setWidth(18);
		$col++;
	}
	
	// Style header row
	$headerStyle = [
		'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '4472C4']],
		'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 11],
		'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
		'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '000000']]]
	];
	
	$sheet->getStyle('A1:V1')->applyFromArray($headerStyle);
	$sheet->getRowDimension(1)->setRowHeight(25);
	
	// Output file
	$filename = 'Bulk_Student_Admission_Template_' . date('Y-m-d') . '.xlsx';
	$filepath = 'uploads/' . $filename;
	
	$writer = new Xlsx($spreadsheet);
	$writer->save($filepath);
	
	echo json_encode(['file_path' => base_url() . $filepath, 'file_name' => $filename]);
}
