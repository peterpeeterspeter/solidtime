"use strict";
Object.defineProperty(exports, "__esModule", { value: true });
exports.getCurrentOrganizationId = getCurrentOrganizationId;
exports.getCurrentUserId = getCurrentUserId;
exports.getCurrentMembershipId = getCurrentMembershipId;
exports.getCurrentRole = getCurrentRole;
exports.getCurrentUser = getCurrentUser;
var vue3_1 = require("@inertiajs/vue3");
var page = (0, vue3_1.usePage)();
function getCurrentUserId() {
    return page.props.auth.user.id;
}
function getCurrentUser() {
    return page.props.auth.user;
}
function getCurrentOrganizationId() {
    return page.props.auth.user.current_team_id;
}
function getCurrentMembershipId() {
    var _a;
    return (_a = page.props.auth.user.all_teams.find(function (team) { return team.id === getCurrentOrganizationId(); })) === null || _a === void 0 ? void 0 : _a.membership.id;
}
function getCurrentRole() {
    var _a;
    return (_a = page.props.auth.user.all_teams.find(function (team) { return team.id === getCurrentOrganizationId(); })) === null || _a === void 0 ? void 0 : _a.membership.role;
}
