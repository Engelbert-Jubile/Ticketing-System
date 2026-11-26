<?php

uses(Tests\TestCase::class);

test('workflow modals teleport above application chrome and lock background scroll', function () {
    $source = file_get_contents(resource_path('js/Components/WorkflowStatusDialog.vue'));

    expect($source)
        ->toContain('<Teleport to="body">')
        ->toContain('z-[2147483000]')
        ->toContain("document.body.style.overflow = 'hidden'")
        ->toContain('document.body.style.overflow = previousOverflow');
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
        ->toContain('item.can_reject')
        ->toContain('history.reason')
        ->not->toContain('item.workflow_edit_url');
});
