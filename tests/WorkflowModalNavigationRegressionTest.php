<?php

test('workflow runtime status modals stay local and submit without page navigation', function () {
    $root = dirname(__DIR__);
    foreach (['Index.vue', 'Show.vue', 'InstanceShow.vue'] as $page) {
        $source = file_get_contents($root.'/resources/js/Pages/Workflows/'.$page);
        expect($source)
            ->toContain('<WorkflowStatusDialog')
            ->toContain('router.reload')
            ->not->toContain('statusForm.patch(')
            ->not->toContain('runtimeForm.patch(')
            ->not->toContain('window.location')
            ->not->toContain('history.pushState');
    }

    $dialog = file_get_contents($root.'/resources/js/Components/WorkflowStatusDialog.vue');
    expect($dialog)->toContain('<Teleport to="body">')->toContain('axios.patch');

    $routes = file_get_contents($root.'/routes/web.php');
    expect($routes)
        ->not->toContain('status/modal')
        ->not->toContain('popup')
        ->toContain("Route::patch('/instances/{instance}/status'");
});
