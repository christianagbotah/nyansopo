<?php
/**
 * Add Location Modal Form
 * 
 * Modal form for adding a new sync location with modern styling,
 * HTML5 validation, and gradient header.
 * 
 * @package    School Manager
 * @subpackage Views
 * @category   Sync
 * 
 * Requirements: 4.1, 4.4, 4.5, 4.6, 4.7, 7.4
 * Task 4.3: Add location modal form
 */
?>

<div class="modal fade" id="addLocationModal" tabindex="-1" role="dialog" aria-labelledby="addLocationModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content sync-modal-content">
            <!-- Modal Header with Gradient -->
            <div class="modal-header sync-modal-header">
                <h5 class="modal-title" id="addLocationModalLabel">
                    <i class="fa fa-plus-circle"></i> Add New Location
                </h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>

            <!-- Modal Body -->
            <div class="modal-body sync-modal-body">
                <form id="addLocationForm" class="sync-modal-form" novalidate>
                    <!-- Location Name -->
                    <div class="form-group">
                        <label for="location_name">
                            Location Name <span style="color: var(--color-error);">*</span>
                        </label>
                        <input 
                            type="text" 
                            class="form-control" 
                            id="location_name" 
                            name="location_name" 
                            placeholder="e.g., Main Campus, Branch Office"
                            required
                            maxlength="100"
                            aria-required="true"
                            aria-describedby="location_name_help"
                        >
                        <small id="location_name_help" class="form-text text-muted">
                            A descriptive name for this sync location
                        </small>
                        <div class="error-message" id="location_name_error" style="display: none;">
                            <i class="fa fa-exclamation-circle"></i>
                            <span></span>
                        </div>
                    </div>

                    <!-- API Endpoint -->
                    <div class="form-group">
                        <label for="api_endpoint">
                            API Endpoint <span style="color: var(--color-error);">*</span>
                        </label>
                        <input 
                            type="url" 
                            class="form-control" 
                            id="api_endpoint" 
                            name="api_endpoint" 
                            placeholder="https://example.com/api/sync"
                            required
                            pattern="https?://.+"
                            aria-required="true"
                            aria-describedby="api_endpoint_help"
                        >
                        <small id="api_endpoint_help" class="form-text text-muted">
                            The full URL to the sync API endpoint (must start with http:// or https://)
                        </small>
                        <div class="error-message" id="api_endpoint_error" style="display: none;">
                            <i class="fa fa-exclamation-circle"></i>
                            <span></span>
                        </div>
                    </div>

                    <!-- Contact Email -->
                    <div class="form-group">
                        <label for="contact_email">
                            Contact Email <span style="color: var(--color-error);">*</span>
                        </label>
                        <input 
                            type="email" 
                            class="form-control" 
                            id="contact_email" 
                            name="contact_email" 
                            placeholder="admin@example.com"
                            required
                            aria-required="true"
                            aria-describedby="contact_email_help"
                        >
                        <small id="contact_email_help" class="form-text text-muted">
                            Primary contact email for this location
                        </small>
                        <div class="error-message" id="contact_email_error" style="display: none;">
                            <i class="fa fa-exclamation-circle"></i>
                            <span></span>
                        </div>
                    </div>

                    <!-- Contact Phone -->
                    <div class="form-group">
                        <label for="contact_phone">
                            Contact Phone
                        </label>
                        <input 
                            type="tel" 
                            class="form-control" 
                            id="contact_phone" 
                            name="contact_phone" 
                            placeholder="+1 (555) 123-4567"
                            aria-describedby="contact_phone_help"
                        >
                        <small id="contact_phone_help" class="form-text text-muted">
                            Optional contact phone number
                        </small>
                        <div class="error-message" id="contact_phone_error" style="display: none;">
                            <i class="fa fa-exclamation-circle"></i>
                            <span></span>
                        </div>
                    </div>

                    <!-- Timezone -->
                    <div class="form-group">
                        <label for="timezone">
                            Timezone <span style="color: var(--color-error);">*</span>
                        </label>
                        <select 
                            class="form-control" 
                            id="timezone" 
                            name="timezone" 
                            required
                            aria-required="true"
                            aria-describedby="timezone_help"
                        >
                            <option value="">Select Timezone</option>
                            <option value="Africa/Accra">Africa/Accra (GMT)</option>
                            <option value="Africa/Cairo">Africa/Cairo (GMT+2)</option>
                            <option value="Africa/Johannesburg">Africa/Johannesburg (GMT+2)</option>
                            <option value="Africa/Lagos">Africa/Lagos (GMT+1)</option>
                            <option value="Africa/Nairobi">Africa/Nairobi (GMT+3)</option>
                            <option value="America/Chicago">America/Chicago (CST)</option>
                            <option value="America/Denver">America/Denver (MST)</option>
                            <option value="America/Los_Angeles">America/Los_Angeles (PST)</option>
                            <option value="America/New_York">America/New_York (EST)</option>
                            <option value="America/Toronto">America/Toronto (EST)</option>
                            <option value="Asia/Dubai">Asia/Dubai (GMT+4)</option>
                            <option value="Asia/Hong_Kong">Asia/Hong_Kong (GMT+8)</option>
                            <option value="Asia/Kolkata">Asia/Kolkata (IST)</option>
                            <option value="Asia/Shanghai">Asia/Shanghai (CST)</option>
                            <option value="Asia/Singapore">Asia/Singapore (SGT)</option>
                            <option value="Asia/Tokyo">Asia/Tokyo (JST)</option>
                            <option value="Australia/Sydney">Australia/Sydney (AEDT)</option>
                            <option value="Europe/Berlin">Europe/Berlin (CET)</option>
                            <option value="Europe/London">Europe/London (GMT)</option>
                            <option value="Europe/Paris">Europe/Paris (CET)</option>
                            <option value="Pacific/Auckland">Pacific/Auckland (NZDT)</option>
                            <option value="UTC">UTC (Universal Time)</option>
                        </select>
                        <small id="timezone_help" class="form-text text-muted">
                            The timezone for this location
                        </small>
                        <div class="error-message" id="timezone_error" style="display: none;">
                            <i class="fa fa-exclamation-circle"></i>
                            <span></span>
                        </div>
                    </div>

                    <!-- Form Note -->
                    <div style="background: var(--color-info-light); padding: var(--spacing-lg); border-radius: var(--radius-md); margin-top: var(--spacing-xl);">
                        <div style="display: flex; align-items: flex-start; gap: var(--spacing-md);">
                            <i class="fa fa-info-circle" style="color: var(--color-info); font-size: 20px; margin-top: 2px;"></i>
                            <div style="flex: 1;">
                                <strong style="color: var(--color-info-dark);">Important:</strong>
                                <p style="margin: var(--spacing-xs) 0 0 0; font-size: var(--font-size-xs); color: var(--color-gray-700);">
                                    Ensure the API endpoint is accessible and the remote location is configured to accept sync requests from this server.
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
                <button type="submit" class="sync-btn sync-btn-primary" id="submitAddLocationBtn" form="addLocationForm">
                    <i class="fa fa-check"></i> Add Location
                </button>
            </div>
        </div>
    </div>
</div>
