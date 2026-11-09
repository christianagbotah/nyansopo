<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Payroll Statutory Model
 * 
 * Manages configurable statutory deduction rates (SSNIT, GETFund, NHIL, etc.)
 * Provides methods to get, update, and track changes to statutory percentages
 * 
 * @author Kiro AI
 * @date September 5, 2026
 */
class Payroll_statutory_model extends CI_Model {

	public function __construct() {
		parent::__construct();
	}

	/**
	 * Get all active statutory settings
	 * 
	 * @return array Associative array with setting_key => setting_value
	 */
	public function get_all_settings() {
		$query = $this->db->where('is_active', 1)
			->order_by('setting_id', 'ASC')
			->get('payroll_statutory_settings');
		
		$settings = [];
		foreach($query->result() as $row) {
			$settings[$row->setting_key] = [
				'value' => $row->setting_value,
				'name' => $row->setting_name,
				'type' => $row->setting_type,
				'description' => $row->description
			];
		}
		
		return $settings;
	}

	/**
	 * Get specific statutory setting by key
	 * 
	 * @param string $setting_key The setting identifier
	 * @return decimal|null The setting value or null if not found
	 */
	public function get_setting($setting_key) {
		$row = $this->db->select('setting_value')
			->where('setting_key', $setting_key)
			->where('is_active', 1)
			->get('payroll_statutory_settings')
			->row();
		
		return $row ? $row->setting_value : null;
	}

	/**
	 * Get setting value as decimal (for calculations)
	 * Converts percentage to decimal (e.g., 13.5% becomes 0.135)
	 * 
	 * @param string $setting_key The setting identifier
	 * @param decimal $default Default value if setting not found
	 * @return decimal The setting value as decimal
	 */
	public function get_rate($setting_key, $default = 0) {
		$value = $this->get_setting($setting_key);
		if($value === null) {
			return $default;
		}
		return $value / 100; // Convert percentage to decimal
	}

	/**
	 * Get all settings for management UI
	 * 
	 * @return array Complete setting records
	 */
	public function get_all_settings_full() {
		return $this->db->order_by('setting_id', 'ASC')
			->get('payroll_statutory_settings')
			->result();
	}

	/**
	 * Update a statutory setting
	 * 
	 * @param int $setting_id The setting ID
	 * @param decimal $new_value The new percentage value
	 * @param int $admin_id The admin user ID making the change
	 * @param string $reason Optional reason for change
	 * @return bool Success status
	 */
	public function update_setting($setting_id, $new_value, $admin_id, $reason = '') {
		// Get current value for audit log
		$current = $this->db->select('setting_key, setting_value')
			->where('setting_id', $setting_id)
			->get('payroll_statutory_settings')
			->row();
		
		if(!$current) {
			return false;
		}

		// Start transaction
		$this->db->trans_start();

		// Update setting (updated_at is automatically set by ON UPDATE CURRENT_TIMESTAMP)
		$this->db->where('setting_id', $setting_id);
		$this->db->update('payroll_statutory_settings', [
			'setting_value' => $new_value,
			'updated_by' => $admin_id
		]);

		// Log the change
		$this->db->insert('payroll_statutory_settings_log', [
			'setting_id' => $setting_id,
			'setting_key' => $current->setting_key,
			'old_value' => $current->setting_value,
			'new_value' => $new_value,
			'changed_by' => $admin_id,
			'reason' => $reason
		]);

		// Complete transaction
		$this->db->trans_complete();

		return $this->db->trans_status();
	}

	/**
	 * Toggle active status of a setting
	 * 
	 * @param int $setting_id The setting ID
	 * @param int $admin_id The admin user ID
	 * @return bool Success status
	 */
	public function toggle_active($setting_id, $admin_id) {
		$this->db->set('is_active', 'IF(is_active = 1, 0, 1)', FALSE);
		$this->db->set('updated_by', $admin_id);
		$this->db->where('setting_id', $setting_id);
		$this->db->update('payroll_statutory_settings');
		
		return $this->db->affected_rows() > 0;
	}

