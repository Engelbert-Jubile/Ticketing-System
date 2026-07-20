<?php

test('workflow modals teleport above application chrome and lock background scroll', function () {
    foreach ([
        resource_path('js/Pages/Workflows/Index.vue'),
        resource_path('js/Pages/Workflows/Show.vue'),
        resource_path('js/Pages/Workflows/InstanceShow.vue'),
    ] as $path) {
        $source = file_get_contents($path);

        expect($source)
            ->toContain('<Teleport to="body">')
            ->toContain('position:fixed;inset:0;z-index:2147483000')
            ->toContain('workflow-modal-open')
            ->toContain('overflow:hidden!important');
    }
});

test('workflow runtime detail keeps required information and responsive timeline controls', function () {
    $source = file_get_contents(resource_path('js/Pages/Workflows/InstanceShow.vue'));

    expect($source)
        ->toContain('item.number')
        ->toContain('item.workflow_name')
        ->toContain('item.requester')
        ->toContain('item.creator')
        ->toContain('item.pic')
        ->toContain('item.priority')
        ->toContain('item.sla')
        ->toContain('item.timeline')
        ->toContain('item.history')
        ->toContain('sm:grid-cols-2')
        ->toContain('xl:grid-cols-4')
        ->toContain('item.can_update_status')
        ->toContain('item.can_update_workflow');
});
