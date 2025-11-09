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
var __spreadArray = (this && this.__spreadArray) || function (to, from, pack) {
    if (pack || arguments.length === 2) for (var i = 0, l = from.length, ar; i < l; i++) {
        if (ar || !(i in from)) {
            if (!ar) ar = Array.prototype.slice.call(from, 0, i);
            ar[i] = from[i];
        }
    }
    return to.concat(ar || Array.prototype.slice.call(from));
};
Object.defineProperty(exports, "__esModule", { value: true });
exports.useTimeEntriesStore = void 0;
var pinia_1 = require("pinia");
var useUser_1 = require("@/utils/useUser");
var vue_1 = require("vue");
var src_1 = require("@/packages/api/src");
var dayjs_1 = require("dayjs");
var notification_1 = require("@/utils/notification");
var vue_query_1 = require("@tanstack/vue-query");
exports.useTimeEntriesStore = (0, pinia_1.defineStore)('timeEntries', function () {
    var timeEntries = (0, vue_1.ref)((0, vue_1.reactive)([]));
    var allTimeEntriesLoaded = (0, vue_1.ref)(false);
    var handleApiRequestNotifications = (0, notification_1.useNotificationsStore)().handleApiRequestNotifications;
    var queryClient = (0, vue_query_1.useQueryClient)();
    function patchTimeEntries() {
        return __awaiter(this, arguments, void 0, function (queryParams) {
            var organizationId, timeEntriesResponse, missingTimeEntries;
            if (queryParams === void 0) { queryParams = {
                only_full_dates: 'true',
                member_id: (0, useUser_1.getCurrentMembershipId)(),
            }; }
            return __generator(this, function (_a) {
                switch (_a.label) {
                    case 0:
                        organizationId = (0, useUser_1.getCurrentOrganizationId)();
                        if (!organizationId) return [3 /*break*/, 2];
                        return [4 /*yield*/, handleApiRequestNotifications(function () {
                                return src_1.api.getTimeEntries({
                                    params: {
                                        organization: organizationId,
                                    },
                                    queries: queryParams,
                                });
                            }, undefined, 'Failed to fetch time entries')];
                    case 1:
                        timeEntriesResponse = _a.sent();
                        if (timeEntriesResponse === null || timeEntriesResponse === void 0 ? void 0 : timeEntriesResponse.data) {
                            missingTimeEntries = timeEntriesResponse.data.filter(function (entry) { return !timeEntries.value.find(function (e) { return e.id === entry.id; }); });
                            timeEntries.value = __spreadArray(__spreadArray([], missingTimeEntries, true), timeEntries.value, true);
                        }
                        _a.label = 2;
                    case 2: return [2 /*return*/];
                }
            });
        });
    }
    function fetchTimeEntries() {
        return __awaiter(this, arguments, void 0, function (queryParams) {
            var organizationId, timeEntriesResponse;
            if (queryParams === void 0) { queryParams = {
                only_full_dates: 'true',
                member_id: (0, useUser_1.getCurrentMembershipId)(),
            }; }
            return __generator(this, function (_a) {
                switch (_a.label) {
                    case 0:
                        organizationId = (0, useUser_1.getCurrentOrganizationId)();
                        if (!organizationId) return [3 /*break*/, 2];
                        return [4 /*yield*/, handleApiRequestNotifications(function () {
                                return src_1.api.getTimeEntries({
                                    params: {
                                        organization: organizationId,
                                    },
                                    queries: queryParams,
                                });
                            }, undefined, 'Failed to fetch time entries')];
                    case 1:
                        timeEntriesResponse = _a.sent();
                        if (timeEntriesResponse === null || timeEntriesResponse === void 0 ? void 0 : timeEntriesResponse.data) {
                            timeEntries.value = timeEntriesResponse.data;
                        }
                        _a.label = 2;
                    case 2: return [2 /*return*/];
                }
            });
        });
    }
    function fetchMoreTimeEntries() {
        return __awaiter(this, void 0, void 0, function () {
            var organizationId, latestTimeEntry_1, timeEntriesResponse;
            return __generator(this, function (_a) {
                switch (_a.label) {
                    case 0:
                        organizationId = (0, useUser_1.getCurrentOrganizationId)();
                        if (!organizationId) return [3 /*break*/, 2];
                        latestTimeEntry_1 = timeEntries.value[timeEntries.value.length - 1];
                        (0, dayjs_1.default)(latestTimeEntry_1.start).utc().format('YYYY-MM-DD');
                        return [4 /*yield*/, handleApiRequestNotifications(function () {
                                return src_1.api.getTimeEntries({
                                    params: {
                                        organization: organizationId,
                                    },
                                    queries: {
                                        only_full_dates: 'true',
                                        member_id: (0, useUser_1.getCurrentMembershipId)(),
                                        end: (0, dayjs_1.default)(latestTimeEntry_1.start).utc().format(),
                                    },
                                });
                            }, undefined, 'Failed to fetch time entries')];
                    case 1:
                        timeEntriesResponse = _a.sent();
                        if ((timeEntriesResponse === null || timeEntriesResponse === void 0 ? void 0 : timeEntriesResponse.data) && timeEntriesResponse.data.length > 0) {
                            timeEntries.value = timeEntries.value.concat(timeEntriesResponse.data);
                        }
                        else {
                            allTimeEntriesLoaded.value = true;
                        }
                        _a.label = 2;
                    case 2: return [2 /*return*/];
                }
            });
        });
    }
    function updateTimeEntries(ids, changes) {
        return __awaiter(this, void 0, void 0, function () {
            var organizationId;
            return __generator(this, function (_a) {
                switch (_a.label) {
                    case 0:
                        organizationId = (0, useUser_1.getCurrentOrganizationId)();
                        if (!organizationId) return [3 /*break*/, 2];
                        return [4 /*yield*/, handleApiRequestNotifications(function () {
                                return src_1.api.updateMultipleTimeEntries({
                                    ids: ids,
                                    changes: changes,
                                }, {
                                    params: {
                                        organization: organizationId,
                                    },
                                });
                            }, 'Time entries updated successfully', 'Failed to update time entries')];
                    case 1:
                        _a.sent();
                        _a.label = 2;
                    case 2: return [2 /*return*/];
                }
            });
        });
    }
    function updateTimeEntry(timeEntry) {
        return __awaiter(this, void 0, void 0, function () {
            var organizationId, response_1;
            return __generator(this, function (_a) {
                switch (_a.label) {
                    case 0:
                        organizationId = (0, useUser_1.getCurrentOrganizationId)();
                        if (!organizationId) return [3 /*break*/, 2];
                        return [4 /*yield*/, handleApiRequestNotifications(function () {
                                return src_1.api.updateTimeEntry(timeEntry, {
                                    params: {
                                        organization: organizationId,
                                        timeEntry: timeEntry.id,
                                    },
                                });
                            }, 'Time entry updated successfully', 'Failed to update time entry')];
                    case 1:
                        response_1 = _a.sent();
                        timeEntries.value = timeEntries.value.map(function (entry) {
                            return entry.id === timeEntry.id ? response_1.data : entry;
                        });
                        queryClient.invalidateQueries({ queryKey: ['timeEntry'] });
                        _a.label = 2;
                    case 2: return [2 /*return*/];
                }
            });
        });
    }
    function createTimeEntry(timeEntry) {
        return __awaiter(this, void 0, void 0, function () {
            var organizationId, memberId, newTimeEntry_1;
            return __generator(this, function (_a) {
                switch (_a.label) {
                    case 0:
                        organizationId = (0, useUser_1.getCurrentOrganizationId)();
                        memberId = (0, useUser_1.getCurrentMembershipId)();
                        if (!(organizationId && memberId !== undefined)) return [3 /*break*/, 3];
                        newTimeEntry_1 = __assign(__assign({}, timeEntry), { member_id: memberId });
                        return [4 /*yield*/, handleApiRequestNotifications(function () {
                                return src_1.api.createTimeEntry(newTimeEntry_1, {
                                    params: {
                                        organization: organizationId,
                                    },
                                });
                            }, 'Time entry created successfully', 'Failed to create time entry')];
                    case 1:
                        _a.sent();
                        return [4 /*yield*/, fetchTimeEntries()];
                    case 2:
                        _a.sent();
                        _a.label = 3;
                    case 3: return [2 /*return*/];
                }
            });
        });
    }
    function deleteTimeEntry(timeEntryId) {
        return __awaiter(this, void 0, void 0, function () {
            var organizationId;
            return __generator(this, function (_a) {
                switch (_a.label) {
                    case 0:
                        organizationId = (0, useUser_1.getCurrentOrganizationId)();
                        if (!organizationId) return [3 /*break*/, 3];
                        return [4 /*yield*/, handleApiRequestNotifications(function () {
                                return src_1.api.deleteTimeEntry(undefined, {
                                    params: {
                                        organization: organizationId,
                                        timeEntry: timeEntryId,
                                    },
                                });
                            }, 'Time entry deleted successfully', 'Failed to delete time entry')];
                    case 1:
                        _a.sent();
                        return [4 /*yield*/, fetchTimeEntries()];
                    case 2:
                        _a.sent();
                        _a.label = 3;
                    case 3: return [2 /*return*/];
                }
            });
        });
    }
    function deleteTimeEntries(timeEntries) {
        return __awaiter(this, void 0, void 0, function () {
            var organizationId, timeEntryIds;
            return __generator(this, function (_a) {
                switch (_a.label) {
                    case 0:
                        organizationId = (0, useUser_1.getCurrentOrganizationId)();
                        timeEntryIds = timeEntries.map(function (entry) { return entry.id; });
                        if (!organizationId) return [3 /*break*/, 3];
                        return [4 /*yield*/, handleApiRequestNotifications(function () {
                                return src_1.api.deleteTimeEntries(undefined, {
                                    queries: {
                                        ids: timeEntryIds,
                                    },
                                    params: {
                                        organization: organizationId,
                                    },
                                });
                            }, 'Time entries deleted successfully', 'Failed to delete time entries')];
                    case 1:
                        _a.sent();
                        return [4 /*yield*/, fetchTimeEntries()];
                    case 2:
                        _a.sent();
                        _a.label = 3;
                    case 3: return [2 /*return*/];
                }
            });
        });
    }
    return {
        timeEntries: timeEntries,
        fetchTimeEntries: fetchTimeEntries,
        updateTimeEntry: updateTimeEntry,
        createTimeEntry: createTimeEntry,
        deleteTimeEntry: deleteTimeEntry,
        fetchMoreTimeEntries: fetchMoreTimeEntries,
        allTimeEntriesLoaded: allTimeEntriesLoaded,
        updateTimeEntries: updateTimeEntries,
        deleteTimeEntries: deleteTimeEntries,
        patchTimeEntries: patchTimeEntries,
    };
});
