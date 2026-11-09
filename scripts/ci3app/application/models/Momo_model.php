<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Momo_model extends CI_Model {
    
    public function __construct() {
        parent::__construct();
    }
    
    function clear_cache() {
        $this->output->set_header('Cache-Control: no-store, no-cache, must-revalidate, post-check=0, pre-check=0');
        $this->output->set_header('Pragma: no-cache');
    }

    function sendMomo($momo_number, $channel, $payer_id="", $student_name, $amount, $invoice_code, $payer_name) {

      $parent_email = $this->db->get_where('parent', ['parent_id' => $payer_id])->row()->email;
      if($parent_email == null || $parent_email == '') {
        $parent_email = '';
      }

      $system_name = get_settings('system_name');
      $amount = doubleval($amount);
      $apiUsername = $this->db->get_where('settings', array('type' => 'hubtel_api_id'))->row()->description;
      $apiPassword     = $this->db->get_where('settings', array('type' => 'hubtel_api_key'))->row()->description;
      $hubtel_pos_sales_id = $this->db->get_where('settings', array('type' => 'hubtel_pos_sales_id'))->row()->description;

      if(strlen($momo_number) == 9 && substr($momo_number, 0, 1) != '0') {
          $mNumber     = '233'. substr($momo_number, 0, 9);
      } else if(strlen($momo_number) == 10 && substr($momo_number, 0, 1) == '0') {
          $mNumber     = '233'. substr($momo_number, 1, 9);
      } else if(strlen($momo_number) == 12) {
          $mNumber     = '+'. $momo_number;
      } else if(strlen($momo_number) == 13 && substr($momo_number, 3, 1) == 0) {
          $mNumber     = '233'. substr($momo_number, 4, 9);
      } else if(strlen($momo_number) == 14 && substr($momo_number, 4, 1) == 0 && substr($momo_number, 0, 1) == '+') {
          $mNumber     = '233'. substr($momo_number, 5, 9);
      } else if(strlen($momo_number) == 13 && substr($momo_number, 0, 1) == '+') {
          $mNumber     = $momo_number;
      }


      /**
     * Requires libcurl
     */
      function getToken($apiUsername, $apiPassword) {
          return base64_encode("$apiUsername:$apiPassword");
      }


    $mobileNumber = $mNumber;
    $curl = curl_init();

    $payload = array(

      "CustomerName" => $payer_name,
      "CustomerMsisdn" => $mobileNumber,
      "CustomerEmail" => $parent_email,
      "Channel" => $channel,
      "Amount" => $amount,
      "PrimaryCallbackUrl" => site_url('parents/mobile_money_checkout_callback'),
      "Description" => "Payment of fees for " .$student_name.'. INVOICE#:'.$invoice_code,
      "ClientReference" => substr(sha1(md5($payer_id.$invoice_code)), 0, 5),
      
    );


   /* $payload = array(
        "totalAmount" => $amount,
        "description" => "Being payment of fees for " .$student_name.'. INVOICE#:'.$invoice_code,
        "callbackUrl" => "https://webhook.site/d5ffa35b-31ba-4d71-beed-56531200b22d",
        "returnUrl" => site_url('parents/mobile_money_checkout/returned'),
        "merchantAccountNumber" => $hubtel_pos_sales_id,
        "cancellationUrl" => site_url('parents/mobile_money_checkout/cancelled'),
        "PayeeMobileNumber" => $mobileNumber,
        "PayeeName" => $payer_name,
        "PayeeEmail" => $parent_email,
        "clientReference" => substr(sha1(md5($payer_id.$invoice_code)), 0, 5)

    ); 
  */

    $curl = curl_init();
    curl_setopt_array($curl, [
      CURLOPT_HTTPHEADER => [
        "Accept: application/json",
        "Content-Type: application/json",
        "Authorization: Basic " . getToken($apiUsername, $apiPassword),
        "Cache-Control: no-cache"
      ],
      CURLOPT_POSTFIELDS => json_encode($payload),
      CURLOPT_URL => "https://rmp.hubtel.com/merchantaccount/merchants/".$hubtel_pos_sales_id."/receive/mobilemoney",
      CURLOPT_ENCODING => '',
      CURLOPT_MAXREDIRS => 10,
      CURLOPT_TIMEOUT => 0,
      CURLOPT_FOLLOWLOCATION => true,
      CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_CUSTOMREQUEST => "POST",
    ]);

    $response = curl_exec($curl);
    $error = curl_error($curl);

    curl_close($curl);

    if ($error) {
      echo $error;
    } else {
      return $response;
    }
  }
}
