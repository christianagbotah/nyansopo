<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Export data to Excel (CSV format)
 */
function export_to_excel($data, $filename = 'export', $headers = []) {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '.csv"');
    
    $output = fopen('php://output', 'w');
    
    // Add BOM for UTF-8
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
    
    // Add headers if provided
    if (!empty($headers)) {
        fputcsv($output, $headers);
    }
    
    // Add data rows
    foreach ($data as $row) {
        fputcsv($output, $row);
    }
    
    fclose($output);
    exit;
}

/**
 * Export data to PDF using TCPDF
 */
function export_to_pdf($html, $filename = 'export', $orientation = 'P') {
    $CI =& get_instance();
    $CI->load->library('pdf');
    
    $CI->pdf->setPrintHeader(false);
    $CI->pdf->setPrintFooter(false);
    $CI->pdf->AddPage($orientation);
    $CI->pdf->writeHTML($html, true, false, true, false, '');
    $CI->pdf->Output($filename . '.pdf', 'D');
}

/**
 * Generate HTML table for PDF export
 */
function generate_pdf_table($data, $headers, $title = '') {
    $html = '<style>
        table { border-collapse: collapse; width: 100%; }
        th { background-color: #4CAF50; color: white; padding: 8px; text-align: left; border: 1px solid #ddd; }
        td { padding: 8px; border: 1px solid #ddd; }
        tr:nth-child(even) { background-color: #f2f2f2; }
        h2 { color: #333; margin-bottom: 20px; }
    </style>';
    
    if ($title) {
        $html .= '<h2>' . $title . '</h2>';
    }
    
    $html .= '<table>';
    
    // Headers
    if (!empty($headers)) {
        $html .= '<thead><tr>';
        foreach ($headers as $header) {
            $html .= '<th>' . $header . '</th>';
        }
        $html .= '</tr></thead>';
    }
    
    // Data rows
    $html .= '<tbody>';
    foreach ($data as $row) {
        $html .= '<tr>';
        foreach ($row as $cell) {
            $html .= '<td>' . $cell . '</td>';
        }
        $html .= '</tr>';
    }
    $html .= '</tbody></table>';
    
    return $html;
}
