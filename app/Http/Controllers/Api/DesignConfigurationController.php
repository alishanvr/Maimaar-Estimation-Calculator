<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\DesignConfigurationResource;
use App\Models\DesignConfiguration;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Cache;

class DesignConfigurationController extends Controller
{
    /**
     * List design configurations filtered by category.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $request->validate([
            'category' => ['required', 'string'],
        ]);

        $category = $request->query('category');

        $configurations = Cache::remember("design_config:{$category}", 86400, function () use ($category) {
            return DesignConfiguration::query()
                ->byCategory($category)
                ->get();
        });

        return DesignConfigurationResource::collection($configurations);
    }

    /**
     * Get freight code configurations.
     */
    public function freightCodes(): AnonymousResourceCollection
    {
        $configurations = Cache::remember('design_config:freight_code', 86400, function () {
            return DesignConfiguration::query()
                ->byCategory('freight_code')
                ->get();
        });

        return DesignConfigurationResource::collection($configurations);
    }

    /**
     * Get paint system configurations.
     */
    public function paintSystems(): AnonymousResourceCollection
    {
        $configurations = Cache::remember('design_config:paint_system', 86400, function () {
            return DesignConfiguration::query()
                ->byCategory('paint_system')
                ->get();
        });

        return DesignConfigurationResource::collection($configurations);
    }
}
