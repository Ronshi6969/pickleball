<?php
// Tells the .html page who is logged in, gives the CSRF token for its forms,
// and hands over flash messages / remembered form input (each shown only once).
require_once __DIR__ . '/../includes/functions.php';
$u = current_user();
$branches = [];
$branchId = 0;
if ($u && $u['role'] === 'manager') {
    $branches = array_map(
        fn($b) => ['id' => (int)$b['id'], 'name' => $b['name'], 'city' => $b['city']],
        manager_branches((int)$u['id'])
    );
    if ($branches) {
        $active = manager_venue((int)$u['id']);
        $branchId = (int)$active['id'];
    }
}
json_out([
    'user'      => $u,
    'branches'  => $branches,
    'branch_id' => $branchId,
    'csrf'      => csrf_token(),
    'flashes'   => pull_flashes(),
    'old'       => pull_old(),
    'today'     => date('Y-m-d'),
]);
