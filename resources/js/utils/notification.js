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
exports.useNotificationsStore = void 0;
var pinia_1 = require("pinia");
var vue_1 = require("vue");
var axios_1 = require("axios");
var vue3_1 = require("@inertiajs/vue3");
var session_1 = require("@/utils/session");
exports.useNotificationsStore = (0, pinia_1.defineStore)('notifications', function () {
    var notifications = (0, vue_1.ref)([]);
    var showActionBlockedModal = (0, vue_1.ref)(false);
    function addNotification(type, title, message) {
        var uuid = Math.random().toString(36).substring(7);
        notifications.value.push({ title: title, message: message, type: type, uuid: uuid });
        setTimeout(function () {
            removeNotification(uuid);
        }, 5000);
    }
    function removeNotification(uuid) {
        var index = notifications.value.findIndex(function (notification) { return notification.uuid === uuid; });
        if (index !== -1) {
            notifications.value.splice(index, 1);
        }
    }
    function handleApiRequestNotifications(apiRequest, successMessage, errorMessage, onSuccess) {
        return __awaiter(this, void 0, void 0, function () {
            var response, error_1, message, response, _a;
            var _b, _c, _d, _e, _f, _g, _h, _j, _k, _l, _m, _o;
            return __generator(this, function (_p) {
                switch (_p.label) {
                    case 0:
                        _p.trys.push([0, 2, , 12]);
                        return [4 /*yield*/, apiRequest()];
                    case 1:
                        response = _p.sent();
                        if (successMessage) {
                            addNotification('success', successMessage);
                        }
                        if (onSuccess) {
                            onSuccess(response);
                        }
                        return [2 /*return*/, response];
                    case 2:
                        error_1 = _p.sent();
                        if (!axios_1.default.isAxiosError(error_1)) return [3 /*break*/, 11];
                        if (!(((_b = error_1 === null || error_1 === void 0 ? void 0 : error_1.response) === null || _b === void 0 ? void 0 : _b.status) === 403 || ((_c = error_1 === null || error_1 === void 0 ? void 0 : error_1.response) === null || _c === void 0 ? void 0 : _c.status) === 400)) return [3 /*break*/, 3];
                        if (((_e = (_d = error_1 === null || error_1 === void 0 ? void 0 : error_1.response) === null || _d === void 0 ? void 0 : _d.data) === null || _e === void 0 ? void 0 : _e.key) ===
                            'organization_has_no_subscription_but_multiple_members') {
                            showActionBlockedModal.value = true;
                        }
                        else {
                            addNotification('error', errorMessage !== null && errorMessage !== void 0 ? errorMessage : 'Request Error', (_l = (_h = (_g = (_f = error_1.response) === null || _f === void 0 ? void 0 : _f.data) === null || _g === void 0 ? void 0 : _g.errorMessage) !== null && _h !== void 0 ? _h : (_k = (_j = error_1 === null || error_1 === void 0 ? void 0 : error_1.response) === null || _j === void 0 ? void 0 : _j.data) === null || _k === void 0 ? void 0 : _k.message) !== null && _l !== void 0 ? _l : 'An request error occurred. Please try again later.');
                        }
                        return [3 /*break*/, 11];
                    case 3:
                        if (!(((_m = error_1 === null || error_1 === void 0 ? void 0 : error_1.response) === null || _m === void 0 ? void 0 : _m.status) === 422)) return [3 /*break*/, 4];
                        message = error_1.response.data.message;
                        addNotification('error', message);
                        return [3 /*break*/, 11];
                    case 4:
                        if (!(((_o = error_1 === null || error_1 === void 0 ? void 0 : error_1.response) === null || _o === void 0 ? void 0 : _o.status) === 401)) return [3 /*break*/, 10];
                        return [4 /*yield*/, (0, session_1.fetchToken)()];
                    case 5:
                        _p.sent();
                        _p.label = 6;
                    case 6:
                        _p.trys.push([6, 8, , 9]);
                        return [4 /*yield*/, apiRequest()];
                    case 7:
                        response = _p.sent();
                        if (successMessage) {
                            addNotification('success', successMessage);
                        }
                        if (onSuccess) {
                            onSuccess(response);
                        }
                        return [2 /*return*/, response];
                    case 8:
                        _a = _p.sent();
                        vue3_1.router.get(route('login'));
                        return [3 /*break*/, 9];
                    case 9: return [3 /*break*/, 11];
                    case 10:
                        addNotification('error', 'The action failed. Please try again later.');
                        _p.label = 11;
                    case 11: throw new Error('Failed to handle API request');
                    case 12: return [2 /*return*/];
                }
            });
        });
    }
    return {
        addNotification: addNotification,
        notifications: notifications,
        handleApiRequestNotifications: handleApiRequestNotifications,
        showActionBlockedModal: showActionBlockedModal,
    };
});
