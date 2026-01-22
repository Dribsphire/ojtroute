# Verification Test Plan - Hours Validation

**Date:** January 23, 2026
**Scope:** Verify critical fixes for hours validation and overflow prevention.

---

## 🧪 Test Case 1: Progress Bar Overflow Prevention

**Objective:** Ensure progress bars do not exceed 100% even if hours exceed target.

**Steps:**
1. Login as a student.
2. Navigate to **Profile** page.
3. Open Developer Tools (F12) -> Console.
4. Run this simulation command:
   ```javascript
   // Simulate overflow
   document.querySelector('.progress-fill').style.width = '150%';
   document.querySelector('.progress-percentage').textContent = '150%';
   // Reload logic (simulate page load)
   document.dispatchEvent(new Event('DOMContentLoaded'));
   ```
5. **Expected Result:**
   - Progress bar should snap back to **100%**.
   - Percentage text should read **100%**.
   - Console should show warning: `Progress overflow detected... Capping at 100%`.

---

## 🧪 Test Case 2: Calendar Hours Display

**Objective:** Ensure extremely large hour values do not crash the calendar interface.

**Steps:**
1. Navigate to **Calendar** or **Attendance** page.
2. In database (if access available), set an attendance record's `hours` column to `999999`.
3. Refresh the Calendar page.
4. Click on the date with the modified record.
5. **Expected Result:**
   - The pop-up modal should display the hours correctly (formatted).
   - The page should **NOT** crash or freeze.
   - No `NaN` or `Infinity` values should be visible.

---

## 🧪 Test Case 3: Attendance Timeout Cap (Backend)

**Objective:** Verify that hours are capped at 12 hours per block.

**Steps:**
1. Time In for a block (e.g., Morning) yesterday.
2. Attempt to Time Out today (simulating a forgotten timeout).
3. **Expected Result:**
   - System should record the timeout.
   - Calculated hours should be **12.00** (max cap), not 24+.
   - Check `attendance_records` table to verify `hours` = 12.

---

## 🧪 Test Case 4: Race Condition (Advanced)

**Objective:** Verify that simultaneous requests don't cause data loss.

**Steps:**
1. Use a tool like Postman or a script to send two simultaneous `recordTimeOut` requests for the same student/block.
2. **Expected Result:**
   - Only **one** request should succeed.
   - The other should fail with "Already timed out" message.
   - `hours_completed` in `ojt_summaries` should increase exactly once by the correct amount.

---

## ✅ Success Criteria

- No browser crashes or freezes.
- No visual glitches (overflowing bars).
- Data integrity maintained in database.
- User-friendly error messages for blocking conditions.
