<?php

test('workflow runtime status modals stay local and submit without page navigation', function () {
    $root = dirname(__DIR__);
    foreach (['Index.vue', 'Show.vue', 'InstanceShow.vue'] as $page) {
        $source = file_get_contents($root.'/resources/js/Pages/Workflows/'.$page);
        expect($source)
            ->toContain('<Teleport to="body">')
            ->toContain('axios.patch')
            ->toContain('router.reload')
            ->not->toContain('statusForm.patch(')
            ->not->toContain('runtimeForm.patch(')
            ->not->toContain('window.location')
            ->not->toContain('history.pushState');
    }

    $routes = file_get_contents($root.'/routes/web.php');
    expect($routes)
        ->not->toContain('status/modal')
        ->not->toContain('popup')
        ->toContain("Route::patch('/instances/{instance}/status'");
});
