<?php

namespace App\Settings\Controllers;

use App\Settings\Requests\UpdateSettingsRequest;
use App\Settings\Services\SettingsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Arr;

class SettingsController
{
    public function index(SettingsService $settings): JsonResponse
    {
        return response()->json($settings->all());
    }

    public function update(UpdateSettingsRequest $request, SettingsService $settings): JsonResponse
    {
        $settings->update(Arr::dot($request->validated()));

        return response()->json($settings->all());
    }
}
