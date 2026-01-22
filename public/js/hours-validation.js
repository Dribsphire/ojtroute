/**
 * Safe Hours Validation Utilities
 * Prevents frontend crashes from invalid or extreme hours values
 */

/**
 * Safely parse hours value with validation
 * @param {*} value - The value to parse
 * @param {number} max - Maximum allowed hours (default: 1000)
 * @returns {number} - Validated hours value
 */
function safeParseHours(value, max = 1000) {
    // Handle null, undefined, or empty values
    if (value === null || value === undefined || value === '') {
        return 0;
    }

    // Convert to number
    const parsed = parseFloat(value);

    // Check for invalid values (NaN, Infinity, -Infinity)
    if (isNaN(parsed) || !isFinite(parsed)) {
        console.error('Invalid hours value detected:', value);
        return 0;
    }

    // Cap at maximum reasonable value
    if (parsed > max) {
        console.warn('Hours value exceeds maximum:', parsed, '- Capping at', max);
        return max;
    }

    // Ensure non-negative
    if (parsed < 0) {
        console.warn('Negative hours value detected:', parsed, '- Returning 0');
        return 0;
    }

    return parsed;
}

/**
 * Calculate safe percentage for progress bars
 * @param {number} current - Current hours
 * @param {number} target - Target hours
 * @param {number} maxPercentage - Maximum percentage to return (default: 100)
 * @returns {number} - Safe percentage value
 */
function safeCalculatePercentage(current, target, maxPercentage = 100) {
    // Validate inputs
    const safeCurrent = safeParseHours(current);
    const safeTarget = safeParseHours(target);

    // Prevent division by zero
    if (safeTarget === 0) {
        return 0;
    }

    // Calculate percentage
    const percentage = (safeCurrent / safeTarget) * 100;

    // Cap at maximum
    return Math.min(percentage, maxPercentage);
}

/**
 * Format hours for display with validation
 * @param {*} value - Hours value to format
 * @param {number} decimals - Number of decimal places (default: 2)
 * @returns {string} - Formatted hours string
 */
function formatHours(value, decimals = 2) {
    const safe = safeParseHours(value);
    return safe.toFixed(decimals);
}

/**
 * Validate hours value and return error message if invalid
 * @param {*} value - Hours value to validate
 * @param {number} maxPerBlock - Maximum hours per block (default: 12)
 * @param {number} maxTotal - Maximum total hours (default: 1000)
 * @returns {object} - {valid: boolean, message: string}
 */
function validateHours(value, maxPerBlock = 12, maxTotal = 1000) {
    const parsed = parseFloat(value);

    if (isNaN(parsed) || !isFinite(parsed)) {
        return {
            valid: false,
            message: 'Invalid hours value'
        };
    }

    if (parsed < 0) {
        return {
            valid: false,
            message: 'Hours cannot be negative'
        };
    }

    if (parsed > maxPerBlock && maxPerBlock > 0) {
        return {
            valid: false,
            message: `Hours exceed maximum per block (${maxPerBlock} hours)`
        };
    }

    if (parsed > maxTotal) {
        return {
            valid: false,
            message: `Hours exceed maximum total (${maxTotal} hours)`
        };
    }

    return {
        valid: true,
        message: 'Valid'
    };
}

/**
 * Safe progress bar width calculation
 * @param {number} current - Current value
 * @param {number} target - Target value
 * @returns {string} - CSS width percentage (e.g., "75%")
 */
function safeProgressWidth(current, target) {
    const percentage = safeCalculatePercentage(current, target);
    return percentage + '%';
}

// Export for use in modules (if using ES6 modules)
if (typeof module !== 'undefined' && module.exports) {
    module.exports = {
        safeParseHours,
        safeCalculatePercentage,
        formatHours,
        validateHours,
        safeProgressWidth
    };
}
