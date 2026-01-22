# Workplace Setup and Change Request Feature - Implementation Summary

## Overview
Implemented a two-tier workplace management system where students can:
1. **Set their workplace ONCE** directly (initial setup)
2. **Request changes** through instructor approval after initial setup

## Changes Made

### 1. Backend Changes (StudentService.php)

#### New Method: `hasWorkplace($userId)`
- Checks if a student has already set up their workplace
- Returns `true` if an active workplace exists in `student_workplaces` table
- Returns `false` if no workplace has been set

#### Updated Method: `submitWorkplaceChangeRequest($studentId, $workplaceData)`
- Now includes `position_title` and `supervisor_name` fields
- Stores change requests in `workplace_change_requests` table with status 'pending'

### 2. Frontend Changes (student_profile.php)

#### Dynamic Button Text
- **Before setup**: Shows "Set Workplace"
- **After setup**: Shows "Request Change of Workplace"

#### Dynamic Modal Behavior
- **Modal Title**: Changes based on workplace status
  - Initial: "Set Your Workplace"
  - Change: "Request Change of Workplace"

- **Notice Banner**: Only shown when requesting changes
  - Displays: "You have already set your workplace. Any changes require instructor approval."

- **Reason Field**: Conditionally displayed
  - Hidden during initial setup
  - Required when requesting changes

#### Form Fields
All scenarios require:
- Workplace name
- Workplace address
- Position
- Supervisor name
- Location (latitude/longitude via map)

Change requests additionally require:
- Reason for change (text area)

### 3. Database Schema

#### Table: `workplace_change_requests`
```sql
CREATE TABLE IF NOT EXISTS `workplace_change_requests` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `student_id` int(11) NOT NULL,
  `workplace_name` varchar(255) NOT NULL,
  `workplace_address` text,
  `position_title` varchar(255) DEFAULT NULL,
  `supervisor_name` varchar(255) DEFAULT NULL,
  `latitude` decimal(10,8) DEFAULT NULL,
  `longitude` decimal(11,8) DEFAULT NULL,
  `change_reason` text NOT NULL,
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `reviewed_at` timestamp NULL DEFAULT NULL,
  `reviewed_by` int(11) DEFAULT NULL,
  `review_notes` text,
  PRIMARY KEY (`id`),
  KEY `student_id` (`student_id`),
  KEY `status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

## User Flow

### Initial Workplace Setup
1. Student clicks "Set Workplace" button
2. Modal opens with title "Set Your Workplace"
3. Student fills in:
   - Workplace name
   - Workplace address
   - Position
   - Supervisor name
   - Clicks on map to set location
4. Clicks "Set Workplace" button
5. Data is saved directly to `student_workplaces` table
6. Success message: "Workplace set successfully!"
7. Page reloads to show updated information
8. Button text changes to "Request Change of Workplace"

### Requesting Workplace Change
1. Student clicks "Request Change of Workplace" button
2. Modal opens with title "Request Change of Workplace"
3. Notice banner appears explaining approval requirement
4. Student fills in:
   - New workplace name
   - New workplace address
   - New position
   - New supervisor name
   - Clicks on map to set new location
   - **Reason for change** (required)
5. Clicks "Submit Request" button
6. Data is saved to `workplace_change_requests` table with status 'pending'
7. Success message: "Workplace change request submitted successfully. Waiting for instructor approval."
8. Page reloads
9. Original workplace remains active until request is approved

## Database Migration

Run the migration file to ensure the `workplace_change_requests` table has all required columns:

**File**: `database/migrations/add_workplace_change_request_fields.sql`

This migration:
- Creates the table if it doesn't exist
- Adds `position_title` and `supervisor_name` columns if missing

## Testing Checklist

- [ ] Student without workplace sees "Set Workplace" button
- [ ] Initial setup saves directly to `student_workplaces` table
- [ ] After setup, button changes to "Request Change of Workplace"
- [ ] Change request shows notice banner
- [ ] Change request requires reason field
- [ ] Change request saves to `workplace_change_requests` table
- [ ] Success messages are appropriate for each scenario
- [ ] Page reloads after successful submission
- [ ] Map functionality works in both scenarios

## Next Steps (For Instructor Side)

The instructor interface will need to:
1. View pending workplace change requests
2. Approve or reject requests
3. When approved, update `student_workplaces` table with new data
4. Update request status in `workplace_change_requests` table
5. Optionally notify student of decision
