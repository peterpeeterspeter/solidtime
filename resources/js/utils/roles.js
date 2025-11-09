"use strict";
Object.defineProperty(exports, "__esModule", { value: true });
exports.filterRoles = filterRoles;
function filterRoles(roles) {
    return roles.filter(function (role) {
        return role.key !== 'placeholder' && role.key !== 'owner';
    });
}
