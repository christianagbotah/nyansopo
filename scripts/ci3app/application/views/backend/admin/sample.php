<?php
$currency = $this->db->get_where('settings', array('type' => 'currency'))->row()->description;
		$fmt = new NumberFormatter('en_US', NumberFormatter::CURRENCY);
		$invoice_code_f = $this->db->get_where('settings', array('type' => 'invoice_number_format'))->row()->description;

		if ($param1 == 'create') {

			//generate sequential invoice number;
			$this->db->select('invoice_code');
			$this->db->order_by('invoice_code', 'desc');
			$this->db->limit(1);
			$inv_query = $this->db->get('invoice');
			$inv_id = $inv_query->row()->invoice_code;

			if ($inv_query->num_rows() > 0) {
				$data['invoice_code'] = $inv_id + 1;

				if (substr($inv_id, 0, 1) == 0) {
					$old_len = strlen($inv_id);
					$new_len = strlen($data['invoice_code']);
					$act_len = ($old_len - $new_len);
					$data['invoice_code'] = substr($inv_id, 0, $act_len) . $data['invoice_code'];

				} else {
					$data['invoice_code'] = $data['invoice_code'];
				}
			} else {
				$data['invoice_code'] = $invoice_code_f;
			}

			

			//get the serialized values from the ajax request and process them
			//if $param2 is empty, use the default id prefix...
			if ($param2 == '' || $param2 == null) {
				$ids_array = array('16484_1565043896');
			} else {
				$ids_array = explode('-', $param2);
			}
			for ($j = 0; $j < count($ids_array); $j++) {

				$data['invoice_code'] = $data['invoice_code'];
				$data['student_id'] = $_REQUEST['student_id'];
				$data['class_id'] = $_REQUEST['class_id'];

				$class_name = $this->db->get_where('class', array('class_id' => $data['class_id']))->row()->name;

				if ($class_name == 'JHSS') {
					$data['sem'] = $_REQUEST['sem'];

				} else {
					$data['term'] = $_REQUEST['term'];
				}

				$data['title'] = strtoupper($_REQUEST[$ids_array[$j] . '_title']);
				$data['amount'] = $_REQUEST[$ids_array[$j] . '_amount'];
				$data['amount_paid'] = 0;
				$data['due'] = $data['amount'] - $data['amount_paid'];
				$data['status'] = 'unpaid';
				$data['creation_timestamp'] = strtotime($_REQUEST['date']);
				$data['year'] = $_REQUEST['year'];

				if ($_REQUEST[$ids_array[$j] . '_description'] != null) {
					$data['description'] = $_REQUEST[$ids_array[$j] . '_description'];
				}

				//Do insertion
				$this->db->insert('invoice', $data);
			}
		}
			