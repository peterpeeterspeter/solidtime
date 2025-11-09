"use strict";
Object.defineProperty(exports, "__esModule", { value: true });
exports.firstDayIndex = void 0;
exports.getDayJsInstance = getDayJsInstance;
exports.formatHumanReadableDuration = formatHumanReadableDuration;
exports.formatDuration = formatDuration;
exports.calculateDifference = calculateDifference;
exports.formatTime = formatTime;
exports.getLocalizedDayJs = getLocalizedDayJs;
exports.getLocalizedDateFromTimestamp = getLocalizedDateFromTimestamp;
exports.formatDate = formatDate;
exports.formatDateLocalized = formatDateLocalized;
exports.formatDateTimeLocalized = formatDateTimeLocalized;
exports.formatWeek = formatWeek;
exports.formatHumanReadableDate = formatHumanReadableDate;
exports.formatWeekday = formatWeekday;
exports.formatStartEnd = formatStartEnd;
exports.parseTimeInput = parseTimeInput;
var dayjs_1 = require("dayjs");
var duration_1 = require("dayjs/plugin/duration");
var relativeTime_1 = require("dayjs/plugin/relativeTime");
var isToday_1 = require("dayjs/plugin/isToday");
var isYesterday_1 = require("dayjs/plugin/isYesterday");
var utc_1 = require("dayjs/plugin/utc");
var timezone_1 = require("dayjs/plugin/timezone");
var weekOfYear_1 = require("dayjs/plugin/weekOfYear");
var parse_duration_1 = require("parse-duration");
var settings_1 = require("./settings");
var updateLocale_1 = require("dayjs/plugin/updateLocale");
var vue_1 = require("vue");
var number_1 = require("./number");
var dateFormatMap = {
    'point-separated-d-m-yyyy': 'D.M.YYYY',
    'slash-separated-mm-dd-yyyy': 'MM/DD/YYYY',
    'slash-separated-dd-mm-yyyy': 'DD/MM/YYYY',
    'hyphen-separated-dd-mm-yyyy': 'DD-MM-YYYY',
    'hyphen-separated-mm-dd-yyyy': 'MM-DD-YYYY',
    'hyphen-separated-yyyy-mm-dd': 'YYYY-MM-DD',
};
dayjs_1.default.extend(relativeTime_1.default);
dayjs_1.default.extend(isToday_1.default);
dayjs_1.default.extend(isYesterday_1.default);
dayjs_1.default.extend(duration_1.default);
dayjs_1.default.extend(utc_1.default);
dayjs_1.default.extend(timezone_1.default);
dayjs_1.default.extend(updateLocale_1.default);
dayjs_1.default.extend(weekOfYear_1.default);
function getDayJsInstance() {
    dayjs_1.default.updateLocale('en', {
        weekStart: exports.firstDayIndex.value,
    });
    return dayjs_1.default;
}
exports.firstDayIndex = (0, vue_1.computed)(function () {
    var apiDayOrder = [
        'sunday',
        'monday',
        'tuesday',
        'wednesday',
        'thursday',
        'friday',
        'saturday',
    ];
    return apiDayOrder.indexOf((0, settings_1.getWeekStart)());
});
function formatHumanReadableDuration(duration, intervalFormat, numberFormat) {
    var dayJsDuration = dayjs_1.default.duration(duration, 's');
    var hours = Math.floor(dayJsDuration.asHours());
    var minutes = dayJsDuration.minutes();
    var seconds = dayJsDuration.seconds();
    switch (intervalFormat) {
        case 'decimal':
            return (0, number_1.formatNumber)(dayJsDuration.asHours(), numberFormat) + ' h';
        case 'hours-minutes':
            return "".concat(hours, "h ").concat(minutes.toString().padStart(2, '0'), "min");
        case 'hours-minutes-colon-separated':
            return "".concat(hours, ":").concat(minutes.toString().padStart(2, '0'));
        case 'hours-minutes-seconds-colon-separated':
            return "".concat(hours, ":").concat(minutes.toString().padStart(2, '0'), ":").concat(seconds.toString().padStart(2, '0'));
        default:
            return "".concat(hours, "h ").concat(minutes.toString().padStart(2, '0'), "min");
    }
}
function formatDuration(duration) {
    var dayJsDuration = dayjs_1.default.duration(duration, 's');
    var hours = Math.floor(dayJsDuration.asHours());
    var minutes = dayJsDuration.minutes();
    var seconds = dayJsDuration.seconds();
    return "".concat(hours.toString().padStart(2, '0'), ":").concat(minutes.toString().padStart(2, '0'), ":").concat(seconds.toString().padStart(2, '0'));
}
function calculateDifference(start, end) {
    if (end === null) {
        end = (0, dayjs_1.default)().utc().format();
    }
    return (0, dayjs_1.default)(end).diff((0, dayjs_1.default)(start), 'second');
}
/**
 * Returns a formatted time.
 * @param date - A UTC date time string.
 * @param timeFormat - The time format to use ('12-hours' or '24-hours')
 */
