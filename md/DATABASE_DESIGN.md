## OJTRoute / TrainTrack – Database Design Plan

This document describes the **planned tables** and **relationships** for the OJT document and attendance system.  
It is a design reference only – use it to guide actual SQL creation and migrations.

---

## 1. High-Level Overview

The system has three main domains:

- **Users & Profiles**: admin, student, instructor
- **OJT Placement & Attendance**: workplace, radius + photo-based attendance, timeout exceptions
- **Documents & Reviews**: required documents, student submissions, instructor approvals

Core entities:

- `users`
- `sections`
- `students`
- `instructors`
- `student_workplaces`
- `document_types`
- `document_submissions`
- `attendance_records`
- `timeout_exceptions`
- optional: `ojt_summaries`, `email_logs`

---

## 2. Users & Roles

### 2.1 `users`

**Purpose**: Authentication + common identity for all roles (admin, student, instructor).

**Example fields (design)**:
- `id` (PK)
- `school_id` – school / employee ID used in CSV & listings
- `full_name`
- `email`
- `password_hash`
- `role` – enum: `admin`, `student`, `instructor`
- `gender`– enum: `male`, `female`, `non-binary`
- `section_id` (FK → `sections.id`, nullable) – **only for students**; instructors are assigned via `sections.instructor_id`, admins don't have sections
- `contact` (since all users have contact number)
- `facebook_name` (since all users have facebook name)
- `year`
- `profile_pic_path` (since all users have profile picture) 
- `is_archived` (for archive-by-year feature)
- timestamps (`created_at`, `updated_at`, optional `archived_at`)

**Relationships**:
- Many–to–1 with `sections` (via `section_id` FK)
- 1–to–1 (optional) with `students` when `role = 'student'`
- 1–to–1 (optional) with `instructors` when `role = 'instructor'`
- Referenced as FK by many tables: `document_submissions`, `attendance_records`, `timeout_exceptions`, `ojt_summaries`, `email_logs`

---

### 2.2 `sections`

**Purpose**: Academic sections/classes that students belong to. Each section has an assigned instructor who supervises students in that section.

**Example fields**:
- `id` (PK)
- `section_code` – short code like "4A", "4B", "4C"
- `section_name` – full name like "BSIT-4A", "BSIT-4B"
- `department` – e.g. "College of Computer Studies","College of Education","College of Engineering","College of Industrial Technology",
- `instructor_id` (FK → `instructors.id`, nullable) – assigned instructor/supervisor for this section
- `year` – academic year (e.g., "2025")
- `is_active` – whether this section is currently active
- timestamps (`created_at`, `updated_at`)

**Relationships**:
- Many–to–1 with `instructors` (assigned instructor)
- 1–to–many with `users` (students and instructors can belong to a section)
- Used for filtering and grouping in admin reports, instructor dashboards, and student listings

**Notes**:
- The `section_id` in `users` table references this table (only for students)
- **One instructor can handle multiple sections** (via `instructor_id` FK)
- Section name (e.g., "BSIT-4A", "BSED-4A") already encodes the department/program in the prefix
- Section codes are typically unique within a department/year combination
- Department field is kept for filtering/reporting convenience even though it's encoded in `section_name`

---

### 2.3 `students`

**Purpose**: Additional profile and academic data for student users.

**Example fields**:
- `id` (PK)
- `user_id` (FK → `users.id`)
- `department` – can be derived from `users.section_id → sections.department` but kept here for convenience
- `target_ojt_hours` (e.g., 600)

**Relationships**:
- 1–to–1 with `users` (student record is an extension of a user)
- Section information comes from `users.section_id → sections` (no direct FK needed here)
- 1–to–many with:
  - `student_workplaces`
  - `document_submissions`
  - `attendance_records`
  - `timeout_exceptions`

---

### 2.3 `instructors`

**Purpose**: Additional data for instructor users.

**Example fields**:
- `id` (PK)
- `user_id` (FK → `users.id`)
- `department`

**Relationships**:
- 1–to–1 with `users` (instructor record is an extension of a user)
- **1–to–many with `sections`** (one instructor can be assigned to multiple sections via `sections.instructor_id`)
- Instructors do NOT have `section_id` in `users` table; assignment is via `sections.instructor_id` only
- 1–to–many with:
  - `document_submissions` (as `reviewer_instructor_id`)
  - `timeout_exceptions` (as reviewer/approver)
  - potentially `student_workplaces` (if each instructor supervises specific students formally)

