<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Sms_model extends CI_Model {
    
    public function __construct() {
        parent::__construct();
    }
    
    function clear_cache() {
        $this->output->set_header('Cache-Control: no-store, no-cache, must-revalidate, post-check=0, pre-check=0');
        $this->output->set_header('Pragma: no-cache');
    }
    
    // Helper function for Hubtel SMS authentication
    private function getToken($clientId, $clientSecret) {
        return base64_encode("$clientId:$clientSecret");
    }
    
    // Helper function for sending Hubtel SMS (simple batch)
    private function SendMessage($messageRequest = array(), $clientId = "", $clientSecret = "") {
        $curl = curl_init();
    
        curl_setopt_array($curl, array(
            CURLOPT_URL => "https://sms.hubtel.com/v1/messages/batch/simple/send",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => json_encode($messageRequest),
            CURLOPT_HTTPHEADER => array(
                "Authorization: Basic ".$this->getToken($clientId, $clientSecret),
                "Content-Type: application/json"
            ),
        ));
    
        $exc = curl_exec($curl);
        $err = curl_error($curl);

        if($err) {
            $response = $err;
        } else {
            $response = $exc;
        }
        
        curl_close($curl);

        return json_decode($response, true);
    }
    
    // Helper function for sending Hubtel SMS (personalized batch)
    private function SendPersonalizedMessage($messageRequest = array(), $clientId = "", $clientSecret = "") {
        $curl = curl_init();
    
        curl_setopt_array($curl, array(
            CURLOPT_URL => "https://sms.hubtel.com/v1/messages/batch/personalized/send",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => json_encode($messageRequest),
            CURLOPT_HTTPHEADER => array(
                "Authorization: Basic ".$this->getToken($clientId, $clientSecret),
                "Content-Type: application/json"
            ),
        ));
    
        $exc = curl_exec($curl);
        $err = curl_error($curl);

        if($err) {
            $response = $err;
        } else {
            $response = $exc;
        }
        
        curl_close($curl);

        return json_decode($response, true);
    }

    //COMMON FUNCTION FOR SENDING SMS
    function send_sms($message = '' , $receiver_phone = array(), $user_name = array())
    {
    

        $counter = 0;
        $data_success = array();
        $data_fail = array();
    
        $active_sms_service = $this->db->get_where('settings' , array(
            'type' => 'active_sms_service'
        ))->row()->description;


        if ($active_sms_service == '' || $active_sms_service == 'disabled')
            return;
        if ($active_sms_service == 'hubtel') {

    
            $hubtel_sender   = $this->db->get_where('settings', array('type' => 'hubtel_sender'))->row()->description;
            $hubtel_client_id       = $this->db->get_where('settings', array('type' => 'hubtel_client_id'))->row()->description;
            $hubtel_client_secret     = $this->db->get_where('settings', array('type' => 'hubtel_client_secret'))->row()->description;
            
    
            if(substr($message, 0, 7) == 'Welcome') {
                $text   = $message.' Kindly change your password on your first login.'."\r\n".'Please use this link to login: ' . base_url();
            } else {
                $text   = $message;
            }
            $from   = $hubtel_sender;
            
            // getToken() and SendMessage() are now class methods defined above
            
            $clientId = $hubtel_client_id;
            $clientSecret = $hubtel_client_secret;

            $correct_numbers_array = array();
            $invalid_numbers_array = array();
            $correct_names_array = array();
            $invalid_names_array = array();
            
            //Sample usage
            $is_invalid = false;
            for($p=0; $p < count($receiver_phone); $p++) {
                
                if(strlen($receiver_phone[$p]) == 9 && substr($receiver_phone[$p], 0, 1) != '0') {
                    $to     = '+233'. substr($receiver_phone[$p], 0, 9);

                } else if(strlen($receiver_phone[$p]) == 10 && substr($receiver_phone[$p], 0, 1) == '0') {
                    $to     = '+233'. substr($receiver_phone[$p], 1, 9);

                } else if(strlen($receiver_phone[$p]) == 12) {
                    $to     = '+'. $receiver_phone[$p];

                } else if(strlen($receiver_phone[$p]) == 13 && substr($receiver_phone[$p], 3, 1) == 0) {
                    $to     = '+233'. substr($receiver_phone[$p], 4, 9);

                } else if(strlen($receiver_phone[$p]) == 14 && substr($receiver_phone[$p], 4, 1) == 0 && substr($receiver_phone[$p], 0, 1) == '+') {
                    $to     = '+233'. substr($receiver_phone[$p], 5, 9);

                } else if(strlen($receiver_phone[$p]) == 13 && substr($receiver_phone[$p], 0, 1) == '+') {
                    $to     = $receiver_phone[$p];

                } else {

                    array_push($invalid_numbers_array, $receiver_phone[$p]);

                    if (!is_array($user_name) || empty($user_name)) { 
                        $invalid_name = '<button class="btn btn-outline-danger" style="padding: 3px">'.$receiver_phone[$p].'</button>';
                    
                        array_push($invalid_names_array, $invalid_name);
                    } else {
                        $name = isset($user_name[$p]) ? ucwords(strtolower($user_name[$p])) : 'Unknown';
                        $invalid_name = '<button class="btn btn-outline-danger" style="padding: 3px">'.$name.' - '.$receiver_phone[$p].'</button>';
                    
                        array_push($invalid_names_array, $invalid_name);
                    }
                    
                    
                   continue;
                }

                $name = isset($user_name[$p]) ? ucwords(strtolower($user_name[$p])) : 'Unknown';
                $correct_name = '<button class="btn btn-outline-success" style="padding: 3px">'.$name.' - '.$to.'</button>';
                array_push($correct_numbers_array, $to);
                array_push($correct_names_array, $correct_name);

            }

            //return $invalid_numbers_array[0];
            
            
            if(!empty($invalid_numbers_array)) {
                //invlid numbers detected
                return get_phrase('FAILED: THE FOLLOWING RECIPIENT(S) PHONE NUMBER(S) ARE INVALID:<br/>'.implode(', ', $invalid_names_array).'!<br><br> PLEASE CHECK AND CORRECT THEM BEFORE PROCEEDING.');
                

            } else {
                //all numbers are correct
                //for($c = 0; $c < count($correct_numbers_array); $c++) {

                    $messageRequest = [
                        "From" => $hubtel_sender,
                        "Recipients" => $correct_numbers_array,
                        "Content" => $text,
                    ];
                    
                    $response = $this->SendMessage($messageRequest, $clientId, $clientSecret);
                    $send_sms_response = $response;
                    
                  
                if(substr($message, 0, 7) == 'Welcome') { 

                    return;
                } else {
                    //return 'response status '. $send_sms_response;

                    //send response
                    if(is_array($send_sms_response) && isset($send_sms_response['status']) && $send_sms_response['status'] == 0) {
                         $finalResponse = '<h2 style="margin-top: -30px;"><button type="button" class="btn btn-success btn-lg" style="border-radius: 5px !important;">Total Recipient(s): <span class="badge">'.count($correct_numbers_array).'</span></button></h2><h4>SMS Successfully sent to the following:</h4>'. implode(', ', $correct_names_array);

                        return $finalResponse;
                    } else {
                        // Check balance and provide specific error message
                        $balance_info = $this->get_hubtel_balance();
                        $error_details = '';
                        
                        if ($balance_info && isset($balance_info['Balance'])) {
                            $current_balance = floatval($balance_info['Balance']);
                            if ($current_balance <= 0) {
                                $error_details = ' - INSUFFICIENT BALANCE: Your Hubtel SMS balance is GHS 0.00. Please top up your account.';
                                log_message('error', 'SMS Send Failed: Hubtel balance is GHS 0.00');
                            } else if ($current_balance < 5) {
                                $error_details = ' - LOW BALANCE: Your Hubtel SMS balance is GHS ' . number_format($current_balance, 2) . '. Please top up soon.';
                                log_message('warning', 'SMS Send Failed: Hubtel balance is low (GHS ' . number_format($current_balance, 2) . ')');
                            }
                        }
                        
                        // Check if Hubtel returned an error message
                        if (isset($send_sms_response['message']) && !empty($send_sms_response['message'])) {
                            return 'failed' . $error_details . ' (Error: ' . $send_sms_response['message'] . ')';
                        }
                        
                        // Return 'failed' with details if available, otherwise just 'failed'
                        return 'failed' . $error_details;
                    }                 
                }
            }
                
        }
    }

    function send_sms_password_reset($message = '' , $receiver_phone = array(), $user_name = array())
    {
    

        $counter = 0;
        $data_success = array();
        $data_fail = array();
    
        $active_sms_service = $this->db->get_where('settings' , array(
            'type' => 'active_sms_service'
        ))->row()->description;


        if ($active_sms_service == '' || $active_sms_service == 'disabled')
            return;
        if ($active_sms_service == 'hubtel') {

    
            $hubtel_sender   = $this->db->get_where('settings', array('type' => 'hubtel_sender'))->row()->description;
            $hubtel_client_id       = $this->db->get_where('settings', array('type' => 'hubtel_client_id'))->row()->description;
            $hubtel_client_secret     = $this->db->get_where('settings', array('type' => 'hubtel_client_secret'))->row()->description;
            
    
            if(substr($message, 0, 7) == 'Welcome') {
                $text   = $message.' Kindly change your password on your first login.'."\r\n".'Please use this link to login: ' . base_url();
            } else {
                $text   = $message;
            }
            $from   = $hubtel_sender;
            
            // getToken() and SendMessage() are now class methods defined above
            
            $clientId = $hubtel_client_id;
            $clientSecret = $hubtel_client_secret;

            $correct_numbers_array = array();
            $invalid_numbers_array = array();
            $correct_names_array = array();
            $invalid_names_array = array();
            
            //Sample usage
            $is_invalid = false;
            for($p=0; $p < count($receiver_phone); $p++) {
                
                if(strlen($receiver_phone[$p]) == 9 && substr($receiver_phone[$p], 0, 1) != '0') {
                    $to     = '+233'. substr($receiver_phone[$p], 0, 9);

                } else if(strlen($receiver_phone[$p]) == 10 && substr($receiver_phone[$p], 0, 1) == '0') {
                    $to     = '+233'. substr($receiver_phone[$p], 1, 9);

                } else if(strlen($receiver_phone[$p]) == 12) {
                    $to     = '+'. $receiver_phone[$p];

                } else if(strlen($receiver_phone[$p]) == 13 && substr($receiver_phone[$p], 3, 1) == 0) {
                    $to     = '+233'. substr($receiver_phone[$p], 4, 9);

                } else if(strlen($receiver_phone[$p]) == 14 && substr($receiver_phone[$p], 4, 1) == 0 && substr($receiver_phone[$p], 0, 1) == '+') {
                    $to     = '+233'. substr($receiver_phone[$p], 5, 9);

                } else if(strlen($receiver_phone[$p]) == 13 && substr($receiver_phone[$p], 0, 1) == '+') {
                    $to     = $receiver_phone[$p];

                } else {

                    array_push($invalid_numbers_array, $receiver_phone[$p]);
                    
                   continue;
                }

                array_push($correct_numbers_array, $to);
                array_push($correct_names_array, $correct_name);

            }

            //return $invalid_numbers_array[0];
            
            
            if(!empty($invalid_numbers_array)) {
                //invlid numbers detected
                return 'failed';

            } else {
                //all numbers are correct
                //for($c = 0; $c < count($correct_numbers_array); $c++) {

                    $messageRequest = [
                        "From" => $hubtel_sender,
                        "Recipients" => $correct_numbers_array,
                        "Content" => $text,
                    ];
                    
                    $response = $this->SendMessage($messageRequest, $clientId, $clientSecret);
                    $send_sms_response = $response;
                    
                  
                //send response
                if($send_sms_response['status'] == 0) {

                    return 'success';
                } else {
                    return 'failed';
                }  
            }
                
        }
    }

    //COMMON FUNCTION FOR SENDING SMS
    function send_sms_batch_personalized($personalizedRecipients = array())
    {
    
    
        $active_sms_service = $this->db->get_where('settings' , array(
            'type' => 'active_sms_service'
        ))->row()->description;


        if ($active_sms_service == '' || $active_sms_service == 'disabled')
            return;
        if ($active_sms_service == 'hubtel') {

    
            $hubtel_sender   = $this->db->get_where('settings', array('type' => 'hubtel_sender'))->row()->description;
            $hubtel_client_id       = $this->db->get_where('settings', array('type' => 'hubtel_client_id'))->row()->description;
            $hubtel_client_secret     = $this->db->get_where('settings', array('type' => 'hubtel_client_secret'))->row()->description;            
            
            
            // getToken() and SendMessage() are now class methods defined above
            
            $clientId = $hubtel_client_id;
            $clientSecret = $hubtel_client_secret;

            // Format phone numbers in recipients array
            $formattedRecipients = [];
            foreach ($personalizedRecipients as $recipient) {
                $phone = $recipient['Recipient'];
                $formatted_phone = $this->format_phone_number($phone);
                
                if ($formatted_phone) {
                    $formattedRecipients[] = [
                        'To' => $formatted_phone,
                        'Content' => $recipient['Content']
                    ];
                }
            }
            
            // Return error if no valid recipients after formatting
            if (empty($formattedRecipients)) {
                return 100; // Invalid request - no valid phone numbers
            }

            $messageRequest = [
                "From" => $hubtel_sender,
                "personalizedRecipients" => $formattedRecipients,
            ];
            
            $response = $this->SendPersonalizedMessage($messageRequest, $clientId, $clientSecret);
            
            // Enhanced error handling with balance checking
            if (isset($response['status']) && $response['status'] != 0) {
                // Check balance and provide specific error message
                $balance_info = $this->get_hubtel_balance();
                $error_details = '';
                
                if ($balance_info && isset($balance_info['Balance'])) {
                    $current_balance = floatval($balance_info['Balance']);
                    if ($current_balance <= 0) {
                        $error_details = ' - INSUFFICIENT BALANCE: Your Hubtel SMS balance is GHS 0.00. Please top up your account.';
                        log_message('error', 'Payroll SMS Failed: Hubtel balance is GHS 0.00');
                    } else if ($current_balance < 5) {
                        $error_details = ' - LOW BALANCE: Your Hubtel SMS balance is GHS ' . number_format($current_balance, 2) . '. Please top up soon.';
                        log_message('warning', 'Payroll SMS Failed: Hubtel balance is low (GHS ' . number_format($current_balance, 2) . ')');
                    }
                }
                
                // Log detailed error
                $error_msg = 'Hubtel Personalized SMS Error (Status ' . $response['status'] . ')' . $error_details;
                if (isset($response['message'])) {
                    $error_msg .= ' - API Message: ' . $response['message'];
                }
                log_message('error', $error_msg . ' | Full Response: ' . json_encode($response));
            }

            return isset($response['status']) ? $response['status'] : 999;
                
        }
    }

    // Format phone number to international format (+233...)
    private function format_phone_number($phone) {
        $phone = trim($phone);
        $original = $phone;
        
        // Remove any spaces or dashes
        $phone = str_replace([' ', '-'], '', $phone);
        
        if (strlen($phone) == 9 && substr($phone, 0, 1) != '0') {
            return '+233' . substr($phone, 0, 9);
        } else if (strlen($phone) == 10 && substr($phone, 0, 1) == '0') {
            return '+233' . substr($phone, 1, 9);
        } else if (strlen($phone) == 12) {
            return '+' . $phone;
        } else if (strlen($phone) == 13 && substr($phone, 3, 1) == '0') {
            return '+233' . substr($phone, 4, 9);
        } else if (strlen($phone) == 14 && substr($phone, 4, 1) == '0' && substr($phone, 0, 1) == '+') {
            return '+233' . substr($phone, 5, 9);
        } else if (strlen($phone) == 13 && substr($phone, 0, 1) == '+') {
            return $phone;
        }
        
        // Log invalid format for debugging
        log_message('error', 'Invalid phone format: ' . $original . ' (length: ' . strlen($phone) . ')');
        return null; // Invalid format
    }

    // Get Hubtel account balance and rate
    function get_hubtel_balance() {
        $active_sms_service = $this->db->get_where('settings', array('type' => 'active_sms_service'))->row()->description;
        
        if ($active_sms_service != 'hubtel') {
            return null;
        }
        
        $hubtel_client_id = $this->db->get_where('settings', array('type' => 'hubtel_client_id'))->row()->description;
        $hubtel_client_secret = $this->db->get_where('settings', array('type' => 'hubtel_client_secret'))->row()->description;
        
        $curl = curl_init();
        
        curl_setopt_array($curl, array(
            CURLOPT_URL => "https://api.hubtel.com/v1/merchantaccount/merchants/" . $hubtel_client_id . "/balance",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 10,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "GET",
            CURLOPT_HTTPHEADER => array(
                "Authorization: Basic " . base64_encode("$hubtel_client_id:$hubtel_client_secret")
            ),
        ));
        
        $response = curl_exec($curl);
        $err = curl_error($curl);
        curl_close($curl);
        
        if ($err) {
            log_message('error', 'Hubtel Balance API Error: ' . $err);
            return null;
        }
        
        $result = json_decode($response, true);
        
        // Log response for debugging
        if (!$result || !isset($result['Balance'])) {
            log_message('error', 'Hubtel Balance Response: ' . $response);
        }
        
        return $result;
    }
    
    // Calculate SMS cost using actual Hubtel rate
    function calculate_sms_cost($messages_array) {
        $total_pages = 0;
        
        foreach ($messages_array as $message) {
            $message_length = strlen($message);
            // SMS pages: 1-160 chars = 1 page, 161-320 = 2 pages, etc.
            $pages = ceil($message_length / 160);
            $total_pages += $pages;
        }
        
        // Get actual rate from Hubtel
        $balance_info = $this->get_hubtel_balance();
        $rate_per_sms = 0.03; // Default fallback
        
        if ($balance_info && isset($balance_info['Credit'])) {
            $rate_per_sms = floatval($balance_info['Credit']);
        }
        
        return [
            'total_messages' => count($messages_array),
            'total_pages' => $total_pages,
            'cost_per_page' => $rate_per_sms,
            'total_cost' => $total_pages * $rate_per_sms
        ];
    }
}
