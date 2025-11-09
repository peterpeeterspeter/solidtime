"use strict";
Object.defineProperty(exports, "__esModule", { value: true });
exports.formatNumber = formatNumber;
/**
 * Formats a number according to the specified format
 * @param value - The number to format
 * @param format - The format to use
 * @returns The formatted number as a string
 */
function formatNumber(value, format) {
    // Convert to fixed 2 decimal places first
    var parts = value.toFixed(2).split('.');
    var wholePart = parts[0];
    var decimalPart = parts[1];
    // Format the whole number part based on the format
    var formattedWhole;
    switch (format) {
        case 'point-comma':
            formattedWhole = wholePart.replace(/\B(?=(\d{3})+(?!\d))/g, '.');
            return "".concat(formattedWhole, ",").concat(decimalPart);
        case 'comma-point':
            formattedWhole = wholePart.replace(/\B(?=(\d{3})+(?!\d))/g, ',');
            return "".concat(formattedWhole, ".").concat(decimalPart);
        case 'space-comma':
            formattedWhole = wholePart.replace(/\B(?=(\d{3})+(?!\d))/g, ' ');
            return "".concat(formattedWhole, ",").concat(decimalPart);
        case 'space-point':
            formattedWhole = wholePart.replace(/\B(?=(\d{3})+(?!\d))/g, ' ');
            return "".concat(formattedWhole, ".").concat(decimalPart);
        case 'apostrophe-point':
            formattedWhole = wholePart.replace(/\B(?=(\d{3})+(?!\d))/g, "'");
            return "".concat(formattedWhole, ".").concat(decimalPart);
        default:
            return value.toString();
    }
}
