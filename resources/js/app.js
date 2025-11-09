"use strict";
Object.defineProperty(exports, "__esModule", { value: true });
require("./bootstrap");
require("../css/app.css");
var vue_1 = require("vue");
var vue3_1 = require("@inertiajs/vue3");
var inertia_helpers_1 = require("laravel-vite-plugin/inertia-helpers");
var ziggy_1 = require("../../vendor/tightenco/ziggy");
var pinia_1 = require("pinia");
var vue_query_1 = require("@tanstack/vue-query");
var appName = import.meta.env.VITE_APP_NAME || 'Laravel';
var pinia = (0, pinia_1.createPinia)();
(0, vue3_1.createInertiaApp)({
    title: function (title) { return "".concat(title, " - ").concat(appName); },
    resolve: function (name) {
        if (name.includes('Invoicing::')) {
            var _a = name.split('::'), module = _a[0], page = _a[1];
            var pagePath = module
                ? "../../extensions/".concat(module, "/resources/js/Pages/").concat(page, ".vue")
                : "./Pages/".concat(page, ".vue");
            // BillingPortal is a Vue 2 Component and therefore should not be imported
            var pages = module
                ? import.meta.glob([
                    '../../extensions/**/resources/js/Pages/*.vue',
                    '!**/BillingPortal.vue',
                ])
                : import.meta.glob('./Pages/**/*.vue');
            return (0, inertia_helpers_1.resolvePageComponent)(pagePath, pages);
        }
        else {
            return (0, inertia_helpers_1.resolvePageComponent)("./Pages/".concat(name, ".vue"), import.meta.glob('./Pages/**/*.vue'));
        }
    },
    setup: function (_a) {
        var el = _a.el, App = _a.App, props = _a.props, plugin = _a.plugin;
        var app = (0, vue_1.createApp)({ render: function () { return (0, vue_1.h)(App, props); } });
        // currently only one vue app setup hook is supported
        if (window.vueAppSetupHook) {
            window.vueAppSetupHook(app);
        }
        window.getWeekStartSetting = function () {
            var _a;
            var page = (0, vue3_1.usePage)();
            return (_a = page.props.auth.user.week_start) !== null && _a !== void 0 ? _a : 'monday';
        };
        window.getTimezoneSetting = function () {
            var page = (0, vue3_1.usePage)();
            return page.props.auth.user.timezone;
        };
        app.use(plugin).use(pinia).use(ziggy_1.ZiggyVue).use(vue_query_1.VueQueryPlugin).mount(el);
    },
    progress: {
        color: '#4B5563',
    },
});
