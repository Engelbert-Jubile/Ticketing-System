<?php

define('LARAVEL_START', microtime(true));
require __DIR__.'/vendor/autoload.php';
$app = require __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$schema = Illuminate\Support\Facades\Schema::getColumnListing('tickets');
$ticket = App\Models\Ticket::with('project')->orderByDesc('id')->first();
$project = App\Models\Project::orderByDesc('id')->first();

echo "Columns:\n";
echo json_encode($schema, JSON_PRETTY_PRINT), "\n\n";
if ($ticket) {
    echo "Latest ticket:\n";
    echo json_encode($ticket->toArray(), JSON_PRETTY_PRINT), "\n";
} else {
    echo "No tickets found\n";
}

if ($project) {
    echo "\nLatest project:\n";
    echo json_encode($project->toArray(), JSON_PRETTY_PRINT), "\n";
} else {
    echo "\nNo projects found\n";
}
