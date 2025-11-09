"use strict";
Object.defineProperty(exports, "__esModule", { value: true });
exports.initializeStores = initializeStores;
exports.refreshStores = refreshStores;
var useProjects_1 = require("@/utils/useProjects");
var useTasks_1 = require("@/utils/useTasks");
var useTags_1 = require("@/utils/useTags");
var useCurrentTimeEntry_1 = require("@/utils/useCurrentTimeEntry");
var useClients_1 = require("@/utils/useClients");
var useMembers_1 = require("@/utils/useMembers");
var useTimeEntries_1 = require("@/utils/useTimeEntries");
var permissions_1 = require("@/utils/permissions");
function initializeStores() {
    refreshStores();
}
function refreshStores() {
    (0, useProjects_1.useProjectsStore)().fetchProjects();
    (0, useTasks_1.useTasksStore)().fetchTasks();
    (0, useTags_1.useTagsStore)().fetchTags();
    (0, useCurrentTimeEntry_1.useCurrentTimeEntryStore)().fetchCurrentTimeEntry();
    (0, useTimeEntries_1.useTimeEntriesStore)().patchTimeEntries();
    if ((0, permissions_1.canViewMembers)()) {
        (0, useMembers_1.useMembersStore)().fetchMembers();
    }
    if ((0, permissions_1.canViewClients)()) {
        (0, useClients_1.useClientsStore)().fetchClients();
    }
}
