<?php

namespace App\Repositories;

use App\Models\Hospital;
use Illuminate\Support\Str;

class HospitalRepository extends BaseRepository
{
    public function __construct(Hospital $model)
    {
        parent::__construct($model);
    }

    public function findByApiKey(string $apiKey): ?Hospital
    {
        return $this->model->where('api_key', $apiKey)->first();
    }

    public function findByCode(string $code): ?Hospital
    {
        return $this->model->where('code', $code)->first();
    }

    public function generateApiKey(): string
    {
        return Str::random(64);
    }
}

