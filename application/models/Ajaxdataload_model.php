<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Ajaxdataload_model extends CI_Model {
	function __construct() {
		parent::__construct();
	}

	/*----------------------------- BOOKS -------------------------------*/

	function all_books_count() {
		$query = $this->db->get('book');
		return $query->num_rows();
	}

	function all_books($limit, $start, $col, $dir) {
		$query = $this
			->db
			->limit($limit, $start)
			->order_by($col, $dir)
			->get('book');

		if ($query->num_rows() > 0) {
			return $query->result();
		} else {
			return null;
		}

	}

	function book_search($limit, $start, $search, $col, $dir) {
		$query = $this
			->db
			->like('name', $search)
			->or_like('author', $search)
			->or_like('book_id', $search)
			->or_like('price', $search)
			->limit($limit, $start)
			->order_by($col, $dir)
			->get('book');

		if ($query->num_rows() > 0) {
			return $query->result();
		} else {
			return null;
		}

	}

	function book_search_count($search) {
		$query = $this
			->db
			->like('name', $search)
			->or_like('book_id', $search)
			->or_like('author', $search)
			->or_like('price', $search)
			->get('book');

		return $query->num_rows();
	}

	/*----------------------------- BOOKS -------------------------------*/

	/*----------------------------- ADMIN -------------------------------*/
	function all_admin_count() {
		$query = $this->db->get('admin');
		return $query->num_rows();
	}

	function all_admin($limit, $start, $col, $dir) {
		$query = $this
			->db
			->limit($limit, $start)
			->order_by($col, $dir)
			->get('admin');

		if ($query->num_rows() > 0) {
			return $query->result();
		} else {
			return null;
		}

	}

	function admin_search($limit, $start, $search, $col, $dir) {
		$query = $this
			->db
			->like('amin_id', $search)
			->or_like('name', $search)
			->or_like('email', $search)
			->limit($limit, $start)
			->order_by($col, $dir)
			->get('admin');

		if ($query->num_rows() > 0) {
			return $query->result();
		} else {
			return null;
		}

	}

	function admin_search_count($search) {
		$query = $this
			->db
			->like('admin_id', $search)
			->or_like('name', $search)
			->or_like('email', $search)
			->get('admin');

		return $query->num_rows();
	}

	/*----------------------------- TEACHERS -------------------------------*/

	function all_teachers_count() {
		$query = $this->db->get('teacher');
		return $query->num_rows();
	}

	function all_teachers($limit, $start, $col, $dir) {
		$query = $this
			->db
			->limit($limit, $start)
			->order_by($col, $dir)
			->get('teacher');

		if ($query->num_rows() > 0) {
			return $query->result();
		} else {
			return null;
		}

	}

	function teacher_search($limit, $start, $search, $col, $dir) {
		$query = $this
			->db
			->like('teacher_id', $search)
			->or_like('name', $search)
			->or_like('email', $search)
			->or_like('phone', $search)
			->or_like('teacher_code', $search)
			->limit($limit, $start)
			->order_by($col, $dir)
			->get('teacher');

		if ($query->num_rows() > 0) {
			return $query->result();
		} else {
			return null;
		}

	}

	function teacher_search_count($search) {
		$query = $this
			->db
			->like('teacher_id', $search)
			->or_like('name', $search)
			->or_like('email', $search)
			->or_like('phone', $search)
			->or_like('teacher_code', $search)
			->get('teacher');

		return $query->num_rows();
	}

	/*----------------------------- TEACHERS -------------------------------*/

	/*----------------------------- PARENTS -------------------------------*/

	function all_parents_count() {
		$query = $this->db->get('parent');
		return $query->num_rows();
	}

	function all_inactive_parents_count() {
		$running_year = get_settings('running_year');
		$running_term = get_settings('running_term');

		$this->db->select('student_id');
		$this->db->from('enroll');
		$this->db->where('mute', '0');
		$this->db->where('year', $running_year);
		$this->db->where('term', $running_term);
		$raw_info = $this->db->get();
		$st_ids = $raw_info->result_array();

		if ($raw_info->num_rows() != 0) {
			$st_ids_array = array();
			$i = 0;
			foreach ($st_ids as $row) {
				$st_ids_array[$i] = $row['student_id'];
				$i++;
			}

			$this->db->select('parent_id');
			$this->db->from('student');
			$this->db->where_in('student_id', $st_ids_array);
			$pt_ids = $this->db->get()->result_array();

			$pt_ids_array = array();
			$j = 0;
			foreach ($pt_ids as $row2) {
				$pt_ids_array[$j] = $row2['parent_id'];
				$j++;
			}
		} else {
			$pt_ids_array = array(0);
		}

		$this->db->where_not_in('parent_id', $pt_ids_array);
		$query = $this->db->get('parent');
		return $query->num_rows();
	}

	function all_active_parents_count() {
		$running_year = get_settings('running_year');
		$running_term = get_settings('running_term');

		$this->db->select('student_id');
		$this->db->from('enroll');
		$this->db->where('mute', '0');
		$this->db->where('year', $running_year);
		$this->db->where('term', $running_term);
		$raw_info = $this->db->get();
		$st_ids = $raw_info->result_array();

		if ($raw_info->num_rows() != 0) {
			$st_ids_array = array();
			$i = 0;
			foreach ($st_ids as $row) {
				$st_ids_array[$i] = $row['student_id'];
				$i++;
			}

			$this->db->select('parent_id');
			$this->db->from('student');
			$this->db->where('mute', '0');
			$this->db->where_in('student_id', $st_ids_array);
			$pt_ids = $this->db->get()->result_array();

			$pt_ids_array = array();
			$j = 0;
			foreach ($pt_ids as $row2) {
				$pt_ids_array[$j] = $row2['parent_id'];
				$j++;
			}
		} else {
			$pt_ids_array = array(0);
		}

		$this->db->where_in('parent_id', $pt_ids_array);
		$query = $this->db->get('parent');
		return $query->num_rows();
	}

	function all_active_parents($limit, $start, $col, $dir) {
		$running_year = get_settings('running_year');
		$running_term = get_settings('running_term');

		$this->db->select('student_id');
		$this->db->from('enroll');
		$this->db->where('mute', '0');
		$this->db->where('year', $running_year);
		$this->db->where('term', $running_term);
		$raw_info = $this->db->get();
		$st_ids = $raw_info->result_array();

		if ($raw_info->num_rows() != 0) {
			$st_ids_array = array();
			$i = 0;
			foreach ($st_ids as $row) {
				$st_ids_array[$i] = $row['student_id'];
				$i++;
			}

			$this->db->select('parent_id');
			$this->db->from('student');
			$this->db->where('mute', '0');
			$this->db->where_in('student_id', $st_ids_array);
			$pt_ids = $this->db->get()->result_array();

			$pt_ids_array = array();
			$j = 0;
			foreach ($pt_ids as $row2) {
				$pt_ids_array[$j] = $row2['parent_id'];
				$j++;
			}
		} else {
			$pt_ids_array = array(0);
		}

		$this->db->where_in('parent_id', $pt_ids_array);
		$query = $this
			->db
			->limit($limit, $start)
			->order_by($col, $dir)
			->get('parent');

		if ($query->num_rows() > 0) {
			return $query->result();
		} else {
			return null;
		}

	}

	function all_inactive_parents($limit, $start, $col, $dir) {
		$running_year = get_settings('running_year');
		$running_term = get_settings('running_term');

		$this->db->select('student_id');
		$this->db->from('enroll');
		$this->db->where('year', $running_year);
		$this->db->where('term', $running_term);
		$raw_info = $this->db->get();
		$st_ids = $raw_info->result_array();

		if ($raw_info->num_rows() != 0) {
			$st_ids_array = array();
			$i = 0;
			foreach ($st_ids as $row) {
				$st_ids_array[$i] = $row['student_id'];
				$i++;
			}

			$this->db->select('parent_id');
			$this->db->from('student');
			$this->db->where_in('student_id', $st_ids_array);
			$pt_ids = $this->db->get()->result_array();

			$pt_ids_array = array();
			$j = 0;
			foreach ($pt_ids as $row2) {
				$pt_ids_array[$j] = $row2['parent_id'];
				$j++;
			}
		} else {
			$pt_ids_array = array(0);
		}

		$this->db->where_not_in('parent_id', $pt_ids_array);
		$query = $this
			->db
			->limit($limit, $start)
			->order_by($col, $dir)
			->get('parent');

		if ($query->num_rows() > 0) {
			return $query->result();
		} else {
			return null;
		}

	}

	function active_parent_search($limit, $start, $search, $col, $dir) {
		$running_year = get_settings('running_year');
		$running_term = get_settings('running_term');

		$this->db->select('student_id');
		$this->db->from('enroll');
		$this->db->where('mute', '0');
		$this->db->where('year', $running_year);
		$this->db->where('term', $running_term);
		$raw_info = $this->db->get();
		$st_ids = $raw_info->result_array();

		if ($raw_info->num_rows() != 0) {
			$st_ids_array = array();
			$i = 0;
			foreach ($st_ids as $row) {
				$st_ids_array[$i] = $row['student_id'];
				$i++;
			}

			$this->db->select('parent_id');
			$this->db->from('student');
			$this->db->where('mute', '0');
			$this->db->where_in('student_id', $st_ids_array);
			$pt_ids = $this->db->get()->result_array();

			$pt_ids_array = array();
			$j = 0;
			foreach ($pt_ids as $row2) {
				$pt_ids_array[$j] = $row2['parent_id'];
				$j++;
			}
		} else {
			$pt_ids_array = array(0);
		}

		$this->db->where_in('parent_id', $pt_ids_array);
		$query = $this
			->db
			->like('parent_id', $search)
			->or_like('name', $search)
			->or_like('email', $search)
			->or_like('phone', $search)
			->or_like('profession', $search)
			->limit($limit, $start)
			->order_by($col, $dir)
			->get('parent');

		if ($query->num_rows() > 0) {
			return $query->result();
		} else {
			return null;
		}

	}

	function inactive_parent_search($limit, $start, $search, $col, $dir) {
		$running_year = get_settings('running_year');
		$running_term = get_settings('running_term');

		$this->db->select('student_id');
		$this->db->from('enroll');
		$this->db->where('year', $running_year);
		$this->db->where('term', $running_term);
		$raw_info = $this->db->get();
		$st_ids = $raw_info->result_array();

		if ($raw_info->num_rows() != 0) {
			$st_ids_array = array();
			$i = 0;
			foreach ($st_ids as $row) {
				$st_ids_array[$i] = $row['student_id'];
				$i++;
			}

			$this->db->select('parent_id');
			$this->db->from('student');
			$this->db->where_in('student_id', $st_ids_array);
			$pt_ids = $this->db->get()->result_array();

			$pt_ids_array = array();
			$j = 0;
			foreach ($pt_ids as $row2) {
				$pt_ids_array[$j] = $row2['parent_id'];
				$j++;
			}
		} else {
			$pt_ids_array = array(0);
		}

		$this->db->where_not_in('parent_id', $pt_ids_array);
		$query = $this
			->db
			->like('parent_id', $search)
			->or_like('name', $search)
			->or_like('email', $search)
			->or_like('phone', $search)
			->or_like('profession', $search)
			->limit($limit, $start)
			->order_by($col, $dir)
			->get('parent');

		if ($query->num_rows() > 0) {
			return $query->result();
		} else {
			return null;
		}

	}

	function active_parent_search_count($search) {
		$running_year = get_settings('running_year');
		$running_term = get_settings('running_term');

		$this->db->select('student_id');
		$this->db->from('enroll');
		$this->db->where('mute', '0');
		$this->db->where('year', $running_year);
		$this->db->where('term', $running_term);
		$raw_info = $this->db->get();
		$st_ids = $raw_info->result_array();

		if ($raw_info->num_rows() != 0) {
			$st_ids_array = array();
			$i = 0;
			foreach ($st_ids as $row) {
				$st_ids_array[$i] = $row['student_id'];
				$i++;
			}

			$this->db->select('parent_id');
			$this->db->from('student');
			$this->db->where('mute', '0');
			$this->db->where_in('student_id', $st_ids_array);
			$pt_ids = $this->db->get()->result_array();

			$pt_ids_array = array();
			$j = 0;
			foreach ($pt_ids as $row2) {
				$pt_ids_array[$j] = $row2['parent_id'];
				$j++;
			}
		} else {
			$pt_ids_array = array(0);
		}

		$this->db->where_in('parent_id', $pt_ids_array);
		$query = $this
			->db
			->like('parent_id', $search)
			->or_like('name', $search)
			->or_like('email', $search)
			->or_like('phone', $search)
			->or_like('profession', $search)
			->get('parent');

		return $query->num_rows();
	}

	function inactive_parent_search_count($search) {
		$running_year = get_settings('running_year');
		$running_term = get_settings('running_term');

		$this->db->select('student_id');
		$this->db->from('enroll');
		$this->db->where('year', $running_year);
		$this->db->where('term', $running_term);
		$raw_info = $this->db->get();
		$st_ids = $raw_info->result_array();

		if ($raw_info->num_rows() != 0) {
			$st_ids_array = array();
			$i = 0;
			foreach ($st_ids as $row) {
				$st_ids_array[$i] = $row['student_id'];
				$i++;
			}

			$this->db->select('parent_id');
			$this->db->from('student');
			$this->db->where_in('student_id', $st_ids_array);
			$pt_ids = $this->db->get()->result_array();

			$pt_ids_array = array();
			$j = 0;
			foreach ($pt_ids as $row2) {
				$pt_ids_array[$j] = $row2['parent_id'];
				$j++;
			}
		} else {
			$pt_ids_array = array(0);
		}

		$this->db->where_not_in('parent_id', $pt_ids_array);
		$query = $this
			->db
			->like('parent_id', $search)
			->or_like('name', $search)
			->or_like('email', $search)
			->or_like('phone', $search)
			->or_like('profession', $search)
			->get('parent');

		return $query->num_rows();
	}

	function all_parents($limit, $start, $col, $dir) {
		$query = $this
			->db
			->limit($limit, $start)
			->order_by($col, $dir)
			->get('parent');

		if ($query->num_rows() > 0) {
			return $query->result();
		} else {
			return null;
		}

	}

	function parent_search($limit, $start, $search, $col, $dir) {
		$query = $this
			->db
			->like('parent_id', $search)
			->or_like('name', $search)
			->or_like('email', $search)
			->or_like('phone', $search)
			->or_like('profession', $search)
			->limit($limit, $start)
			->order_by($col, $dir)
			->get('parent');

		if ($query->num_rows() > 0) {
			return $query->result();
		} else {
			return null;
		}

	}

	function parent_search_count($search) {
		$query = $this
			->db
			->like('parent_id', $search)
			->or_like('name', $search)
			->or_like('email', $search)
			->or_like('phone', $search)
			->or_like('profession', $search)
			->get('parent');

		return $query->num_rows();
	}

	/*----------------------------- PARENTS -------------------------------*/

	/*----------------------------- EXPENSES -------------------------------*/

	function all_expenses_count() {
		// Get current academic year and term
		$CI =& get_instance();
		$running_year = $CI->db->get_where('settings', array('type' => 'running_year'))->row()->description;
		$running_term = $CI->db->get_where('settings', array('type' => 'running_term'))->row()->description;
		
		$array = array('payment_type' => 'expense');
		$query = $this
			->db
			->where($array)
			->where('year', $running_year)
			->where('term', $running_term)
			->where('can_delete !=', 'trash')
			->get('payment');
		return $query->num_rows();
	}

	function all_expenses_total() {
		// Get current academic year and term
		$CI =& get_instance();
		$running_year = $CI->db->get_where('settings', array('type' => 'running_year'))->row()->description;
		$running_term = $CI->db->get_where('settings', array('type' => 'running_term'))->row()->description;
		
		$this->db->select_sum('amount');
		$this->db->where('payment_type', 'expense');
		$this->db->where('year', $running_year);
		$this->db->where('term', $running_term);
		$this->db->where('can_delete !=', 'trash');
		$query = $this->db->get('payment');
		return $query->row()->amount ?? 0;
	}

	function all_expenses_total_reload($start_date, $end_date, $payment_method = '') {
		$this->db->select_sum('amount');
		$this->db->where('payment_type', 'expense');
		$this->db->where('day_timestamp BETWEEN "'. strtotime($start_date) .'" AND "'. strtotime($end_date) . '"');
		$this->db->where('can_delete !=', 'trash');
		
		// Add payment method filter if provided
		if(!empty($payment_method)) {
			$this->db->where('payment_method', $payment_method);
		}
		
		$query = $this->db->get('payment');
		return $query->row()->amount ?? 0;
	}

	function all_expenses_reload($limit, $start, $col, $dir, $start_date, $end_date, $payment_method = '') {
		$array = array('payment_type' => 'expense');
		$this->db->where($array);
		$this->db->where('day_timestamp BETWEEN "'. strtotime($start_date) .'" AND "'. strtotime($end_date) . '"');
		$this->db->where('can_delete !=', 'trash');
		
		// Add payment method filter if provided
		if(!empty($payment_method)) {
			$this->db->where('payment_method', $payment_method);
		}
		
		$query = $this->db
			->limit($limit, $start)
			->order_by($col, $dir)
			->get('payment');

		if ($query->num_rows() > 0) {
			return $query->result();
		} else {
			return null;
		}

	}

	function all_expenses($limit, $start, $col, $dir) {
		// Get current academic year and term
		$CI =& get_instance();
		$running_year = $CI->db->get_where('settings', array('type' => 'running_year'))->row()->description;
		$running_term = $CI->db->get_where('settings', array('type' => 'running_term'))->row()->description;
		
		$array = array('payment_type' => 'expense');
		$query = $this
			->db
			->where($array)
			->where('year', $running_year)
			->where('term', $running_term)
			->where('can_delete !=', 'trash')
			->limit($limit, $start)
			->order_by($col, $dir)
			->get('payment');

		if ($query->num_rows() > 0) {
			return $query->result();
		} else {
			return null;
		}

	}

	function expense_search($limit, $start, $search, $col, $dir) {
		// Get current academic year and term
		$CI =& get_instance();
		$running_year = $CI->db->get_where('settings', array('type' => 'running_year'))->row()->description;
		$running_term = $CI->db->get_where('settings', array('type' => 'running_term'))->row()->description;
		
		$query = $this
			->db
			->like('payment_id', $search)
			->or_like('title', $search)
			->or_like('amount', $search)
			->where('year', $running_year)
			->where('term', $running_term)
			->where('can_delete !=', 'trash')
			->limit($limit, $start)
			->order_by($col, $dir)
			->get('payment');

		if ($query->num_rows() > 0) {
			return $query->result();
		} else {
			return null;
		}

	}

	function expense_search_count($search) {
		// Get current academic year and term
		$CI =& get_instance();
		$running_year = $CI->db->get_where('settings', array('type' => 'running_year'))->row()->description;
		$running_term = $CI->db->get_where('settings', array('type' => 'running_term'))->row()->description;
		
		$query = $this
			->db
			->like('payment_id', $search)
			->or_like('title', $search)
			->or_like('amount', $search)
			->where('year', $running_year)
			->where('term', $running_term)
			->where('can_delete !=', 'trash')
			->get('payment');

		return $query->num_rows();
	}

	/*----------------------------- EXPENSES -------------------------------*/

	/*----------------------------- INVOICES -------------------------------*/

	function all_invoices_count() {
		//$array = array('year' => get_settings('running_year'));
		$query = $this
			->db
			->select('invoice_code')
			->distinct()
			//->where($array)
			->where('can_delete !=', 'trash')
			->get('invoice');
		return $query->num_rows();
	}

	function all_invoices($limit, $start, $col, $dir) {
		//$array = array('year' => get_settings('running_year'));
		$query = $this
			->db
			->select('invoice_code')
			->distinct()
			//->where($array)
			->limit($limit, $start)
			->where('can_delete !=', 'trash')
			->order_by($col, $dir)
			->get('invoice');

		if ($query->num_rows() > 0) {
			return $query->result();
		} else {
			return null;
		}

	}

	function all_invoices_page() {
		$array = array('year' => get_settings('running_year'), 'term' => get_settings('running_term'));
		$query = $this
			->db
			->select('invoice_code')
			->distinct()
			->where($array)
			->where('can_delete !=', 'trash')
			//->limit(1)
			->order_by('invoice_code', 'desc')
			->get('invoice');

		if ($query->num_rows() > 0) {
			return $query->result();
		} else {
			return null;
		}

	}

	function invoice_search($limit, $start, $search, $col, $dir) {
		/**$this->db->select('invoice_code');
			        $this->db->distinct();
			        $num_invoice_rows = $this->db->get('invoice')->num_rows();
		*///i have reset the limit value to the number of rows returned for an invoice code per search.

		$query = $this
			->db
			/**->like('invoice_id', $search)
				                ->or_like('title', $search)
				                ->or_like('amount', $search)
				                ->or_like('year', $search)
			*/
			->like('invoice_code', $search)
			//->or_like('status', $search)
			->or_like('name', $search)
			->join('student', 'student.student_id = invoice.student_id')
			->select('invoice_code') //let's select just unique invoice code at a time
			->distinct() //distinct invoice codes selection
			->limit($limit, $start)
			->where('can_delete !=', 'trash')
			->order_by($col, $dir)
			->get('invoice');

		if ($query->num_rows() > 0) {
			return $query->result();
		} else {
			return null;
		}

	}

	function invoice_search_count($search) {
		$query = $this
			->db
			->like('invoice_id', $search)
			->or_like('title', $search)
			->or_like('amount', $search)
			->or_like('year', $search)
			->or_like('term', $search)
			->or_like('invoice_code', $search)
			->or_like('status', $search)
			->or_like('name', $search)
			->where('can_delete !=', 'trash')
			->join('student', 'student.student_id = invoice.student_id')
			->get('invoice');

		return $query->num_rows();
	}

	//CUSTOMIZED
	function load_invoice($param1 = '', $param2 = '', $param3 = '', $param4 = '') {

		if ($param1 != '') {
			//search the enroll table for all students currently in this class
			$class_name = $this->crud_model->get_class_name($param1);

			if ($class_name == 'JHSS') {

				$year = $param2;
				$sem = $param4;
				$class_students = $this->db->get_where('enroll', array('class_id' => $param1, 'mute' => '0', 'year' => $year, 'sem' => $sem));
			} else {
				$year = $param2;
				$term = $param3;

				if($year != '0') {
                    $this->db->where('year', $year);
                }
                
                if($term != '0') {
                    $this->db->where('term', $term);
                }
                
				$class_students = $this->db->get_where('enroll', array('class_id' => $param1, 'mute' => '0'));

			}

			if ($class_students->num_rows() > 0) {
				$class_students_array = $class_students->result_array();
				return $class_students_array;

			} else {
				return null;
			}
		} else {
			return null;
		}

	}

	function load_invoice_per_student($param1 = '') {
		$array = array('student_id' => $param1);

		$query = $this
			->db
			->select('invoice_code')
			->distinct()
			->where($array)
			->where('can_delete !=', 'trash')
			// ->limit($limit)
			->order_by('invoice_code', 'desc')
			->get('invoice');

		if ($query->num_rows() > 0) {
			$invoices = $query->result();
			return $invoices;
		} else {
			return null;
		}

	}
	/*----------------------------- INVOICES -------------------------------*/

	/*----------------------------- PAYMENTS -------------------------------*/

	function all_payments_count() {
		$array = array('payment_type' => 'income', 'year' => get_settings('running_year'));
		$query = $this
			->db
			->where($array)
			->where('can_delete !=', 'trash')
			->get('payment');
		return $query->num_rows();
	}

	function all_payments($limit, $start, $col, $dir) {
		$array = array('payment_type' => 'income', 'year' => get_settings('running_year'));
		$query = $this
			->db
			->where($array)
			->limit($limit, $start)
			->where('can_delete !=', 'trash')
			->order_by($col, $dir)
			->get('payment');

		if ($query->num_rows() > 0) {
			return $query->result();
		} else {
			return null;
		}

	}

	function payment_search($limit, $start, $search, $col, $dir) {
		$query = $this
			->db
			->like('payment_id', $search)
			->or_like('title', $search)
			->or_like('amount', $search)
			->or_like('year', $search)
			->or_like('term', $search)
			->or_like('invoice_code', $search)
			->or_like('receipt_code', $search)
			->limit($limit, $start)
			->where('can_delete !=', 'trash')
			->order_by($col, $dir)
			->get('payment');

		if ($query->num_rows() > 0) {
			return $query->result();
		} else {
			return null;
		}

	}

	function payment_search_count($search) {
		$query = $this
			->db
			->like('payment_id', $search)
			->or_like('title', $search)
			->or_like('amount', $search)
			->or_like('year', $search)
			->or_like('term', $search)
			->or_like('invoice_code', $search)
			->or_like('receipt_code', $search)
			->where('can_delete !=', 'trash')
			->get('payment');

		return $query->num_rows();
	}

	/*----------------------------- PAYMENTS -------------------------------*/

	/*----------------------------- LOAD RECEIPT BY DATE -------------------------------*/

	function get_receipt_bydate($search = '', $date = '') {
		$currency = $this->db->get_where('settings', array('type' => 'currency'))->row()->description;
		$fmt = new NumberFormatter('en_US', NumberFormatter::CURRENCY);

		$date_timestamp = ($search == 'search') ? strtotime($date) : strtotime(date('d-m-Y'));

		$this->db->select('COUNT(DISTINCT receipt_code) as total_number, SUM(amount) as total_amount');
		$this->db->where('day_timestamp', $date_timestamp);
		$this->db->where('invoice_id IS NOT NULL');
		$this->db->where('can_delete !=', 'trash');
		$result = $this->db->get('payment')->row();

		$nestedData['date_chosen'] = date('l M d, Y', $date_timestamp);
		$nestedData['total_number'] = $result->total_number;
		$nestedData['total_amount'] = ($result->total_number < 1) 
			? 'No Data' 
			: numfmt_format_currency($fmt, $result->total_amount, $currency);

		$data[] = $nestedData;
		echo json_encode($data);
	}

	function get_feeding_fee_receipt_bydate($search = '', $date = '') {
		$currency = $this->db->get_where('settings', array('type' => 'currency'))->row()->description;
		$fmt = new NumberFormatter('en_US', NumberFormatter::CURRENCY);

		if ($search == 'search') {

			$date_timestamp = strtotime($date);

		} else {

			$date_timestamp = strtotime(date('d-m-Y'));

		}

		$this->db->select_sum('amount');
		$this->db->where('can_delete !=', 'trash');
		$receipt_issued = $this->db->get_where('payment', array('day_timestamp' => $date_timestamp, 'invoice_id' => NULL, 'title' => 'Feeding Fee'))->result_array();

		$total_receipt_issued_amount = 0;

		$this->db->select('receipt_code');
		$this->db->distinct();
		$this->db->where('can_delete !=', 'trash');
		$total_receipt_issued = $this->db->get_where('payment', array('day_timestamp' => $date_timestamp, 'invoice_id' => NULL, 'title' => 'Feeding Fee'))->num_rows();

		foreach ($receipt_issued as $re) {
			$total_receipt_issued_amount = $total_receipt_issued_amount + $re['amount'];
		}

		if ($total_receipt_issued < 1) {
			$nestedData['date_chosen'] = date('l M d, Y', $date_timestamp);
			$nestedData['total_number'] = $total_receipt_issued;
			$nestedData['total_amount'] = 'No Data';
		} else {
			$nestedData['date_chosen'] = date('l M d, Y', $date_timestamp);
			$nestedData['total_number'] = $total_receipt_issued;
			$nestedData['total_amount'] = numfmt_format_currency($fmt, $total_receipt_issued_amount, $currency);
		}

		$data[] = $nestedData;

		echo json_encode($data);

	}

	function get_classes_fee_receipt_bydate($search = '', $date = '') {
		$currency = $this->db->get_where('settings', array('type' => 'currency'))->row()->description;
		$fmt = new NumberFormatter('en_US', NumberFormatter::CURRENCY);

		if ($search == 'search') {

			$date_timestamp = strtotime($date);

		} else {

			$date_timestamp = strtotime(date('d-m-Y'));

		}

		$this->db->select_sum('amount');
		$this->db->where('can_delete !=', 'trash');
		$receipt_issued = $this->db->get_where('payment', array('day_timestamp' => $date_timestamp, 'invoice_id' => NULL, 'title' => 'Classes Fee'))->result_array();

		$total_receipt_issued_amount = 0;

		$this->db->select('receipt_code');
		$this->db->distinct();
		$this->db->where('can_delete !=', 'trash');
		$total_receipt_issued = $this->db->get_where('payment', array('day_timestamp' => $date_timestamp, 'invoice_id' => NULL, 'title' => 'Classes Fee'))->num_rows();

		foreach ($receipt_issued as $re) {
			$total_receipt_issued_amount = $total_receipt_issued_amount + $re['amount'];
		}

		if ($total_receipt_issued < 1) {
			$nestedData['date_chosen'] = date('l M d, Y', $date_timestamp);
			$nestedData['total_number'] = $total_receipt_issued;
			$nestedData['total_amount'] = 'No Data';
		} else {
			$nestedData['date_chosen'] = date('l M d, Y', $date_timestamp);
			$nestedData['total_number'] = $total_receipt_issued;
			$nestedData['total_amount'] = numfmt_format_currency($fmt, $total_receipt_issued_amount, $currency);
		}

		$data[] = $nestedData;

		echo json_encode($data);

	}

	function get_transport_fare_receipt_bydate($search = '', $date = '') {
		$currency = $this->db->get_where('settings', array('type' => 'currency'))->row()->description;
		$fmt = new NumberFormatter('en_US', NumberFormatter::CURRENCY);

		if ($search == 'search') {

			$date_timestamp = strtotime($date);

		} else {

			$date_timestamp = strtotime(date('d-m-Y'));

		}

		$this->db->select_sum('amount');
		$this->db->where('can_delete !=', 'trash');
		$receipt_issued = $this->db->get_where('payment', array('day_timestamp' => $date_timestamp, 'invoice_id' => NULL, 'title' => 'Transport Fare'))->result_array();

		$total_receipt_issued_amount = 0;

		$this->db->select('receipt_code');
		$this->db->distinct();
		$this->db->where('can_delete !=', 'trash');
		$total_receipt_issued = $this->db->get_where('payment', array('day_timestamp' => $date_timestamp, 'invoice_id' => NULL, 'title' => 'Transport Fare'))->num_rows();

		foreach ($receipt_issued as $re) {
			$total_receipt_issued_amount = $total_receipt_issued_amount + $re['amount'];
		}

		if ($total_receipt_issued < 1) {
			$nestedData['date_chosen'] = date('l M d, Y', $date_timestamp);
			$nestedData['total_number'] = $total_receipt_issued;
			$nestedData['total_amount'] = 'No Data';
		} else {
			$nestedData['date_chosen'] = date('l M d, Y', $date_timestamp);
			$nestedData['total_number'] = $total_receipt_issued;
			$nestedData['total_amount'] = numfmt_format_currency($fmt, $total_receipt_issued_amount, $currency);
		}

		$data[] = $nestedData;

		echo json_encode($data);

	}

	function issuer_update($date = '', $issuer_id = '') {
		$currency = $this->db->get_where('settings', array('type' => 'currency'))->row()->description;
		$fmt = new NumberFormatter('en_US', NumberFormatter::CURRENCY);

		$date_timestamp = strtotime($date);

		$this->db->select_sum('amount');
		$this->db->where('can_delete !=', 'trash');
		$receipt_issued = $this->db->get_where('payment', array('day_timestamp' => $date_timestamp, 'issuer_id' => $issuer_id, 'invoice_id !=' => NULL))->result_array();

		$total_receipt_issued_amount = 0;

		$this->db->select('receipt_code');
		$this->db->distinct();
		$this->db->where('can_delete !=', 'trash');
		$total_receipt_issued = $this->db->get_where('payment', array('day_timestamp' => $date_timestamp, 'issuer_id' => $issuer_id, 'invoice_id !=' => NULL))->num_rows();

		foreach ($receipt_issued as $re) {
			$total_receipt_issued_amount = $total_receipt_issued_amount + $re['amount'];
		}

		if ($total_receipt_issued < 1) {
			$nestedData['date_chosen'] = date('l M d, Y', $date_timestamp);
			$nestedData['total_number'] = $total_receipt_issued;
			$nestedData['total_amount'] = 'No Data';
		} else {
			$nestedData['date_chosen'] = date('l M d, Y', $date_timestamp);
			$nestedData['total_number'] = $total_receipt_issued;
			$nestedData['total_amount'] = numfmt_format_currency($fmt, $total_receipt_issued_amount, $currency);
		}

		$data[] = $nestedData;

		echo json_encode($data);

	}

	function feeding_fee_issuer_update($date = '', $issuer_id = '') {
		$currency = $this->db->get_where('settings', array('type' => 'currency'))->row()->description;
		$fmt = new NumberFormatter('en_US', NumberFormatter::CURRENCY);

		$date_timestamp = strtotime($date);

		$this->db->select_sum('amount');
		$this->db->where('can_delete !=', 'trash');
		$receipt_issued = $this->db->get_where('payment', array('day_timestamp' => $date_timestamp, 'issuer_id' => $issuer_id, 'invoice_id' => NULL, 'title' => 'Feeding Fee'))->result_array();

		$total_receipt_issued_amount = 0;

		$this->db->select('receipt_code');
		$this->db->distinct();
		$this->db->where('can_delete !=', 'trash');
		$total_receipt_issued = $this->db->get_where('payment', array('day_timestamp' => $date_timestamp, 'issuer_id' => $issuer_id, 'invoice_id' => NULL, 'title' => 'Feeding Fee'))->num_rows();

		foreach ($receipt_issued as $re) {
			$total_receipt_issued_amount = $total_receipt_issued_amount + $re['amount'];
		}

		if ($total_receipt_issued < 1) {
			$nestedData['date_chosen'] = date('l M d, Y', $date_timestamp);
			$nestedData['total_number'] = $total_receipt_issued;
			$nestedData['total_amount'] = 'No Data';
		} else {
			$nestedData['date_chosen'] = date('l M d, Y', $date_timestamp);
			$nestedData['total_number'] = $total_receipt_issued;
			$nestedData['total_amount'] = numfmt_format_currency($fmt, $total_receipt_issued_amount, $currency);
		}

		$data[] = $nestedData;

		echo json_encode($data);

	}

	function classes_fee_issuer_update($date = '', $issuer_id = '') {
		$currency = $this->db->get_where('settings', array('type' => 'currency'))->row()->description;
		$fmt = new NumberFormatter('en_US', NumberFormatter::CURRENCY);

		$date_timestamp = strtotime($date);

		$this->db->select_sum('amount');
		$this->db->where('can_delete !=', 'trash');
		$receipt_issued = $this->db->get_where('payment', array('day_timestamp' => $date_timestamp, 'issuer_id' => $issuer_id, 'invoice_id' => NULL, 'title' => 'Classes Fee'))->result_array();

		$total_receipt_issued_amount = 0;

		$this->db->select('receipt_code');
		$this->db->distinct();
		$this->db->where('can_delete !=', 'trash');
		$total_receipt_issued = $this->db->get_where('payment', array('day_timestamp' => $date_timestamp, 'issuer_id' => $issuer_id, 'invoice_id' => NULL, 'title' => 'Classes Fee'))->num_rows();

		foreach ($receipt_issued as $re) {
			$total_receipt_issued_amount = $total_receipt_issued_amount + $re['amount'];
		}

		if ($total_receipt_issued < 1) {
			$nestedData['date_chosen'] = date('l M d, Y', $date_timestamp);
			$nestedData['total_number'] = $total_receipt_issued;
			$nestedData['total_amount'] = 'No Data';
		} else {
			$nestedData['date_chosen'] = date('l M d, Y', $date_timestamp);
			$nestedData['total_number'] = $total_receipt_issued;
			$nestedData['total_amount'] = numfmt_format_currency($fmt, $total_receipt_issued_amount, $currency);
		}

		$data[] = $nestedData;

		echo json_encode($data);

	}

	function transport_fare_issuer_update($date = '', $issuer_id = '') {
		$currency = $this->db->get_where('settings', array('type' => 'currency'))->row()->description;
		$fmt = new NumberFormatter('en_US', NumberFormatter::CURRENCY);

		$date_timestamp = strtotime($date);

		$this->db->select_sum('amount');
		$this->db->where('can_delete !=', 'trash');
		$receipt_issued = $this->db->get_where('payment', array('day_timestamp' => $date_timestamp, 'issuer_id' => $issuer_id, 'invoice_id' => NULL, 'title' => 'Transport Fare'))->result_array();

		$total_receipt_issued_amount = 0;

		$this->db->select('receipt_code');
		$this->db->distinct();
		$this->db->where('can_delete !=', 'trash');
		$total_receipt_issued = $this->db->get_where('payment', array('day_timestamp' => $date_timestamp, 'issuer_id' => $issuer_id, 'invoice_id' => NULL, 'title' => 'Transport Fare'))->num_rows();

		foreach ($receipt_issued as $re) {
			$total_receipt_issued_amount = $total_receipt_issued_amount + $re['amount'];
		}

		if ($total_receipt_issued < 1) {
			$nestedData['date_chosen'] = date('l M d, Y', $date_timestamp);
			$nestedData['total_number'] = $total_receipt_issued;
			$nestedData['total_amount'] = 'No Data';
		} else {
			$nestedData['date_chosen'] = date('l M d, Y', $date_timestamp);
			$nestedData['total_number'] = $total_receipt_issued;
			$nestedData['total_amount'] = numfmt_format_currency($fmt, $total_receipt_issued_amount, $currency);
		}

		$data[] = $nestedData;

		echo json_encode($data);

	}

	function class_update($date = '', $class_id = '') {
		$currency = $this->db->get_where('settings', array('type' => 'currency'))->row()->description;
		$fmt = new NumberFormatter('en_US', NumberFormatter::CURRENCY);

		$date_timestamp = strtotime($date);

		$this->db->select_sum('amount');
		$this->db->where('can_delete !=', 'trash');
		$receipt_issued = $this->db->get_where('payment', array('day_timestamp' => $date_timestamp, 'class_id' => $class_id, 'invoice_id !=' => NULL))->result_array();

		$total_receipt_issued_amount = 0;

		$this->db->select('receipt_code');
		$this->db->distinct();
		$this->db->where('can_delete !=', 'trash');
		$total_receipt_issued = $this->db->get_where('payment', array('day_timestamp' => $date_timestamp, 'class_id' => $class_id, 'invoice_id !=' => NULL))->num_rows();

		foreach ($receipt_issued as $re) {
			$total_receipt_issued_amount = $total_receipt_issued_amount + $re['amount'];
		}

		if ($total_receipt_issued < 1) {
			$nestedData['date_chosen'] = date('l M d, Y', $date_timestamp);
			$nestedData['total_number'] = $total_receipt_issued;
			$nestedData['total_amount'] = 'No Data';
		} else {
			$nestedData['date_chosen'] = date('l M d, Y', $date_timestamp);
			$nestedData['total_number'] = $total_receipt_issued;
			$nestedData['total_amount'] = numfmt_format_currency($fmt, $total_receipt_issued_amount, $currency);
		}

		$data[] = $nestedData;

		echo json_encode($data);

	}

	function feeding_fee_class_update($date = '', $class_id = '') {
		$currency = $this->db->get_where('settings', array('type' => 'currency'))->row()->description;
		$fmt = new NumberFormatter('en_US', NumberFormatter::CURRENCY);

		$date_timestamp = strtotime($date);

		$this->db->select_sum('amount');
		$this->db->where('can_delete !=', 'trash');
		$receipt_issued = $this->db->get_where('payment', array('day_timestamp' => $date_timestamp, 'class_id' => $class_id, 'invoice_id' => NULL, 'title' => 'Feeding Fee'))->result_array();

		$total_receipt_issued_amount = 0;

		$this->db->select('receipt_code');
		$this->db->distinct();
		$this->db->where('can_delete !=', 'trash');
		$total_receipt_issued = $this->db->get_where('payment', array('day_timestamp' => $date_timestamp, 'class_id' => $class_id, 'invoice_id' => NULL, 'title' => 'Feeding Fee'))->num_rows();

		foreach ($receipt_issued as $re) {
			$total_receipt_issued_amount = $total_receipt_issued_amount + $re['amount'];
		}

		if ($total_receipt_issued < 1) {
			$nestedData['date_chosen'] = date('l M d, Y', $date_timestamp);
			$nestedData['total_number'] = $total_receipt_issued;
			$nestedData['total_amount'] = 'No Data';
		} else {
			$nestedData['date_chosen'] = date('l M d, Y', $date_timestamp);
			$nestedData['total_number'] = $total_receipt_issued;
			$nestedData['total_amount'] = numfmt_format_currency($fmt, $total_receipt_issued_amount, $currency);
		}

		$data[] = $nestedData;

		echo json_encode($data);

	}

	function classes_fee_class_update($date = '', $class_id = '') {
		$currency = $this->db->get_where('settings', array('type' => 'currency'))->row()->description;
		$fmt = new NumberFormatter('en_US', NumberFormatter::CURRENCY);

		$date_timestamp = strtotime($date);

		$this->db->select_sum('amount');
		$this->db->where('can_delete !=', 'trash');
		$receipt_issued = $this->db->get_where('payment', array('day_timestamp' => $date_timestamp, 'class_id' => $class_id, 'invoice_id' => NULL, 'title' => 'Classes Fee'))->result_array();

		$total_receipt_issued_amount = 0;

		$this->db->select('receipt_code');
		$this->db->distinct();
		$this->db->where('can_delete !=', 'trash');
		$total_receipt_issued = $this->db->get_where('payment', array('day_timestamp' => $date_timestamp, 'class_id' => $class_id, 'invoice_id' => NULL, 'title' => 'Classes Fee'))->num_rows();

		foreach ($receipt_issued as $re) {
			$total_receipt_issued_amount = $total_receipt_issued_amount + $re['amount'];
		}

		if ($total_receipt_issued < 1) {
			$nestedData['date_chosen'] = date('l M d, Y', $date_timestamp);
			$nestedData['total_number'] = $total_receipt_issued;
			$nestedData['total_amount'] = 'No Data';
		} else {
			$nestedData['date_chosen'] = date('l M d, Y', $date_timestamp);
			$nestedData['total_number'] = $total_receipt_issued;
			$nestedData['total_amount'] = numfmt_format_currency($fmt, $total_receipt_issued_amount, $currency);
		}

		$data[] = $nestedData;

		echo json_encode($data);

	}

	function transport_fare_class_update($date = '', $class_id = '') {
		$currency = $this->db->get_where('settings', array('type' => 'currency'))->row()->description;
		$fmt = new NumberFormatter('en_US', NumberFormatter::CURRENCY);

		$date_timestamp = strtotime($date);

		$this->db->select_sum('amount');
		$this->db->where('can_delete !=', 'trash');
		$receipt_issued = $this->db->get_where('payment', array('day_timestamp' => $date_timestamp, 'class_id' => $class_id, 'invoice_id' => NULL, 'title' => 'Transport Fare'))->result_array();

		$total_receipt_issued_amount = 0;

		$this->db->select('receipt_code');
		$this->db->distinct();
		$this->db->where('can_delete !=', 'trash');
		$total_receipt_issued = $this->db->get_where('payment', array('day_timestamp' => $date_timestamp, 'class_id' => $class_id, 'invoice_id' => NULL, 'title' => 'Transport Fare'))->num_rows();

		foreach ($receipt_issued as $re) {
			$total_receipt_issued_amount = $total_receipt_issued_amount + $re['amount'];
		}

		if ($total_receipt_issued < 1) {
			$nestedData['date_chosen'] = date('l M d, Y', $date_timestamp);
			$nestedData['total_number'] = $total_receipt_issued;
			$nestedData['total_amount'] = 'No Data';
		} else {
			$nestedData['date_chosen'] = date('l M d, Y', $date_timestamp);
			$nestedData['total_number'] = $total_receipt_issued;
			$nestedData['total_amount'] = numfmt_format_currency($fmt, $total_receipt_issued_amount, $currency);
		}

		$data[] = $nestedData;

		echo json_encode($data);

	}

	function get_receipt_byterm($search = '', $year = '', $term = '') {
		$currency = $this->db->get_where('settings', array('type' => 'currency'))->row()->description;
		$fmt = new NumberFormatter('en_US', NumberFormatter::CURRENCY);

		if ($search == 'search') {
			$selected_year = $year;
			$selected_term = $term;
		} else {
			$selected_year = get_settings('running_year');
			$selected_term = get_settings('running_term');
		}

		$this->db->select('COUNT(DISTINCT receipt_code) as total_number, SUM(amount) as total_amount');
		$this->db->where('invoice_id IS NOT NULL');
		$this->db->where('year', $selected_year);
		$this->db->where('term', $selected_term);
		$this->db->where('can_delete !=', 'trash');
		$result = $this->db->get('payment')->row();

		$nestedData['duration'] = 'Year: ' . $selected_year . '| Term: ' . $selected_term;
		$nestedData['total_number'] = $result->total_number;
		$nestedData['total_amount'] = ($result->total_number < 1) 
			? 'No Data' 
			: numfmt_format_currency($fmt, $result->total_amount, $currency);

		$data[] = $nestedData;
		echo json_encode($data);
	}

	function issuer_update_term($issuer_id = '', $year = '', $term = '') {
		$currency = $this->db->get_where('settings', array('type' => 'currency'))->row()->description;
		$fmt = new NumberFormatter('en_US', NumberFormatter::CURRENCY);

		$selected_year = $year;
		$selected_term = $term;
		//$selected_sem  = $sem;

		/*$data_array = array(
			            'issuer_id'=> $issuer_id,
			            'year'=> $selected_year,
			            'term'=> $selected_term,
			            'invoice_id !=' => NULL
		*/

		$this->db->select_sum('amount');
		$this->db->where('invoice_id !=', NULL);
		$this->db->where('issuer_id', $issuer_id);
		$this->db->where('year', $selected_year);
		$this->db->where('term', $selected_term);
		$this->db->where('can_delete !=', 'trash');
		//$this->db->or_where('sem', $selected_sem);
		$receipt_issued = $this->db->get('payment')->result_array();

		$total_receipt_issued_amount = 0;

		$this->db->select('receipt_code');
		$this->db->distinct();
		$this->db->where('invoice_id !=', NULL);
		$this->db->where('issuer_id', $issuer_id);
		$this->db->where('year', $selected_year);
		$this->db->where('term', $selected_term);
		$this->db->where('can_delete !=', 'trash');
		//$this->db->or_where('sem', $selected_sem);
		$total_receipt_issued = $this->db->get('payment')->num_rows();

		foreach ($receipt_issued as $re) {
			$total_receipt_issued_amount = $total_receipt_issued_amount + $re['amount'];
		}

		if ($total_receipt_issued < 1) {
			$nestedData['total_amount'] = 'No Data';
		} else {
			$nestedData['duration'] = 'Year: ' . $selected_year . '| Term: ' . $selected_term;
			$nestedData['total_number'] = $total_receipt_issued;
			$nestedData['total_amount'] = numfmt_format_currency($fmt, $total_receipt_issued_amount, $currency);
		}

		$data[] = $nestedData;

		echo json_encode($data);

	}

	function class_update_term($class_id = '', $year = '', $term = '') {
		$currency = $this->db->get_where('settings', array('type' => 'currency'))->row()->description;
		$fmt = new NumberFormatter('en_US', NumberFormatter::CURRENCY);

		$selected_year = $year;
		$selected_term = $term;
		//$selected_sem = $sem;

		$data_array = array(
			'class_id' => $class_id,
			'year' => $selected_year,
			'invoice_id !=' => NULL,
		);

		$class_name = $this->crud_model->get_class_name($class_id);

		if ($class_name == 'JHSS') {

			$this->db->select_sum('amount');
			$this->db->where($data_array);
			$this->db->where('sem', $selected_sem);
			$this->db->where('can_delete !=', 'trash');
			// $this->db->or_where('sem', $selected_sem);
			$receipt_issued = $this->db->get('payment')->result_array();

			$total_receipt_issued_amount = 0;

			$this->db->select('receipt_code');
			$this->db->distinct();
			$this->db->where($data_array);
			$this->db->where('sem', $selected_sem);
			$this->db->where('can_delete !=', 'trash');
			//$this->db->or_where('sem', $selected_sem);
			$total_receipt_issued = $this->db->get('payment')->num_rows();

			foreach ($receipt_issued as $re) {
				$total_receipt_issued_amount = $total_receipt_issued_amount + $re['amount'];
			}

			if ($total_receipt_issued < 1) {
				$nestedData['duration'] = 'Year: ' . explode('-', $selected_year)[1] . '| Semester: ' . $selected_sem;
				$nestedData['total_number'] = $total_receipt_issued;
				$nestedData['total_amount'] = 'No Data';
			} else {
				$nestedData['duration'] = 'Year: ' . explode('-', $selected_year)[1] . '| Semester: ' . $selected_sem;
				$nestedData['total_number'] = $total_receipt_issued;
				$nestedData['total_amount'] = numfmt_format_currency($fmt, $total_receipt_issued_amount, $currency);
			}

//end of JHS
		} else {
			$this->db->select_sum('amount');
			$this->db->where($data_array);
			$this->db->where('term', $selected_term);
			$this->db->where('can_delete !=', 'trash');
			// $this->db->or_where('sem', $selected_sem);
			$receipt_issued = $this->db->get('payment')->result_array();

			$total_receipt_issued_amount = 0;

			$this->db->select('receipt_code');
			$this->db->distinct();
			$this->db->where($data_array);
			$this->db->where('term', $selected_term);
			$this->db->where('can_delete !=', 'trash');
			//$this->db->or_where('sem', $selected_sem);
			$total_receipt_issued = $this->db->get('payment')->num_rows();

			foreach ($receipt_issued as $re) {
				$total_receipt_issued_amount = $total_receipt_issued_amount + $re['amount'];
			}

			if ($total_receipt_issued < 1) {
				$nestedData['duration'] = 'Year: ' . explode('-', $selected_year)[1] . '| Term: ' . $selected_term;
				$nestedData['total_number'] = $total_receipt_issued;
				$nestedData['total_amount'] = 'No Data';
			} else {
				$nestedData['duration'] = 'Year: ' . explode('-', $selected_year)[1] . '| Term: ' . $selected_term;
				$nestedData['total_number'] = $total_receipt_issued;
				$nestedData['total_amount'] = numfmt_format_currency($fmt, $total_receipt_issued_amount, $currency);
			}
		}

		$data[] = $nestedData;

		echo json_encode($data);

	}

	//for feeding, classes fees and transport fare by date
	function get_fct_bydate($search = '', $date = '') {
		$currency = $this->db->get_where('settings', array('type' => 'currency'))->row()->description;
		$fmt = new NumberFormatter('en_US', NumberFormatter::CURRENCY);

		if ($search == 'search') {
			$date_timestamp = strtotime($date);
		} else {
			$date_timestamp = strtotime(date('d-m-Y'));
		}

		// Calculate PAID amounts on this specific date from daily_fee_transactions
		$this->db->select_sum('feeding_amount');
		$this->db->where('payment_date', $date_timestamp);
		$total_feeding_paid = $this->db->get('daily_fee_transactions')->row()->feeding_amount ?? 0;

		$this->db->select_sum('classes_amount');
		$this->db->where('payment_date', $date_timestamp);
		$total_classes_paid = $this->db->get('daily_fee_transactions')->row()->classes_amount ?? 0;

		$this->db->select_sum('transport_amount');
		$this->db->where('payment_date', $date_timestamp);
		$total_fare_paid = $this->db->get('daily_fee_transactions')->row()->transport_amount ?? 0;

		$this->db->select_sum('breakfast_amount');
		$this->db->where('payment_date', $date_timestamp);
		$total_breakfast_paid = $this->db->get('daily_fee_transactions')->row()->breakfast_amount ?? 0;

		$this->db->select_sum('water_amount');
		$this->db->where('payment_date', $date_timestamp);
		$total_water_paid = $this->db->get('daily_fee_transactions')->row()->water_amount ?? 0;

		// Get active students (only latest enrollment per student)
		$active_students_query = "
			SELECT DISTINCT dfw.student_id
			FROM daily_fee_wallet dfw
			WHERE EXISTS (
				SELECT 1 
				FROM enroll e
				WHERE e.student_id = dfw.student_id
				AND e.mute = '0'
				AND e.enroll_id = (
					SELECT MAX(enroll_id)
					FROM enroll e2
					WHERE e2.student_id = dfw.student_id
				)
			)
		";

		// Calculate OUTSTANDING amounts (arrears) from daily_fee_wallet - only active students with latest enrollment
		$this->db->select_sum('dfw.feeding_arrears');
		$this->db->from('daily_fee_wallet dfw');
		$this->db->where("dfw.student_id IN ($active_students_query)", NULL, FALSE);
		$total_feeding_owe = $this->db->get()->row()->feeding_arrears ?? 0;

		$this->db->select_sum('dfw.classes_arrears');
		$this->db->from('daily_fee_wallet dfw');
		$this->db->where("dfw.student_id IN ($active_students_query)", NULL, FALSE);
		$total_classes_owe = $this->db->get()->row()->classes_arrears ?? 0;

		$this->db->select_sum('dfw.transport_arrears');
		$this->db->from('daily_fee_wallet dfw');
		$this->db->where("dfw.student_id IN ($active_students_query)", NULL, FALSE);
		$total_fare_owe = $this->db->get()->row()->transport_arrears ?? 0;

		$this->db->select_sum('dfw.breakfast_arrears');
		$this->db->from('daily_fee_wallet dfw');
		$this->db->where("dfw.student_id IN ($active_students_query)", NULL, FALSE);
		$total_breakfast_owe = $this->db->get()->row()->breakfast_arrears ?? 0;

		$this->db->select_sum('dfw.water_arrears');
		$this->db->from('daily_fee_wallet dfw');
		$this->db->where("dfw.student_id IN ($active_students_query)", NULL, FALSE);
		$total_water_owe = $this->db->get()->row()->water_arrears ?? 0;

		// Calculate PAYABLES (prepaid balances) from daily_fee_wallet - only active students with latest enrollment
		$this->db->select_sum('dfw.feeding_balance');
		$this->db->from('daily_fee_wallet dfw');
		$this->db->where("dfw.student_id IN ($active_students_query)", NULL, FALSE);
		$total_feeding_payable = $this->db->get()->row()->feeding_balance ?? 0;

		$this->db->select_sum('dfw.classes_balance');
		$this->db->from('daily_fee_wallet dfw');
		$this->db->where("dfw.student_id IN ($active_students_query)", NULL, FALSE);
		$total_classes_payable = $this->db->get()->row()->classes_balance ?? 0;

		$this->db->select_sum('dfw.transport_balance');
		$this->db->from('daily_fee_wallet dfw');
		$this->db->where("dfw.student_id IN ($active_students_query)", NULL, FALSE);
		$total_transport_payable = $this->db->get()->row()->transport_balance ?? 0;

		$this->db->select_sum('dfw.breakfast_balance');
		$this->db->from('daily_fee_wallet dfw');
		$this->db->where("dfw.student_id IN ($active_students_query)", NULL, FALSE);
		$total_breakfast_payable = $this->db->get()->row()->breakfast_balance ?? 0;

		$this->db->select_sum('dfw.water_balance');
		$this->db->from('daily_fee_wallet dfw');
		$this->db->where("dfw.student_id IN ($active_students_query)", NULL, FALSE);
		$total_water_payable = $this->db->get()->row()->water_balance ?? 0;

		//summary
		$fo = $total_feeding_owe;
		$co = $total_classes_owe;
		$to = $total_fare_owe;

		// Format numbers with 2 decimal places (currency symbol added by JavaScript)
		$total_feeding_owe = number_format($total_feeding_owe, 2);
		$total_fare_owe = number_format($total_fare_owe, 2);
		$total_classes_owe = number_format($total_classes_owe, 2);
		$total_breakfast_owe = number_format($total_breakfast_owe, 2);
		$total_water_owe = number_format($total_water_owe, 2);

		$total_feeding_paid = number_format($total_feeding_paid, 2);
		$total_fare_paid = number_format($total_fare_paid, 2);
		$total_classes_paid = number_format($total_classes_paid, 2);
		$total_breakfast_paid = number_format($total_breakfast_paid, 2);
		$total_water_paid = number_format($total_water_paid, 2);

		$total_feeding_payable = number_format($total_feeding_payable, 2);
		$total_classes_payable = number_format($total_classes_payable, 2);
		$total_transport_payable = number_format($total_transport_payable, 2);
		$total_breakfast_payable = number_format($total_breakfast_payable, 2);
		$total_water_payable = number_format($total_water_payable, 2);

		//payments
		$nestedData['total_feeding_paid'] = $total_feeding_paid;
		$nestedData['total_classes_paid'] = $total_classes_paid;
		$nestedData['total_fare_paid'] = $total_fare_paid;
		$nestedData['total_breakfast_paid'] = $total_breakfast_paid;
		$nestedData['total_water_paid'] = $total_water_paid;

		//owings
		$nestedData['total_feeding_owe'] = $total_feeding_owe;
		$nestedData['total_classes_owe'] = $total_classes_owe;
		$nestedData['total_fare_owe'] = $total_fare_owe;
		$nestedData['total_breakfast_owe'] = $total_breakfast_owe;
		$nestedData['total_water_owe'] = $total_water_owe;

		$nestedData['fo'] = $fo;
		$nestedData['co'] = $co;
		$nestedData['to'] = $to;

		//payables
		$nestedData['total_feeding_payable'] = $total_feeding_payable;
		$nestedData['total_classes_payable'] = $total_classes_payable;
		$nestedData['total_transport_payable'] = $total_transport_payable;
		$nestedData['total_breakfast_payable'] = $total_breakfast_payable;
		$nestedData['total_water_payable'] = $total_water_payable;

		$nestedData['date_chosen'] = date('l M d, Y', $date_timestamp);
		$nestedData['timestamp'] = $date_timestamp;

		$data[] = $nestedData;

		echo json_encode($data);

	}

	//for feeding, classes fees and transport fare by term
	function get_fct_byterm($search = '', $year = '', $term = '') {
		$running_year = get_settings('running_year');
		$running_term = get_settings('running_term');

		$currency = $this->db->get_where('settings', array('type' => 'currency'))->row()->description;
		$fmt = new NumberFormatter('en_US', NumberFormatter::CURRENCY);

		if ($search == 'search') {
			$un_year = $year;
			$un_term = $term;
		} else {
			$un_year = $running_year;
			$un_term = $running_term;
		}

		// Calculate PAID amounts for the term from daily_fee_transactions
		$this->db->select_sum('feeding_amount');
		$this->db->where('year', $un_year);
		$this->db->where('term', $un_term);
		$total_feeding_paid = $this->db->get('daily_fee_transactions')->row()->feeding_amount ?? 0;

		$this->db->select_sum('classes_amount');
		$this->db->where('year', $un_year);
		$this->db->where('term', $un_term);
		$total_classes_paid = $this->db->get('daily_fee_transactions')->row()->classes_amount ?? 0;

		$this->db->select_sum('transport_amount');
		$this->db->where('year', $un_year);
		$this->db->where('term', $un_term);
		$total_fare_paid = $this->db->get('daily_fee_transactions')->row()->transport_amount ?? 0;

		$this->db->select_sum('breakfast_amount');
		$this->db->where('year', $un_year);
		$this->db->where('term', $un_term);
		$total_breakfast_paid = $this->db->get('daily_fee_transactions')->row()->breakfast_amount ?? 0;

		$this->db->select_sum('water_amount');
		$this->db->where('year', $un_year);
		$this->db->where('term', $un_term);
		$total_water_paid = $this->db->get('daily_fee_transactions')->row()->water_amount ?? 0;

		// Get active students (only latest enrollment per student) for the specified year/term
		$active_students_query = "
			SELECT DISTINCT dfw.student_id
			FROM daily_fee_wallet dfw
			WHERE dfw.year = '$un_year'
			AND dfw.term = '$un_term'
			AND EXISTS (
				SELECT 1 
				FROM enroll e
				WHERE e.student_id = dfw.student_id
				AND e.year = '$un_year'
				AND e.term = '$un_term'
				AND e.mute = '0'
				AND e.enroll_id = (
					SELECT MAX(enroll_id)
					FROM enroll e2
					WHERE e2.student_id = dfw.student_id
					AND e2.year = '$un_year'
					AND e2.term = '$un_term'
				)
			)
		";

		// Calculate OUTSTANDING amounts (arrears) from daily_fee_wallet - only active students with latest enrollment
		$this->db->select_sum('dfw.feeding_arrears');
		$this->db->from('daily_fee_wallet dfw');
		$this->db->where("dfw.student_id IN ($active_students_query)", NULL, FALSE);
		$this->db->where('dfw.year', $un_year);
		$this->db->where('dfw.term', $un_term);
		$total_feeding_owe = $this->db->get()->row()->feeding_arrears ?? 0;

		$this->db->select_sum('dfw.classes_arrears');
		$this->db->from('daily_fee_wallet dfw');
		$this->db->where("dfw.student_id IN ($active_students_query)", NULL, FALSE);
		$this->db->where('dfw.year', $un_year);
		$this->db->where('dfw.term', $un_term);
		$total_classes_owe = $this->db->get()->row()->classes_arrears ?? 0;

		$this->db->select_sum('dfw.transport_arrears');
		$this->db->from('daily_fee_wallet dfw');
		$this->db->where("dfw.student_id IN ($active_students_query)", NULL, FALSE);
		$this->db->where('dfw.year', $un_year);
		$this->db->where('dfw.term', $un_term);
		$total_fare_owe = $this->db->get()->row()->transport_arrears ?? 0;

		$this->db->select_sum('dfw.breakfast_arrears');
		$this->db->from('daily_fee_wallet dfw');
		$this->db->where("dfw.student_id IN ($active_students_query)", NULL, FALSE);
		$this->db->where('dfw.year', $un_year);
		$this->db->where('dfw.term', $un_term);
		$total_breakfast_owe = $this->db->get()->row()->breakfast_arrears ?? 0;

		$this->db->select_sum('dfw.water_arrears');
		$this->db->from('daily_fee_wallet dfw');
		$this->db->where("dfw.student_id IN ($active_students_query)", NULL, FALSE);
		$this->db->where('dfw.year', $un_year);
		$this->db->where('dfw.term', $un_term);
		$total_water_owe = $this->db->get()->row()->water_arrears ?? 0;

		// Calculate PAYABLES (prepaid balances) from daily_fee_wallet - only active students with latest enrollment
		$this->db->select_sum('dfw.feeding_balance');
		$this->db->from('daily_fee_wallet dfw');
		$this->db->where("dfw.student_id IN ($active_students_query)", NULL, FALSE);
		$this->db->where('dfw.year', $un_year);
		$this->db->where('dfw.term', $un_term);
		$total_feeding_payable = $this->db->get()->row()->feeding_balance ?? 0;

		$this->db->select_sum('dfw.classes_balance');
		$this->db->from('daily_fee_wallet dfw');
		$this->db->where("dfw.student_id IN ($active_students_query)", NULL, FALSE);
		$this->db->where('dfw.year', $un_year);
		$this->db->where('dfw.term', $un_term);
		$total_classes_payable = $this->db->get()->row()->classes_balance ?? 0;

		$this->db->select_sum('dfw.transport_balance');
		$this->db->from('daily_fee_wallet dfw');
		$this->db->where("dfw.student_id IN ($active_students_query)", NULL, FALSE);
		$this->db->where('dfw.year', $un_year);
		$this->db->where('dfw.term', $un_term);
		$total_transport_payable = $this->db->get()->row()->transport_balance ?? 0;

		$this->db->select_sum('dfw.breakfast_balance');
		$this->db->from('daily_fee_wallet dfw');
		$this->db->where("dfw.student_id IN ($active_students_query)", NULL, FALSE);
		$this->db->where('dfw.year', $un_year);
		$this->db->where('dfw.term', $un_term);
		$total_breakfast_payable = $this->db->get()->row()->breakfast_balance ?? 0;

		$this->db->select_sum('dfw.water_balance');
		$this->db->from('daily_fee_wallet dfw');
		$this->db->where("dfw.student_id IN ($active_students_query)", NULL, FALSE);
		$this->db->where('dfw.year', $un_year);
		$this->db->where('dfw.term', $un_term);
		$total_water_payable = $this->db->get()->row()->water_balance ?? 0;

		$feeding_timestamp = 0;
		$classes_timestamp = 0;
		$fare_timestamp = 0;

		// Store raw values for links
		$fo = $total_feeding_owe;
		$co = $total_classes_owe;
		$to = $total_fare_owe;

		// Format numbers with 2 decimal places (currency symbol added by JavaScript)
		$nestedData['total_feeding_paid'] = number_format($total_feeding_paid, 2);
		$nestedData['total_classes_paid'] = number_format($total_classes_paid, 2);
		$nestedData['total_fare_paid'] = number_format($total_fare_paid, 2);
		$nestedData['total_breakfast_paid'] = number_format($total_breakfast_paid, 2);
		$nestedData['total_water_paid'] = number_format($total_water_paid, 2);

		$nestedData['total_feeding_owe'] = number_format($total_feeding_owe, 2);
		$nestedData['total_classes_owe'] = number_format($total_classes_owe, 2);
		$nestedData['total_fare_owe'] = number_format($total_fare_owe, 2);
		$nestedData['total_breakfast_owe'] = number_format($total_breakfast_owe, 2);
		$nestedData['total_water_owe'] = number_format($total_water_owe, 2);

		$nestedData['total_feeding_payable'] = number_format($total_feeding_payable, 2);
		$nestedData['total_classes_payable'] = number_format($total_classes_payable, 2);
		$nestedData['total_transport_payable'] = number_format($total_transport_payable, 2);
		$nestedData['total_breakfast_payable'] = number_format($total_breakfast_payable, 2);
		$nestedData['total_water_payable'] = number_format($total_water_payable, 2);

		$nestedData['fo'] = $fo;
		$nestedData['co'] = $co;
		$nestedData['to'] = $to;

		$nestedData['feeding_timestamp'] = $feeding_timestamp;
		$nestedData['classes_timestamp'] = $classes_timestamp;
		$nestedData['fare_timestamp'] = $fare_timestamp;

		$nestedData['duration_chosen'] = 'Year|Term: ' . $year . '|' . $term;

		$data[] = $nestedData;

		echo json_encode($data);
	}

	//by date on modal
	function get_receipt_bydate_modal($param2) {
		$currency = $this->db->get_where('settings', array('type' => 'currency'))->row()->description;
		$fmt = new NumberFormatter('en_US', NumberFormatter::CURRENCY);
		$active_sms_service = $this->db->get_where('settings', array('type' => 'active_sms_service'))->row()->description;

		$param2 = strtotime($param2);
		
		// Get receipt totals first
		$this->db->select('receipt_code, SUM(amount) as total_amount');
		$this->db->where('day_timestamp', $param2);
		$this->db->where('invoice_id IS NOT NULL');
		$this->db->group_by('receipt_code');
		$totals = $this->db->get('payment')->result_array();
		$receipt_totals = array();
		foreach($totals as $t) {
			$receipt_totals[$t['receipt_code']] = $t['total_amount'];
		}
		
		// Get receipt details with JOINs
		$this->db->select('p.receipt_code, p.student_id, p.year, p.term, p.timestamp, p.account_type,
						   s.student_code, s.name as student_name, s.sex,
						   a.name as issuer_name,
						   e.class_id,
						   c.name as class_name, c.name_numeric,
						   sec.name as section_name');
		$this->db->from('payment p');
		$this->db->join('student s', 'p.student_id = s.student_id');
		$this->db->join('admin a', 'p.issuer_id = a.admin_id', 'left');
		$this->db->join('enroll e', 'p.student_id = e.student_id AND e.mute = "0"', 'left');
		$this->db->join('class c', 'e.class_id = c.class_id', 'left');
		$this->db->join('section sec', 'e.class_id = sec.class_id', 'left');
		$this->db->where('p.day_timestamp', $param2);
		$this->db->where('p.invoice_id IS NOT NULL');
		$this->db->group_by('p.receipt_code');
		$this->db->order_by('p.timestamp', 'DESC');
		$receipts = $this->db->get()->result_array();

		if (empty($receipts)) {
			echo '<tr><td align="center" colspan="10"><h4>No Data Found</h4></td></tr>';
		} else {
			// Get class counts for section display
			$class_counts = array();
			foreach($receipts as $r) {
				if($r['class_name']) {
					$key = $r['class_name'].'_'.$r['name_numeric'];
					$class_counts[$key] = isset($class_counts[$key]) ? $class_counts[$key] + 1 : 1;
				}
			}
			
			foreach ($receipts as $row):
				$issuer_name = !empty($row['issuer_name']) && !empty($row['account_type']) 
					? $row['issuer_name'] . '<small>(' . $row['account_type'] . ')</small>' 
					: 'Not Available';

				$key = $row['class_name'].'_'.$row['name_numeric'];
				$sec_name = (isset($class_counts[$key]) && $class_counts[$key] > 1) ? $row['section_name'] : '';
				
				$class = ($row['class_name'] == 'CRECHE') 
					? $row['class_name'] 
					: $row['class_name'] . ' ' . $row['name_numeric'] . $sec_name;

				$total_amt = isset($receipt_totals[$row['receipt_code']]) ? $receipt_totals[$row['receipt_code']] : 0;
				
				echo '<tr>
					<td>' . $row['student_code'] . '</td>
					<td align="center"><img src="' . $this->crud_model->get_image_url('student', $row['student_id'], $row['sex']) . '" class="img-circle" width="30" /></td>
					<td>' . $row['student_name'] . '</td>
					<td>' . $class . '</td>
					<td>' . $row['receipt_code'] . '</td>
					<td style="text-align: right">' . numfmt_format_currency($fmt, $total_amt, $currency) . '</td>
					<td>' . date('l M d, Y H:i:s', $row['timestamp']) . '</td>
					<td>' . $row['year'] . '|' . $row['term'] . '</td>
					<td>' . $issuer_name . '</td>
					<td>

                <div class="btn-group">
                    <button type="button" class="btn btn-info btn-sm dropdown-toggle" data-toggle="dropdown">
                        Action <span class="caret"></span>
                    </button>
                    <ul class="dropdown-menu dropdown-default pull-right" role="menu">

                        <!-- STUDENT PROFILE LINK -->
                        <li>
                            <a href="#" style="color: #0029ff;" onclick="view_receipts_modal(' . $row['student_id'] . ',' . $param2 . ')">
                                <i class="entypo-credit-card"></i>
                                    ' . get_phrase('view_student\'s_receipt') . '
                                </a>
                        </li>
                        <li class="divider"></li>

                        <!-- SMS LINK -->
                        <li>
                            <a href="' . site_url('admin/message/sms_send?si=' . $row['student_id']) . '" class="pt_link" onclick="check_sms_status()" style="color: green;"><i class="glyphicon glyphicon-envelope"></i>
                                ' . get_phrase('send_sMS') . '
                            </a>
                        </li>
                        <li class="divider"></li>
                    </ul>
                </div>

            </td>
        </tr>';

			endforeach;
		}
	}

	//feeding fee  by date on modal
	function get_feeding_fee_receipt_bydate_modal($param2) {
		$currency = $this->db->get_where('settings', array('type' => 'currency'))->row()->description;
		$fmt = new NumberFormatter('en_US', NumberFormatter::CURRENCY);
		$active_sms_service = $this->db->get_where('settings', array('type' => 'active_sms_service'))->row()->description;

		$param2 = strtotime($param2);
		$this->db->select('receipt_code');
		$this->db->distinct();
		$this->db->where('can_delete !=', 'trash');
		$check = $this->db->get_where('payment', array('day_timestamp' => $param2, 'invoice_id' => NULL, 'title' => 'Feeding Fee'));
		$receipt_issued = $check->result_array();

		if ($check->num_rows() < 1) {
			echo '<tr>
                    <td align="center" rowspan="10"><h4>No Data Found</h4></td>
                    </tr>';
		} else {
			$receipt_code_array = array();
			foreach ($receipt_issued as $row) {
				array_push($receipt_code_array, $row['receipt_code']);
			}

			$total_amount = 0;
			for ($i = 0; $i < count($receipt_code_array); $i++) {
				//total amount selection
				$this->db->select_sum('amount');
				$receipt_amount = $this->db->get_where('payment', array('receipt_code' => $receipt_code_array[$i], 'invoice_id' => NULL, 'title' => 'Feeding Fee'))->result_array();

				foreach ($receipt_amount as $ra) {
					$total_amount = $total_amount + $ra['amount'];
				}

				//generation selection
				$this->db->limit(1);
				$receipts = $this->db->get_where('payment', array('receipt_code' => $receipt_code_array[$i], 'invoice_id' => NULL, 'title' => 'Feeding Fee'))->result_array();

				foreach ($receipts as $row):
					$gender = $this->db->get_where('student', array('student_id' => $row['student_id']))->row()->sex;

					//who issued the receipt
					$issuer_id = $this->db->get_where('payment', array('receipt_code' => $row['receipt_code'], 'student_id' => $row['student_id']))->row()->issuer_id;

					if (substr($issuer_id, 0, 1) == 't') {
						//find out it is a teacher
						$issuer_id = substr($issuer_id, 1);
						$issuer_name = $this->db->get_where('teacher', array('teacher_id' => $issuer_id))->row()->name;
						$account_type = 'Teacher';

					} else {
						//not a teacher
						$issuer_name = $this->db->get_where('admin', array('admin_id' => $issuer_id))->row()->name;
						$account_type = $this->db->get_where('payment', array('receipt_code' => $row['receipt_code'], 'student_id' => $row['student_id']))->row()->account_type;
					}

					if (!empty($issuer_name) && !empty($account_type)) {
						$issuer_name = $issuer_name . '<small>(' . $account_type . ')</small>';
					} else {
						$issuer_name = 'Not Available';
					}

					//add section A or B if the class has more than one section
					$class_id = $this->db->get_where('enroll', array('student_id' => $row['student_id'], 'mute' => '0'))->row()->class_id;
					$class_name = $this->db->get_where('class', array('class_id' => $class_id))->row()->name;
					$class_name_numeric = $this->db->get_where('class', array('class_id' => $class_id))->row()->name_numeric;
					$section_name = $this->db->get_where('section', array('class_id' => $class_id))->row()->name;
					$class_has_more_sections = $this->db->get_where('class', array('name' => $class_name, 'name_numeric' => $class_name_numeric))->num_rows();
					$sec_name = '';
					if ($class_has_more_sections > 1) {
						$sec_name = $section_name;
					}

					$class = '';
					if ($class_name == 'CRECHE') {
						$class = $class_name;
					} else {
						$class = $class_name . ' ' . $class_name_numeric . $sec_name;
					}

					echo ' <tr>
	                        <td>' . $this->db->get_where('student', array(
'student_id' => $row['student_id']))->row()->student_code . '</td>

	                        <td align="center"><img src="' . $this->crud_model->get_image_url('student', $row['student_id'], $gender) . '" class="img-circle" width="30" /></td>
	                        <td>

	                                ' . $this->db->get_where('student', array(
'student_id' => $row['student_id'],
))->row()->name
. '
	                        </td>
	                        <td>
	                            ' . $class . '
	                        </td>
	                        <td>

	                                ' . $receipt_code_array[$i] . '

	                        </td>
	                        <td style="text-align: right">' .

