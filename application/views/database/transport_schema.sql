-- Enhanced Transport Management System Database Schema

-- Routes table (enhanced)
CREATE TABLE IF NOT EXISTS `transport_routes` (
  `route_id` int(11) NOT NULL AUTO_INCREMENT,
  `route_name` varchar(255) NOT NULL,
  `route_code` varchar(50) NOT NULL UNIQUE,
  `route_fare` decimal(10,2) DEFAULT 0.00,
  `total_distance` decimal(10,2) DEFAULT 0.00,
  `total_stops` int(11) DEFAULT 0,
  `description` text,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`route_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- Route stops table
CREATE TABLE IF NOT EXISTS `transport_stops` (
  `stop_id` int(11) NOT NULL AUTO_INCREMENT,
  `route_id` int(11) NOT NULL,
  `stop_name` varchar(255) NOT NULL,
  `stop_order` int(11) NOT NULL,
  `latitude` decimal(10,8) DEFAULT NULL,
  `longitude` decimal(11,8) DEFAULT NULL,
  `pickup_time` time DEFAULT NULL,
  `dropoff_time` time DEFAULT NULL,
  `distance_from_school` decimal(10,2) DEFAULT 0.00,
  `fare_from_stop` decimal(10,2) DEFAULT 0.00,
  `landmark` varchar(255) DEFAULT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  PRIMARY KEY (`stop_id`),
  KEY `route_id` (`route_id`),
  FOREIGN KEY (`route_id`) REFERENCES `transport_routes`(`route_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- Vehicles table
CREATE TABLE IF NOT EXISTS `transport_vehicles` (
  `vehicle_id` int(11) NOT NULL AUTO_INCREMENT,
  `vehicle_number` varchar(50) NOT NULL UNIQUE,
  `vehicle_type` varchar(50) DEFAULT NULL,
  `vehicle_model` varchar(100) DEFAULT NULL,
  `manufacture_year` year DEFAULT NULL,
  `seating_capacity` int(11) NOT NULL,
  `registration_number` varchar(100) DEFAULT NULL,
  `insurance_number` varchar(100) DEFAULT NULL,
  `insurance_expiry` date DEFAULT NULL,
  `fitness_certificate` varchar(255) DEFAULT NULL,
  `fitness_expiry` date DEFAULT NULL,
  `pollution_certificate` varchar(255) DEFAULT NULL,
  `pollution_expiry` date DEFAULT NULL,
  `gps_device_id` varchar(100) DEFAULT NULL,
  `status` enum('active','inactive','maintenance') DEFAULT 'active',
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`vehicle_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- Vehicle maintenance log
CREATE TABLE IF NOT EXISTS `transport_vehicle_maintenance` (
  `maintenance_id` int(11) NOT NULL AUTO_INCREMENT,
  `vehicle_id` int(11) NOT NULL,
  `maintenance_type` varchar(100) NOT NULL,
  `description` text,
  `maintenance_date` date NOT NULL,
  `cost` decimal(10,2) DEFAULT 0.00,
  `next_maintenance_date` date DEFAULT NULL,
  `performed_by` varchar(255) DEFAULT NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`maintenance_id`),
  KEY `vehicle_id` (`vehicle_id`),
  FOREIGN KEY (`vehicle_id`) REFERENCES `transport_vehicles`(`vehicle_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- Drivers table
CREATE TABLE IF NOT EXISTS `transport_drivers` (
  `driver_id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `email` varchar(255) DEFAULT NULL,
  `address` text,
  `license_number` varchar(100) NOT NULL UNIQUE,
  `license_type` varchar(50) DEFAULT NULL,
  `license_expiry` date NOT NULL,
  `date_of_birth` date DEFAULT NULL,
  `blood_group` varchar(10) DEFAULT NULL,
  `emergency_contact` varchar(20) DEFAULT NULL,
  `emergency_contact_name` varchar(255) DEFAULT NULL,
  `joining_date` date DEFAULT NULL,
  `experience_years` int(11) DEFAULT 0,
  `background_check` enum('pending','verified','failed') DEFAULT 'pending',
  `photo` varchar(255) DEFAULT NULL,
  `status` enum('active','inactive','suspended') DEFAULT 'active',
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`driver_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- Vehicle-Driver-Route assignment
CREATE TABLE IF NOT EXISTS `transport_assignments` (
  `assignment_id` int(11) NOT NULL AUTO_INCREMENT,
  `vehicle_id` int(11) NOT NULL,
  `driver_id` int(11) NOT NULL,
  `route_id` int(11) NOT NULL,
  `assistant_name` varchar(255) DEFAULT NULL,
  `assistant_phone` varchar(20) DEFAULT NULL,
  `shift_type` enum('morning','afternoon','both') DEFAULT 'both',
  `start_date` date NOT NULL,
  `end_date` date DEFAULT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  PRIMARY KEY (`assignment_id`),
  KEY `vehicle_id` (`vehicle_id`),
  KEY `driver_id` (`driver_id`),
  KEY `route_id` (`route_id`),
  FOREIGN KEY (`vehicle_id`) REFERENCES `transport_vehicles`(`vehicle_id`) ON DELETE CASCADE,
  FOREIGN KEY (`driver_id`) REFERENCES `transport_drivers`(`driver_id`) ON DELETE CASCADE,
  FOREIGN KEY (`route_id`) REFERENCES `transport_routes`(`route_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- Student transport assignment
CREATE TABLE IF NOT EXISTS `transport_student_assignment` (
  `assignment_id` int(11) NOT NULL AUTO_INCREMENT,
  `student_id` int(11) NOT NULL,
  `route_id` int(11) NOT NULL,
  `stop_id` int(11) NOT NULL,
  `transport_type` enum('pickup','dropoff','both') DEFAULT 'both',
  `start_date` date NOT NULL,
  `end_date` date DEFAULT NULL,
  `monthly_fee` decimal(10,2) DEFAULT 0.00,
  `parent_phone` varchar(20) DEFAULT NULL,
  `emergency_contact` varchar(20) DEFAULT NULL,
  `special_needs` text,
  `status` enum('active','inactive','suspended') DEFAULT 'active',
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`assignment_id`),
  KEY `student_id` (`student_id`),
  KEY `route_id` (`route_id`),
  KEY `stop_id` (`stop_id`),
  FOREIGN KEY (`route_id`) REFERENCES `transport_routes`(`route_id`) ON DELETE CASCADE,
  FOREIGN KEY (`stop_id`) REFERENCES `transport_stops`(`stop_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- Transport attendance
CREATE TABLE IF NOT EXISTS `transport_attendance` (
  `attendance_id` int(11) NOT NULL AUTO_INCREMENT,
  `student_id` int(11) NOT NULL,
  `route_id` int(11) NOT NULL,
  `vehicle_id` int(11) NOT NULL,
  `stop_id` int(11) NOT NULL,
  `attendance_date` date NOT NULL,
  `pickup_time` time DEFAULT NULL,
  `pickup_status` enum('present','absent','late','pending') DEFAULT 'pending',
  `pickup_latitude` decimal(10,8) DEFAULT NULL,
  `pickup_longitude` decimal(11,8) DEFAULT NULL,
  `dropoff_time` time DEFAULT NULL,
  `dropoff_status` enum('present','absent','late','pending') DEFAULT 'pending',
  `dropoff_latitude` decimal(10,8) DEFAULT NULL,
  `dropoff_longitude` decimal(11,8) DEFAULT NULL,
  `marked_by` int(11) DEFAULT NULL,
  `remarks` text,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`attendance_id`),
  KEY `student_id` (`student_id`),
  KEY `route_id` (`route_id`),
  KEY `attendance_date` (`attendance_date`),
  FOREIGN KEY (`route_id`) REFERENCES `transport_routes`(`route_id`) ON DELETE CASCADE,
  FOREIGN KEY (`vehicle_id`) REFERENCES `transport_vehicles`(`vehicle_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- Vehicle tracking (GPS data)
CREATE TABLE IF NOT EXISTS `transport_vehicle_tracking` (
  `tracking_id` int(11) NOT NULL AUTO_INCREMENT,
  `vehicle_id` int(11) NOT NULL,
  `latitude` decimal(10,8) NOT NULL,
  `longitude` decimal(11,8) NOT NULL,
  `speed` decimal(5,2) DEFAULT 0.00,
  `direction` varchar(10) DEFAULT NULL,
  `ignition_status` enum('on','off') DEFAULT 'off',
  `timestamp` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`tracking_id`),
  KEY `vehicle_id` (`vehicle_id`),
  KEY `timestamp` (`timestamp`),
  FOREIGN KEY (`vehicle_id`) REFERENCES `transport_vehicles`(`vehicle_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- Parent notifications
CREATE TABLE IF NOT EXISTS `transport_notifications` (
  `notification_id` int(11) NOT NULL AUTO_INCREMENT,
  `student_id` int(11) NOT NULL,
  `notification_type` enum('pickup','dropoff','delay','emergency','general') NOT NULL,
  `title` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `sent_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `read_status` enum('unread','read') DEFAULT 'unread',
  `delivery_status` enum('pending','sent','failed') DEFAULT 'pending',
  PRIMARY KEY (`notification_id`),
  KEY `student_id` (`student_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- Trip schedule
CREATE TABLE IF NOT EXISTS `transport_trip_schedule` (
  `schedule_id` int(11) NOT NULL AUTO_INCREMENT,
  `route_id` int(11) NOT NULL,
  `vehicle_id` int(11) NOT NULL,
  `trip_type` enum('pickup','dropoff') NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time DEFAULT NULL,
  `days_of_week` varchar(50) DEFAULT 'Mon,Tue,Wed,Thu,Fri',
  `status` enum('active','inactive') DEFAULT 'active',
  PRIMARY KEY (`schedule_id`),
  KEY `route_id` (`route_id`),
  KEY `vehicle_id` (`vehicle_id`),
  FOREIGN KEY (`route_id`) REFERENCES `transport_routes`(`route_id`) ON DELETE CASCADE,
  FOREIGN KEY (`vehicle_id`) REFERENCES `transport_vehicles`(`vehicle_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- Incidents/Issues log
CREATE TABLE IF NOT EXISTS `transport_incidents` (
  `incident_id` int(11) NOT NULL AUTO_INCREMENT,
  `vehicle_id` int(11) DEFAULT NULL,
  `driver_id` int(11) DEFAULT NULL,
  `route_id` int(11) DEFAULT NULL,
  `incident_type` enum('accident','breakdown','delay','complaint','other') NOT NULL,
  `incident_date` date NOT NULL,
  `incident_time` time DEFAULT NULL,
  `description` text NOT NULL,
  `severity` enum('low','medium','high','critical') DEFAULT 'low',
  `location` varchar(255) DEFAULT NULL,
  `action_taken` text,
  `reported_by` varchar(255) DEFAULT NULL,
  `status` enum('open','investigating','resolved','closed') DEFAULT 'open',
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`incident_id`),
  KEY `vehicle_id` (`vehicle_id`),
  KEY `driver_id` (`driver_id`),
  FOREIGN KEY (`vehicle_id`) REFERENCES `transport_vehicles`(`vehicle_id`) ON DELETE SET NULL,
  FOREIGN KEY (`driver_id`) REFERENCES `transport_drivers`(`driver_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- Transport fee payments
CREATE TABLE IF NOT EXISTS `transport_payments` (
  `payment_id` int(11) NOT NULL AUTO_INCREMENT,
  `student_id` int(11) NOT NULL,
  `route_id` int(11) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `payment_month` varchar(20) NOT NULL,
  `payment_date` date NOT NULL,
  `payment_method` enum('cash','card','online','cheque') DEFAULT 'cash',
  `transaction_id` varchar(100) DEFAULT NULL,
  `receipt_number` varchar(100) DEFAULT NULL,
  `status` enum('pending','paid','overdue','cancelled') DEFAULT 'pending',
  `remarks` text,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`payment_id`),
  KEY `student_id` (`student_id`),
  KEY `route_id` (`route_id`),
  FOREIGN KEY (`route_id`) REFERENCES `transport_routes`(`route_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- Indexes for performance
CREATE INDEX idx_attendance_date_student ON transport_attendance(attendance_date, student_id);
CREATE INDEX idx_tracking_vehicle_time ON transport_vehicle_tracking(vehicle_id, timestamp);
CREATE INDEX idx_student_assignment_active ON transport_student_assignment(student_id, status);
