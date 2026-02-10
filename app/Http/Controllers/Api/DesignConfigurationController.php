<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\DesignConfigurationResource;
use App\Models\DesignConfiguration;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

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

        $configurations = DesignConfiguration::query()
            ->byCategory($request->query('category'))
            ->get();

        return DesignConfigurationResource::collection($configurations);
    }

    /**
     * Get freight code configurations.
     */
    public function freightCodes(): AnonymousResourceCollection
    {
        $configurations = DesignConfiguration::query()
            ->byCategory('freight_code')
            ->get();

        return DesignConfigurationResource::collection($configurations);
    }

    /**
     * Get paint system configurations.
     */
    public function paintSystems(): AnonymousResourceCollection
    {
        $configurations = DesignConfiguration::query()
            ->byCategory('paint_system')
            ->get();

        return DesignConfigurationResource::collection($configurations);
    }
}