---

## 3. OJT Placement (Student Workplace)

### 3.1 `student_workplaces`

**Purpose**: Store student OJT company and location for radius-based attendance and admin reporting.

**Example fields**:
- `id` (PK)
- `student_id` (FK → `students.id`)
- `company_name`
- `company_head`
- `position_title` – student’s role
- `workplace_latitude`
- `workplace_longitude`
- `start_date`
- optional: `is_active` flag if you allow multiple placements over time

**Relationships**:
- Many `student_workplaces` per `student`
- Can be joined with `attendance_records` (by date range) for richer reports, but attendance primarily links directly to `students`.

---

## 4. Documents & Reviews

### 4.1 `document_types`

**Purpose**: Master list of document requirements and categories. Distinguishes between **pre-OJT requirements** (must be approved before attendance can start) and **post-OJT documents** (submitted during OJT like weekly/monthly reports).

**Example fields**:
- `id` (PK)
- `name` – e.g. "Memorandum of Agreement (MOA)", "Application Letter", "Endorsement Letter to HTE", "Certificate of attendance OJT orientation", "Medical Certificate", "Parent's Consent", "Pledge of good conduct", "Misdemeanor penalty policy", "Resume", "Weekly Report", "Monthly Report", "Excuse Letter"
- `code` – short key if needed (e.g., `MOA`, `Application letter`, `Endorsement`, `certificate attendance`, `Medical cert`, `Consent`, `Good Conduct`, `Misdemeanor`, `Resume`, `Weekly Report`, `Monthly Report`, `Excuse`)
- `category` – e.g. `pre_required`, `weekly`, `monthly`, `excuse`, `other`
- `is_pre_required` – **boolean: if true, student cannot start OJT attendance until all pre-required documents are approved**
- `is_required` – whether this is a mandatory requirement (can be required but not pre-OJT)
- `frequency` – e.g. `once`, `weekly`, `monthly`, `per_incident`
- `description`

**Relationships**:
- 1–to–many with `document_submissions` (each submission is one type)

---

### 4.2 `document_submissions`

**Purpose**: Actual student-uploaded documents visible in student and instructor UIs.

**Example fields**:
- `id` (PK)
- `student_id` (FK → `students.id`)
- `document_type_id` (FK → `document_types.id`)
- `file_path`
- `file_type` – extension / mime info
- `file_size_bytes`
- `submitted_at`
- `status` – e.g. `pending`, `approved`, `revise`, `rejected`
- `reviewer_instructor_id` (FK → `instructors.id`, nullable until reviewed)
- `reviewed_at` (nullable)
- `feedback` – instructor remarks (e.g. “Please re-upload with signature”)

**Relationships**:
- Many–to–1 to `students`
- Many–to–1 to `document_types`
- Many–to–1 to `instructors` (reviewer)

**Notes**:
- **No versioning**: When a student re-uploads the same document type, it **overwrites the latest file** (update existing record or delete old and insert new)
- If you want full history (multiple reviews or versions), add a separate `document_submission_history` table and keep the latest state in `document_submissions`
- Pre-required documents must all be `status = 'approved'` before student can start recording attendance

---

## 5. Attendance & Timeout Exceptions

### 5.1 `attendance_records`

**Purpose**: Block-based attendance (morning / afternoon / overtime) with photo and geolocation. **Time blocks are fixed in code** (not configurable):
- **Morning block**: 6:00 AM - 11:59 AM
- **Afternoon block**: 12:00 PM - 5:59 PM  
- **Overtime block**: 6:00 PM - 10:00 PM

**Example fields**:
- `id` (PK)
- `student_id` (FK → `students.id`)
- `attendance_date` (DATE)
- `block_type` – enum: `morning`, `afternoon`, `overtime`
- `time_in` (DATETIME)
- `time_out` (DATETIME, nullable)
- `hours` (DECIMAL; derived from `time_in`/`time_out` but can be cached)
- `time_in_latitude` – GPS latitude when student timed in
- `time_in_longitude` – GPS longitude when student timed in
- `within_radius` (boolean) – **system checks if student's GPS is within allowed radius of their workplace location** (compare `time_in_latitude/longitude` with `student_workplaces.workplace_latitude/longitude`)
- `photo_path` – path to captured image at time-in (required for verification)
- `status` – e.g. `ongoing`, `completed`, `pending_exception`
- timestamps (`created_at`, `updated_at`)

