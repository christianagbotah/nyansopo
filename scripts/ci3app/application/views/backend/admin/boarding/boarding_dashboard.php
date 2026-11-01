<div class="row">
    <div class="col-md-12">
        <div class="panel panel-primary">
            <div class="panel-heading">
                <h3 class="panel-title">
                    <i class="fa fa-building"></i> <?php echo get_phrase('boarding_management_dashboard'); ?>
                </h3>
            </div>
            <div class="panel-body">
                
                <!-- Statistics Cards -->
                <div class="row">
                    <div class="col-md-3">
                        <div class="tile-stats tile-blue">
                            <div class="icon"><i class="fa fa-home"></i></div>
                            <div class="num" data-start="0" data-end="<?php echo $this->db->count_all('boarding_house'); ?>" data-postfix="" data-duration="1500" data-delay="0">0</div>
                            <h3><?php echo get_phrase('total_houses'); ?></h3>
                        </div>
                    </div>
                    
                    <div class="col-md-3">
                        <div class="tile-stats tile-green">
                            <div class="icon"><i class="fa fa-building-o"></i></div>
                            <div class="num" data-start="0" data-end="<?php echo $this->db->count_all('boarding_dormitory'); ?>" data-postfix="" data-duration="1500" data-delay="0">0</div>
                            <h3><?php echo get_phrase('total_dormitories'); ?></h3>
                        </div>
                    </div>
                    
                    <div class="col-md-3">
                        <div class="tile-stats tile-red">
                            <div class="icon"><i class="fa fa-bed"></i></div>
                            <div class="num" data-start="0" data-end="<?php echo $this->db->count_all('boarding_bed'); ?>" data-postfix="" data-duration="1500" data-delay="0">0</div>
                            <h3><?php echo get_phrase('total_beds'); ?></h3>
                        </div>
                    </div>
                    
                    <div class="col-md-3">
                        <div class="tile-stats tile-purple">
                            <div class="icon"><i class="fa fa-users"></i></div>
                            <?php 
                            $this->db->where('bed_status', 'Assigned');
                            $occupied = $this->db->count_all_results('boarding_bed');
                            ?>
                            <div class="num" data-start="0" data-end="<?php echo $occupied; ?>" data-postfix="" data-duration="1500" data-delay="0">0</div>
                            <h3><?php echo get_phrase('occupied_beds'); ?></h3>
                        </div>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="row" style="margin-top: 30px;">
                    <div class="col-md-12">
                        <h4><?php echo get_phrase('quick_actions'); ?></h4>
                        <hr>
                    </div>
                    
                    <div class="col-md-3">
                        <a href="<?php echo site_url('admin/manageBoardingHouse'); ?>" class="btn btn-primary btn-block btn-lg">
                            <i class="fa fa-home"></i><br>
                            <?php echo get_phrase('manage_houses'); ?>
                        </a>
                    </div>
                    
                    <div class="col-md-3">
                        <a href="<?php echo site_url('admin/manageBoardingDormitory'); ?>" class="btn btn-success btn-block btn-lg">
                            <i class="fa fa-building-o"></i><br>
                            <?php echo get_phrase('manage_dormitories'); ?>
                        </a>
                    </div>
                    
                    <div class="col-md-3">
                        <a href="<?php echo site_url('admin/manageDormitoryBed'); ?>" class="btn btn-info btn-block btn-lg">
                            <i class="fa fa-bed"></i><br>
                            <?php echo get_phrase('manage_beds'); ?>
                        </a>
                    </div>
                    
                    <div class="col-md-3">
                        <a href="<?php echo site_url('admin/boarding_student_assignment'); ?>" class="btn btn-warning btn-block btn-lg">
                            <i class="fa fa-user-plus"></i><br>
                            <?php echo get_phrase('assign_students'); ?>
                        </a>
                    </div>
                </div>

                <!-- Recent Activity -->
                <div class="row" style="margin-top: 30px;">
                    <div class="col-md-12">
                        <h4><?php echo get_phrase('recent_assignments'); ?></h4>
                        <hr>
                        <table class="table table-bordered table-striped">
                            <thead>
                                <tr>
                                    <th><?php echo get_phrase('student'); ?></th>
                                    <th><?php echo get_phrase('house'); ?></th>
                                    <th><?php echo get_phrase('dormitory'); ?></th>
                                    <th><?php echo get_phrase('bed_code'); ?></th>
                                    <th><?php echo get_phrase('assigned_date'); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $this->db->select('sr.*, s.name as student_name, bh.house_name, bd.dormitory_name, bb.bed_code');
                                $this->db->from('student_residence sr');
                                $this->db->join('student s', 's.student_id = sr.student_id');
                                $this->db->join('boarding_house bh', 'bh.house_id = sr.house_id', 'left');
                                $this->db->join('boarding_dormitory bd', 'bd.dormitory_id = sr.dormitory_id', 'left');
                                $this->db->join('boarding_bed bb', 'bb.bed_id = sr.bed_id', 'left');
                                $this->db->where('sr.status', 'Active');
                                $this->db->order_by('sr.created_at', 'DESC');
                                $this->db->limit(10);
                                $recent = $this->db->get()->result_array();
                                
                                if(count($recent) > 0):
                                    foreach($recent as $row):
                                ?>
                                <tr>
                                    <td><?php echo $row['student_name']; ?></td>
                                    <td><?php echo $row['house_name']; ?></td>
                                    <td><?php echo $row['dormitory_name']; ?></td>
                                    <td><?php echo $row['bed_code']; ?></td>
                                    <td><?php echo date('d M Y', strtotime($row['assigned_date'])); ?></td>
                                </tr>
                                <?php 
                                    endforeach;
                                else:
                                ?>
                                <tr>
                                    <td colspan="5" class="text-center"><?php echo get_phrase('no_recent_assignments'); ?></td>
                                </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // Animate numbers
    $('.tile-stats .num').each(function() {
        var $this = $(this);
        var countTo = $this.attr('data-end');
        
        $({ countNum: $this.text()}).animate({
            countNum: countTo
        }, {
            duration: 1500,
            easing: 'linear',
            step: function() {
                $this.text(Math.floor(this.countNum));
            },
            complete: function() {
                $this.text(this.countNum);
            }
        });
    });
});
</script>

<style>
.tile-stats {
    position: relative;
    display: block;
    margin-bottom: 20px;
    border: 1px solid #e4e4e4;
    border-radius: 5px;
    padding: 20px;
    background: #fff;
    transition: all 0.3s ease;
}
.tile-stats:hover {
    box-shadow: 0 5px 15px rgba(0,0,0,0.1);
    transform: translateY(-5px);
}
.tile-stats .icon {
    font-size: 50px;
    opacity: 0.3;
    position: absolute;
    right: 20px;
    top: 20px;
}
.tile-stats .num {
    font-size: 36px;
    font-weight: bold;
    margin: 10px 0;
}
.tile-stats h3 {
    font-size: 14px;
    margin: 0;
    text-transform: uppercase;
}
.tile-blue { border-left: 4px solid #3498db; }
.tile-green { border-left: 4px solid #2ecc71; }
.tile-red { border-left: 4px solid #e74c3c; }
.tile-purple { border-left: 4px solid #9b59b6; }
</style>
