const isBlank = (value) =>
    value === undefined || value === null || value === '' || (typeof value === 'string' && value.trim() === '');

/** Field is required (fails on undefined, null, empty or whitespace-only values). */
export const required = (message = 'This field is required') => (value) => isBlank(value) ? message : true;

/** Minimum string length (after trimming). Skips blank values. */
export const minLength = (min, message = `Must be at least ${min} characters`) => (value) =>
    isBlank(value) ? true : String(value).trim().length >= min ? true : message;

/** Maximum string length. Skips blank values. */
export const maxLength = (max, message = `Must be ${max} characters or fewer`) => (value) =>
    isBlank(value) ? true : String(value).length <= max ? true : message;

/** Basic email format check. Skips blank values. */
export const email = (message = 'Enter a valid email address') => (value) =>
    isBlank(value) || /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(String(value)) ? true : message;

/** Numeric value (including negative numbers and decimals). Skips blank values. */
export const numeric = (message = 'Must be a number') => (value) =>
    isBlank(value) || !Number.isNaN(Number(value)) ? true : message;

/** Numeric minimum (inclusive). Skips blank values. */
export const min = (min, message = `Must be at least ${min}`) => (value) =>
    isBlank(value) || Number(value) >= Number(min) ? true : message;

/** Numeric maximum (inclusive). Skips blank values. */
export const max = (max, message = `Must be at most ${max}`) => (value) =>
    isBlank(value) || Number(value) <= Number(max) ? true : message;

/** Runs every rule in order and returns the first failure (or `true`). */
export const compose = (...rules) => (value) => {
    for (const rule of rules) {
        const result = rule(value);

        if (result !== true) {
            return result;
        }
    }

    return true;
};

export default {
    required,
    minLength,
    maxLength,
    email,
    numeric,
    min,
    max,
    compose,
};