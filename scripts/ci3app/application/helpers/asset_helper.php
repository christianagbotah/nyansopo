<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Asset Helper
 * Provides versioned asset URLs for cache busting
 * 
 * @package     School Manager
 * @category    Helpers
 * @author      Professional Development Team
 */

if (!function_exists('asset_url')) {
    /**
     * Generate versioned asset URL
     * 
     * @param string $asset Asset path relative to base URL
     * @param bool $use_filemtime Use file modification time (default: true)
     * @return string Versioned asset URL
     */
    function asset_url($asset, $use_filemtime = true) {
        $CI =& get_instance();
        $base_url = $CI->config->item('base_url');
        
        // Remove leading slash if present
        $asset = ltrim($asset, '/');
        
        // Get version
        if ($use_filemtime) {
            // Use file modification time for automatic cache busting
            $file_path = FCPATH . $asset;
            if (file_exists($file_path)) {
                $version = filemtime($file_path);
            } else {
                // Fallback to app version if file doesn't exist
                $version = defined('APP_VERSION') ? APP_VERSION : '1.0.0';
            }
        } else {
            // Use application version
            $version = defined('APP_VERSION') ? APP_VERSION : '1.0.0';
        }
        
        // Build URL with version parameter
        $url = $base_url . $asset;
        $separator = (strpos($url, '?') !== false) ? '&' : '?';
        
        return $url . $separator . 'v=' . $version;
    }
}

if (!function_exists('css_url')) {
    /**
     * Generate versioned CSS URL
     * 
     * @param string $filename CSS filename (without .css extension)
     * @param string $folder Folder path (default: 'assets/css/')
     * @return string Versioned CSS URL
     */
    function css_url($filename, $folder = 'assets/css/') {
        // Add .css extension if not present
        if (substr($filename, -4) !== '.css') {
            $filename .= '.css';
        }
        
        return asset_url($folder . $filename);
    }
}

if (!function_exists('js_url')) {
    /**
     * Generate versioned JavaScript URL
     * 
     * @param string $filename JS filename (without .js extension)
     * @param string $folder Folder path (default: 'assets/js/')
     * @return string Versioned JS URL
     */
    function js_url($filename, $folder = 'assets/js/') {
        // Add .js extension if not present
        if (substr($filename, -3) !== '.js') {
            $filename .= '.js';
        }
        
        return asset_url($folder . $filename);
    }
}

if (!function_exists('img_url')) {
    /**
     * Generate versioned image URL
     * 
     * @param string $filename Image filename
     * @param string $folder Folder path (default: 'assets/images/')
     * @return string Versioned image URL
     */
    function img_url($filename, $folder = 'assets/images/') {
        return asset_url($folder . $filename);
    }
}

if (!function_exists('font_url')) {
    /**
     * Generate versioned font URL
     * 
     * @param string $filename Font filename
     * @param string $folder Folder path (default: 'assets/fonts/')
     * @return string Versioned font URL
     */
    function font_url($filename, $folder = 'assets/fonts/') {
        return asset_url($folder . $filename);
    }
}

if (!function_exists('upload_url')) {
    /**
     * Generate versioned upload URL
     * 
     * @param string $filename Upload filename
     * @param string $folder Folder path (default: 'uploads/')
     * @return string Versioned upload URL
     */
    function upload_url($filename, $folder = 'uploads/') {
        return asset_url($folder . $filename);
    }
}

if (!function_exists('preload_css')) {
    /**
     * Generate preload link tag for CSS
     * 
     * @param string $filename CSS filename
     * @param string $folder Folder path
     * @return string Preload link tag
     */
    function preload_css($filename, $folder = 'assets/css/') {
        $url = css_url($filename, $folder);
        return '<link rel="preload" href="' . $url . '" as="style" onload="this.onload=null;this.rel=\'stylesheet\'">';
    }
}

if (!function_exists('preload_js')) {
    /**
     * Generate preload link tag for JavaScript
     * 
     * @param string $filename JS filename
     * @param string $folder Folder path
     * @return string Preload link tag
     */
    function preload_js($filename, $folder = 'assets/js/') {
        $url = js_url($filename, $folder);
        return '<link rel="preload" href="' . $url . '" as="script">';
    }
}

if (!function_exists('preload_font')) {
    /**
     * Generate preload link tag for fonts
     * 
     * @param string $filename Font filename
     * @param string $folder Folder path
     * @param string $type Font type (default: 'font/woff2')
     * @return string Preload link tag
     */
    function preload_font($filename, $folder = 'assets/fonts/', $type = 'font/woff2') {
        $url = font_url($filename, $folder);
        return '<link rel="preload" href="' . $url . '" as="font" type="' . $type . '" crossorigin>';
    }
}