function formatTime(date, timeFormat) {
    if (timeFormat === void 0) { timeFormat = '24-hours'; }
    var format = timeFormat === '12-hours' ? 'hh:mm A' : 'HH:mm';
    return dayjs_1.default.utc(date).tz((0, settings_1.getUserTimezone)()).format(format);
}
function getLocalizedDayJs(timestamp) {
    return dayjs_1.default.utc(timestamp).tz((0, settings_1.getUserTimezone)());
}
function getLocalizedDateFromTimestamp(timestamp) {
    return getLocalizedDayJs(timestamp).format('YYYY-MM-DD');
}
/*
 * Returns a formatted date.
 * @param date - date in the format of 'YYYY-MM-DD'
 */
function formatDate(date, format) {
    if (format === void 0) { format = 'point-separated-d-m-yyyy'; }
    if (date === null || date === void 0 ? void 0 : date.includes('+')) {
        console.warn('Date contains timezone information, use formatDateLocalized instead');
    }
    return getDayJsInstance()(date).format(dateFormatMap[format]);
}
/*
 * Returns a formatted date.
 * @param date - date in the format of 'YYYY-MM-DD'
 */
function formatDateLocalized(date, format) {
    if (format === void 0) { format = 'point-separated-d-m-yyyy'; }
    return getLocalizedDayJs(date).format(dateFormatMap[format]);
}
function formatDateTimeLocalized(date, dateFormat, timeFormat) {
    var format = "".concat(dateFormatMap[dateFormat !== null && dateFormat !== void 0 ? dateFormat : 'point-separated-d-m-yyyy'], " ").concat(timeFormat === '12-hours' ? 'hh:mm A' : 'HH:mm');
    return getLocalizedDayJs(date).format(format);
}
function formatWeek(date) {
    return 'Week ' + getDayJsInstance()(date).week();
}
/*
 * Returns a human readable date format.
 * @param date - date in the format of 'YYYY-MM-DD'
 */
function formatHumanReadableDate(date) {
    var dateObj = (0, dayjs_1.default)(date);
    var today = (0, dayjs_1.default)();
    if (dateObj.isToday()) {
        return 'Today';
    }
    else if (dateObj.isYesterday()) {
        return 'Yesterday';
    }
    // Calculate difference in days
    var diffInDays = today.diff(dateObj, 'day');
    if (diffInDays > 0 && diffInDays <= 30) {
        // For dates in the past (2-30 days ago)
        return "".concat(diffInDays, " ").concat(diffInDays === 1 ? 'day' : 'days', " ago");
    }
    else if (diffInDays < 0 && diffInDays >= -30) {
        // For dates in the future (within 30 days)
        var futureDays = Math.abs(diffInDays);
        return "In ".concat(futureDays, " ").concat(futureDays === 1 ? 'day' : 'days');
    }
    // For dates older than 30 days, show the actual date
    return dateObj.format('MMM D, YYYY');
}
function formatWeekday(date) {
    return (0, dayjs_1.default)(date).format('dddd');
}
function formatStartEnd(start, end, timeFormat) {
    if (timeFormat === void 0) { timeFormat = '24-hours'; }
    if (end) {
        return "".concat(formatTime(start, timeFormat), " - ").concat(formatTime(end, timeFormat));
    }
    else {
        return "".concat(formatTime(start, timeFormat), " - ...");
    }
}
function parseTimeInput(input, defaultUnit) {
    if (defaultUnit === void 0) { defaultUnit = 'minutes'; }
    // Check if input is a decimal number (hours)
    var decimalRegex = /^-?\d+[.,]\d+$/;
    if (decimalRegex.test(input)) {
        var hours = parseFloat(input.replace(',', '.'));
        return Math.round(hours * 3600);
    }
    // Check if input is just a number (minutes or hours based on defaultUnit)
    if (/^-?\d+$/.test(input)) {
        var value = parseInt(input);
        return defaultUnit === 'minutes' ? value * 60 : value * 3600;
    }
    // Check if input is in HH:MM:SS format
    var HHMMSStimeRegex = /^([0-9]{1,2}):([0-5]?[0-9]):([0-5]?[0-9])$/;
    if (HHMMSStimeRegex.test(input)) {
        var match = input.match(HHMMSStimeRegex);
        if (match) {
            var hours = parseInt(match[1]);
            var minutes = parseInt(match[2]);
            var seconds = parseInt(match[3]);
            return hours * 3600 + minutes * 60 + seconds;
        }
    }
    // Check if input is in HH:MM format
    var HHMMtimeRegex = /^([0-9]{1,2}):([0-5]?[0-9])$/;
    if (HHMMtimeRegex.test(input)) {
        var match = input.match(HHMMtimeRegex);
        if (match) {
            var hours = parseInt(match[1]);
            var minutes = parseInt(match[2]);
            return (hours * 60 + minutes) * 60;
        }
    }
    // Try to parse natural language like "1h 30m"
    var parsedDuration = (0, parse_duration_1.default)(input, 's');
    if (parsedDuration && parsedDuration > 0) {
        return parsedDuration;
    }
    return null;
}
