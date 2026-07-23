<?php

namespace Database\Factories;

use App\Models\Survey;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Survey>
 */
class SurveyFactory extends Factory
{
    protected $model = Survey::class;

    public function definition(): array
    {
        return [
            'user_id'             => User::factory()->admin(),
            'title'               => fake()->sentence(4),
            'description'         => fake()->paragraph(),
            'status'              => 'draft',
            'starts_at'           => null,
            'ends_at'             => null,
            'max_responses'       => null,
            'response_limit_type' => 'none',
            'thanks_message'      => 'ご回答ありがとうございました。',
            'public_token'        => Str::uuid()->toString(),
        ];
    }

    /** 公開中の状態 */
    public function published(): static
    {
        return $this->state(['status' => 'published']);
    }

    /** 終了状態 */
    public function closed(): static
    {
        return $this->state(['status' => 'closed']);
    }
}