**Notes**:
- **Radius verification**: When student times in, system captures their GPS coordinates and compares distance to their active `student_workplaces` location. If within allowed radius (e.g., 100 meters), `within_radius = true`. Radius value is **fixed in code** (not stored in database).
- Photo capture is mandatory at time-in for verification purposes

**Relationships**:
- Many `attendance_records` per `student`
- 1–to–many with `timeout_exceptions` (usually 0 or 1 per record)

---

### 5.2 `timeout_exceptions`

**Purpose**: Support “forgot to time out” / missing timeout flows (student uploads a letter + reason for instructor review).

**Example fields**:
- `id` (PK)
- `attendance_id` (FK → `attendance_records.id`)
- `student_id` (FK → `students.id` – redundant but convenient for queries)
- `block_type` – copied from attendance for display (morning/afternoon/overtime)
- `letter_file_path`
- `letter_file_name`
- `reason` – student’s explanation
- `status` – `pending`, `approved`, `rejected`
- `instructor_id` (FK → `instructors.id`, nullable until reviewed)
- `instructor_response` – feedback / justification
- `submitted_at`
- `reviewed_at` (nullable)

**Relationships**:
- Many–to–1 with `attendance_records`
- Many–to–1 with `students`
- Many–to–1 with `instructors` (reviewer)

**Notes**:
- **Only the instructor assigned to the student's section** (via `sections.instructor_id` matching student's `users.section_id`) can approve/reject timeout exceptions
- When approved, the **forgotten timeout hours are added to the student's total accumulated OJT hours**
- `attendance_records.status` can be updated when an exception is approved to allow hours to be counted

---

## 6. OJT Hours Summary (Optional Layer)

### 6.1 `ojt_summaries` (optional)

**Purpose**: Cache OJT hours per student for faster admin/student dashboards and CSV export. **Instructors can manually adjust hours** for students in their assigned sections.

**Example fields**:
- `id` (PK)
- `student_id` (FK → `students.id`)
- `hours_completed` – sum of approved/completed attendance hours **+ manual adjustments**
- `hours_required` – same as `students.target_ojt_hours` or program default
- `manual_adjustment_hours` (DECIMAL, default 0) – **instructor-added hours** (e.g., +8 hours for school-related excuses, +3 hours bulk adjustment)
- `last_updated`
- `adjusted_by_instructor_id` (FK → `instructors.id`, nullable) – who made the manual adjustment
- `adjustment_reason` (TEXT, nullable) – reason for manual adjustment (e.g., "School activity", "Bulk adjustment for section")

**Relationships**:
- 1–to–1 or 1–to–many (latest record) with `students`
- Many–to–1 with `instructors` (who adjusted the hours)

**Notes**:
- Can be recomputed from `attendance_records` + `manual_adjustment_hours` if needed
- **Only instructors assigned to the student's section** can adjust hours (manual or bulk)
- Manual adjustments are additive (e.g., +8 hours) and persist even if attendance records are recalculated
- Student profile and admin reports (Student OJT Reports) will read from this or from live aggregation

---

## 7. Admin Email & Communication (Optional)

### 7.1 `email_logs` (optional)

**Purpose**: Track emails sent from the admin “Compose Email” feature. **One log entry per email broadcast** (not per recipient).

**Example fields**:
- `id` (PK)
- `admin_id` (FK → `users.id` with role `admin`)
- `recipient_scope` – enum: `all_students`, `all_instructors`, `specific_student`
- `student_id` (FK → `students.id`, nullable; used when `recipient_scope = 'specific_student'`)
- `subject`
- `body`
- `sent_at`

**Relationships**:
- Many–to–1 with `users` (admin sender)
- Many–to–1 with `students` (optional specific recipient)

**Notes**:
- **One log entry per email sent**: When admin sends to "all_students" or "all_instructors", create **one log entry** with `recipient_scope` indicating the broadcast type. Do NOT create individual log entries for each recipient.
- This is for **audit trail** purposes (who sent what, when) rather than tracking individual delivery status
- If you need per-recipient tracking (delivery status, opens, etc.), create a separate `email_recipients` table with FK to `email_logs.id`

---

## 8. Relationship Summary (ER-style)

