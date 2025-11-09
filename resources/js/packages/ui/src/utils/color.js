"use strict";
Object.defineProperty(exports, "__esModule", { value: true });
exports.colors = void 0;
exports.getRandomColor = getRandomColor;
exports.getRandomColorWithSeed = getRandomColorWithSeed;
var random_1 = require("@/packages/ui/src/utils/random");
exports.colors = [
    '#ef5350',
    '#ec407a',
    '#ab47bc',
    '#7e57c2',
    '#5c6bc0',
    '#42a5f5',
    '#29b6f6',
    '#26c6da',
    '#26a69a',
    '#66bb6a',
    '#9ccc65',
    '#d4e157',
    '#ffee58',
    '#ffca28',
    '#ffa726',
    '#ff7043',
    '#8d6e63',
    '#bdbdbd',
    '#78909c',
];
function getRandomColor() {
    return exports.colors[Math.floor(Math.random() * exports.colors.length)];
}
function getRandomColorWithSeed(seed) {
    var pseudoRandom = new random_1.default(seed);
    var index = pseudoRandom.nextInt(0, exports.colors.length - 1);
    return exports.colors[index];
}
