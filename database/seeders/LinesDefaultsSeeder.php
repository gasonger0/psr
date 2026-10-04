<?php

namespace Database\Seeders;

use App\Models\LinesDefault;
use Illuminate\Database\Seeder;

class LinesDefaultsSeeder extends Seeder
{
    /**
     * Значения линий по умолчанию.
     * Источник правды — этот массив (раньше данные лежали в config/lines_defaults.php,
     * удалённом вместе с fallback в Util::getDefaults после перехода на lines_defaults).
     */
    private const DEFAULTS = [
        [
            'line_id' => 33,
            'workers_count' => 3,
            'prep_time' => 0,
            'after_time' => 10
        ],
        [
            'line_id' => 34,
            'workers_count' => 2,
            'prep_time' => 10,
            'after_time' => 10
        ],
        [
            'line_id' => 40,
            'workers_count' => 1,
            'prep_time' => 0,
            'after_time' => 0
        ],
        [
            'line_id' => 47,
            'workers_count' => 1,
            'prep_time' => 0,
            'after_time' => 0
        ],
        [
            'line_id' => 44,
            'workers_count' => 5,
            'prep_time' => 60,
            'after_time' => 30
        ],
        [
            'line_id' => 45,
            'workers_count' => 2,
            'prep_time' => 10,
            'after_time' => 10
        ],
        [
            'line_id' => 6,
            'workers_count' => 5,
            'prep_time' => 30,
            'after_time' => 30
        ],
        [
            'line_id' => 7,
            'workers_count' => 4,
            'prep_time' => 30,
            'after_time' => 30
        ],
        [
            'line_id' => 8,
            'perfomance' => '19 отсадок для зефира с начинкой, 23 отсадки',
            'workers_count' => 4,
            'prep_time' => 45,
            'after_time' => 60
        ],
        [
            'line_id' => 9,
            'perfomance' => '19 отсадок для зефира с начинкой, 23 отсадки',
            'workers_count' => 4,
            'prep_time' => 45,
            'after_time' => 60
        ],
        [
            'line_id' => 10,
            'perfomance' => '19 отсадок для зефира с начинкой, 23 отсадки',
            'workers_count' => 4,
            'prep_time' => 45,
            'after_time' => 60
        ],
        [
            'line_id' => 11,
            'perfomance' => '27 отсадок для зефира с начинкой, 30 отсадок без начинки',
            'workers_count' => 3,
            'prep_time' => 45,
            'after_time' => 60
        ],
        [
            'line_id' => 12,
            'perfomance' => '27 отсадок для зефира с начинкой, 31 отсадка без начинки',
            'workers_count' => 3,
            'prep_time' => 45,
            'after_time' => 60
        ],
        [
            'line_id' => 43,
            'workers_count' => 3,
            'prep_time' => null,
            'after_time' => 60
        ],
        [
            'line_id' => 41,
            'workers_count' => 6,
            'prep_time' => 15,
            'after_time' => 30
        ],
        [
            'line_id' => 13,
            'workers_count' => 3,
            'prep_time' => null,
            'after_time' => 10
        ],
        [
            'line_id' => 14,
            'workers_count' => 6,
            'prep_time' => 15,
            'after_time' => 30
        ],
        [
            'line_id' => 15,
            'workers_count' => 3,
            'prep_time' => null,
            'after_time' => 10
        ],
        [
            'line_id' => 16,
            'workers_count' => 4,
            'prep_time' => null,
            'after_time' => 10
        ],
        [
            'line_id' => 17,
            'workers_count' => 6,
            'prep_time' => 15,
            'after_time' => 30
        ],
        [
            'line_id' => 18,
            'workers_count' => 6,
            'prep_time' => 15,
            'after_time' => 30
        ],
        [
            'line_id' => 19,
            'workers_count' => 3,
            'prep_time' => null,
            'after_time' => 10
        ],
        [
            'line_id' => 20,
            'workers_count' => 7,
            'prep_time' => 5,
            'after_time' => 30
        ],
        [
            'line_id' => 21,
            'workers_count' => 3,
            'prep_time' => null,
            'after_time' => 10
        ],
        [
            'line_id' => 22,
            'workers_count' => 3,
            'prep_time' => null,
            'after_time' => 10
        ],
        [
            'line_id' => 23,
            'workers_count' => 3,
            'prep_time' => null,
            'after_time' => 10
        ],
        [
            'line_id' => 24,
            'workers_count' => 5,
            'prep_time' => 5,
            'after_time' => 30
        ],
        [
            'line_id' => 25,
            'workers_count' => 6,
            'prep_time' => 5,
            'after_time' => 15
        ],
        [
            'line_id' => 26,
            'workers_count' => 3,
            'prep_time' => null,
            'after_time' => 10
        ],
        [
            'line_id' => 27,
            'workers_count' => 3,
            'prep_time' => null,
            'after_time' => 10
        ],
        [
            'line_id' => 28,
            'workers_count' => 3,
            'prep_time' => null,
            'after_time' => 10
        ],
        [
            'line_id' => 29,
            'workers_count' => 2,
            'prep_time' => null,
            'after_time' => 10
        ],
        [
            'line_id' => 30,
            'workers_count' => 2,
            'prep_time' => null,
            'after_time' => 10
        ],
        [
            'line_id' => 31,
            'workers_count' => 3,
            'prep_time' => 30,
            'after_time' => 30
        ],
        [
            'line_id' => 32,
            'workers_count' => 3,
            'prep_time' => null,
            'after_time' => 10
        ],
        [
            'line_id' => 42,
            'workers_count' => 1,
            'prep_time' => null,
            'after_time' => 10
        ],
        [
            'line_id' => 46,
            'workers_count' => 1,
            'prep_time' => null,
            'after_time' => 10
        ],
        [
            'line_id' => 36,
            'workers_count' => 1,
            'prep_time' => null,
            'after_time' => 10
        ],
        [
            'line_id' => 37,
            'workers_count' => 1,
            'prep_time' => null,
            'after_time' => 10
        ]
    ];

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        foreach (self::DEFAULTS as $row) {
            LinesDefault::updateOrCreate(
                ['line_id' => $row['line_id']],
                $row
            );
        }
    }
}
