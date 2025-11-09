"use strict";
Object.defineProperty(exports, "__esModule", { value: true });
exports.theme = exports.themeSetting = void 0;
exports.useTheme = useTheme;
var core_1 = require("@vueuse/core");
var vue_1 = require("vue");
var themeSetting = (0, core_1.useStorage)('theme', 'system');
exports.themeSetting = themeSetting;
var preferredColor = (0, core_1.usePreferredColorScheme)();
var theme = (0, vue_1.computed)(function () {
    if (themeSetting.value === 'system') {
        console.log(preferredColor.value);
        if (preferredColor.value === 'no-preference') {
            return 'dark';
        }
        return preferredColor.value;
    }
    return themeSetting.value;
});
exports.theme = theme;
function useTheme() {
    document.documentElement.classList.add(theme.value);
    (0, vue_1.watch)(theme, function (newTheme, oldTheme) {
        document.documentElement.classList.remove(oldTheme);
        document.documentElement.classList.add(newTheme);
    });
}