if (!function_exists('inline_css')) {
    /**
     * Inline critical CSS
     * 
     * @param string $filename CSS filename
     * @param string $folder Folder path
     * @return string Inline style tag with CSS content
     */
    function inline_css($filename, $folder = 'assets/css/') {
        $file_path = FCPATH . $folder . $filename;
        if (!file_exists($file_path)) {
            return '<!-- CSS file not found: ' . $filename . ' -->';
        }
        
        $css = file_get_contents($file_path);
        return '<style>' . $css . '</style>';
    }
}

if (!function_exists('defer_js')) {
    /**
     * Generate deferred script tag
     * 
     * @param string $filename JS filename
     * @param string $folder Folder path
     * @return string Script tag with defer attribute
     */
    function defer_js($filename, $folder = 'assets/js/') {
        $url = js_url($filename, $folder);
        return '<script src="' . $url . '" defer></script>';
    }
}

if (!function_exists('async_js')) {
    /**
     * Generate async script tag
     * 
     * @param string $filename JS filename
     * @param string $folder Folder path
     * @return string Script tag with async attribute
     */
    function async_js($filename, $folder = 'assets/js/') {
        $url = js_url($filename, $folder);
        return '<script src="' . $url . '" async></script>';
    }
}

if (!function_exists('lazy_img')) {
    /**
     * Generate lazy-loaded image tag
     * 
     * @param string $filename Image filename
     * @param string $alt Alt text
     * @param string $folder Folder path
     * @param string $class CSS classes
     * @return string Image tag with lazy loading
     */
    function lazy_img($filename, $alt = '', $folder = 'assets/images/', $class = '') {
        $url = img_url($filename, $folder);
        $placeholder = img_url('placeholder.png', 'uploads/');
        
        return '<img src="' . $placeholder . '" data-src="' . $url . '" alt="' . htmlspecialchars($alt) . '" class="' . $class . ' lazy" loading="lazy">';
    }
}

if (!function_exists('responsive_img')) {
    /**
     * Generate responsive image with srcset
     * 
     * @param string $filename Base image filename
     * @param string $alt Alt text
     * @param array $sizes Array of sizes (e.g., ['320w', '640w', '1024w'])
     * @param string $folder Folder path
     * @return string Picture element with responsive images
     */
    function responsive_img($filename, $alt = '', $sizes = [], $folder = 'assets/images/') {
        if (empty($sizes)) {
            return '<img src="' . img_url($filename, $folder) . '" alt="' . htmlspecialchars($alt) . '">';
        }
        
        $srcset = [];
        $ext = pathinfo($filename, PATHINFO_EXTENSION);
        $name = pathinfo($filename, PATHINFO_FILENAME);
        
        foreach ($sizes as $size) {
            $size_filename = $name . '-' . $size . '.' . $ext;
            $srcset[] = img_url($size_filename, $folder) . ' ' . $size;
        }
        
        $default_url = img_url($filename, $folder);
        
        return '<img src="' . $default_url . '" srcset="' . implode(', ', $srcset) . '" alt="' . htmlspecialchars($alt) . '" loading="lazy">';
    }
}

if (!function_exists('cache_bust')) {
    /**
     * Generate cache-busting query string
     * 
     * @param string $file_path File path relative to FCPATH
     * @return string Version query string
     */
    function cache_bust($file_path) {
        $full_path = FCPATH . ltrim($file_path, '/');
        
        if (file_exists($full_path)) {
            return '?v=' . filemtime($full_path);
        }
        
        return '?v=' . (defined('APP_VERSION') ? APP_VERSION : time());
    }
}

if (!function_exists('resource_hints')) {
    /**
     * Generate resource hints for external domains
     * 
     * @param array $domains Array of domains to preconnect
     * @return string Resource hint link tags
     */
    function resource_hints($domains = []) {
        $hints = '';
        
        foreach ($domains as $domain) {
            $hints .= '<link rel="preconnect" href="' . $domain . '">' . "\n";
            $hints .= '<link rel="dns-prefetch" href="' . $domain . '">' . "\n";
        }
        
        return $hints;
    }
}

if (!function_exists('critical_css')) {
    /**
     * Load critical CSS inline and defer full CSS
     * 
     * @param string $critical_file Critical CSS filename
     * @param string $full_file Full CSS filename
     * @param string $folder Folder path
     * @return string Critical CSS inline + deferred full CSS
     */
    function critical_css($critical_file, $full_file, $folder = 'assets/css/') {
        $output = inline_css($critical_file, $folder);
        $output .= "\n" . preload_css($full_file, $folder);
        $output .= "\n" . '<noscript><link rel="stylesheet" href="' . css_url($full_file, $folder) . '"></noscript>';
        
        return $output;
    }
}
