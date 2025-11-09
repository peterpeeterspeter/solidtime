"use strict";
Object.defineProperty(exports, "__esModule", { value: true });
exports.getWeekStart = getWeekStart;
exports.getUserTimezone = getUserTimezone;
function getWeekStart() {
    var weekStart = window === null || window === void 0 ? void 0 : window.getWeekStartSetting();
    if (!weekStart) {
        throw new Error('Please make sure to provide the current user week start setting as a vue inject (week_start)');
    }
    return weekStart;
}
function getUserTimezone() {
    var timezone = window === null || window === void 0 ? void 0 : window.getTimezoneSetting();
    if (!timezone) {
        throw new Error('Please make sure to provide the current user timezone as a vue inject (timezone)');
    }
    return timezone;
}
