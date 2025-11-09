"use strict";
Object.defineProperty(exports, "__esModule", { value: true });
exports.useCssVariable = useCssVariable;
var vue_1 = require("vue");
function useCssVariable(variableName) {
    var value = (0, vue_1.ref)('');
    var observer = null;
    var mediaQuery = null;
    var updateValue = function () {
        var computedStyle = getComputedStyle(document.documentElement);
        var cssValue = computedStyle.getPropertyValue(variableName).trim();
        value.value = cssValue;
    };
    (0, vue_1.onMounted)(function () {
        // Initialize with current value
        updateValue();
        // Watch for class changes on document.documentElement (where theme classes are applied)
        observer = new MutationObserver(function (mutations) {
            mutations.forEach(function (mutation) {
                if (mutation.type === 'attributes' && mutation.attributeName === 'class') {
                    updateValue();
                }
            });
        });
        observer.observe(document.documentElement, {
            attributes: true,
            attributeFilter: ['class'],
        });
        // Also watch for system color scheme changes
        if (window.matchMedia) {
            mediaQuery = window.matchMedia('(prefers-color-scheme: dark)');
            mediaQuery.addEventListener('change', updateValue);
        }
    });
    (0, vue_1.onUnmounted)(function () {
        if (observer) {
            observer.disconnect();
        }
        if (mediaQuery) {
            mediaQuery.removeEventListener('change', updateValue);
        }
    });
    return value;
}
