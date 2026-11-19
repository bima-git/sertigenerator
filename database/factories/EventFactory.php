<?php

namespace Database\Factories;

use App\Models\Event;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Event>
 */
class EventFactory extends Factory
{
    /**
     * The model that this factory creates.
     *
     * @var class-string<Event>
     */
    protected $model = Event::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'kode' => fake()->numerify('###'),
            'judul' => Str::limit(fake()->sentence(6), 500, ''),
            'jenis' => 'seminar',
            'penerbit' => 'Xcodetraining',
            'bernomor' => true,
            'pakai_qr' => true,
            'program_default' => null,
            'aktif' => true,
        ];
    }

    /**
     * Indicate that the event is a seminar.
     */
    public function seminar(): static
    {
        return $this->state(fn (array $attributes) => [
            'jenis' => 'seminar',
            'program_default' => null,
        ]);
    }

    /**
     * Indicate that the event is a bootcamp.
     */
    public function bootcamp(): static
    {
        return $this->state(fn (array $attributes) => [
            'jenis' => 'bootcamp',
            'program_default' => Str::limit(fake()->words(3, true), 200, ''),
        ]);
    }

    /**
     * Indicate that the event is inactive.
     */
    public function nonaktif(): static
    {
        return $this->state(fn (array $attributes) => [
            'aktif' => false,
        ]);
    }

    /**
     * Indicate that the event does not use certificate numbers.
     */
    public function tanpaNomor(): static
    {
        return $this->state(fn (array $attributes) => [
            'bernomor' => false,
        ]);
    }

    /**
     * Indicate that the event does not use QR codes.
     */
    public function tanpaQr(): static
    {
        return $this->state(fn (array $attributes) => [
            'pakai_qr' => false,
        ]);
    }
}