- **Users & Profiles**
  - `sections` 1–N `users` (many users belong to one section)
  - `instructors` 1–N `sections` (instructor assigned to sections)
  - `users` 1–1 `students` (for student accounts)
  - `users` 1–1 `instructors` (for instructor accounts)

- **Student Placement**
  - `students` 1–N `student_workplaces`

- **Documents**
  - `document_types` 1–N `document_submissions`
  - `students` 1–N `document_submissions`
  - `instructors` 1–N `document_submissions` (as reviewers)

- **Attendance**
  - `students` 1–N `attendance_records`
  - `attendance_records` 1–N `timeout_exceptions` (usually 0 or 1)
  - `students` 1–N `timeout_exceptions`
  - `instructors` 1–N `timeout_exceptions` (as reviewers)

- **OJT Summary / Reports**
  - `students` 1–1 `ojt_summaries` (optional)
  - `instructors` 1–N `ojt_summaries` (instructors can manually adjust hours for students in their sections)
  - Admin report screens read from `students` + `ojt_summaries` (or live aggregates from `attendance_records` + `student_workplaces`)

- **Email**
  - `users (admin)` 1–N `email_logs`
  - `students` 1–N `email_logs` (when specific student is selected)

---

## 9. Next Steps (Implementation Guide)

1. **Finalize field lists & data types** for each table above (e.g., exact VARCHAR lengths, enums vs reference tables).
2. **Create SQL migration scripts** (or a single `schema.sql`) based on this design.
3. **Gradually replace static/sample arrays** in the PHP files with real queries:
   - `SELECT` from `users`, `students`, `document_submissions`, `attendance_records`, etc.
4. **Wire up form submissions** (document uploads, time-ins, timeout letters, instructor approvals) to `INSERT` / `UPDATE` these tables.
5. Add **indexes and foreign keys**:
   - Index on `students.student_id`, `attendance_records.student_id + attendance_date`, `document_submissions.student_id + document_type_id`, etc.
6. Only after everything is stable, consider **optimizations** like `ojt_summaries` for faster reporting.

---

## 10. Clarifications & Explanations

### 10.1 Radius-Based Attendance Verification (Question 6)

**How it works:**
1. Student sets their workplace location (`student_workplaces.workplace_latitude`, `workplace_longitude`)
2. When student times in, the app captures:
   - Their current GPS location (`attendance_records.time_in_latitude`, `time_in_longitude`)
   - A photo (`attendance_records.photo_path`)
3. System calculates the **distance** between student's GPS and workplace GPS
4. If distance ≤ allowed radius (e.g., 40 meters), set `within_radius = true`
5. If outside radius, `within_radius = false` (attendance may be rejected or flagged)

**Implementation note:** The allowed radius value (e.g., 100 meters) is **hardcoded in your PHP/JavaScript code**, not stored in the database. You can use the Haversine formula or a simple distance calculation function.

**Example:**
- Workplace: `10.652160, 122.938901`
- Student times in at: `10.652200, 122.938950`
- Distance: ~5 meters → `within_radius = true` ✅

### 10.2 Email Logs (Question 10)

**Purpose:** Track what emails admins sent, not individual delivery status.

**When admin sends email:**
- **To "all_students"**: Create **one log entry** with `recipient_scope = 'all_students'`, `student_id = NULL`
- **To "all_instructors"**: Create **one log entry** with `recipient_scope = 'all_instructors'`, `student_id = NULL`
- **To specific student**: Create **one log entry** with `recipient_scope = 'specific_student'`, `student_id = [student_id]`

**Why one entry?** This is an **audit log** (who sent what, when), not a delivery tracking system. The actual email sending (via PHPMailer) happens separately and may send to 50+ recipients, but you only log the action once.

**If you need per-recipient tracking later:** Create a separate `email_recipients` table:
```sql
email_recipients (
  id, email_log_id (FK), recipient_user_id (FK), 
  delivery_status, opened_at, etc.
)
```

### 10.3 Additional Design Decisions

- **Contact/Facebook fields**: Stored only in `users` table since all roles (admin, student, instructor) have these fields
- **Section assignment**: Instructors are assigned via `sections.instructor_id`, NOT via `users.section_id` (only students have `section_id` in users table)
- **Pre-required documents**: Students cannot start attendance until all pre-required documents (`is_pre_required = true`) are approved
- **Document overwriting**: Re-uploading the same document type overwrites the previous file (no versioning by default)
- **Manual hours adjustment**: Only instructors assigned to student's section can add manual hours adjustments

