<!-- Metro UI Mega Menu -->
<style>
.metro-mega-menu {
    background: #1a1a1a;
    padding: 20px 0;
    border-bottom: 1px solid #333;
}

.metro-container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 0 20px;
}

.metro-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 15px;
    margin-top: 20px;
}

.metro-tile {
    background: linear-gradient(135deg, #0078d4, #106ebe);
    color: white;
    padding: 20px;
    text-decoration: none;
    border-radius: 4px;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    min-height: 80px;
}

.metro-tile:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(0,120,212,0.3);
    color: white;
    text-decoration: none;
}

.metro-tile.students { background: linear-gradient(135deg, #0078d4, #106ebe); }
.metro-tile.teachers { background: linear-gradient(135deg, #107c10, #0e6e0e); }
.metro-tile.parents { background: linear-gradient(135deg, #d83b01, #c13a00); }
.metro-tile.academics { background: linear-gradient(135deg, #5c2d91, #4c2579); }
.metro-tile.attendance { background: linear-gradient(135deg, #ff8c00, #e67e00); }
.metro-tile.financial { background: linear-gradient(135deg, #00bcf2, #00a8d6); }
.metro-tile.reports { background: linear-gradient(135deg, #881798, #7a1585); }
.metro-tile.settings { background: linear-gradient(135deg, #767676, #666666); }

.metro-icon {
    font-size: 24px;
    margin-right: 15px;
    opacity: 0.9;
}

.metro-content h4 {
    margin: 0 0 5px 0;
    font-size: 16px;
    font-weight: 600;
}

.metro-content p {
    margin: 0;
    font-size: 12px;
    opacity: 0.8;
}

.metro-header {
    color: #fff;
    text-align: center;
    margin-bottom: 10px;
}

.metro-header h3 {
    margin: 0;
    font-weight: 300;
    font-size: 24px;
}
</style>

<div class="metro-mega-menu">
    <div class="metro-container">
        <div class="metro-header">
            <h3>School Management System</h3>
        </div>
        
        <div class="metro-grid">
            <!-- Students -->
            <a href="<?php echo site_url('admin/student'); ?>" class="metro-tile students">
                <i class="fa fa-users metro-icon"></i>
                <div class="metro-content">
                    <h4>Students</h4>
                    <p>Manage student records</p>
                </div>
            </a>

            <!-- Teachers -->
            <a href="<?php echo site_url('admin/teacher'); ?>" class="metro-tile teachers">
                <i class="fa fa-chalkboard-teacher metro-icon"></i>
                <div class="metro-content">
                    <h4>Teachers</h4>
                    <p>Staff management</p>
                </div>
            </a>

            <!-- Parents -->
            <a href="<?php echo site_url('admin/parent'); ?>" class="metro-tile parents">
                <i class="fa fa-user-friends metro-icon"></i>
                <div class="metro-content">
                    <h4>Parents</h4>
                    <p>Parent portal</p>
                </div>
            </a>

            <!-- Academics -->
            <a href="<?php echo site_url('admin/class'); ?>" class="metro-tile academics">
                <i class="fa fa-graduation-cap metro-icon"></i>
                <div class="metro-content">
                    <h4>Academics</h4>
                    <p>Classes & subjects</p>
                </div>
            </a>

            <!-- Attendance -->
            <a href="<?php echo site_url('attendance_enterprise'); ?>" class="metro-tile attendance">
                <i class="fa fa-calendar-check metro-icon"></i>
                <div class="metro-content">
                    <h4>Attendance</h4>
                    <p>Daily attendance</p>
                </div>
            </a>

            <!-- Financial -->
            <a href="<?php echo site_url('admin/income_dashboard'); ?>" class="metro-tile financial">
                <i class="fa fa-money-bill-wave metro-icon"></i>
                <div class="metro-content">
                    <h4>Financial</h4>
                    <p>Fees & payments</p>
                </div>
            </a>

            <!-- Reports -->
            <a href="<?php echo site_url('admin/expenditure_reports'); ?>" class="metro-tile reports">
                <i class="fa fa-chart-bar metro-icon"></i>
                <div class="metro-content">
                    <h4>Reports</h4>
                    <p>Analytics & reports</p>
                </div>
            </a>

            <!-- Settings -->
            <a href="<?php echo site_url('admin/system_settings'); ?>" class="metro-tile settings">
                <i class="fa fa-cog metro-icon"></i>
                <div class="metro-content">
                    <h4>Settings</h4>
                    <p>System configuration</p>
                </div>
            </a>
        </div>
    </div>
</div>