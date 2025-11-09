"use strict";
var __assign = (this && this.__assign) || function () {
    __assign = Object.assign || function(t) {
        for (var s, i = 1, n = arguments.length; i < n; i++) {
            s = arguments[i];
            for (var p in s) if (Object.prototype.hasOwnProperty.call(s, p))
                t[p] = s[p];
        }
        return t;
    };
    return __assign.apply(this, arguments);
};
var __awaiter = (this && this.__awaiter) || function (thisArg, _arguments, P, generator) {
    function adopt(value) { return value instanceof P ? value : new P(function (resolve) { resolve(value); }); }
    return new (P || (P = Promise))(function (resolve, reject) {
        function fulfilled(value) { try { step(generator.next(value)); } catch (e) { reject(e); } }
        function rejected(value) { try { step(generator["throw"](value)); } catch (e) { reject(e); } }
        function step(result) { result.done ? resolve(result.value) : adopt(result.value).then(fulfilled, rejected); }
        step((generator = generator.apply(thisArg, _arguments || [])).next());
    });
};
var __generator = (this && this.__generator) || function (thisArg, body) {
    var _ = { label: 0, sent: function() { if (t[0] & 1) throw t[1]; return t[1]; }, trys: [], ops: [] }, f, y, t, g = Object.create((typeof Iterator === "function" ? Iterator : Object).prototype);
    return g.next = verb(0), g["throw"] = verb(1), g["return"] = verb(2), typeof Symbol === "function" && (g[Symbol.iterator] = function() { return this; }), g;
    function verb(n) { return function (v) { return step([n, v]); }; }
    function step(op) {
        if (f) throw new TypeError("Generator is already executing.");
        while (g && (g = 0, op[0] && (_ = 0)), _) try {
            if (f = 1, y && (t = op[0] & 2 ? y["return"] : op[0] ? y["throw"] || ((t = y["return"]) && t.call(y), 0) : y.next) && !(t = t.call(y, op[1])).done) return t;
            if (y = 0, t) op = [op[0] & 2, t.value];
            switch (op[0]) {
                case 0: case 1: t = op; break;
                case 4: _.label++; return { value: op[1], done: false };
                case 5: _.label++; y = op[1]; op = [0]; continue;
                case 7: op = _.ops.pop(); _.trys.pop(); continue;
                default:
                    if (!(t = _.trys, t = t.length > 0 && t[t.length - 1]) && (op[0] === 6 || op[0] === 2)) { _ = 0; continue; }
                    if (op[0] === 3 && (!t || (op[1] > t[0] && op[1] < t[3]))) { _.label = op[1]; break; }
                    if (op[0] === 6 && _.label < t[1]) { _.label = t[1]; t = op; break; }
                    if (t && _.label < t[2]) { _.label = t[2]; _.ops.push(op); break; }
                    if (t[2]) _.ops.pop();
                    _.trys.pop(); continue;
            }
            op = body.call(thisArg, _);
        } catch (e) { op = [6, e]; y = 0; } finally { f = t = 0; }
        if (op[0] & 5) throw op[1]; return { value: op[0] ? op[1] : void 0, done: true };
    }
};
Object.defineProperty(exports, "__esModule", { value: true });
exports.useCurrentTimeEntryStore = void 0;
var pinia_1 = require("pinia");
var vue_1 = require("vue");
var src_1 = require("@/packages/api/src");
var dayjs_1 = require("dayjs");
var utc_1 = require("dayjs/plugin/utc");
var useUser_1 = require("@/utils/useUser");
var core_1 = require("@vueuse/core");
var useTimeEntries_1 = require("@/utils/useTimeEntries");
var notification_1 = require("@/utils/notification");
dayjs_1.default.extend(utc_1.default);
var emptyTimeEntry = {
    id: '',
    description: '',
    user_id: '',
    start: '',
    end: null,
    duration: null,
    task_id: null,
    project_id: null,
    tags: [],
    billable: false,
    organization_id: '',
};
exports.useCurrentTimeEntryStore = (0, pinia_1.defineStore)('currentTimeEntry', function () {
    var currentTimeEntry = (0, vue_1.ref)((0, vue_1.reactive)(emptyTimeEntry));
    var handleApiRequestNotifications = (0, notification_1.useNotificationsStore)().handleApiRequestNotifications;
    (0, core_1.useLocalStorage)('solidtime/current-time-entry', currentTimeEntry, {
        deep: true,
    });
    function $reset() {
        currentTimeEntry.value = __assign({}, emptyTimeEntry);
    }
    var now = (0, vue_1.ref)(null);
    var interval = (0, vue_1.ref)(null);
    function startLiveTimer() {
        stopLiveTimer();
        now.value = (0, dayjs_1.default)().utc();
        interval.value = setInterval(function () {
            now.value = (0, dayjs_1.default)().utc();
        }, 1000);
    }
    function stopLiveTimer() {
        if (interval.value !== null) {
            clearInterval(interval.value);
        }
    }
    function fetchCurrentTimeEntry() {
        return __awaiter(this, void 0, void 0, function () {
            var organizationId, timeEntriesResponse, _a;
            return __generator(this, function (_b) {
                switch (_b.label) {
                    case 0:
                        organizationId = (0, useUser_1.getCurrentOrganizationId)();
                        if (!organizationId) return [3 /*break*/, 5];
                        _b.label = 1;
                    case 1:
                        _b.trys.push([1, 3, , 4]);
                        return [4 /*yield*/, src_1.api.getMyActiveTimeEntry({})];
                    case 2:
                        timeEntriesResponse = _b.sent();
                        if (timeEntriesResponse === null || timeEntriesResponse === void 0 ? void 0 : timeEntriesResponse.data) {
                            if (timeEntriesResponse.data) {
                                currentTimeEntry.value = timeEntriesResponse.data;
                                if (currentTimeEntry.value.start !== '' &&
                                    currentTimeEntry.value.end === null) {
                                    startLiveTimer();
                                }
                            }
                            else {
                                currentTimeEntry.value = __assign({}, emptyTimeEntry);
                            }
                        }
                        return [3 /*break*/, 4];
                    case 3:
                        _a = _b.sent();
                        currentTimeEntry.value = __assign({}, emptyTimeEntry);
                        return [3 /*break*/, 4];
                    case 4: return [3 /*break*/, 6];
                    case 5: throw new Error('Failed to fetch current time entry because organization ID is missing.');
                    case 6: return [2 /*return*/];
                }
            });
        });
    }
    function startTimer() {
        return __awaiter(this, void 0, void 0, function () {
            var organization, membership, startTime_1, response;
            return __generator(this, function (_a) {
                switch (_a.label) {
                    case 0:
                        organization = (0, useUser_1.getCurrentOrganizationId)();
                        membership = (0, useUser_1.getCurrentMembershipId)();
                        if (!(organization && membership)) return [3 /*break*/, 2];
                        startTime_1 = currentTimeEntry.value.start !== ''
                            ? currentTimeEntry.value.start
                            : (0, dayjs_1.default)().utc().format();
                        return [4 /*yield*/, handleApiRequestNotifications(function () {
                                var _a, _b, _c, _d;
                                return src_1.api.createTimeEntry({
                                    member_id: membership,
                                    start: startTime_1,
                                    description: (_a = currentTimeEntry.value) === null || _a === void 0 ? void 0 : _a.description,
                                    project_id: (_b = currentTimeEntry.value) === null || _b === void 0 ? void 0 : _b.project_id,
                                    task_id: (_c = currentTimeEntry.value) === null || _c === void 0 ? void 0 : _c.task_id,
                                    billable: currentTimeEntry.value.billable,
                                    tags: (_d = currentTimeEntry.value) === null || _d === void 0 ? void 0 : _d.tags,
                                }, { params: { organization: organization } });
                            }, 'Timer started!')];
                    case 1:
                        response = _a.sent();
                        if (response === null || response === void 0 ? void 0 : response.data) {
                            currentTimeEntry.value = response.data;
                        }
                        return [3 /*break*/, 3];
                    case 2: throw new Error('Failed to fetch current time entry because organization ID is missing.');
                    case 3: return [2 /*return*/];
                }
            });
        });
    }
    function stopTimer() {
        return __awaiter(this, void 0, void 0, function () {
            var user, organization, currentDateTime_1;
            return __generator(this, function (_a) {
                switch (_a.label) {
                    case 0:
                        user = (0, useUser_1.getCurrentUserId)();
                        organization = (0, useUser_1.getCurrentOrganizationId)();
                        if (!organization) return [3 /*break*/, 2];
                        currentDateTime_1 = (0, dayjs_1.default)().utc().format();
                        return [4 /*yield*/, handleApiRequestNotifications(function () {
                                return src_1.api.updateTimeEntry({
                                    user_id: user,
                                    start: currentTimeEntry.value.start,
                                    end: currentDateTime_1,
                                }, {
                                    params: {
                                        organization: organization,
                                        timeEntry: currentTimeEntry.value.id,
                                    },
                                });
                            }, 'Timer stopped!')];
                    case 1:
                        _a.sent();
                        $reset();
                        return [3 /*break*/, 3];
                    case 2: throw new Error('Failed to stop current timer because organization ID is missing.');
                    case 3: return [2 /*return*/];
                }
            });
        });
    }
    function updateTimer() {
        return __awaiter(this, void 0, void 0, function () {
            var user, organization, response;
            return __generator(this, function (_a) {
                switch (_a.label) {
                    case 0:
                        user = (0, useUser_1.getCurrentUserId)();
                        organization = (0, useUser_1.getCurrentOrganizationId)();
                        if (!organization) return [3 /*break*/, 2];
                        return [4 /*yield*/, handleApiRequestNotifications(function () {
                                return src_1.api.updateTimeEntry({
                                    description: currentTimeEntry.value.description,
                                    user_id: user,
                                    project_id: currentTimeEntry.value.project_id,
                                    task_id: currentTimeEntry.value.task_id,
                                    start: currentTimeEntry.value.start,
                                    billable: currentTimeEntry.value.billable,
                                    end: currentTimeEntry.value.end,
                                    tags: currentTimeEntry.value.tags,
                                }, {
                                    params: {
                                        organization: organization,
                                        timeEntry: currentTimeEntry.value.id,
                                    },
                                });
                            }, 'Time entry updated!')];
                    case 1:
                        response = _a.sent();
                        if (response === null || response === void 0 ? void 0 : response.data) {
                            if (response.data.end === null) {
                                currentTimeEntry.value = response.data;
                            }
                            else {
                                $reset();
                                stopLiveTimer();
                            }
                        }
                        return [3 /*break*/, 3];
                    case 2: throw new Error('Failed to fetch current time entry because organization ID is missing.');
                    case 3: return [2 /*return*/];
                }
            });
        });
    }
    var isActive = (0, vue_1.computed)(function () {
        if (currentTimeEntry.value) {
            return (currentTimeEntry.value.start !== '' &&
                currentTimeEntry.value.start !== null &&
                currentTimeEntry.value.end === null);
        }
        return false;
    });
    function setActiveState(newState) {
        return __awaiter(this, void 0, void 0, function () {
            return __generator(this, function (_a) {
                switch (_a.label) {
                    case 0:
                        if (!newState) return [3 /*break*/, 2];
                        startLiveTimer();
                        return [4 /*yield*/, startTimer()];
                    case 1:
                        _a.sent();
                        return [3 /*break*/, 4];
                    case 2:
                        stopLiveTimer();
                        return [4 /*yield*/, stopTimer()];
                    case 3:
                        _a.sent();
                        _a.label = 4;
                    case 4:
                        (0, useTimeEntries_1.useTimeEntriesStore)().fetchTimeEntries();
                        return [2 /*return*/];
                }
            });
        });
    }
    return {
        currentTimeEntry: currentTimeEntry,
        fetchCurrentTimeEntry: fetchCurrentTimeEntry,
        updateTimer: updateTimer,
        isActive: isActive,
        startLiveTimer: startLiveTimer,
        stopLiveTimer: stopLiveTimer,
        now: now,
        setActiveState: setActiveState,
        $reset: $reset,
    };
});
