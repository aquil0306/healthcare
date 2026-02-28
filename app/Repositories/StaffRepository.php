<?php

namespace App\Repositories;

use App\Models\Staff;

class StaffRepository extends BaseRepository
{
    public function __construct(Staff $model)
    {
        parent::__construct($model);
    }

    public function findByEmail(string $email): ?Staff
    {
        return $this->model->where('email', $email)->first();
    }

    public function getAvailableByDepartmentAndRole(string $department, string $role): \Illuminate\Database\Eloquent\Collection
    {
        return $this->model
            ->where('department', $department)
            ->whereHas('user.roles', function ($q) use ($role) {
                $q->where('name', $role);
            })
            ->where('is_available', true)
            ->get();
    }

    public function getAdmins(): \Illuminate\Database\Eloquent\Collection
    {
        return $this->model
            ->whereHas('user.roles', function ($q) {
                $q->where('name', 'admin');
            })
            ->where('is_available', true)
            ->get();
    }
}