	/**
	 * Get audit log for a specific setting
	 * 
	 * @param int $setting_id The setting ID
	 * @param int $limit Number of records to return
	 * @return array Log records
	 */
	public function get_setting_history($setting_id, $limit = 50) {
		$this->db->select('l.*, a.name as admin_name');
		$this->db->from('payroll_statutory_settings_log l');
		$this->db->join('admin a', 'a.admin_id = l.changed_by', 'left');
		$this->db->where('l.setting_id', $setting_id);
		$this->db->order_by('l.changed_at', 'DESC');
		$this->db->limit($limit);
		
		return $this->db->get()->result();
	}

	/**
	 * Calculate statutory deductions based on current settings
	 * 
	 * @param decimal $basic_salary The basic salary amount
	 * @return array Calculated deductions
	 */
	public function calculate_deductions($basic_salary) {
		$settings = $this->get_all_settings();
		
		$deductions = [];
		
		// SSNIT Tier 1 Employer (not deducted from salary, but employer pays)
		if(isset($settings['ssnit_tier1_employer'])) {
			$deductions['ssnit_tier1_employer'] = $basic_salary * ($settings['ssnit_tier1_employer']['value'] / 100);
		}
		
		// SSNIT Tier 1 Employee (deducted from salary)
		if(isset($settings['ssnit_tier1_employee'])) {
			$deductions['ssnit_tier1_employee'] = $basic_salary * ($settings['ssnit_tier1_employee']['value'] / 100);
		}
		
		// GETFund
		if(isset($settings['getfund'])) {
			$deductions['getfund'] = $basic_salary * ($settings['getfund']['value'] / 100);
		}
		
		// NHIL
		if(isset($settings['nhil'])) {
			$deductions['nhil'] = $basic_salary * ($settings['nhil']['value'] / 100);
		}
		
		return $deductions;
	}

	/**
	 * Get statutory rates as JSON for JavaScript
	 * 
	 * @return string JSON encoded rates
	 */
	public function get_rates_json() {
		$settings = $this->get_all_settings();
		
		$rates = [];
		foreach($settings as $key => $data) {
			$rates[$key] = $data['value'];  // Keep as percentage for display
		}
		
		return json_encode($rates);
	}

	/**
	 * Get statutory rates as array (percentages)
	 * 
	 * @return array Associative array with setting_key => percentage_value
	 */
	public function get_rates_array() {
		$settings = $this->get_all_settings();
		
		// Debug: Log what we're getting from database
		if (empty($settings)) {
			log_message('error', 'Payroll_statutory_model::get_rates_array() - No settings found in database!');
		} else {
			log_message('debug', 'Payroll_statutory_model::get_rates_array() - Found ' . count($settings) . ' settings: ' . json_encode(array_keys($settings)));
		}
		
		$rates = [];
		foreach($settings as $key => $data) {
			$rates[$key] = $data['value'];  // Keep as percentage (e.g., 13.5, not 0.135)
		}
		
		return $rates;
	}

	/**
	 * Create a new statutory setting
	 * 
	 * @param array $data Setting data
	 * @return bool|int Setting ID or false on failure
	 */
	public function create_setting($data) {
		$insert_data = [
			'setting_key' => $data['setting_key'],
			'setting_name' => $data['setting_name'],
			'setting_value' => $data['setting_value'],
			'setting_type' => $data['setting_type'] ?? 'percentage',
			'description' => $data['description'] ?? '',
			'is_active' => $data['is_active'] ?? 1,
			'updated_by' => $data['updated_by'] ?? null
		];
		
		$this->db->insert('payroll_statutory_settings', $insert_data);
		
		return $this->db->insert_id();
	}

	/**
	 * Delete a statutory setting (soft delete by setting is_active to 0)
	 * 
	 * @param int $setting_id The setting ID
	 * @return bool Success status
	 */
	public function delete_setting($setting_id) {
		$this->db->where('setting_id', $setting_id);
		$this->db->update('payroll_statutory_settings', ['is_active' => 0]);
		
		return $this->db->affected_rows() > 0;
	}
}