numfmt_format_currency($fmt, $total_amount, $currency) . '

	                        </td>
	                        <td>

	                            ' . date('l M d, Y H:s:i', $row['timestamp']) . '

	                        </td>
	                        <td>
	                            ' .
$row['year'] . '|' . $row['term'] . '

	                        </td>
	                        <td>
	                            ' . $issuer_name . '

	                        </td>
	                        <td>

	                            <div class="btn-group">
	                                <button type="button" class="btn btn-info btn-sm dropdown-toggle" data-toggle="dropdown">
	                                    Action <span class="caret"></span>
	                                </button>
	                                <ul class="dropdown-menu dropdown-default pull-right" role="menu">

	                                    <!-- STUDENT PROFILE LINK -->
	                                    <li>
	                                        <a href="#" style="color: #0029ff;" onclick="view_receipts_modal(' . $row['student_id'] . ',' . $param2 . ', \'Feeding Fee\')">
	                                            <i class="entypo-credit-card"></i>
	                                                ' . get_phrase('view_student\'s_receipt') . '
	                                            </a>
	                                    </li>
	                                    <li class="divider"></li>

	                                    <!-- SMS LINK -->
	                                    <li>
	                                        <a href="' . site_url('admin/message/sms_send?si=' . $row['student_id']) . '" class="pt_link" onclick="check_sms_status()" style="color: green;"><i class="glyphicon glyphicon-envelope"></i>
	                                            ' . get_phrase('send_sMS') . '
	                                        </a>
	                                    </li>
	                                    <li class="divider"></li>
	                                </ul>
	                            </div>

	                        </td>
	                    </tr>';

					//let's reset the total amount value to 0
					$total_amount = 0;
				endforeach;
			}
		}

	}

	//classes fee  by date on modal
	function get_classes_fee_receipt_bydate_modal($param2) {
		$currency = $this->db->get_where('settings', array('type' => 'currency'))->row()->description;
		$fmt = new NumberFormatter('en_US', NumberFormatter::CURRENCY);
		$active_sms_service = $this->db->get_where('settings', array('type' => 'active_sms_service'))->row()->description;

		$param2 = strtotime($param2);
		$this->db->select('receipt_code');
		$this->db->distinct();
		$this->db->where('can_delete !=', 'trash');
		$check = $this->db->get_where('payment', array('day_timestamp' => $param2, 'invoice_id' => NULL, 'title' => 'Classes Fee'));
		$receipt_issued = $check->result_array();

		if ($check->num_rows() < 1) {
			echo '<tr>
                    <td align="center" rowspan="10"><h4>No Data Found</h4></td>
                    </tr>';
		} else {
			$receipt_code_array = array();
			foreach ($receipt_issued as $row) {
				array_push($receipt_code_array, $row['receipt_code']);
			}

			$total_amount = 0;
			for ($i = 0; $i < count($receipt_code_array); $i++) {
				//total amount selection
				$this->db->select_sum('amount');
				$receipt_amount = $this->db->get_where('payment', array('receipt_code' => $receipt_code_array[$i], 'invoice_id' => NULL, 'title' => 'Classes Fee'))->result_array();

				foreach ($receipt_amount as $ra) {
					$total_amount = $total_amount + $ra['amount'];
				}

				//generation selection
				$this->db->limit(1);
				$receipts = $this->db->get_where('payment', array('receipt_code' => $receipt_code_array[$i], 'invoice_id' => NULL, 'title' => 'Classes Fee'))->result_array();

				foreach ($receipts as $row):
					$gender = $this->db->get_where('student', array('student_id' => $row['student_id']))->row()->sex;

					//who issued the receipt
					$issuer_id = $this->db->get_where('payment', array('receipt_code' => $row['receipt_code'], 'student_id' => $row['student_id']))->row()->issuer_id;

					if (substr($issuer_id, 0, 1) == 't') {
						//find out it is a teacher
						$issuer_id = substr($issuer_id, 1);
						$issuer_name = $this->db->get_where('teacher', array('teacher_id' => $issuer_id))->row()->name;
						$account_type = 'Teacher';

					} else {
						//not a teacher
						$issuer_name = $this->db->get_where('admin', array('admin_id' => $issuer_id))->row()->name;
						$account_type = $this->db->get_where('payment', array('receipt_code' => $row['receipt_code'], 'student_id' => $row['student_id']))->row()->account_type;
					}

					if (!empty($issuer_name) && !empty($account_type)) {
						$issuer_name = $issuer_name . '<small>(' . $account_type . ')</small>';
					} else {
						$issuer_name = 'Not Available';
					}

					//add section A or B if the class has more than one section
					$class_id = $this->db->get_where('enroll', array('student_id' => $row['student_id'], 'mute' => '0'))->row()->class_id;
					$class_name = $this->db->get_where('class', array('class_id' => $class_id))->row()->name;
					$class_name_numeric = $this->db->get_where('class', array('class_id' => $class_id))->row()->name_numeric;
					$section_name = $this->db->get_where('section', array('class_id' => $class_id))->row()->name;
					$class_has_more_sections = $this->db->get_where('class', array('name' => $class_name, 'name_numeric' => $class_name_numeric))->num_rows();
					$sec_name = '';
					if ($class_has_more_sections > 1) {
						$sec_name = $section_name;
					}

					$class = '';
					if ($class_name == 'CRECHE') {
						$class = $class_name;
					} else {
						$class = $class_name . ' ' . $class_name_numeric . $sec_name;
					}

					echo ' <tr>
				            <td>' . $this->db->get_where('student', array(
'student_id' => $row['student_id']))->row()->student_code . '</td>

				            <td align="center"><img src="' . $this->crud_model->get_image_url('student', $row['student_id'], $gender) . '" class="img-circle" width="30" /></td>
				            <td>

				                    ' . $this->db->get_where('student', array(
'student_id' => $row['student_id'],
))->row()->name
. '
				            </td>
				            <td>
				                ' . $class . '
				            </td>
				            <td>

				                    ' . $receipt_code_array[$i] . '

				            </td>
				            <td style="text-align: right">' .

