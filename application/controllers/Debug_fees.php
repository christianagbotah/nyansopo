<?php
/**
 * DEBUG SCRIPT: Breakfast & Water Charging
 * Place this in: application/controllers/Debug_fees.php
 * Access via: /debug_fees/check_charging/[student_id]
 */

defined('BASEPATH') OR exit('No direct script access allowed');

class Debug_fees extends CI_Controller {
    
    public function check_charging($student_id) {
        
        $running_year = get_settings('running_year');
        $running_term = get_settings('running_term');
        
        echo "<h1>Breakfast & Water Charging Debug</h1>";
        echo "<h2>Student ID: $student_id</h2>";
        
        // 1. Get student info
        $student = $this->db->query("
            SELECT s.*, e.class_id, c.name as class_name
            FROM student s
            JOIN enroll e ON s.student_id = e.student_id
            JOIN class c ON e.class_id = c.class_id
            WHERE s.student_id = ? AND e.year = ? AND e.term = ?
        ", [$student_id, $running_year, $running_term])->row_array();
        
        echo "<h3>1. Student Info</h3>";
        echo "<pre>";
        print_r($student);
        echo "</pre>";
        
        // 2. Get rates
        $rates = $this->Daily_fee_model->get_class_rates($student['class_id'], $running_year, $running_term);
        echo "<h3>2. Class Rates</h3>";
        echo "<pre>";
        print_r($rates);
        echo "</pre>";
        
        // 3. Get preferences
        $prefs = $this->Daily_fee_model->get_student_preferences($student_id);
        echo "<h3>3. Student Preferences</h3>";
        echo "<pre>";
        print_r($prefs);
        echo "</pre>";
        
        // 4. Get wallet
        $wallet = $this->Daily_fee_model->get_student_wallet($student_id);
        echo "<h3>4. Student Wallet</h3>";
        echo "<pre>";
        print_r($wallet);
        echo "</pre>";
        
        // 5. Check modules
        echo "<h3>5. Module Status</h3>";
        echo "Breakfast module enabled: " . (is_fee_module_enabled('breakfast') ? 'YES' : 'NO') . "<br>";
        echo "Water module enabled: " . (is_fee_module_enabled('water') ? 'YES' : 'NO') . "<br>";
        
        // 6. Check breakfast charging conditions
        echo "<h3>6. Breakfast Charging Conditions</h3>";
        $b1 = is_fee_module_enabled('breakfast');
        $b2 = $rates['breakfast_rate'] > 0;
        $b3 = $prefs['breakfast_subscribed'];
        
        echo "Module enabled: " . ($b1 ? '✅ YES' : '❌ NO') . "<br>";
        echo "Rate > 0: " . ($b2 ? "✅ YES ({$rates['breakfast_rate']})" : '❌ NO') . "<br>";
        echo "Student subscribed: " . ($b3 ? '✅ YES' : '❌ NO') . "<br>";
        echo "<strong>WILL CHARGE: " . ($b1 && $b2 && $b3 ? '✅ YES' : '❌ NO') . "</strong><br>";
        
        // 7. Check water charging conditions
        echo "<h3>7. Water Charging Conditions</h3>";
        $w1 = is_fee_module_enabled('water');
        $w2 = $rates['water_rate'] > 0;
        $w3 = $prefs['water_subscribed'];
        
        echo "Module enabled: " . ($w1 ? '✅ YES' : '❌ NO') . "<br>";
        echo "Rate > 0: " . ($w2 ? "✅ YES ({$rates['water_rate']})" : '❌ NO') . "<br>";
        echo "Student subscribed: " . ($w3 ? '✅ YES' : '❌ NO') . "<br>";
        echo "<strong>WILL CHARGE: " . ($w1 && $w2 && $w3 ? '✅ YES' : '❌ NO') . "</strong><br>";
        
        // 8. Check last charge log
        $last_charge = $this->db->where('student_id', $student_id)
            ->order_by('charged_at', 'DESC')
            ->limit(1)
            ->get('daily_charge_log')
            ->row_array();
        
        echo "<h3>8. Last Charge Log</h3>";
        if ($last_charge) {
            echo "<pre>";
            print_r($last_charge);
            echo "</pre>";
        } else {
            echo "No charges found<br>";
        }
        
        // 9. Simulate charging
        echo "<h3>9. Simulate Charging</h3>";
        if ($b1 && $b2 && $b3) {
            $amount = $rates['breakfast_rate'];
            $balance = $wallet['breakfast_balance'];
            $arrears = $wallet['breakfast_arrears'];
            
            echo "Breakfast: Would charge GHS $amount<br>";
            echo "Current balance: GHS $balance<br>";
            echo "Current arrears: GHS $arrears<br>";
            
            if ($balance >= $amount) {
                echo "Result: Deduct GHS $amount from balance<br>";
                echo "New balance: GHS " . ($balance - $amount) . "<br>";
            } else {
                echo "Result: Add GHS $amount to arrears<br>";
                echo "New arrears: GHS " . ($arrears + $amount) . "<br>";
            }
        } else {
            echo "Breakfast: Would NOT charge (conditions not met)<br>";
        }
        
        if ($w1 && $w2 && $w3) {
            $amount = $rates['water_rate'];
            $balance = $wallet['water_balance'];
            $arrears = $wallet['water_arrears'];
            
            echo "<br>Water: Would charge GHS $amount<br>";
            echo "Current balance: GHS $balance<br>";
            echo "Current arrears: GHS $arrears<br>";
            
            if ($balance >= $amount) {
                echo "Result: Deduct GHS $amount from balance<br>";
                echo "New balance: GHS " . ($balance - $amount) . "<br>";
            } else {
                echo "Result: Add GHS $amount to arrears<br>";
                echo "New arrears: GHS " . ($arrears + $amount) . "<br>";
            }
        } else {
            echo "Water: Would NOT charge (conditions not met)<br>";
        }
    }
}
