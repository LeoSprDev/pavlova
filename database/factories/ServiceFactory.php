<?php
namespace Database\Factories;

use App\Models\Service;
use Illuminate\Database\Eloquent\Factories\Factory;

class ServiceFactory extends Factory
{
    protected $model = Service::class;

    public function definition()
    {
        return [
            'nom' => $this->faker->word(),
            'code' => 'S' . strtoupper($this->faker->lexify('??')),
        ];
    }
}