numfmt_format_currency($fmt, $total_amount, $currency) . '

				            </td>
				            <td>

				                ' . date('l M d, Y H:s:i', $row['timestamp']) . '

				            </td>
				            <td>
				                ' .
$row['year'] . '|' . $row['term'] . '

				            </td>
				            <td>
				                ' . $issuer_name . '

				            </td>
				            <td>

				                <div class="btn-group">
				                    <button type="button" class="btn btn-info btn-sm dropdown-toggle" data-toggle="dropdown">
				                        Action <span class="caret"></span>
				                    </button>
				                    <ul class="dropdown-menu dropdown-default pull-right" role="menu">

				                        <!-- STUDENT PROFILE LINK -->
				                        <li>
				                            <a href="#" style="color: #0029ff;" onclick="view_receipts_modal(' . $row['student_id'] . ',' . $param2 . ', \'Classes Fee\')">
				                                <i class="entypo-credit-card"></i>
				                                    ' . get_phrase('view_student\'s_receipt') . '
				                                </a>
				                        </li>
				                        <li class="divider"></li>

				                        <!-- SMS LINK -->
				                        <li>
				                            <a href="' . site_url('admin/message/sms_send?si=' . $row['student_id']) . '" class="pt_link" onclick="check_sms_status()" style="color: green;"><i class="glyphicon glyphicon-envelope"></i>
				                                ' . get_phrase('send_sMS') . '
				                            </a>
				                        </li>
				                        <li class="divider"></li>
				                    </ul>
				                </div>

				            </td>
				        </tr>';

					//let's reset the total amount value to 0
					$total_amount = 0;
				endforeach;
			}
		}

	}

	//feeding fee  by date on modal
	function get_transport_fare_receipt_bydate_modal($param2) {
		$currency = $this->db->get_where('settings', array('type' => 'currency'))->row()->description;
		$fmt = new NumberFormatter('en_US', NumberFormatter::CURRENCY);
		$active_sms_service = $this->db->get_where('settings', array('type' => 'active_sms_service'))->row()->description;

		$param2 = strtotime($param2);
		$this->db->select('receipt_code');
		$this->db->distinct();
		$this->db->where('can_delete !=', 'trash');
		$check = $this->db->get_where('payment', array('day_timestamp' => $param2, 'invoice_id' => NULL, 'title' => 'Transport Fare'));
		$receipt_issued = $check->result_array();

		if ($check->num_rows() < 1) {
			echo '<tr>
                    <td align="center" rowspan="10"><h4>No Data Found</h4></td>
                    </tr>';
		} else {
			$receipt_code_array = array();
			foreach ($receipt_issued as $row) {
				array_push($receipt_code_array, $row['receipt_code']);
			}

			$total_amount = 0;
			for ($i = 0; $i < count($receipt_code_array); $i++) {
				//total amount selection
				$this->db->select_sum('amount');
				$receipt_amount = $this->db->get_where('payment', array('receipt_code' => $receipt_code_array[$i], 'invoice_id' => NULL, 'title' => 'Transport Fare'))->result_array();

				foreach ($receipt_amount as $ra) {
					$total_amount = $total_amount + $ra['amount'];
				}

				//general selections
				$this->db->limit(1);
				$receipts = $this->db->get_where('payment', array('receipt_code' => $receipt_code_array[$i], 'invoice_id' => NULL, 'title' => 'Transport Fare'))->result_array();

				foreach ($receipts as $row):
					$gender = $this->db->get_where('student', array('student_id' => $row['student_id']))->row()->sex;

					//who issued the receipt
					$issuer_id = $this->db->get_where('payment', array('receipt_code' => $row['receipt_code'], 'student_id' => $row['student_id']))->row()->issuer_id;

					if (substr($issuer_id, 0, 1) == 't') {
						//find out it is a teacher
						$issuer_id = substr($issuer_id, 1);
						$issuer_name = $this->db->get_where('teacher', array('teacher_id' => $issuer_id))->row()->name;
						$account_type = 'Teacher';

					} else {
						//not a teacher
						$issuer_name = $this->db->get_where('admin', array('admin_id' => $issuer_id))->row()->name;
						$account_type = $this->db->get_where('payment', array('receipt_code' => $row['receipt_code'], 'student_id' => $row['student_id']))->row()->account_type;
					}

					if (!empty($issuer_name) && !empty($account_type)) {
						$issuer_name = $issuer_name . '<small>(' . $account_type . ')</small>';
					} else {
						$issuer_name = 'Not Available';
					}

					//add section A or B if the class has more than one section
					$class_id = $this->db->get_where('enroll', array('student_id' => $row['student_id'], 'mute' => '0'))->row()->class_id;
					$class_name = $this->db->get_where('class', array('class_id' => $class_id))->row()->name;
					$class_name_numeric = $this->db->get_where('class', array('class_id' => $class_id))->row()->name_numeric;
					$section_name = $this->db->get_where('section', array('class_id' => $class_id))->row()->name;
					$class_has_more_sections = $this->db->get_where('class', array('name' => $class_name, 'name_numeric' => $class_name_numeric))->num_rows();
					$sec_name = '';
					if ($class_has_more_sections > 1) {
						$sec_name = $section_name;
					}

					$class = '';
					if ($class_name == 'CRECHE') {
						$class = $class_name;
					} else {
						$class = $class_name . ' ' . $class_name_numeric . $sec_name;
					}

					echo ' <tr>
				        <td>' . $this->db->get_where('student', array(
'student_id' => $row['student_id']))->row()->student_code . '</td>

				        <td align="center"><img src="' . $this->crud_model->get_image_url('student', $row['student_id'], $gender) . '" class="img-circle" width="30" /></td>
				        <td>

				                ' . $this->db->get_where('student', array(
'student_id' => $row['student_id'],
))->row()->name
. '
				        </td>
				        <td>
				            ' . $class . '
				        </td>
				        <td>

				                ' . $receipt_code_array[$i] . '

				        </td>
				        <td style="text-align: right">' .

