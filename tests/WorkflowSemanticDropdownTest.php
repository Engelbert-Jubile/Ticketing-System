<?php

test('workflow pages use the accessible teleported dropdown consistently', function () {
    $root = dirname(__DIR__);
    $component = file_get_contents($root.'/resources/js/Components/WorkflowSelect.vue');

    expect($component)
        ->toContain('<Teleport to="body">')
        ->toContain('role="combobox"')
        ->toContain('role="listbox"')
        ->toContain('aria-activedescendant')
        ->toContain("event.key === 'ArrowDown'")
        ->toContain("document.addEventListener('pointerdown', outside)")
        ->toContain('loadingText')
        ->toContain('emptyText');

    foreach (glob($root.'/resources/js/Pages/Workflows/*.vue') as $page) {
        expect(file_get_contents($page))->not->toContain('<select');
    }
});

test('workflow routes declare semantic definitions and public runtime identifiers', function () {
    $routes = file_get_contents(dirname(__DIR__).'/routes/web.php');
    $controller = file_get_contents(dirname(__DIR__).'/App/Http/Controllers/Main/WorkflowController.php');

    expect($routes)
        ->toContain("Route::prefix('definitions')")
        ->toContain("name('instances.show')")
        ->toContain("name('legacy.show')")
        ->and($controller)
        ->toContain("'instance' => \$number")
        ->toContain('resolveInstance(string $identifier)')
        ->toContain("where('ticket_no', \$identifier)")
        ->toContain("where('task_no', \$identifier)");
});