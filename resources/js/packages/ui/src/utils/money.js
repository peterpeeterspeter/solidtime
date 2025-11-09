"use strict";
Object.defineProperty(exports, "__esModule", { value: true });
exports.formatCents = formatCents;
exports.getOrganizationCurrencySymbol = getOrganizationCurrencySymbol;
var number_1 = require("./number");
function formatMoney(amount, currency, format, currencySymbol, numberFormat) {
    var formattedAmount = (0, number_1.formatNumber)(amount, numberFormat);
    switch (format) {
        case 'iso-code-before-with-space':
            return "".concat(currency, " ").concat(formattedAmount);
        case 'iso-code-after-with-space':
            return "".concat(formattedAmount, " ").concat(currency);
        case 'symbol-before':
            return "".concat(currencySymbol).concat(formattedAmount);
        case 'symbol-after':
            return "".concat(formattedAmount).concat(currencySymbol);
        case 'symbol-before-with-space':
            return "".concat(currencySymbol, " ").concat(formattedAmount);
        case 'symbol-after-with-space':
            return "".concat(formattedAmount, " ").concat(currencySymbol);
    }
}
function formatCents(amount, currency, format, currencySymbol, numberFormat) {
    return formatMoney(amount / 100, currency, format, currencySymbol, numberFormat);
}
function getOrganizationCurrencySymbol(currency) {
    return (0)
        .toLocaleString('de-DE', {
        style: 'currency',
        currency: currency,
        minimumFractionDigits: 0,
        maximumFractionDigits: 0,
    })
        .replace(/\d/g, '')
        .trim();
}
