"use strict";
Object.defineProperty(exports, "__esModule", { value: true });
exports.capitalizeFirstLetter = capitalizeFirstLetter;
function capitalizeFirstLetter(string) {
    var _a;
    return ((_a = string === null || string === void 0 ? void 0 : string.charAt(0)) === null || _a === void 0 ? void 0 : _a.toUpperCase()) + (string === null || string === void 0 ? void 0 : string.slice(1));
}
