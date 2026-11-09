<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo get_phrase('barcode_scanner_attendance'); ?></title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Inter', sans-serif; }
        body { background: #f8fafc; }
        .scanner-container { max-width: 800px; margin: 0 auto; padding: 24px; }
        .scanner-header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 24px; border-radius: 12px; margin-bottom: 24px; text-align: center; }
        .scanner-box { background: white; border-radius: 12px; padding: 24px; box-shadow: 0 2px 8px rgba(0,0,0,0.08); margin-bottom: 20px; }
        #reader { width: 100%; border-radius: 8px; overflow: hidden; }
        .scan-result { background: #d1fae5; border: 2px solid #10b981; padding: 16px; border-radius: 8px; margin-top: 16px; display: none; }
        .student-info { display: flex; align-items: center; gap: 16px; }
        .student-photo { width: 80px; height: 80px; border-radius: 50%; object-fit: cover; }
        .btn { padding: 12px 24px; border-radius: 8px; font-weight: 600; border: none; cursor: pointer; transition: all 0.3s; }
        .btn-primary { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; }
        .btn-success { background: #10b981; color: white; }
        .btn-danger { background: #ef4444; color: white; }
        .status-buttons { display: flex; gap: 12px; margin-top: 16px; }
        .scanned-list { background: white; border-radius: 12px; padding: 24px; box-shadow: 0 2px 8px rgba(0,0,0,0.08); }
        .scanned-item { display: flex; justify-content: space-between; align-items: center; padding: 12px; border-bottom: 1px solid #e5e7eb; }
        .badge { padding: 6px 12px; border-radius: 6px; font-size: 12px; font-weight: 600; }
        .badge-success { background: #d1fae5; color: #065f46; }
        .badge-danger { background: #fee2e2; color: #991b1b; }
    </style>
</head>
<body>
    <div class="scanner-container">
        <div class="scanner-header">
            <h1><i class="fa fa-qrcode"></i> <?php echo get_phrase('barcode_scanner_attendance'); ?></h1>
            <p style="margin-top: 8px; opacity: 0.9;"><?php echo date('l, F j, Y'); ?></p>
        </div>

        <div class="scanner-box">
            <div id="reader"></div>
            
            <div id="scan-result" class="scan-result">
                <div class="student-info">
                    <img id="student-photo" class="student-photo" src="" alt="Student">
                    <div>
                        <h3 id="student-name" style="margin-bottom: 4px;"></h3>
                        <p id="student-code" style="color: #6b7280;"></p>
                        <p id="student-class" style="color: #6b7280; font-size: 14px;"></p>
                    </div>
                </div>
                
                <div class="status-buttons">
                    <button class="btn btn-success" onclick="markStatus(1)">
                        <i class="fa fa-check"></i> <?php echo get_phrase('present'); ?>
                    </button>
                    <button class="btn btn-danger" onclick="markStatus(2)">
                        <i class="fa fa-times"></i> <?php echo get_phrase('absent'); ?>
                    </button>
                    <button class="btn btn-primary" onclick="markStatus(3)">
                        <i class="fa fa-clock-o"></i> <?php echo get_phrase('late'); ?>
                    </button>
                </div>
            </div>
        </div>

        <div class="scanned-list">
            <h3 style="margin-bottom: 16px;"><?php echo get_phrase('scanned_students'); ?> (<span id="scan-count">0</span>)</h3>
            <div id="scanned-students"></div>
            
            <button class="btn btn-primary" onclick="saveAll()" style="margin-top: 20px; width: 100%;">
                <i class="fa fa-save"></i> <?php echo get_phrase('save_all_attendance'); ?>
            </button>
        </div>
    </div>

    <script src="<?php echo base_url(); ?>assets/cdn/js/html5-qrcode.min.js"></script>
    <script>
    let scannedStudents = {};
    let currentStudent = null;
    
    const html5QrcodeScanner = new Html5QrcodeScanner(
        "reader", 
        { fps: 10, qrbox: 250 }
    );
    
    html5QrcodeScanner.render(onScanSuccess, onScanError);
    
    function onScanSuccess(decodedText, decodedResult) {
        // Decode student code
        getStudentInfo(decodedText);
    }
    
    function onScanError(errorMessage) {
        // Handle scan error
    }
    
    function getStudentInfo(studentCode) {
        $.ajax({
            url: '<?php echo site_url('attendance/get_student_by_code'); ?>',
            type: 'POST',
            data: { student_code: studentCode },
            dataType: 'json'
        }).done(function(response) {
            if(response.status === 'success') {
                currentStudent = response.data;
                displayStudent(response.data);
            } else {
                showAjaxModal_alert('Student not found');
            }
        });
    }
    
    function displayStudent(student) {
        $('#student-photo').attr('src', student.photo || '<?php echo base_url(); ?>uploads/student_image/default.jpg');
        $('#student-name').text(student.name);
        $('#student-code').text(student.student_code);
        $('#student-class').text(student.class_name);
        $('#scan-result').slideDown();
    }
    
    function markStatus(status) {
        if(!currentStudent) return;
        
        scannedStudents[currentStudent.student_id] = {
            ...currentStudent,
            status: status
        };
        
        updateScannedList();
        $('#scan-result').slideUp();
        currentStudent = null;
    }
    
    function updateScannedList() {
        let html = '';
        let count = 0;
        
        for(let id in scannedStudents) {
            const student = scannedStudents[id];
            let statusText = 'Present';
            let badgeClass = 'badge-success';
            
            switch(parseInt(student.status)) {
                case 1: statusText = 'Present'; badgeClass = 'badge-success'; break;
                case 2: statusText = 'Absent'; badgeClass = 'badge-danger'; break;
                case 3: statusText = 'Late'; badgeClass = 'badge-warning'; break;
                case 4: statusText = 'Sick-Home'; badgeClass = 'badge-warning'; break;
                case 5: statusText = 'Sick-Clinic'; badgeClass = 'badge-info'; break;
                case 6: statusText = 'Excused'; badgeClass = 'badge-secondary'; break;
            }
            
            html += `
                <div class="scanned-item">
                    <div>
                        <strong>${student.name}</strong>
                        <span style="color: #6b7280; margin-left: 8px;">${student.student_code}</span>
                    </div>
                    <span class="badge ${badgeClass}">${statusText}</span>
                </div>
            `;
            count++;
        }
        
        $('#scanned-students').html(html);
        $('#scan-count').text(count);
    }
    
    function saveAll() {
        if(Object.keys(scannedStudents).length === 0) {
            showAjaxModal_alert('No students scanned');
            return;
        }
        
        const attendance = {};
        for(let id in scannedStudents) {
            attendance[id] = scannedStudents[id].status;
        }
        
        $.ajax({
            url: '<?php echo site_url('attendance/save'); ?>',
            type: 'POST',
            data: {
                attendance: attendance,
                date: '<?php echo date('Y-m-d'); ?>',
                class_id: '<?php echo $class_id ?? ''; ?>',
                section_id: '<?php echo $section_id ?? ''; ?>'
            },
            dataType: 'json'
        }).done(function(response) {
            showAjaxModal_alert(response.message);
            if(response.status === 'success') {
                scannedStudents = {};
                updateScannedList();
            }
        });
    }
    </script>
</body>
</html>
