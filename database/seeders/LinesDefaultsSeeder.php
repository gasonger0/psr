<?php

namespace Database\Seeders;

use App\Models\LinesDefault;
use Illuminate\Database\Seeder;

class LinesDefaultsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $defaults = config('lines_defaults');

        if (!is_array($defaults)) {
            return;
        }

        foreach ($defaults as $row) {
            if (!isset($row['line_id'])) {
                continue;
            }

            LinesDefault::updateOrCreate(
                ['line_id' => $row['line_id']],
                $row
            );
        }
    }
}