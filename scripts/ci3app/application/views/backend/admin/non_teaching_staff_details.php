<style>
/* Modern Staff Details Styling */
.staff-container { max-width: 1400px; margin: 0 auto; padding: 20px; }

/* Profile Header */
.staff-profile-header { 
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); 
    border-radius: 16px; 
    overflow: hidden; 
    box-shadow: 0 10px 40px rgba(102, 126, 234, 0.3); 
    margin-bottom: 30px;
}
.staff-banner { height: 120px; background: linear-gradient(135deg, rgba(255,255,255,0.1) 0%, rgba(255,255,255,0) 100%); }
.staff-profile-content { padding: 0 30px 30px; }
.staff-profile-main { display: flex; flex-wrap: wrap; align-items: flex-end; justify-content: space-between; margin-top: -60px; gap: 20px; }
.staff-profile-left { display: flex; align-items: flex-end; gap: 20px; flex: 1; min-width: 300px; }

/* Avatar */
.staff-avatar { 
    width: 120px; 
    height: 120px; 
    border-radius: 50%; 
    border: 5px solid white; 
    box-shadow: 0 8px 24px rgba(0,0,0,0.15); 
    object-fit: cover;
    background: white;
}

/* Staff Info */
.staff-info { flex: 1; }
.staff-name { font-size: 28px; font-weight: 700; color: white; margin: 0 0 8px; }
.staff-code { font-size: 16px; color: rgba(255,255,255,0.9); margin-bottom: 12px; }
.staff-badges { display: flex; gap: 8px; flex-wrap: wrap; }
.staff-badge { 
    padding: 6px 14px; 
    border-radius: 20px; 
    font-size: 13px; 
    font-weight: 600; 
    background: rgba(255,255,255,0.25); 
    color: white; 
    backdrop-filter: blur(10px);
}

/* Action Button */
.staff-edit-btn { 
    padding: 12px 24px; 
    background: white; 
    color: #667eea; 
    border: none; 
    border-radius: 10px; 
    font-size: 15px; 
    font-weight: 600; 
    cursor: pointer; 
    transition: all 0.3s; 
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
}
.staff-edit-btn:hover { transform: translateY(-2px); box-shadow: 0 6px 20px rgba(0,0,0,0.15); }
.staff-edit-btn i { margin-right: 8px; }

/* Quick Info Cards */
.staff-quick-info { 
    display: grid; 
    grid-template-columns: repeat(2, 1fr); 
    gap: 20px; 
    margin-top: 25px; 
}
.staff-quick-card { 
    background: rgba(255,255,255,0.15); 
    backdrop-filter: blur(10px); 
    padding: 20px; 
    border-radius: 12px; 
    border: 1px solid rgba(255,255,255,0.2);
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 20px;
}
.staff-quick-item { 
    display: flex;
    flex-direction: column;
    gap: 6px;
}
.staff-quick-label { 
    font-size: 11px; 
    color: rgba(255,255,255,0.75); 
    text-transform: uppercase; 
    letter-spacing: 0.5px; 
    font-weight: 600;
}
.staff-quick-value { 
    font-size: 15px; 
    color: white; 
    font-weight: 600; 
    word-break: break-word; 
    line-height: 1.4;
}

