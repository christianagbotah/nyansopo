<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Subject Code Helper
 * 
 * Helper functions for generating subject codes from subject names
 */

if (!function_exists('get_subject_code')) {
    /**
     * Generate subject code from subject name
     * Maps common GES subject names to standard codes
     * 
     * @param string $subject_name The full subject name
     * @return string The subject code (uppercase)
     */
    function get_subject_code($subject_name) {
        // Normalize the subject name
        $name = trim($subject_name);
        $name_lower = strtolower($name);
        
        // Standard GES subject code mappings
        $subject_mappings = array(
            // Core Subjects
            'english language' => 'ENG',
            'english' => 'ENG',
            'mathematics' => 'MATHS',
            'maths' => 'MATHS',
            'integrated science' => 'SCI',
            'science' => 'SCI',
            'social studies' => 'SOC',
            'social' => 'SOC',
            
            // Languages
            'french' => 'FRE',
            'french language' => 'FRE',
            'ga' => 'GA',
            'ewe' => 'EWE',
            'twi' => 'TWI',
            'fante' => 'FAN',
            'asante twi' => 'TWI',
            'akuapem twi' => 'TWI',
            'dagbani' => 'DAG',
            'nzema' => 'NZE',
            
            // Religious & Moral Education
            'religious and moral education' => 'RME',
            'rme' => 'RME',
            'religious education' => 'RME',
            'moral education' => 'RME',
            
            // ICT & Computing
            'information and communication technology' => 'ICT',
            'information technology' => 'ICT',
            'ict' => 'ICT',
            'computing' => 'ICT',
            'computer studies' => 'ICT',
            
            // Creative Arts & Design
            'creative arts' => 'CRA',
            'creative arts and design' => 'CRA',
            'visual arts' => 'VAR',
            'music' => 'MUS',
            'dance' => 'DAN',
            'drama' => 'DRA',
            
            // Career Technology
            'career technology' => 'CT',
            'basic design and technology' => 'BDT',
            'technical skills' => 'TECH',
            'home economics' => 'HE',
            'home science' => 'HE',
            
            // Physical Education
            'physical education' => 'PE',
            'pe' => 'PE',
            'physical and health education' => 'PHE',
            'health education' => 'HE',
            
            // JHS Elective Subjects
            'agricultural science' => 'AGRIC',
            'agriculture' => 'AGRIC',
            'business studies' => 'BUS',
            'business' => 'BUS',
            'history' => 'HIST',
            'geography' => 'GEOG',
            'economics' => 'ECONS',
            'religious studies' => 'RS',
            'biology' => 'BIO',
            'chemistry' => 'CHEM',
            'physics' => 'PHY',
            
            // SHS Core Subjects
            'core mathematics' => 'C/MATHS',
            'elective mathematics' => 'E/MATHS',
            'general knowledge in art' => 'GKA',
            
            // SHS Elective Subjects - Science
            'elective chemistry' => 'E/CHEM',
            'elective physics' => 'E/PHY',
            'elective biology' => 'E/BIO',
            
            // SHS Elective Subjects - Arts
            'literature' => 'LIT',
            'literature in english' => 'LIT',
            'government' => 'GOV',
            'christian religious studies' => 'CRS',
            'islamic studies' => 'IRS',
            
            // SHS Elective Subjects - Business
            'financial accounting' => 'F/ACC',
            'cost accounting' => 'C/ACC',
            'business management' => 'B/MGT',
            'economics' => 'ECONS',
            'costing' => 'COST',
            
            // SHS Elective Subjects - Technical
            'technical drawing' => 'TD',
            'graphic design' => 'GD',
            'woodwork' => 'WOOD',
            'metalwork' => 'METAL',
            'building construction' => 'BC',
            'electronics' => 'ELECT',
            'auto mechanics' => 'AUTO',
            
            // SHS Elective Subjects - Home Economics
            'general home economics' => 'GHE',
            'food and nutrition' => 'F&N',
            'clothing and textiles' => 'C&T',
            'management in living' => 'MIL',
            
            // Creche/Primary Subjects
            'literacy' => 'LIT',
            'numeracy' => 'NUM',
            'our world our people' => 'OWOP',
            'phonics' => 'PHO',
            'writing' => 'WRI',
            'reading' => 'READ',
            'rhymes' => 'RHY',
            'colouring' => 'COL',
            'environmental studies' => 'ENV',
            'nature study' => 'NAT',
        );
        
        // Check for exact match (case-insensitive)
        if (isset($subject_mappings[$name_lower])) {
            return $subject_mappings[$name_lower];
        }
        
        // Check for partial match (if subject name contains a key)
        foreach ($subject_mappings as $key => $code) {
            if (stripos($name_lower, $key) !== false) {
                return $code;
            }
        }
        
        // If no match found, generate code from first 3-4 characters
        // Remove common words
        $words_to_remove = array('the', 'and', 'or', 'of', 'in', 'for', 'to', 'a', 'an');
        $words = explode(' ', $name_lower);
        $filtered_words = array_filter($words, function($word) use ($words_to_remove) {
            return !in_array($word, $words_to_remove) && strlen($word) > 2;
        });
        
        if (!empty($filtered_words)) {
            // Use first letters of significant words (acronym style)
            if (count($filtered_words) >= 2) {
                $code = '';
                foreach ($filtered_words as $word) {
                    $code .= strtoupper(substr($word, 0, 1));
                    if (strlen($code) >= 4) break;
                }
                return $code;
            } else {
                // Single significant word - take first 3-4 chars
                $word = reset($filtered_words);
                return strtoupper(substr($word, 0, min(4, strlen($word))));
            }
        }
        
        // Fallback: first 3 characters of original name
        return strtoupper(substr($name, 0, 3));
    }
}

if (!function_exists('get_subject_code_with_fallback')) {
    /**
     * Get subject code with database fallback
     * Checks if subject array has subject_code field, otherwise generates one
     * 
     * @param array $subject Subject array from database
     * @return string The subject code (uppercase)
     */
    function get_subject_code_with_fallback($subject) {
        // Check if subject_code exists and is not empty
        if (isset($subject['subject_code']) && !empty($subject['subject_code'])) {
            return strtoupper(trim($subject['subject_code']));
        }
        
        // Generate from subject name
        if (isset($subject['name']) && !empty($subject['name'])) {
            return get_subject_code($subject['name']);
        }
        
        // Ultimate fallback
        return 'SUB';
    }
}
