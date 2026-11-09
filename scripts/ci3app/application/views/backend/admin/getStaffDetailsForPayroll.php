<div class="border-t pt-4">
    <div class="space-y-3 divide-y-2 divide-gray-300">
        <?php
            // Null-safety check for $ids - Fix count() on null error
            if (!is_array($ids) || empty($ids)) {
                echo '<div class="text-sm text-gray-500 italic">No staff selected</div>';
                return;
            }
            
            // Task 17.1: Optimize staff selection query - Build payroll records array for batch query
            $payroll_records = [];
            $ids_count = count($ids);
            for($i = 0; $i < $ids_count; $i++) {
                $stringPos = strpos($ids[$i], '_');
                $table = substr($ids[$i], 0, $stringPos);
                $staff_code = substr($ids[$i], $stringPos + 1);
                
                $payroll_records[] = [
                    'employment_category' => $table,
                    'employee_code' => $staff_code
                ];
            }
            
            // Task 17.1: Load all staff info in single batch query to avoid N+1 problem
            $staff_info_map = $this->crud_model->get_staff_info_batch($payroll_records);
            
            // Now loop through and display with optimized data
            for($i = 0; $i < $ids_count; $i++):
                $stringPos = strpos($ids[$i], '_');
                $table = substr($ids[$i], 0, $stringPos);
                $staff_code = substr($ids[$i], $stringPos + 1);
                
                // Get staff info from batch query result
                $staff_key = $table . '_' . $staff_code;
                $staffRow = isset($staff_info_map[$staff_key]) ? $staff_info_map[$staff_key] : null;
                
                if ($staffRow === null) {
                    continue; // Skip if staff not found
                }
                
                // Get staff name and escape HTML to prevent injection and parsing errors
                $staff_name = isset($staffRow->name) ? htmlspecialchars($staffRow->name, ENT_QUOTES, 'UTF-8') : '';
        ?>
        <div class="flex justify-between">
            <span class="text-sm text-gray-600">Name:</span>
            <span id="teacherName" class="text-sm font-medium"><?php echo $staff_name; ?></span>
        </div>
        <?php
            endfor;
        ?>
    </div>
</div>