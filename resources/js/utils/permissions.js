"use strict";
Object.defineProperty(exports, "__esModule", { value: true });
exports.canUpdateOrganization = canUpdateOrganization;
exports.canViewProjects = canViewProjects;
exports.canCreateProjects = canCreateProjects;
exports.canUpdateProjects = canUpdateProjects;
exports.canDeleteProjects = canDeleteProjects;
exports.canViewProjectMembers = canViewProjectMembers;
exports.canCreateTasks = canCreateTasks;
exports.canUpdateTasks = canUpdateTasks;
exports.canDeleteTasks = canDeleteTasks;
exports.canCreateClients = canCreateClients;
exports.canUpdateClients = canUpdateClients;
exports.canDeleteClients = canDeleteClients;
exports.canViewClients = canViewClients;
exports.canViewMembers = canViewMembers;
exports.canUpdateMembers = canUpdateMembers;
exports.canDeleteMembers = canDeleteMembers;
exports.canMergeMembers = canMergeMembers;
exports.canMakeMembersPlaceholders = canMakeMembersPlaceholders;
exports.canInvitePlaceholderMembers = canInvitePlaceholderMembers;
exports.canCreateInvitations = canCreateInvitations;
exports.canViewTags = canViewTags;
exports.canCreateTags = canCreateTags;
exports.canDeleteTags = canDeleteTags;
exports.canManageBilling = canManageBilling;
exports.canViewReport = canViewReport;
exports.canUpdateReport = canUpdateReport;
exports.canDeleteReport = canDeleteReport;
exports.canViewAllTimeEntries = canViewAllTimeEntries;
exports.canViewInvoices = canViewInvoices;
exports.canCreateReports = canCreateReports;
var vue3_1 = require("@inertiajs/vue3");
var page = (0, vue3_1.usePage)();
function currentUserHasPermission(permission) {
    if (Array.isArray(page.props.auth.permissions)) {
        return page.props.auth.permissions.includes(permission);
    }
    return false;
}
function canUpdateOrganization() {
    return currentUserHasPermission('organizations:update');
}
function canViewProjects() {
    return currentUserHasPermission('projects:view');
}
function canCreateProjects() {
    return currentUserHasPermission('projects:create');
}
function canUpdateProjects() {
    return currentUserHasPermission('projects:update');
}
function canDeleteProjects() {
    return currentUserHasPermission('projects:delete');
}
function canViewProjectMembers() {
    return currentUserHasPermission('project-members:view');
}
function canCreateTasks() {
    return currentUserHasPermission('tasks:create');
}
function canUpdateTasks() {
    return currentUserHasPermission('tasks:update');
}
function canDeleteTasks() {
    return currentUserHasPermission('tasks:delete');
}
function canCreateClients() {
    return currentUserHasPermission('clients:create');
}
function canUpdateClients() {
    return currentUserHasPermission('clients:update');
}
function canDeleteClients() {
    return currentUserHasPermission('clients:delete');
}
function canViewClients() {
    return currentUserHasPermission('clients:view');
}
function canViewMembers() {
    return currentUserHasPermission('members:view');
}
function canUpdateMembers() {
    return currentUserHasPermission('members:update');
}
function canDeleteMembers() {
    return currentUserHasPermission('members:delete');
}
function canMergeMembers() {
    return currentUserHasPermission('members:merge-into');
}
function canMakeMembersPlaceholders() {
    return currentUserHasPermission('members:make-placeholder');
}
function canInvitePlaceholderMembers() {
    return currentUserHasPermission('members:invite-placeholder');
}
function canCreateInvitations() {
    return currentUserHasPermission('invitations:create');
}
function canViewTags() {
    return currentUserHasPermission('tags:view');
}
function canCreateTags() {
    return currentUserHasPermission('tags:create');
}
function canDeleteTags() {
    return currentUserHasPermission('tags:delete');
}
function canManageBilling() {
    return currentUserHasPermission('billing');
}
function canViewReport() {
    return currentUserHasPermission('reports:view');
}
function canUpdateReport() {
    return currentUserHasPermission('reports:update');
}
function canDeleteReport() {
    return currentUserHasPermission('reports:delete');
}
function canViewAllTimeEntries() {
    return currentUserHasPermission('time-entries:view:all');
}
function canViewInvoices() {
    return currentUserHasPermission('invoices:view');
}
function canCreateReports() {
    return currentUserHasPermission('reports:create');
}
