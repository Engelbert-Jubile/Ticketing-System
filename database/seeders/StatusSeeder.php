<?php

namespace Database\Seeders;

use App\Domains\Project\Models\Status;
use Illuminate\Database\Seeder;

class StatusSeeder extends Seeder
{
    public function run(): void
    {
        Status::ensureDefaults();
    }
}
