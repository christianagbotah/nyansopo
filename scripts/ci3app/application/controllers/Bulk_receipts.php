<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Bulk_receipts extends CI_Controller {

    public function __construct() {
        parent::__construct();
        $this->load->library('pdf');
    }

    public function generate() {
        $payment_ids = $this->input->post('payment_ids');
        
        if (empty($payment_ids)) {
            echo json_encode(['status' => 'error', 'message' => get_phrase('no_payments_selected')]);
            return;
        }

        $receipts_html = '';
        foreach ($payment_ids as $payment_id) {
            $receipts_html .= $this->generate_single_receipt_html($payment_id);
            $receipts_html .= '<div style="page-break-after: always;"></div>';
        }

        // Generate PDF
        $filename = 'bulk_receipts_' . date('YmdHis') . '.pdf';
        $this->generate_pdf($receipts_html, $filename);

        echo json_encode([
            'status' => 'success',
            'message' => count($payment_ids) . ' ' . get_phrase('receipts_generated'),
            'file' => $filename
        ]);
    }

    private function generate_single_receipt_html($payment_id) {
        $payment = $this->db->select('p.*, s.name as student_name, s.student_code, i.invoice_code')
            ->from('payment p')
            ->join('student s', 's.student_id = p.student_id')
            ->join('invoice i', 'i.invoice_id = p.invoice_id', 'left')
            ->where('p.payment_id', $payment_id)
            ->get()->row();

        if (!$payment) return '';

        $currency = $this->db->get_where('settings', ['type' => 'currency'])->row()->description;
        $fmt = new NumberFormatter('en_US', NumberFormatter::CURRENCY);
        $receipt_number = 'RCP-' . str_pad($payment_id, 6, '0', STR_PAD_LEFT);

        ob_start();
        ?>
        <div style="max-width: 800px; margin: 0 auto; padding: 20px; font-family: Arial, sans-serif;">
            <div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 30px; text-align: center;">
                <h1 style="margin: 0;">PAYMENT RECEIPT</h1>
                <p style="margin: 10px 0 0 0; font-size: 18px;">#<?php echo $receipt_number; ?></p>
            </div>
            
            <div style="padding: 30px; background: #f8f9fa;">
                <table style="width: 100%; margin-bottom: 20px;">
                    <tr>
                        <td><strong>Date:</strong> <?php echo date('d M, Y', $payment->timestamp); ?></td>
                        <td style="text-align: right;"><strong>Invoice:</strong> <?php echo $payment->invoice_code; ?></td>
                    </tr>
                    <tr>
                        <td><strong>Student:</strong> <?php echo $payment->student_name; ?></td>
                        <td style="text-align: right;"><strong>ID:</strong> <?php echo $payment->student_code; ?></td>
                    </tr>
                </table>
                
                <table style="width: 100%; border-collapse: collapse; margin: 20px 0;">
                    <tr style="background: #667eea; color: white;">
                        <th style="padding: 15px; text-align: left;">Description</th>
                        <th style="padding: 15px; text-align: right;">Amount</th>
                    </tr>
                    <tr>
                        <td style="padding: 15px; border-bottom: 1px solid #ddd;">Payment Received</td>
                        <td style="padding: 15px; border-bottom: 1px solid #ddd; text-align: right; font-size: 18px; font-weight: bold;">
                            <?php echo numfmt_format_currency($fmt, $payment->amount, $currency); ?>
                        </td>
                    </tr>
                </table>
                
                <p style="text-align: center; margin-top: 40px; color: #666;">
                    Thank you for your payment
                </p>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    private function generate_pdf($html, $filename) {
        require_once APPPATH . 'third_party/dompdf/autoload.inc.php';
        
        $dompdf = new \Dompdf\Dompdf();
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();
        $dompdf->stream($filename, ['Attachment' => 0]);
    }

    public function print_multiple() {
        $payment_ids = $this->input->post('payment_ids');
        
        if (empty($payment_ids)) {
            show_error('No payments selected');
            return;
        }

        $data['payment_ids'] = $payment_ids;
        $this->load->view('backend/admin/bulk_receipts_print', $data);
    }
}
