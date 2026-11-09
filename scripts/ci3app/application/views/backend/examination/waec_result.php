<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>JHS Statement of Results</title>
    <script src="<?php echo base_url(); ?>assets/cdn/js/tailwindcss.js"></script>
    <style>
        @media print {
            body { margin: 0; padding: 20px; }
            .no-print { display: none; }
            @page { size: A4; margin: 15mm; }
        }
        .waec-border { border: 3px double #000; }
        .signature-line { border-bottom: 1px solid #000; display: inline-block; min-width: 200px; }
    </style>
</head>
<body class="bg-white">

<!-- Print Button -->
<div class="no-print fixed top-4 right-4 space-x-2">
    <button onclick="window.print()" class="bg-blue-600 text-white px-4 py-2 rounded">Print</button>
    <button onclick="generatePDF()" class="bg-green-600 text-white px-4 py-2 rounded">Download PDF</button>
</div>

<!-- Report Container -->
<div class="max-w-4xl mx-auto bg-white p-8">
    
    <!-- Header -->
    <div class="waec-border p-6 mb-6">
        <div class="text-center mb-4">
            <img src="<?php echo base_url('uploads/school_logo.png'); ?>" alt="School Logo" class="h-20 mx-auto mb-2">
            <h1 class="text-2xl font-bold uppercase"><?php echo $school_name; ?></h1>
            <p class="text-sm"><?php echo $school_address; ?></p>
            <p class="text-sm">Tel: <?php echo $school_phone; ?> | Email: <?php echo $school_email; ?></p>
        </div>
        
        <div class="border-t-2 border-b-2 border-black py-2 my-4">
            <h2 class="text-xl font-bold text-center">JUNIOR HIGH SCHOOL STATEMENT OF RESULTS</h2>
            <p class="text-center text-sm">(BASIC EDUCATION CERTIFICATE EXAMINATION - MOCK)</p>
        </div>
        
        <div class="grid grid-cols-2 gap-4 text-sm">
            <div>
                <p><strong>Candidate Name:</strong> <?php echo strtoupper($student_name); ?></p>
                <p><strong>Index Number:</strong> <?php echo $index_number; ?></p>
            </div>
            <div>
                <p><strong>Exam Session:</strong> <?php echo $exam_session; ?></p>
                <p><strong>Year:</strong> <?php echo $year; ?></p>
            </div>
        </div>
    </div>

    <!-- Core Subjects -->
    <div class="mb-6">
        <h3 class="bg-gray-800 text-white px-4 py-2 font-bold">CORE SUBJECTS</h3>
        <table class="w-full border-collapse border border-gray-400">
            <thead>
                <tr class="bg-gray-200">
                    <th class="border border-gray-400 px-4 py-2 text-left">Subject</th>
                    <th class="border border-gray-400 px-4 py-2 text-center w-20">Grade</th>
                    <th class="border border-gray-400 px-4 py-2 text-left">Interpretation</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($core_subjects as $subject): ?>
                <tr>
                    <td class="border border-gray-400 px-4 py-2"><?php echo $subject['name']; ?></td>
                    <td class="border border-gray-400 px-4 py-2 text-center font-bold"><?php echo $subject['grade']; ?></td>
                    <td class="border border-gray-400 px-4 py-2"><?php echo $subject['interpretation']; ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- Elective Subjects -->
    <div class="mb-6">
        <h3 class="bg-gray-800 text-white px-4 py-2 font-bold">ELECTIVE SUBJECTS</h3>
        <table class="w-full border-collapse border border-gray-400">
            <thead>
                <tr class="bg-gray-200">
                    <th class="border border-gray-400 px-4 py-2 text-left">Subject</th>
                    <th class="border border-gray-400 px-4 py-2 text-center w-20">Grade</th>
                    <th class="border border-gray-400 px-4 py-2 text-left">Interpretation</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($elective_subjects as $subject): ?>
                <tr>
                    <td class="border border-gray-400 px-4 py-2"><?php echo $subject['name']; ?></td>
                    <td class="border border-gray-400 px-4 py-2 text-center font-bold"><?php echo $subject['grade']; ?></td>
                    <td class="border border-gray-400 px-4 py-2"><?php echo $subject['interpretation']; ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- Aggregate -->
    <div class="bg-yellow-50 border-2 border-yellow-600 p-4 mb-6">
        <div class="flex justify-between items-center">
            <div>
                <p class="text-sm text-gray-600">Best 4 Core + Best 2 Electives</p>
                <p class="text-xs text-gray-500">Core: <?php echo implode(', ', $best_core_grades); ?> | Electives: <?php echo implode(', ', $best_elective_grades); ?></p>
            </div>
            <div class="text-right">
                <p class="text-sm font-semibold">AGGREGATE SCORE</p>
                <p class="text-4xl font-bold text-yellow-700"><?php echo $aggregate; ?></p>
            </div>
        </div>
    </div>

    <!-- Grade Interpretation Key -->
    <div class="mb-6 text-xs">
        <h4 class="font-bold mb-2">GRADE INTERPRETATION:</h4>
        <div class="grid grid-cols-2 gap-2">
            <div><strong>1-3:</strong> Highly Proficient</div>
            <div><strong>4-6:</strong> Proficient</div>
            <div><strong>7-8:</strong> Basic</div>
            <div><strong>9:</strong> Unsatisfactory</div>
        </div>
    </div>

    <!-- Footer -->
    <div class="border-t-2 border-black pt-6 mt-8">
        <div class="grid grid-cols-2 gap-8">
            <div>
                <p class="text-sm mb-8">Head of School</p>
                <div class="signature-line mb-2"></div>
                <p class="text-xs">Name: <?php echo $head_name; ?></p>
                <p class="text-xs">Date: <span class="signature-line"><?php echo date('d/m/Y'); ?></span></p>
            </div>
            <div class="text-center">
                <div class="border-2 border-dashed border-gray-400 h-24 flex items-center justify-center mb-2">
                    <p class="text-xs text-gray-500">SCHOOL STAMP</p>
                </div>
                <div class="border-2 border-dashed border-gray-400 h-16 flex items-center justify-center">
                    <p class="text-xs text-gray-500">OFFICE STAMP</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer Note -->
    <div class="text-center text-xs text-gray-500 mt-6 border-t pt-4">
        <p>This is an official document. Any alteration renders it invalid.</p>
        <p>Generated on: <?php echo date('l, F j, Y \a\t g:i A'); ?></p>
    </div>

</div>

<script>
function generatePDF() {
    window.location.href = '<?php echo site_url("examination/download_result_pdf/".$student_id."/".$exam_id); ?>';
}
</script>

</body>
</html>