/* Tabs */
.staff-tabs { 
    display: flex; 
    gap: 8px; 
    border-bottom: 2px solid #e5e7eb; 
    margin-bottom: 30px; 
    flex-wrap: wrap;
    position: sticky;
    top: 0;
    background: white;
    z-index: 100;
    padding: 10px 0;
}
.staff-tab { 
    padding: 14px 28px; 
    background: transparent; 
    border: none; 
    border-bottom: 3px solid transparent; 
    cursor: pointer; 
    font-size: 15px; 
    font-weight: 600; 
    color: #6b7280; 
    transition: all 0.3s;
    border-radius: 8px 8px 0 0;
}
.staff-tab:hover { color: #667eea; background: #f9fafb; }
.staff-tab.active { color: #667eea; border-bottom-color: #667eea; background: #f9fafb; }
.staff-tab i { margin-right: 8px; }

/* Tab Content */
.staff-tab-content { display: none; }
.staff-tab-content.active { display: block; animation: fadeIn 0.3s; }

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}

/* Detail Cards */
.staff-detail-card { 
    background: white; 
    border-radius: 16px; 
    padding: 30px; 
    box-shadow: 0 2px 8px rgba(0,0,0,0.08); 
    margin-bottom: 24px;
}
.staff-detail-title { 
    font-size: 20px; 
    font-weight: 700; 
    color: #1f2937; 
    margin-bottom: 24px; 
    padding-bottom: 12px; 
    border-bottom: 3px solid #f3f4f6;
    display: flex;
    align-items: center;
    gap: 10px;
}
.staff-detail-title i { color: #667eea; }

/* Detail Grid */
.staff-detail-grid { 
    display: grid; 
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); 
    gap: 30px; 
}
.staff-detail-section { }
.staff-detail-row { 
    display: flex; 
    justify-content: space-between; 
    align-items: flex-start;
    padding: 16px 0; 
    border-bottom: 1px solid #f3f4f6;
    gap: 20px;
}
.staff-detail-row:last-child { border-bottom: none; }
.staff-detail-label { 
    font-size: 14px; 
    font-weight: 600; 
    color: #6b7280; 
    min-width: 140px;
}
.staff-detail-value { 
    font-size: 15px; 
    color: #1f2937; 
    font-weight: 500; 
    text-align: right;
    flex: 1;
    word-break: break-word;
}

/* Payroll Table */
.staff-payroll-container { 
    background: white; 
    border-radius: 16px; 
    overflow: hidden; 
    box-shadow: 0 2px 8px rgba(0,0,0,0.08); 
}
.staff-payroll-header { 
    padding: 24px 30px; 
    background: linear-gradient(135deg, #f9fafb 0%, #f3f4f6 100%); 
    border-bottom: 2px solid #e5e7eb;
}
.staff-payroll-title { 
    font-size: 20px; 
    font-weight: 700; 
    color: #1f2937; 
    margin: 0;
    display: flex;
    align-items: center;
    gap: 10px;
}
.staff-payroll-title i { color: #667eea; }
.staff-payroll-table-wrapper { padding: 20px; overflow-x: auto; }
.staff-payroll-table { width: 100%; border-collapse: separate; border-spacing: 0; }
.staff-payroll-table thead th { 
    background: #f9fafb; 
    padding: 14px 16px; 
    text-align: left; 
    font-size: 13px; 
    font-weight: 700; 
    color: #374151; 
    text-transform: uppercase; 
    letter-spacing: 0.5px;
    border-bottom: 2px solid #e5e7eb;
    white-space: nowrap;
}
.staff-payroll-table thead th.text-right { text-align: right; }
.staff-payroll-table thead th.text-center { text-align: center; }
.staff-payroll-table tbody td { 
    padding: 16px; 
    border-bottom: 1px solid #f3f4f6; 
    font-size: 14px; 
    color: #4b5563;
}
.staff-payroll-table tbody tr:hover { background: #f9fafb; }
.staff-payroll-table tbody td.text-right { text-align: right; font-weight: 600; color: #1f2937; }
.staff-payroll-table tbody td.text-center { text-align: center; }
.staff-payroll-link { color: #667eea; font-weight: 600; text-decoration: none; }
.staff-payroll-link:hover { text-decoration: underline; }
.staff-payroll-btn { 
    padding: 8px 16px; 
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); 
    color: white; 
    border: none; 
    border-radius: 8px; 
    font-size: 13px; 
    font-weight: 600; 
    cursor: pointer; 
    transition: all 0.3s;
    text-decoration: none;
    display: inline-block;
}
.staff-payroll-btn:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4); }

/* Empty State */
.staff-empty-state { 
    text-align: center; 
    padding: 60px 20px; 
    color: #9ca3af; 
}
.staff-empty-state i { font-size: 64px; margin-bottom: 20px; opacity: 0.5; }
.staff-empty-state p { font-size: 16px; margin: 0; }

/* Responsive */
@media (max-width: 768px) {
    .staff-container { padding: 15px; }
    .staff-profile-content { padding: 0 20px 20px; }
    .staff-profile-main { margin-top: -50px; }
    .staff-profile-left { flex-direction: column; align-items: center; text-align: center; }
    .staff-avatar { width: 100px; height: 100px; }
    .staff-name { font-size: 22px; }
    .staff-quick-info { grid-template-columns: 1fr; }
    .staff-quick-card { grid-template-columns: 1fr; gap: 15px; }
    .staff-tabs { overflow-x: auto; }
    .staff-tab { white-space: nowrap; padding: 12px 20px; font-size: 14px; }
    .staff-detail-card { padding: 20px; }
    .staff-detail-grid { grid-template-columns: 1fr; gap: 20px; }
    .staff-detail-row { flex-direction: column; gap: 8px; }
    .staff-detail-label { min-width: auto; }
    .staff-detail-value { text-align: left; }
    .staff-payroll-header { padding: 20px; }
    .staff-payroll-table-wrapper { padding: 15px; }
    .staff-edit-btn { width: 100%; }
}
</style>

<div class="staff-container">
    <!-- Profile Header -->
    <div class="staff-profile-header">
        <div class="staff-banner"></div>
        <div class="staff-profile-content">
            <div class="staff-profile-main">
                <div class="staff-profile-left">
                    <?php
                    // Generate initials for avatar
                    $name_parts = explode(' ', $staffInfoData->name);
                    $initials = substr($name_parts[0], 0, 1);
                    if (isset($name_parts[1])) {
                        $initials .= substr($name_parts[1], 0, 1);
                    }
                    $initials = strtoupper($initials);
                    
                    // Non-teaching staff uses staff_code and staff_id
                    $staff_code = $staffInfoData->staff_code;
                    $staff_id = $staffInfoData->staff_id;
                    $staff_type = 'non_teaching_staff';
                    
                    // Format date of birth (hide if invalid)
                    $dob_display = '';
                    if (!empty($staffInfoData->birthday)) {
                        $dob_timestamp = is_numeric($staffInfoData->birthday) ? $staffInfoData->birthday : strtotime($staffInfoData->birthday);
                        // Only display if it's a valid date after Jan 2, 1970 (timestamp > 86400)
                        if ($dob_timestamp && $dob_timestamp > 86400) {
                            $dob_display = date('d M, Y', $dob_timestamp);
                        }
                    }
                    
                    // Get role/portfolio from designation field
                    $staff_role = !empty($staffInfoData->designation) ? $staffInfoData->designation : 'Staff Member';
                    $role_icon = 'fa-user-tie';
                    
                    // Check if role is driver and get vehicle assignment
                    $is_driver = (stripos($staff_role, 'driver') !== false);
                    $assigned_vehicles = array();
                    if ($is_driver) {
                        $role_icon = 'fa-bus';
                        // Query for assigned vehicles from transport table - get distinct vehicle-route combinations
                        $this->db->select('transport_id, number_of_vehicle, route_name');
                        $this->db->from('transport t');
                        $this->db->where('t.driver_id', $staff_id);
                        $this->db->group_by(array('number_of_vehicle', 'route_name'));
                        $this->db->order_by('number_of_vehicle', 'ASC');
                        $vehicle_query = $this->db->get();
                        if ($vehicle_query->num_rows() > 0) {
                            $assigned_vehicles = $vehicle_query->result_array();
                        }
                    }
                    
                    // Count unique vehicles for badge display
                    $unique_vehicles = array();
                    if ($is_driver && !empty($assigned_vehicles)) {
                        foreach ($assigned_vehicles as $v) {
                            $unique_vehicles[$v['number_of_vehicle']] = true;
                        }
                    }
                    $vehicle_count = count($unique_vehicles);
                    
                    // Check for uploaded profile image
                    $image_path = 'uploads/admin_image/' . $staff_id . '.jpg';
                    $has_uploaded_image = file_exists($image_path);
                    
                    if ($has_uploaded_image) {
                        $avatar_src = base_url($image_path);
                    } else {
                        $avatar_src = "data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='120' height='120'%3E%3Crect width='120' height='120' fill='%234F46E5'/%3E%3Ctext x='50%25' y='50%25' dominant-baseline='middle' text-anchor='middle' font-size='42' font-weight='bold' fill='white' font-family='Arial'%3E{$initials}%3C/text%3E%3C/svg%3E";
                    }
                    ?>
                    <img class="staff-avatar" 
                         src="<?= $avatar_src; ?>" 
                         alt="<?= htmlspecialchars($staffInfoData->name); ?>">
                    <div class="staff-info">
                        <h1 class="staff-name"><?= htmlspecialchars($staffInfoData->name); ?></h1>
                        <p class="staff-code"><i class="fa fa-id-badge"></i> <?= htmlspecialchars($staff_code); ?></p>
                        <div class="staff-badges">
                            <span class="staff-badge"><i class="fa <?= $role_icon; ?>"></i> <?= htmlspecialchars($staff_role); ?></span>
                            <?php if ($is_driver && $vehicle_count > 0): ?>
                            <span class="staff-badge"><i class="fa fa-car"></i> <?= $vehicle_count; ?> Vehicle<?= $vehicle_count > 1 ? 's' : ''; ?> Assigned</span>
                            <?php endif; ?>
                            <span class="staff-badge"><i class="fa fa-check-circle"></i> Active</span>
                        </div>
                    </div>
                </div>
                <button type="button" onclick="showAjaxModal('<?= site_url('modal/popup/modal_non_teaching_staff_edit/'.$staff_id); ?>');" class="staff-edit-btn">
                    <i class="fa fa-edit"></i>Edit Profile
                </button>
            </div>

            <!-- Quick Info Cards -->
            <div class="staff-quick-info">
                <!-- Left Card: Contact & Personal -->
                <div class="staff-quick-card">
                    <div class="staff-quick-item">
                        <div class="staff-quick-label"><i class="fa fa-envelope"></i> Email</div>
                        <div class="staff-quick-value"><?= htmlspecialchars(strtolower($staffInfoData->email)); ?></div>
                    </div>
                    <div class="staff-quick-item">
                        <div class="staff-quick-label"><i class="fa fa-phone"></i> Phone</div>
                        <div class="staff-quick-value"><?= htmlspecialchars($staffInfoData->phone); ?></div>
                    </div>
                    <div class="staff-quick-item">
                        <div class="staff-quick-label"><i class="fa fa-venus-mars"></i> Gender</div>
                        <div class="staff-quick-value"><?= htmlspecialchars(ucfirst($staffInfoData->sex)); ?></div>
                    </div>
                    <?php if (!empty($dob_display)): ?>
                    <div class="staff-quick-item">
                        <div class="staff-quick-label"><i class="fa fa-birthday-cake"></i> Date of Birth</div>
                        <div class="staff-quick-value"><?= $dob_display; ?></div>
                    </div>
                    <?php endif; ?>
                </div>
                
                <!-- Right Card: Professional -->
                <div class="staff-quick-card">
                    <div class="staff-quick-item">
                        <div class="staff-quick-label"><i class="fa <?= $role_icon; ?>"></i> Role/Portfolio</div>
                        <div class="staff-quick-value"><?= htmlspecialchars($staff_role); ?></div>
                    </div>
                    <div class="staff-quick-item">
                        <div class="staff-quick-label"><i class="fa fa-id-card"></i> Employee ID</div>
                        <div class="staff-quick-value"><?= htmlspecialchars($staffInfoData->staff_code); ?></div>
                    </div>
                    <div class="staff-quick-item">
                        <div class="staff-quick-label"><i class="fa fa-shield"></i> SSNIT ID</div>
                        <div class="staff-quick-value"><?= htmlspecialchars($staffInfoData->ssnit_id); ?></div>
                    </div>
                    <?php if ($is_driver && $vehicle_count > 0): 
                        // Get comma-separated list of unique vehicle numbers
                        $vehicle_numbers = implode(', ', array_keys($unique_vehicles));
                    ?>
                    <div class="staff-quick-item">
                        <div class="staff-quick-label"><i class="fa fa-bus"></i> Assigned Vehicles</div>
                        <div class="staff-quick-value"><?= $vehicle_count; ?> vehicle<?= $vehicle_count > 1 ? 's' : ''; ?> (<?= htmlspecialchars($vehicle_numbers); ?>)</div>
                    </div>
                    <?php elseif (!empty($staffInfoData->provider_name)): ?>
                    <div class="staff-quick-item">
                        <div class="staff-quick-label"><i class="fa fa-university"></i> Tier 2 Provider</div>
                        <div class="staff-quick-value"><?= htmlspecialchars($staffInfoData->provider_name); ?></div>
                    </div>
                    <?php else: ?>
                    <div class="staff-quick-item">
                        <div class="staff-quick-label"><i class="fa fa-university"></i> Account Number</div>
                        <div class="staff-quick-value"><?= htmlspecialchars($staffInfoData->account_number); ?></div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Tabs -->
    <div class="staff-tabs">
        <button class="staff-tab active" onclick="switchStaffTab('basic')" id="tab-basic">
            <i class="fa fa-user"></i>Basic Information
        </button>
        <button class="staff-tab" onclick="switchStaffTab('payroll')" id="tab-payroll">
            <i class="fa fa-dollar"></i>Payroll History
        </button>
    </div>

    <!-- Tab Content -->
    <div id="content-basic" class="staff-tab-content active">
        <div class="staff-detail-grid">
            <!-- Personal Details -->
            <div class="staff-detail-section">
                <div class="staff-detail-card">
                    <h3 class="staff-detail-title"><i class="fa fa-user-circle"></i>Personal Details</h3>
                    <div class="staff-detail-row">
                        <span class="staff-detail-label">Full Name</span>
                        <span class="staff-detail-value"><?= htmlspecialchars($staffInfoData->name); ?></span>
                    </div>
                    <?php if (!empty($dob_display)): ?>
                    <div class="staff-detail-row">
                        <span class="staff-detail-label">Date of Birth</span>
                        <span class="staff-detail-value"><?= $dob_display; ?></span>
                    </div>
                    <?php endif; ?>
                    <div class="staff-detail-row">
                        <span class="staff-detail-label">Gender</span>
                        <span class="staff-detail-value"><?= htmlspecialchars(ucfirst($staffInfoData->sex)); ?></span>
                    </div>
                    <div class="staff-detail-row">
                        <span class="staff-detail-label">Ghana Card ID</span>
                        <span class="staff-detail-value"><?= htmlspecialchars($staffInfoData->ghana_card_id); ?></span>
                    </div>
                    <div class="staff-detail-row">
                        <span class="staff-detail-label">Address</span>
                        <span class="staff-detail-value"><?= htmlspecialchars($staffInfoData->address); ?></span>
                    </div>
                </div>
            </div>

            <!-- Employment Details -->
            <div class="staff-detail-section">
                <div class="staff-detail-card">
                    <h3 class="staff-detail-title"><i class="fa fa-briefcase"></i>Employment Details</h3>
                    <div class="staff-detail-row">
                        <span class="staff-detail-label">Role/Portfolio</span>
                        <span class="staff-detail-value"><?= htmlspecialchars($staff_role); ?></span>
                    </div>
                    <div class="staff-detail-row">
                        <span class="staff-detail-label">Employee ID</span>
                        <span class="staff-detail-value"><?= htmlspecialchars($staff_code); ?></span>
                    </div>
                    <div class="staff-detail-row">
                        <span class="staff-detail-label">SSNIT ID</span>
                        <span class="staff-detail-value"><?= htmlspecialchars($staffInfoData->ssnit_id); ?></span>
                    </div>
                    <?php if (!empty($staffInfoData->provider_name)): ?>
                    <div class="staff-detail-row">
                        <span class="staff-detail-label">Tier 2 Provider</span>
                        <span class="staff-detail-value"><?= htmlspecialchars($staffInfoData->provider_name); ?></span>
                    </div>
                    <?php endif; ?>
                    <?php if (!empty($staffInfoData->tier2_member_id)): ?>
                    <div class="staff-detail-row">
                        <span class="staff-detail-label">Tier 2 Member ID</span>
                        <span class="staff-detail-value"><?= htmlspecialchars($staffInfoData->tier2_member_id); ?></span>
                    </div>
                    <?php endif; ?>
                    <?php if (!empty($staffInfoData->account_details)): ?>
                    <div class="staff-detail-row">
                        <span class="staff-detail-label">Bank Details</span>
                        <span class="staff-detail-value"><?= nl2br(htmlspecialchars($staffInfoData->account_details)); ?></span>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- Vehicle Assignment Section (for drivers) -->
        <?php if ($is_driver && count($assigned_vehicles) > 0): ?>
        <div class="staff-detail-card">
            <h3 class="staff-detail-title"><i class="fa fa-bus"></i>Assigned Transport Routes</h3>
            <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 16px;">
                <?php foreach ($assigned_vehicles as $vehicle): ?>
                <a href="<?= site_url('admin/transport_students/'.$vehicle['transport_id']); ?>" 
                   style="text-decoration: none; color: inherit; transition: all 0.3s;">
                    <div style="padding: 20px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border-radius: 12px; color: white; box-shadow: 0 4px 6px rgba(0,0,0,0.1); cursor: pointer; height: 100%;"
                         onmouseover="this.style.transform='translateY(-4px)'; this.style.boxShadow='0 8px 16px rgba(102,126,234,0.3)';"
                         onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 4px 6px rgba(0,0,0,0.1)';">
                        <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 12px;">
                            <div style="width: 48px; height: 48px; background: rgba(255,255,255,0.2); border-radius: 10px; display: flex; align-items: center; justify-content: center;">
                                <i class="fa fa-bus" style="font-size: 24px; color: white;"></i>
                            </div>
                            <div style="flex: 1;">
                                <div style="font-size: 17px; font-weight: 700; color: white; margin-bottom: 4px;">
                                    <?= htmlspecialchars($vehicle['number_of_vehicle']); ?>
                                </div>
                                <div style="font-size: 12px; color: rgba(255,255,255,0.8); text-transform: uppercase; letter-spacing: 0.5px;">
                                    Vehicle
                                </div>
                            </div>
                        </div>
                        <?php if (!empty($vehicle['route_name'])): ?>
                        <div style="padding: 12px; background: rgba(255,255,255,0.15); border-radius: 8px; backdrop-filter: blur(10px);">
                            <div style="font-size: 11px; color: rgba(255,255,255,0.8); text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 4px;">
                                Route
                            </div>
                            <div style="font-size: 14px; color: white; font-weight: 600; display: flex; align-items: center; gap: 6px;">
                                <i class="fa fa-route" style="font-size: 12px;"></i>
                                <span><?= htmlspecialchars($vehicle['route_name']); ?></span>
                            </div>
                        </div>
                        <?php endif; ?>
                        <div style="margin-top: 12px; display: flex; align-items: center; justify-content: flex-end; gap: 6px; font-size: 13px; color: rgba(255,255,255,0.9);">
                            <span>View Students</span>
                            <i class="fa fa-arrow-right"></i>
                        </div>
                    </div>
                </a>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <div id="content-payroll" class="staff-tab-content">
        <div class="staff-payroll-container">
            <div class="staff-payroll-header">
                <h3 class="staff-payroll-title"><i class="fa fa-history"></i>Payment History</h3>
            </div>
            <div class="staff-payroll-table-wrapper">
                <?php if (!empty($staffPayrollData)): ?>
                <table class="staff-payroll-table datatable" id="payrollTable">
                    <thead>
                        <tr>
                            <th>Reference</th>
                            <th>ID No</th>
                            <th>Name</th>
                            <th>Account #</th>
                            <th>Month</th>
                            <th>Year</th>
                            <th class="text-right">Basic Salary</th>
                            <th class="text-right">Allowances</th>
                            <th class="text-right">Gross Salary</th>
                            <th class="text-right">Deductions</th>
                            <th class="text-right">Net Salary</th>
                            <th class="text-center">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        // Task 17.1: Optimize staff selection query with single JOIN
                        // Fetch all staff info in one query instead of N+1 queries
                        $staff_info_map = $this->crud_model->get_staff_info_batch($staffPayrollData);
                        
                        foreach($staffPayrollData as $data): 
                            $staff_key = $data['employment_category'] . '_' . $data['employee_code'];
                            $staffRow = isset($staff_info_map[$staff_key]) ? $staff_info_map[$staff_key] : null;
                            
                            if (!$staffRow) {
                                // Fallback to original method if staff not found
                                $staffRow = $this->crud_model->getStaffInfo($data['employment_category'], $data['employee_code']);
                            }
                            
                            $staffName = ucwords(strtolower($staffRow->name));
                            $staffAccountNumber = $staffRow->account_number;
                        ?>
                        <tr>
                            <td>
                                <a href="<?= site_url('admin/payslip_preview/'.$data['employee_code'].'/'.$data['month'].'/'.$data['year'].'/'.$data['employment_category']) ?>" 
                                   target="_blank" 
                                   class="staff-payroll-link">
                                    <?= htmlspecialchars($data['reference']); ?>
                                </a>
                            </td>
                            <td><?= htmlspecialchars($data['employee_code']); ?></td>
                            <td><?= htmlspecialchars($staffName); ?></td>
                            <td><?= htmlspecialchars($staffAccountNumber); ?></td>
                            <td><?= htmlspecialchars($data['month']); ?></td>
                            <td><?= htmlspecialchars($data['year']); ?></td>
                            <td class="text-right"><?= number_format($data['basic_salary'], 2, '.', ','); ?></td>
                            <td class="text-right"><?= number_format($data['total_allowances'], 2, '.', ','); ?></td>
                            <td class="text-right"><?= number_format($data['gross_salary'], 2, '.', ','); ?></td>
                            <td class="text-right"><?= number_format($data['total_deductions'], 2, '.', ','); ?></td>
                            <td class="text-right"><?= number_format($data['net_salary'], 2, '.', ','); ?></td>
                            <td class="text-center">
                                <a href="<?= site_url('admin/payslip_preview/'.$data['employee_code'].'/'.$data['month'].'/'.$data['year'].'/'.$data['employment_category']) ?>" 
                                   target="_blank" 
                                   class="staff-payroll-btn">
                                    <i class="fa fa-eye"></i> Preview
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php else: ?>
                <div class="staff-empty-state">
                    <i class="fa fa-inbox"></i>
                    <p>No payroll records found for this staff member.</p>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
function switchStaffTab(tabName) {
    // Hide all tab contents
    document.querySelectorAll('.staff-tab-content').forEach(content => {
        content.classList.remove('active');
    });
    
    // Remove active class from all tabs
    document.querySelectorAll('.staff-tab').forEach(tab => {
        tab.classList.remove('active');
    });
    
    // Show selected tab content
    document.getElementById('content-' + tabName).classList.add('active');
    
    // Add active class to selected tab
    document.getElementById('tab-' + tabName).classList.add('active');
}

// Initialize DataTable for payroll if it exists
$(document).ready(function() {
    if ($('#payrollTable').length && $.fn.DataTable) {
        $('#payrollTable').DataTable({
            responsive: true,
            order: [[4, 'desc'], [5, 'desc']], // Sort by month and year descending
            pageLength: 10,
            language: {
                search: "Search payroll:",
                lengthMenu: "Show _MENU_ records per page",
                info: "Showing _START_ to _END_ of _TOTAL_ records",
                infoEmpty: "No records available",
                infoFiltered: "(filtered from _MAX_ total records)",
                paginate: {
                    first: "First",
                    last: "Last",
                    next: "Next",
                    previous: "Previous"
                }
            }
        });
    }
});
</script>
