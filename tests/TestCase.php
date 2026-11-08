<?php

namespace Tests;

use App\Models\Order;
use App\Models\Service;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function makeService(array $attributes = []): Service
    {
        return Service::create($attributes + [
            'name' => 'Chat box',
            'category' => 'chatbox',
            'summary' => 'A chat box coded to match your overlay.',
            'features' => ['Badges and emotes supported'],
            'price_idr' => 350000,
            'price_usd' => 30,
            'turnaround' => '3 to 5 days',
            'revisions' => 2,
            'is_active' => true,
            'sort_order' => 1,
        ]);
    }

    protected function makeOrder(string $status = 'new', array $attributes = []): Order
    {
        return Order::create($attributes + [
            'name' => 'Mochi',
            'email' => 'mochi@example.com',
            'contact_platform' => 'discord',
            'contact_handle' => 'mochi_bun',
            'currency' => 'IDR',
            'platform' => 'streamelements',
            'has_design' => 'yes',
            'description' => 'Chat box pink dengan frame per role untuk debut.',
            'status' => $status,
        ]);
    }

    /** Isian form order yang valid. */
    protected function orderPayload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Mochi',
            'email' => 'mochi@example.com',
            'contact_platform' => 'discord',
            'contact_handle' => 'mochi_bun',
            'currency' => 'IDR',
            'service_id' => null,
            'platform' => 'streamelements',
            'has_design' => 'yes',
            'description' => 'Chat box pink dengan frame per role untuk debut.',
            'agree_terms' => '1',
        ], $overrides);
    }
}
