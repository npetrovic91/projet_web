<?php
$action = '/users/' . (int) ($user['uti_id'] ?? $user['use_id'] ?? 0) . '/update';
$mode = 'edit';
include __DIR__ . '/_form.php';
