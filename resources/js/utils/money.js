"use strict";
Object.defineProperty(exports, "__esModule", { value: true });
exports.getOrganizationCurrencyString = getOrganizationCurrencyString;
var vue3_1 = require("@inertiajs/vue3");
var page = (0, vue3_1.usePage)();
function getOrganizationCurrencyString() {
    var _a, _b, _c, _d, _e;
    return (_e = (_d = (_c = (_b = (_a = page.props) === null || _a === void 0 ? void 0 : _a.auth) === null || _b === void 0 ? void 0 : _b.user) === null || _c === void 0 ? void 0 : _c.current_team) === null || _d === void 0 ? void 0 : _d.currency) !== null && _e !== void 0 ? _e : 'EUR';
}
