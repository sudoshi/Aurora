<?php

namespace Database\Factories\Commons;

use App\Models\Commons\Channel;
use App\Models\Commons\Message;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Message>
 */
class MessageFactory extends Factory
{
    protected $model = Message::class;

    public function definition(): array
    {
        return [
            'channel_id' => Channel::factory(),
            'user_id' => User::factory(),
            'parent_id' => null,
            'depth' => 0,
            'body' => fake()->sentence(),
        ];
    }
}
