"use strict";
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
exports.useReportingStore = void 0;
var pinia_1 = require("pinia");
var src_1 = require("@/packages/api/src");
var vue_1 = require("vue");
var useUser_1 = require("@/utils/useUser");
var notification_1 = require("@/utils/notification");
var useProjects_1 = require("@/utils/useProjects");
var useMembers_1 = require("@/utils/useMembers");
var useTasks_1 = require("@/utils/useTasks");
var useClients_1 = require("@/utils/useClients");
var useTags_1 = require("@/utils/useTags");
var solid_1 = require("@heroicons/vue/20/solid");
var solid_2 = require("@heroicons/vue/16/solid");
var BillableIcon_vue_1 = require("@/packages/ui/src/Icons/BillableIcon.vue");
exports.useReportingStore = (0, pinia_1.defineStore)('reporting', function () {
    var reportingGraphResponse = (0, vue_1.ref)(null);
    var reportingTableResponse = (0, vue_1.ref)(null);
    var handleApiRequestNotifications = (0, notification_1.useNotificationsStore)().handleApiRequestNotifications;
    function fetchGraphReporting(params) {
        return __awaiter(this, void 0, void 0, function () {
            var organization, _a;
            return __generator(this, function (_b) {
                switch (_b.label) {
                    case 0:
                        organization = (0, useUser_1.getCurrentOrganizationId)();
                        if (!organization) return [3 /*break*/, 2];
                        _a = reportingGraphResponse;
                        return [4 /*yield*/, handleApiRequestNotifications(function () {
                                return src_1.api.getAggregatedTimeEntries({
                                    params: {
                                        organization: organization,
                                    },
                                    queries: params,
                                });
                            }, undefined, 'Failed to fetch reporting data')];
                    case 1:
                        _a.value = _b.sent();
                        _b.label = 2;
                    case 2: return [2 /*return*/];
                }
            });
        });
    }
    function fetchTableReporting(params) {
        return __awaiter(this, void 0, void 0, function () {
            var organization, _a;
            return __generator(this, function (_b) {
                switch (_b.label) {
                    case 0:
                        organization = (0, useUser_1.getCurrentOrganizationId)();
                        if (!organization) return [3 /*break*/, 2];
                        _a = reportingTableResponse;
                        return [4 /*yield*/, handleApiRequestNotifications(function () {
                                return src_1.api.getAggregatedTimeEntries({
                                    params: {
                                        organization: organization,
                                    },
                                    queries: params,
                                });
                            }, undefined, 'Failed to fetch reporting data')];
                    case 1:
                        _a.value = _b.sent();
                        _b.label = 2;
                    case 2: return [2 /*return*/];
                }
            });
        });
    }
    var aggregatedGraphTimeEntries = (0, vue_1.computed)(function () {
        var _a;
        return (_a = reportingGraphResponse.value) === null || _a === void 0 ? void 0 : _a.data;
    });
    var aggregatedTableTimeEntries = (0, vue_1.computed)(function () {
        var _a;
        return (_a = reportingTableResponse.value) === null || _a === void 0 ? void 0 : _a.data;
    });
    var emptyPlaceholder = {
        user: 'No User',
        project: 'No Project',
        task: 'No Task',
        billable: 'Non-Billable',
        client: 'No Client',
        description: 'No Description',
        tag: 'No Tag',
    };
    function getNameForReportingRowEntry(key, type) {
        var _a, _b, _c, _d, _e;
        if (type === null) {
            return null;
        }
        if (key === null) {
            return emptyPlaceholder[type];
        }
        if (type === 'project') {
            var projectsStore = (0, useProjects_1.useProjectsStore)();
            var projects = (0, pinia_1.storeToRefs)(projectsStore).projects;
            return (_a = projects.value.find(function (project) { return project.id === key; })) === null || _a === void 0 ? void 0 : _a.name;
        }
        if (type === 'user') {
            if ((0, useUser_1.getCurrentRole)() === 'employee') {
                return (0, useUser_1.getCurrentUser)().name;
            }
            var memberStore = (0, useMembers_1.useMembersStore)();
            var members = (0, pinia_1.storeToRefs)(memberStore).members;
            return (_b = members.value.find(function (member) { return member.user_id === key; })) === null || _b === void 0 ? void 0 : _b.name;
        }
        if (type === 'task') {
            var taskStore = (0, useTasks_1.useTasksStore)();
            var tasks = (0, pinia_1.storeToRefs)(taskStore).tasks;
            return (_c = tasks.value.find(function (task) { return task.id === key; })) === null || _c === void 0 ? void 0 : _c.name;
        }
        if (type === 'client') {
            var clientsStore = (0, useClients_1.useClientsStore)();
            var clients = (0, pinia_1.storeToRefs)(clientsStore).clients;
            return (_d = clients.value.find(function (client) { return client.id === key; })) === null || _d === void 0 ? void 0 : _d.name;
        }
        if (type === 'tag') {
            var tagsStore = (0, useTags_1.useTagsStore)();
            var tags = (0, pinia_1.storeToRefs)(tagsStore).tags;
            return (_e = tags.value.find(function (tag) { return tag.id === key; })) === null || _e === void 0 ? void 0 : _e.name;
        }
        if (type === 'billable') {
            if (key === '0') {
                return 'Non-Billable';
            }
            else {
                return 'Billable';
            }
        }
        return key;
    }
    var groupByOptions = [
        {
            label: 'Members',
            value: 'user',
            icon: solid_1.UserGroupIcon,
        },
        {
            label: 'Projects',
            value: 'project',
            icon: solid_2.FolderIcon,
        },
        {
            label: 'Tasks',
            value: 'task',
            icon: solid_1.CheckCircleIcon,
        },
        {
            label: 'Clients',
            value: 'client',
            icon: solid_1.UserCircleIcon,
        },
        {
            label: 'Billable',
            value: 'billable',
            icon: BillableIcon_vue_1.default,
        },
        {
            label: 'Description',
            value: 'description',
            icon: solid_2.DocumentTextIcon,
        },
        {
            label: 'Tags',
            value: 'tag',
            icon: solid_2.DocumentTextIcon,
        },
    ];
    return {
        aggregatedGraphTimeEntries: aggregatedGraphTimeEntries,
        fetchGraphReporting: fetchGraphReporting,
        fetchTableReporting: fetchTableReporting,
        aggregatedTableTimeEntries: aggregatedTableTimeEntries,
        getNameForReportingRowEntry: getNameForReportingRowEntry,
        groupByOptions: groupByOptions,
        emptyPlaceholder: emptyPlaceholder,
    };
});
