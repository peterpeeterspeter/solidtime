"use strict";
Object.defineProperty(exports, "__esModule", { value: true });
exports.isBillingActivated = isBillingActivated;
exports.isInvoicingActivated = isInvoicingActivated;
exports.isInTrial = isInTrial;
exports.daysLeftInTrial = daysLeftInTrial;
exports.isBlocked = isBlocked;
exports.isFreePlan = isFreePlan;
exports.hasActiveSubscription = hasActiveSubscription;
exports.isAllowedToPerformPremiumAction = isAllowedToPerformPremiumAction;
var vue3_1 = require("@inertiajs/vue3");
var time_1 = require("@/packages/ui/src/utils/time");
function isBillingActivated() {
    var page = (0, vue3_1.usePage)();
    return page.props.has_billing_extension;
}
function isInvoicingActivated() {
    var page = (0, vue3_1.usePage)();
    return page.props.has_invoicing_extension;
}
function isInTrial() {
    var page = (0, vue3_1.usePage)();
    return page.props.billing.has_trial;
}
function daysLeftInTrial() {
    var page = (0, vue3_1.usePage)();
    return ((0, time_1.getDayJsInstance)()(page.props.billing.trial_until).diff((0, time_1.getDayJsInstance)()(), 'days') + 1);
}
function isBlocked() {
    var page = (0, vue3_1.usePage)();
    return page.props.billing.is_blocked;
}
function isFreePlan() {
    return !hasActiveSubscription() && !isInTrial();
}
function hasActiveSubscription() {
    var page = (0, vue3_1.usePage)();
    return page.props.billing.has_subscription;
}
function isAllowedToPerformPremiumAction() {
    return (!isBillingActivated() ||
        (isBillingActivated() && hasActiveSubscription()) ||
        (isBillingActivated() && isInTrial()));
}
