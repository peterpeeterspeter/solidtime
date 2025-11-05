<?php

declare(strict_types=1);

return [

    'tasks' => [
        'time_entry_send_still_running_mails' => (bool) env('SCHEDULING_TASK_TIME_ENTRY_SEND_STILL_RUNNING_MAILS', true),
        'auth_send_mails_expiring_api_tokens' => (bool) env('SCHEDULING_TASK_AUTH_SEND_MAILS_EXPIRING_API_TOKENS', true),
        'self_hosting_check_for_update' => (bool) env('SCHEDULING_TASK_SELF_HOSTING_CHECK_FOR_UPDATE', true),
        'self_hosting_telemetry' => (bool) env('SCHEDULING_TASK_SELF_HOSTING_TELEMETRY', true),
        'self_hosting_database_consistency' => (bool) env('SCHEDULING_TASK_SELF_HOSTING_DATABASE_CONSISTENCY', false),
        'activities_cleanup' => (bool) env('SCHEDULING_TASK_ACTIVITIES_CLEANUP', true),
        'focus_sessions_detect_daily' => (bool) env('SCHEDULING_TASK_FOCUS_SESSIONS_DETECT_DAILY', true),
        'webhooks_process_retries' => (bool) env('SCHEDULING_TASK_WEBHOOKS_PROCESS_RETRIES', true),
    ],
];
