<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * QR Code Helper with Logo Overlay
 * Generates QR code with school logo in the center
 */

if (!function_exists('generate_qr_with_logo')) {
    /**
     * Generate QR Code with school logo overlay
     * 
     * @param string $data Data to encode in QR code
     * @param string $logo_path Path to school logo
     * @param int $size QR code size (default: 300)
     * @param int $logo_size Logo size as percentage of QR (default: 25)
     * @return string Base64 encoded image data or false on failure
     */
    function generate_qr_with_logo($data, $logo_path, $size = 300, $logo_size_percent = 25) {
        // Generate QR code from external API with higher error correction (for logo overlay)
        // Using level H (high) error correction - can recover up to 30% data loss
        $qr_url = 'https://api.qrserver.com/v1/create-qr-code/?size=' . $size . 'x' . $size . '&ecc=H&data=' . urlencode($data);
        
        // Get QR code image
        $qr_image_data = @file_get_contents($qr_url);
        if ($qr_image_data === false) {
            return false;
        }
        
        // Create image from QR code data
        $qr_image = @imagecreatefromstring($qr_image_data);
        if ($qr_image === false) {
            return false;
        }
        
        // Check if logo file exists
        if (!file_exists($logo_path)) {
            // Return QR without logo
            ob_start();
            imagepng($qr_image);
            $output = ob_get_clean();
            imagedestroy($qr_image);
            return 'data:image/png;base64,' . base64_encode($output);
        }
        
        // Load logo image
        $logo_image = false;
        $logo_ext = strtolower(pathinfo($logo_path, PATHINFO_EXTENSION));
        
        switch ($logo_ext) {
            case 'png':
                $logo_image = @imagecreatefrompng($logo_path);
                break;
            case 'jpg':
            case 'jpeg':
                $logo_image = @imagecreatefromjpeg($logo_path);
                break;
            case 'gif':
                $logo_image = @imagecreatefromgif($logo_path);
                break;
        }
        
        if ($logo_image === false) {
            // Return QR without logo
            ob_start();
            imagepng($qr_image);
            $output = ob_get_clean();
            imagedestroy($qr_image);
            return 'data:image/png;base64,' . base64_encode($output);
        }
        
        // Calculate logo dimensions
        $logo_width = imagesx($logo_image);
        $logo_height = imagesy($logo_image);
        
        // Calculate new logo size (percentage of QR code)
        $logo_new_size = ($size * $logo_size_percent) / 100;
        
        // Maintain aspect ratio
        if ($logo_width > $logo_height) {
            $logo_new_width = $logo_new_size;
            $logo_new_height = ($logo_height / $logo_width) * $logo_new_size;
        } else {
            $logo_new_height = $logo_new_size;
            $logo_new_width = ($logo_width / $logo_height) * $logo_new_size;
        }
        
        // Create white background for logo (makes it stand out better)
        $logo_bg_size = $logo_new_size + 10; // Add 5px padding on each side
        $logo_bg = imagecreatetruecolor($logo_bg_size, $logo_bg_size);
        $white = imagecolorallocate($logo_bg, 255, 255, 255);
        imagefill($logo_bg, 0, 0, $white);
        
        // Calculate position to center logo on white background
        $logo_x_on_bg = ($logo_bg_size - $logo_new_width) / 2;
        $logo_y_on_bg = ($logo_bg_size - $logo_new_height) / 2;
        
        // Resize and copy logo onto white background
        imagecopyresampled(
            $logo_bg, 
            $logo_image, 
            $logo_x_on_bg, 
            $logo_y_on_bg, 
            0, 
            0, 
            $logo_new_width, 
            $logo_new_height, 
            $logo_width, 
            $logo_height
        );
        
        // Calculate position to center logo on QR code
        $qr_x = ($size - $logo_bg_size) / 2;
        $qr_y = ($size - $logo_bg_size) / 2;
        
        // Overlay logo (with background) onto QR code
        imagecopy($qr_image, $logo_bg, $qr_x, $qr_y, 0, 0, $logo_bg_size, $logo_bg_size);
        
        // Output as base64 encoded PNG
        ob_start();
        imagepng($qr_image);
        $output = ob_get_clean();
        
        // Clean up
        imagedestroy($qr_image);
        imagedestroy($logo_image);
        imagedestroy($logo_bg);
        
        return 'data:image/png;base64,' . base64_encode($output);
    }
}
