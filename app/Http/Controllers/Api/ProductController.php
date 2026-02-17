<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\MbsdbProductResource;
use App\Http\Resources\Api\RawMaterialResource;
use App\Http\Resources\Api\SsdbProductResource;
use App\Models\MbsdbProduct;
use App\Models\RawMaterial;
use App\Models\SsdbProduct;
use App\Services\Estimation\CachingService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ProductController extends Controller
{
    /**
     * Search MBSDB products by code or description.
     */
    public function search(Request $request): AnonymousResourceCollection
    {
        $request->validate([
            'q' => ['required', 'string', 'min:1'],
            'category' => ['nullable', 'string'],
        ]);

        $q = $request->query('q');

        $query = MbsdbProduct::query()
            ->where(function ($builder) use ($q) {
                $builder->where('code', 'LIKE', "%{$q}%")
                    ->orWhere('description', 'LIKE', "%{$q}%");
            });

        if ($request->filled('category')) {
            $query->where('category', $request->query('category'));
        }

        $results = $query->limit(50)->get();

        return MbsdbProductResource::collection($results);
    }

    /**
     * Get a single MBSDB product by exact code.
     */
    public function show(string $code, CachingService $cachingService): MbsdbProductResource
    {
        $product = $cachingService->getProductByCode($code);

        abort_if(! $product, 404, 'Product not found.');

        return new MbsdbProductResource($product);
    }

    /**
     * Search SSDB (structural steel) products by code or description.
     */
    public function searchStructuralSteel(Request $request): AnonymousResourceCollection
    {
        $request->validate([
            'q' => ['required', 'string', 'min:1'],
        ]);

        $q = $request->query('q');

        $results = SsdbProduct::query()
            ->where(function ($builder) use ($q) {
                $builder->where('code', 'LIKE', "%{$q}%")
                    ->orWhere('description', 'LIKE', "%{$q}%");
            })
            ->limit(50)
            ->get();

        return SsdbProductResource::collection($results);
    }

    /**
     * Search raw materials by code or description.
     */
    public function searchRawMaterials(Request $request): AnonymousResourceCollection
    {
        $request->validate([
            'q' => ['required', 'string', 'min:1'],
        ]);

        $q = $request->query('q');

        $results = RawMaterial::query()
            ->where(function ($builder) use ($q) {
                $builder->where('code', 'LIKE', "%{$q}%")
                    ->orWhere('description', 'LIKE', "%{$q}%");
            })
            ->limit(50)
            ->get();

        return RawMaterialResource::collection($results);
    }
}
