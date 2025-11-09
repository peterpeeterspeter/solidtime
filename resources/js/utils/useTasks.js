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
exports.useTasksStore = void 0;
var pinia_1 = require("pinia");
var useUser_1 = require("@/utils/useUser");
var src_1 = require("@/packages/api/src");
var vue_1 = require("vue");
var notification_1 = require("@/utils/notification");
exports.useTasksStore = (0, pinia_1.defineStore)('tasks', function () {
    var tasks = (0, vue_1.ref)((0, vue_1.reactive)([]));
    var handleApiRequestNotifications = (0, notification_1.useNotificationsStore)().handleApiRequestNotifications;
    function fetchTasks() {
        return __awaiter(this, void 0, void 0, function () {
            var organizationId, tasksResponse;
            return __generator(this, function (_a) {
                switch (_a.label) {
                    case 0:
                        organizationId = (0, useUser_1.getCurrentOrganizationId)();
                        if (!organizationId) return [3 /*break*/, 2];
                        return [4 /*yield*/, handleApiRequestNotifications(function () {
                                return src_1.api.getTasks({
                                    params: {
                                        organization: organizationId,
                                    },
                                    queries: {
                                        done: 'all',
                                    },
                                });
                            })];
                    case 1:
                        tasksResponse = _a.sent();
                        if (tasksResponse === null || tasksResponse === void 0 ? void 0 : tasksResponse.data) {
                            tasks.value = tasksResponse.data;
                        }
                        _a.label = 2;
                    case 2: return [2 /*return*/];
                }
            });
        });
    }
    function updateTask(taskId, taskBody) {
        return __awaiter(this, void 0, void 0, function () {
            var organizationId;
            return __generator(this, function (_a) {
                switch (_a.label) {
                    case 0:
                        organizationId = (0, useUser_1.getCurrentOrganizationId)();
                        if (!organizationId) return [3 /*break*/, 3];
                        return [4 /*yield*/, handleApiRequestNotifications(function () {
                                return src_1.api.updateTask(taskBody, {
                                    params: {
                                        task: taskId,
                                        organization: organizationId,
                                    },
                                });
                            }, 'Task updated successfully', 'Failed to update task')];
                    case 1:
                        _a.sent();
                        return [4 /*yield*/, fetchTasks()];
                    case 2:
                        _a.sent();
                        _a.label = 3;
                    case 3: return [2 /*return*/];
                }
            });
        });
    }
    function createTask(task) {
        return __awaiter(this, void 0, void 0, function () {
            var organizationId;
            return __generator(this, function (_a) {
                switch (_a.label) {
                    case 0:
                        organizationId = (0, useUser_1.getCurrentOrganizationId)();
                        if (!organizationId) return [3 /*break*/, 3];
                        return [4 /*yield*/, handleApiRequestNotifications(function () {
                                return src_1.api.createTask(task, {
                                    params: {
                                        organization: organizationId,
                                    },
                                });
                            }, 'Task created successfully', 'Failed to create task')];
                    case 1:
                        _a.sent();
                        return [4 /*yield*/, fetchTasks()];
                    case 2:
                        _a.sent();
                        _a.label = 3;
                    case 3: return [2 /*return*/];
                }
            });
        });
    }
    function deleteTask(taskId) {
        return __awaiter(this, void 0, void 0, function () {
            var organizationId;
            return __generator(this, function (_a) {
                switch (_a.label) {
                    case 0:
                        organizationId = (0, useUser_1.getCurrentOrganizationId)();
                        if (!organizationId) return [3 /*break*/, 3];
                        return [4 /*yield*/, handleApiRequestNotifications(function () {
                                return src_1.api.deleteTask(undefined, {
                                    params: {
                                        organization: organizationId,
                                        task: taskId,
                                    },
                                });
                            }, 'Task deleted successfully', 'Failed to delete task')];
                    case 1:
                        _a.sent();
                        return [4 /*yield*/, fetchTasks()];
                    case 2:
                        _a.sent();
                        _a.label = 3;
                    case 3: return [2 /*return*/];
                }
            });
        });
    }
    return {
        tasks: tasks,
        fetchTasks: fetchTasks,
        updateTask: updateTask,
        createTask: createTask,
        deleteTask: deleteTask,
    };
});
