"use strict";
Object.defineProperty(exports, "__esModule", { value: true });
exports.useSelectEvents = useSelectEvents;
var vue_1 = require("vue");
var core_1 = require("@vueuse/core");
function useSelectEvents(filteredItems, highlightedItemId, getKeyFromItem, open) {
    function moveHighlightUp() {
        if (highlightedItem.value) {
            var currentHightlightedIndex = filteredItems.value.indexOf(highlightedItem.value);
            if (currentHightlightedIndex === 0) {
                highlightedItemId.value = getKeyFromItem(filteredItems.value[filteredItems.value.length - 1]);
            }
            else {
                highlightedItemId.value = getKeyFromItem(filteredItems.value[currentHightlightedIndex - 1]);
            }
        }
        else {
            highlightedItemId.value = getKeyFromItem(filteredItems.value[filteredItems.value.length - 1]);
        }
    }
    function moveHighlightDown() {
        if (highlightedItem.value) {
            var currentHightlightedIndex = filteredItems.value.indexOf(highlightedItem.value);
            if (currentHightlightedIndex === filteredItems.value.length - 1) {
                highlightedItemId.value = getKeyFromItem(filteredItems.value[0]);
            }
            else {
                highlightedItemId.value = getKeyFromItem(filteredItems.value[currentHightlightedIndex + 1]);
            }
        }
        else {
            highlightedItemId.value = getKeyFromItem(filteredItems.value[0]);
        }
    }
    var highlightedItem = (0, vue_1.computed)(function () {
        return filteredItems.value.find(function (item) { return getKeyFromItem(item) === highlightedItemId.value; });
    });
    (0, core_1.onKeyStroke)('ArrowDown', function (e) {
        if (open.value === true) {
            moveHighlightDown();
            e.preventDefault();
        }
    });
    (0, core_1.onKeyStroke)('ArrowUp', function (e) {
        if (open.value === true) {
            moveHighlightUp();
            e.preventDefault();
        }
    });
    (0, vue_1.watch)(open, function (newOpen) {
        if (newOpen === false) {
            highlightedItemId.value = null;
        }
    });
}
