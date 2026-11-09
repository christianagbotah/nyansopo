<?php
/**
 * Edit Location Modal Form
 * 
 * Modal form for editing an existing sync location with pre-filled data,
 * modern styling, HTML5 validation, and gradient header.
 * 
 * @package    School Manager
 * @subpackage Views
 * @category   Sync
 * 
 * Requirements: 4.2, 4.4, 4.5, 4.6, 4.7, 7.5
 * Task 4.4: Edit location modal form
 */

// Get location data from controller
$location = $this->location ?? [];
$location_id = $location['id'] ?? 0;
$location_name = $location['location_name'] ?? '';
$api_endpoint = $location['api_endpoint'] ?? '';
$contact_email = $location['contact_email'] ?? '';
$contact_phone = $location['contact_phone'] ?? '';
$timezone = $location['timezone'] ?? '';
$status = $location['status'] ?? 'active';
?>

<div class="modal fade" id="editLocationModal" tabindex="-1" role="dialog" aria-labelledby="editLocationModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content sync-modal-content">
            <!-- Modal Header with Gradient -->
            <div class="modal-header sync-modal-header">
                <h5 class="modal-title" id="editLocationModalLabel">
                    <i class="fa fa-edit"></i> Edit Location
                </h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>

            <!-- Modal Body -->
            <div class="modal-body sync-modal-body">
                <form id="editLocationForm" class="sync-modal-form" novalidate>
                    <!-- Hidden Location ID -->
                    <input type="hidden" name="location_id" id="edit_location_id" value="<?php echo $location_id; ?>">

                    <!-- Location Name -->
                    <div class="form-group">
                        <label for="edit_location_name">
                            Location Name <span style="color: var(--color-error);">*</span>
                        </label>
                        <input 
                            type="text" 
                            class="form-control" 
                            id="edit_location_name" 
                            name="location_name" 
                            placeholder="e.g., Main Campus, Branch Office"
                            value="<?php echo htmlspecialchars($location_name); ?>"
                            required
                            maxlength="100"
                            aria-required="true"
                            aria-describedby="edit_location_name_help"
                        >
                        <small id="edit_location_name_help" class="form-text text-muted">
                            A descriptive name for this sync location
                        </small>
                        <div class="error-message" id="edit_location_name_error" style="display: none;">
                            <i class="fa fa-exclamation-circle"></i>
                            <span></span>
                        </div>
                    </div>

                    <!-- API Endpoint -->
                    <div class="form-group">
                        <label for="edit_api_endpoint">
                            API Endpoint <span style="color: var(--color-error);">*</span>
                        </label>
                        <input 
                            type="url" 
                            class="form-control" 
                            id="edit_api_endpoint" 
                            name="api_endpoint" 
                            placeholder="https://example.com/api/sync"
                            value="<?php echo htmlspecialchars($api_endpoint); ?>"
                            required
                            pattern="https?://.+"
                            aria-required="true"
                            aria-describedby="edit_api_endpoint_help"
                        >
                        <small id="edit_api_endpoint_help" class="form-text text-muted">
                            The full URL to the sync API endpoint (must start with http:// or https://)
                        </small>
                        <div class="error-message" id="edit_api_endpoint_error" style="display: none;">
                            <i class="fa fa-exclamation-circle"></i>
                            <span></span>
                        </div>
                    </div>

                    <!-- Contact Email -->
                    <div class="form-group">
                        <label for="edit_contact_email">
                            Contact Email <span style="color: var(--color-error);">*</span>
                        </label>
                        <input 
                            type="email" 
                            class="form-control" 
                            id="edit_contact_email" 
                            name="contact_email" 
                            placeholder="admin@example.com"
                            value="<?php echo htmlspecialchars($contact_email); ?>"
                            required
                            aria-required="true"
                            aria-describedby="edit_contact_email_help"
                        >
                        <small id="edit_contact_email_help" class="form-text text-muted">
                            Primary contact email for this location
                        </small>
                        <div class="error-message" id="edit_contact_email_error" style="display: none;">
                            <i class="fa fa-exclamation-circle"></i>
                            <span></span>
                        </div>
                    </div>

                    <!-- Contact Phone -->
                    <div class="form-group">
                        <label for="edit_contact_phone">
                            Contact Phone
                        </label>
                        <input 
                            type="tel" 
                            class="form-control" 
                            id="edit_contact_phone" 
                            name="contact_phone" 
                            placeholder="+1 (555) 123-4567"
                            value="<?php echo htmlspecialchars($contact_phone); ?>"
                            aria-describedby="edit_contact_phone_help"
                        >
                        <small id="edit_contact_phone_help" class="form-text text-muted">
                            Optional contact phone number
                        </small>
                        <div class="error-message" id="edit_contact_phone_error" style="display: none;">
                            <i class="fa fa-exclamation-circle"></i>
                            <span></span>
                        </div>
                    </div>

                    <!-- Timezone -->
                    <div class="form-group">
                        <label for="edit_timezone">
                            Timezone <span style="color: var(--color-error);">*</span>
                        </label>
                        <select 
                            class="form-control" 
                            id="edit_timezone" 
                            name="timezone" 
                            required
                            aria-required="true"
                            aria-describedby="edit_timezone_help"
                        >
                            <option value="">Select Timezone</option>
                            <option value="Africa/Accra" <?php echo $timezone === 'Africa/Accra' ? 'selected' : ''; ?>>Africa/Accra (GMT)</option>
                            <option value="Africa/Cairo" <?php echo $timezone === 'Africa/Cairo' ? 'selected' : ''; ?>>Africa/Cairo (GMT+2)</option>
                            <option value="Africa/Johannesburg" <?php echo $timezone === 'Africa/Johannesburg' ? 'selected' : ''; ?>>Africa/Johannesburg (GMT+2)</option>
                            <option value="Africa/Lagos" <?php echo $timezone === 'Africa/Lagos' ? 'selected' : ''; ?>>Africa/Lagos (GMT+1)</option>
                            <option value="Africa/Nairobi" <?php echo $timezone === 'Africa/Nairobi' ? 'selected' : ''; ?>>Africa/Nairobi (GMT+3)</option>
                            <option value="America/Chicago" <?php echo $timezone === 'America/Chicago' ? 'selected' : ''; ?>>America/Chicago (CST)</option>
                            <option value="America/Denver" <?php echo $timezone === 'America/Denver' ? 'selected' : ''; ?>>America/Denver (MST)</option>
                            <option value="America/Los_Angeles" <?php echo $timezone === 'America/Los_Angeles' ? 'selected' : ''; ?>>America/Los_Angeles (PST)</option>
                            <option value="America/New_York" <?php echo $timezone === 'America/New_York' ? 'selected' : ''; ?>>America/New_York (EST)</option>
                            <option value="America/Toronto" <?php echo $timezone === 'America/Toronto' ? 'selected' : ''; ?>>America/Toronto (EST)</option>
                            <option value="Asia/Dubai" <?php echo $timezone === 'Asia/Dubai' ? 'selected' : ''; ?>>Asia/Dubai (GMT+4)</option>
                            <option value="Asia/Hong_Kong" <?php echo $timezone === 'Asia/Hong_Kong' ? 'selected' : ''; ?>>Asia/Hong_Kong (GMT+8)</option>
                            <option value="Asia/Kolkata" <?php echo $timezone === 'Asia/Kolkata' ? 'selected' : ''; ?>>Asia/Kolkata (IST)</option>
                            <option value="Asia/Shanghai" <?php echo $timezone === 'Asia/Shanghai' ? 'selected' : ''; ?>>Asia/Shanghai (CST)</option>
                            <option value="Asia/Singapore" <?php echo $timezone === 'Asia/Singapore' ? 'selected' : ''; ?>>Asia/Singapore (SGT)</option>
                            <option value="Asia/Tokyo" <?php echo $timezone === 'Asia/Tokyo' ? 'selected' : ''; ?>>Asia/Tokyo (JST)</option>
                            <option value="Australia/Sydney" <?php echo $timezone === 'Australia/Sydney' ? 'selected' : ''; ?>>Australia/Sydney (AEDT)</option>
                            <option value="Europe/Berlin" <?php echo $timezone === 'Europe/Berlin' ? 'selected' : ''; ?>>Europe/Berlin (CET)</option>
                            <option value="Europe/London" <?php echo $timezone === 'Europe/London' ? 'selected' : ''; ?>>Europe/London (GMT)</option>
                            <option value="Europe/Paris" <?php echo $timezone === 'Europe/Paris' ? 'selected' : ''; ?>>Europe/Paris (CET)</option>
                            <option value="Pacific/Auckland" <?php echo $timezone === 'Pacific/Auckland' ? 'selected' : ''; ?>>Pacific/Auckland (NZDT)</option>
                            <option value="UTC" <?php echo $timezone === 'UTC' ? 'selected' : ''; ?>>UTC (Universal Time)</option>
                        </select>
                        <small id="edit_timezone_help" class="form-text text-muted">
                            The timezone for this location
                        </small>
                        <div class="error-message" id="edit_timezone_error" style="display: none;">
                            <i class="fa fa-exclamation-circle"></i>
                            <span></span>
                        </div>
                    </div>

                    <!-- Status -->
                    <div class="form-group">
                        <label for="edit_status">
                            Status <span style="color: var(--color-error);">*</span>
                        </label>
                        <select 
                            class="form-control" 
                            id="edit_status" 
                            name="status" 
                            required
                            aria-required="true"
                            aria-describedby="edit_status_help"
                        >
                            <option value="active" <?php echo $status === 'active' ? 'selected' : ''; ?>>Active</option>
                            <option value="inactive" <?php echo $status === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                            <option value="suspended" <?php echo $status === 'suspended' ? 'selected' : ''; ?>>Suspended</option>
                        </select>
                        <small id="edit_status_help" class="form-text text-muted">
                            The current status of this location
                        </small>
                        <div class="error-message" id="edit_status_error" style="display: none;">
                            <i class="fa fa-exclamation-circle"></i>
                            <span></span>
                        </div>
                    </div>

                    <!-- Form Note -->
                    <div style="background: var(--color-warning-light); padding: var(--spacing-lg); border-radius: var(--radius-md); margin-top: var(--spacing-xl);">
                        <div style="display: flex; align-items: flex-start; gap: var(--spacing-md);">
                            <i class="fa fa-exclamation-triangle" style="color: var(--color-warning); font-size: 20px; margin-top: 2px;"></i>
                            <div style="flex: 1;">
                                <strong style="color: var(--color-warning-dark);">Warning:</strong>
                                <p style="margin: var(--spacing-xs) 0 0 0; font-size: var(--font-size-xs); color: var(--color-gray-700);">
                                    Changing the API endpoint or status may affect ongoing sync operations. Ensure the remote location is properly configured before saving changes.
                                </p>
                            </div>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Modal Footer -->
            <div class="modal-footer sync-modal-footer">
                <button type="button" class="sync-btn sync-btn-secondary" data-dismiss="modal">
                    <i class="fa fa-times"></i> Cancel
                </button>
                <button type="submit" class="sync-btn sync-btn-primary" id="submitEditLocationBtn" form="editLocationForm">
                    <i class="fa fa-save"></i> Update Location
                </button>
            </div>
        </div>
    </div>
</div>
