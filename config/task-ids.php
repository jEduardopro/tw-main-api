<?php

use App\Utils\Task;

return [
    /*
    |--------------------------------------------------------------------------
    | Default Task Ids of Flows
    |--------------------------------------------------------------------------
    |
    | These task ids help us to know the flow of a user within app
    |
    */

    "tasks" => [
        Task::PASSWORD_RESET_BEGIN,
        Task::LOGIN_BEGIN
    ]

];
