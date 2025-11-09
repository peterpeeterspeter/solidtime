"use strict";
Object.defineProperty(exports, "__esModule", { value: true });
exports.api = exports.createApiClient = void 0;
var openapi_json_client_1 = require("./openapi.json.client");
Object.defineProperty(exports, "createApiClient", { enumerable: true, get: function () { return openapi_json_client_1.createApiClient; } });
var api = (0, openapi_json_client_1.createApiClient)('/api', { validate: 'none' });
exports.api = api;
