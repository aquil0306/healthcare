<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreIcd10CodeRequest;
use App\Http\Requests\UpdateIcd10CodeRequest;
use App\Http\Resources\Icd10CodeResource;
use App\Models\Icd10Code;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class AdminIcd10CodeController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        if (!Gate::allows('viewAny', Icd10Code::class)) {
            abort(403, 'This action is unauthorized.');
        }

        $query = Icd10Code::query();

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('code', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhere('category', 'like', "%{$search}%");
            });
        }

        if ($request->has('category')) {
            $query->where('category', $request->category);
        }

        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        $icd10Codes = $query->orderBy('code')->paginate(15);

        return Icd10CodeResource::collection($icd10Codes)->additional([
            'success' => true,
        ])->response();
    }

    public function show(Icd10Code $icd10Code): JsonResponse
    {
        $this->authorize('view', $icd10Code);

        return (new Icd10CodeResource($icd10Code))->additional([
            'success' => true,
        ])->response();
    }

    public function store(StoreIcd10CodeRequest $request): JsonResponse
    {
        $icd10Code = Icd10Code::create($request->validated());

        return (new Icd10CodeResource($icd10Code))->additional([
            'success' => true,
            'message' => 'ICD-10 code created successfully',
        ])->response()->setStatusCode(201);
    }

    public function update(UpdateIcd10CodeRequest $request, Icd10Code $icd10Code): JsonResponse
    {
        $icd10Code->update($request->validated());

        return (new Icd10CodeResource($icd10Code->fresh()))->additional([
            'success' => true,
            'message' => 'ICD-10 code updated successfully',
        ])->response();
    }

    public function destroy(Icd10Code $icd10Code): JsonResponse
    {
        $this->authorize('delete', $icd10Code);

        if ($icd10Code->referralIcd10Codes()->count() > 0) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete ICD-10 code that is in use by referrals',
            ], 422);
        }

        $icd10Code->delete();

        return response()->json([
            'success' => true,
            'message' => 'ICD-10 code deleted successfully',
        ]);
    }
}
