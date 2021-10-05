<?php

namespace Database\Factories;

use App\Media;
use App\Request;
use App\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class RequestFactory extends Factory
{

    protected $model = Request::class;

    public function definition()
    {
        return [
            'media_id' => function() {
                return Media::inRandomOrder()->first()->id;
            }
        ];
    }
}