numfmt_format_currency($fmt, $total_amount, $currency) . '

				        </td>
				        <td>

				            ' . date('l M d, Y H:s:i', $row['timestamp']) . '

				        </td>
				        <td>
				            ' .
					$row['year'] . '|' . $row['term'] . '

    </td>
    <td>
        ' . $issuer_name . '

    </td>
    <td>

        <div class="btn-group">
            <button type="button" class="btn btn-info btn-sm dropdown-toggle" data-toggle="dropdown">
                Action <span class="caret"></span>
            </button>
            <ul class="dropdown-menu dropdown-default pull-right" role="menu">

                <!-- STUDENT PROFILE LINK -->
                <li>
                    <a href="#" style="color: #0029ff;" onclick="view_receipts_modal(' . $row['student_id'] . ',' . $param2 . ', \'Transport Fare\')">
                        <i class="entypo-credit-card"></i>
                            ' . get_phrase('view_student\'s_receipt') . '
                        </a>
                </li>
                <li class="divider"></li>

                <!-- SMS LINK -->
                <li>
                    <a href="' . site_url('admin/message/sms_send?si=' . $row['student_id']) . '" class="pt_link" onclick="check_sms_status()" style="color: green;"><i class="glyphicon glyphicon-envelope"></i>
                        ' . get_phrase('send_sMS') . '
                    </a>
                </li>
                <li class="divider"></li>
            </ul>
        </div>

    </td>
