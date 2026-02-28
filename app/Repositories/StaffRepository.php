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
            ->where('role', $role)
            ->where('is_available', true)
            ->get();
    }

    public function getAdmins(): \Illuminate\Database\Eloquent\Collection
    {
        return $this->model
            ->where('role', 'admin')
            ->where('is_available', true)
            ->get();
    }
}
