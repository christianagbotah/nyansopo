	function get_bulk_invoices() {
		$term = $this->input->post('term');
		$year = $this->input->post('year');
		$filter = $this->input->post('filter');
		$class_id = $this->input->post('class_id');

		if(!$term || !$year) {
			echo json_encode([
				'status' => 'error',
				'message' => 'Term and year are required'
			]);
			return;
		}

		$this->db->select('i.*, s.name as student_name, s.student_code, c.name as class_name, c.name_numeric');
		$this->db->from('invoice i');
		$this->db->join('student s', 's.student_id = i.student_id');
		$this->db->join('enroll e', 'e.student_id = s.student_id AND e.year = i.year AND e.term = i.term', 'left');
		$this->db->join('class c', 'c.class_id = e.class_id', 'left');
		$this->db->where('i.year', $year);
		$this->db->where('i.term', $term);

		if($filter === 'class' && $class_id) {
			$this->db->where('e.class_id', $class_id);
		}

		$this->db->group_by('i.invoice_code, i.student_id');
		$this->db->order_by('i.invoice_code', 'DESC');

		$invoices = $this->db->get()->result_array();

		$data = [];
		$unique_students = [];
		$unique_invoice_codes = [];
		$stats = [
			'invoice_count' => 0,
			'total_amount' => 0,
			'total_paid' => 0,
			'total_due' => 0,
			'total_receivables' => 0
		];

		foreach($invoices as $invoice) {
			$invoice_code = $invoice['invoice_code'];
			$unique_key = $invoice_code . '_' . $invoice['student_id'];
			
			if(!isset($data[$unique_key])) {
				$this->db->select_sum('amount');
				$this->db->select_sum('amount_paid');
				$this->db->select_sum('due');
				$this->db->where('invoice_code', $invoice_code);
				$this->db->where('student_id', $invoice['student_id']);
				$totals = $this->db->get('invoice')->row();

				$data[$unique_key] = [
					'invoice_id' => $invoice['invoice_id'],
					'invoice_code' => $invoice_code,
					'student_id' => $invoice['student_id'],
					'student_name' => $invoice['student_name'],
					'student_code' => $invoice['student_code'],
					'class_name' => $invoice['class_name'] . ' ' . $invoice['name_numeric'],
					'section_name' => '',
					'total_amount' => $totals->amount,
					'amount_paid' => $totals->amount_paid,
					'due' => $totals->due,
					'status' => $totals->due > 0 ? 'unpaid' : 'paid',
					'creation_timestamp' => $invoice['creation_timestamp']
				];

				if(!in_array($invoice_code, $unique_invoice_codes)) {
					$unique_invoice_codes[] = $invoice_code;
				}
				
				$stats['total_amount'] += $totals->amount;
				$stats['total_paid'] += $totals->amount_paid;
				$stats['total_due'] += $totals->due;
				$stats['total_receivables'] += $totals->due;
				
				if(!in_array($invoice['student_id'], $unique_students)) {
					$unique_students[] = $invoice['student_id'];
				}
			}
		}

		$stats['invoice_count'] = count($unique_invoice_codes);
		$stats['unique_students'] = count($unique_students);
		
		echo json_encode([
			'status' => 'success',
			'message' => count($data) . ' invoices loaded',
			'data' => array_values($data),
			'stats' => $stats
		]);
	}