</tr>';

					//let's reset the total amount value to 0
					$total_amount = 0;
				endforeach;
			}
		}

	}

	//by term and semester on modal
	function get_receipt_byterm_modal($param2, $param3, $param4) {
		$currency = $this->db->get_where('settings', array('type' => 'currency'))->row()->description;
		$fmt = new NumberFormatter('en_US', NumberFormatter::CURRENCY);
		$active_sms_service = $this->db->get_where('settings', array('type' => 'active_sms_service'))->row()->description;

		// Get receipt totals first
		$this->db->select('receipt_code, SUM(amount) as total_amount');
		$this->db->where('invoice_id IS NOT NULL');
		$this->db->where('year', $param2);
		$this->db->where('term', $param3);
		$this->db->group_by('receipt_code');
		$totals = $this->db->get('payment')->result_array();

		$receipt_totals = array();
		foreach($totals as $t) {
			$receipt_totals[$t['receipt_code']] = $t['total_amount'];
		}

		// Get receipt details with JOINs
		$this->db->select('p.receipt_code, p.student_id, p.year, p.term, p.timestamp, p.account_type,
						   s.student_code, s.name as student_name, s.sex,
						   a.name as issuer_name,
						   e.class_id,
						   c.name as class_name, c.name_numeric,
						   sec.name as section_name');
		$this->db->from('payment p');
		$this->db->join('student s', 'p.student_id = s.student_id');
		$this->db->join('admin a', 'p.issuer_id = a.admin_id', 'left');
		$this->db->join('enroll e', 'p.student_id = e.student_id AND e.mute = "0"', 'left');
		$this->db->join('class c', 'e.class_id = c.class_id', 'left');
		$this->db->join('section sec', 'e.class_id = sec.class_id', 'left');
		$this->db->where('p.invoice_id IS NOT NULL');
		$this->db->where('p.year', $param2);
		$this->db->where('p.term', $param3);
		$this->db->group_by('p.receipt_code');
		$this->db->order_by('p.timestamp', 'DESC');
		$receipts = $this->db->get()->result_array();

		if (empty($receipts)) {
			echo '<tr><td align="center" colspan="10"><h4>No Data Found</h4></td></tr>';
		} else {
			$class_counts = array();
			foreach($receipts as $r) {
				if($r['class_name']) {
					$key = $r['class_name'].'_'.$r['name_numeric'];
					$class_counts[$key] = isset($class_counts[$key]) ? $class_counts[$key] + 1 : 1;
				}
			}

			foreach ($receipts as $row):
				$total_amt = isset($receipt_totals[$row['receipt_code']]) ? $receipt_totals[$row['receipt_code']] : 0;
				$gender = $this->db->get_where('student', array('student_id' => $row['student_id']))->row()->sex;

				//who issued the receipt
				$issuer_id = $this->db->get_where('payment', array('receipt_code' => $row['receipt_code'], 'student_id' => $row['student_id']))->row()->issuer_id;

				$issuer_name = $this->db->get_where('admin', array('admin_id' => $issuer_id))->row()->name;
				$account_type = $this->db->get_where('payment', array('receipt_code' => $row['receipt_code'], 'student_id' => $row['student_id']))->row()->account_type;

				if (!empty($issuer_name) && !empty($account_type)) {
					$issuer_name = $issuer_name . '<small>(' . $account_type . ')</small>';
				} else {
					$issuer_name = 'Not Available';
				}

				//add section A or B if the class has more than one section
				$class_id = $this->db->get_where('enroll', array('student_id' => $row['student_id'], 'mute' => '0'))->row()->class_id;
				$class_name = $this->db->get_where('class', array('class_id' => $class_id))->row()->name;
				$class_name_numeric = $this->db->get_where('class', array('class_id' => $class_id))->row()->name_numeric;
				$section_name = $this->db->get_where('section', array('class_id' => $class_id))->row()->name;
				$class_has_more_sections = $this->db->get_where('class', array('name' => $class_name, 'name_numeric' => $class_name_numeric))->num_rows();
				$sec_name = '';
				if ($class_has_more_sections > 1) {
					$sec_name = $section_name;
				}

				$class = '';
				if ($class_name == 'CRECHE') {
					$class = $class_name;
				} else {
					$class = $class_name . ' ' . $class_name_numeric . $sec_name;
				}

					echo '<tr>
					<td>' . $row['student_code'] . '</td>
					<td align="center"><img src="' . $this->crud_model->get_image_url('student', $row['student_id'], $row['sex']) . '" class="img-circle" width="30" /></td>
					<td>' . $row['student_name'] . '</td>
					<td>' . $class . '</td>
					<td>' . $row['receipt_code'] . '</td>
					<td style="text-align: right">' . numfmt_format_currency($fmt, $total_amt, $currency) . '</td>
					<td>' . date('l M d, Y H:i:s', $row['timestamp']) . '</td>
					<td>' . $row['year'] . '|' . $row['term'] . '</td>
					<td>' . $issuer_name . '</td>
					<td>

                <div class="btn-group">
                    <button type="button" class="btn btn-info btn-sm dropdown-toggle" data-toggle="dropdown">
                        Action <span class="caret"></span>
                    </button>
                    <ul class="dropdown-menu dropdown-default pull-right" role="menu">

                        <!-- STUDENT PROFILE LINK -->
                        <li>
                            <a href="#" style="color: #0029ff;" onclick="view_receipts_modal(' . $row['student_id'] . ',\'' . $param2 . '\',\'' . $param3 . '\')">
                                <i class="entypo-credit-card"></i>
                                    ' . get_phrase('view_student\'s_receipt') . '
                                </a>
                        </li>
                        <li class="divider"></li>

                        <!-- SMS LINK -->
                        <li>
                            <a href="' . site_url('admin/message/sms_send?si=' . $row['student_id']) . '" class="pt_link" onclick="check_sms_status()" style="color: green;"><i class="glyphicon glyphicon-envelope"></i>
                                ' . get_phrase('send_sMS') . '
                            </a>
                        </li>
                        <li class="divider"></li>
                    </ul>
                </div>

            </td>
        </tr>';
			endforeach;
		}

	}

	/*----------------------------- MUTED STUDENTS -------------------------------*/

	function all_muted_students_count() {
		$this->db->where('mute', '1');
		$query = $this->db->get('student');
		return $query->num_rows();
	}

	function all_muted_students($limit, $start, $col, $dir) {
		$query = $this
			->db
			->where('mute', '1')
			->limit($limit, $start)
			->order_by($col, $dir)
			->get('student');

		if ($query->num_rows() > 0) {
			return $query->result();
		} else {
			return null;
		}

	}

	function muted_student_search($limit, $start, $search, $col, $dir) {
		$query = $this
			->db
			->where('mute', '1')
			->like('name', $search)
			->limit($limit, $start)
			->order_by($col, $dir)
			->get('student');

		if ($query->num_rows() > 0) {
			return $query->result();
		} else {
			return null;
		}

	}

	function muted_student_search_count($search) {
		$query = $this
			->db
			->where('mute', '1')
			->like('name', $search)
			->get('student');

		return $query->num_rows();
	}

	/*----------------------------- OLD STUDENTS / ALUMNI -------------------------------*/

	function all_old_students_count() {
		$query = $this->db->get('alumni');
		return $query->num_rows();
	}

	function all_old_students($limit, $start, $col, $dir) {
		$query = $this
			->db
			->limit($limit, $start)
			->order_by($col, $dir)
			->get('alumni');

		if ($query->num_rows() > 0) {
			return $query->result();
		} else {
			return null;
		}

	}

	function old_student_search($limit, $start, $search, $col, $dir) {
		$query = $this
			->db
			->like('name', $search)
			->limit($limit, $start)
			->order_by($col, $dir)
			->join('student', 'student.student_id = alumni.student_id')
			->get('alumni');

		if ($query->num_rows() > 0) {
			return $query->result();
		} else {
			return null;
		}

	}

	function old_student_search_count($search) {
		$query = $this
			->db
			->like('name', $search)
			->join('student', 'student.student_id = alumni.student_id')
			->get('alumni');

		return $query->num_rows();
	}

	/*----------------------------- NON-TEACHING STAFF -------------------------------*/
	function all_non_teaching_staff_count() {
		$query = $this->db->get('non_teaching_staff');
		return $query->num_rows();
	}

	function all_non_teaching_staff($limit, $start, $col, $dir) {
		$query = $this
			->db
			->limit($limit, $start)
			->order_by($col, $dir)
			->get('non_teaching_staff');

		if ($query->num_rows() > 0) {
			return $query->result();
		} else {
			return null;
		}
	}

	function non_teaching_staff_search($limit, $start, $search, $col, $dir) {
		$query = $this
			->db
			->like('staff_code', $search)
			->or_like('name', $search)
			->or_like('email', $search)
			->or_like('phone', $search)
			->or_like('department', $search)
			->or_like('position', $search)
			->limit($limit, $start)
			->order_by($col, $dir)
			->get('non_teaching_staff');

		if ($query->num_rows() > 0) {
			return $query->result();
		} else {
			return null;
		}
	}

	function non_teaching_staff_search_count($search) {
		$query = $this
			->db
			->like('staff_code', $search)
			->or_like('name', $search)
			->or_like('email', $search)
			->or_like('phone', $search)
			->or_like('department', $search)
			->or_like('position', $search)
			->get('non_teaching_staff');

		return $query->num_rows();
	}
}