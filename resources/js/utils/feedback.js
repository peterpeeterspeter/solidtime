"use strict";
Object.defineProperty(exports, "__esModule", { value: true });
exports.openFeedback = openFeedback;
function openFeedback() {
    if (typeof window !== 'undefined' &&
        'showChatWindow' in window &&
        typeof window.showChatWindow === 'function') {
        window.showChatWindow();
    }
}
