<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';

json_response([
    'csrf_token' => issue_csrf_token(),
]);

