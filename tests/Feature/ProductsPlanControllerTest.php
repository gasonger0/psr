<?php

namespace Tests\Feature;

use App\Http\Controllers\ProductsPlanController;
use App\Models\Lines;
use App\Models\ProductsSlots;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Tests\TestCase;

const TEST_DATE = '2026-04-02';
const TEST_IS_DAY = 0;

class ProductsPlanControllerTest extends TestCase
{

    static public function addSession(Request &$request)
    {
        $request->attributes->set('date', TEST_DATE);
        $request->attributes->set('isDay', TEST_IS_DAY);
    }

    static public function compare(array $expected, array $actual): void
    {
        foreach ($actual as $lineId => $plans) {
            self::assertArrayHasKey($lineId, $expected);
            foreach ($plans as $index => $plan) {
                $expectedPlan = $expected[$lineId][$index];
                foreach ($expectedPlan as $key => $value) {
                    self::assertArrayHasKey($key, $plan);
                    self::assertEquals($value, $plan[$key]);
                }
            }
        }
    }

    static public function extractOrder(Response $response): array
    {
        $content = json_decode($response->getContent(), true);
        $responseBody = json_decode($response->content(), true);
        return $responseBody['plansOrder'];
    }

    // Проверяем создание первого запроса с ВГОУ
    public function test_can_create_first_plan(): void
    {
        $request = Request::create('/plans/create', 'POST', [
            "slot_id" => 2997,
            "started_at" => TEST_DATE . " 20:30:00",
            "ended_at" => TEST_DATE . " 22:59:00",
            "amount" => 100,
            "delay" => 30,
            "colon" => "2",
            "hardware" => 1,
            "packs" => [610, 3000, 3001]
        ]);
        self::addSession($request);

        $controller = new ProductsPlanController();
        $response = $controller->create($request);

        $this->assertSame(201, $response->getStatusCode());

        $order = self::extractOrder($response);

        $orderCase = [
            // Варка (1)
            8 => [
                [
                    'slot_id' => 2997,
                    'started_at' => TEST_DATE . " 20:30:00",
                    'ended_at' => TEST_DATE . " 22:59:00",
                ]
            ],
            // Глазировка (3)
            14 => [
                [
                    "slot_id" => 3000,
                    "started_at" => TEST_DATE . " 21:00:00",
                    "ended_at" => TEST_DATE . " 23:44:00",
                ]
            ],
            // Обсыпка (4)
            17 => [
                [
                    "slot_id" => 3001,
                    "started_at" => TEST_DATE . " 20:30:00",
                    "ended_at" => TEST_DATE . " 21:59:00",
                ]
            ],
            // Сборка ящиков (2)
            37 => [
                [
                    "slot_id" => 610,
                    "started_at" => TEST_DATE . " 20:30:00",
                    "ended_at" => TEST_DATE . " 21:44:00",
                ]
            ],

        ];

        self::compare($orderCase, $order);
    }

    public function test_can_insert_before_first_plan(): void
    {
        $request = Request::create('/plans/create', 'POST', [
            "slot_id" => 631,
            "started_at" => TEST_DATE . " 22:59:07",
            "ended_at" => TEST_DATE . " 00:12:22",
            "amount" => 100,
            "delay" => 30,
            "colon" => "2",
            "hardware" => 1,
            "packs" => [633, 3013]
        ]);
        self::addSession($request);

        $controller = new ProductsPlanController();
        $response = $controller->create($request);

        $this->assertSame(201, $response->getStatusCode());

        $orderCase = [
            8 => [
                [
                    "slot_id" => 631,
                    "started_at" => TEST_DATE . " 22:59:07",
                    "ended_at" => TEST_DATE . " 00:12:22",
                ],
                [
                    "slot_id" => 2997,
                    "started_at" => TEST_DATE . " 20:30:00",
                    "ended_at" => TEST_DATE . " 22:59:07",
                ]
            ],
            13 => [
                [
                    "slot_id" => 633,
                    "started_at" => TEST_DATE . " 23:29:07",
                    "ended_at" => TEST_DATE . " 01:12:22",
                ]
            ],
            14 => [
                [
                    "slot_id" => 3000,
                    "started_at" => TEST_DATE . " 21:00:00",
                    "ended_at" => TEST_DATE . " 23:44:07",
                ],
                [
                    "slot_id" => 3013,
                    "started_at" => TEST_DATE . " 23:44:07",
                    "ended_at" => TEST_DATE . " 01:12:22",
                ]
            ]
        ];

        $order = self::extractOrder($response);
        self::compare($orderCase, $order);
    }

    // public function test_can_
}