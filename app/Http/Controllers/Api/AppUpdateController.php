<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CheckAppUpdateRequest;
use App\Http\Requests\StoreAppReleaseRequest;
use App\Models\AppRelease;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;

class AppUpdateController extends Controller
{
    public function check(CheckAppUpdateRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $currentVersionCode = (int)($validated['version_code'] ?? 0);
        $platform = $validated['platform'] ?? 'android';
        $channel = $validated['channel'] ?? 'stable';

        $latestRelease = AppRelease::query()
            ->where('platform', $platform)
            ->where('channel', $channel)
            ->where('is_active', true)
            ->orderByDesc('version_code')
            ->first();

        if (!$latestRelease) {
            return response()->json([
                'updateAvailable' => false,
                'versionCode' => $currentVersionCode,
                'versionName' => '',
                'downloadUrl' => '',
                'changelog' => null,
            ]);
        }

        $isUpdateAvailable = $latestRelease->version_code > $currentVersionCode;

        return response()->json([
            'updateAvailable' => $isUpdateAvailable,
            'versionCode' => $latestRelease->version_code,
            'versionName' => $latestRelease->version_name,
            'downloadUrl' => $isUpdateAvailable
                ? secure_url(Storage::url($latestRelease->apk_path))
                : '',
            'changelog' => $isUpdateAvailable ? $latestRelease->changelog : null,
        ]);
    }

    public function store(StoreAppReleaseRequest $request): JsonResponse
    {
        $validated = $request->validated();

        try {
            $release = AppRelease::query()->create([
                'platform' => $validated['platform'] ?? 'android',
                'channel' => $validated['channel'] ?? 'stable',
                'version_code' => $validated['version_code'],
                'version_name' => $validated['version_name'],
                'apk_path' => $request->file('apk')->store('app_releases', 'public'),
                'changelog' => $validated['changelog'] ?? null,
                'is_active' => $validated['is_active'] ?? true,
            ]);
        } catch (QueryException $e) {
            return response()->json([
                'message' => 'Release with this version already exists.',
            ], 409);
        }

        return response()->json([
            'message' => 'App release uploaded successfully',
            'release' => [
                'id' => $release->id,
                'platform' => $release->platform,
                'channel' => $release->channel,
                'version_code' => $release->version_code,
                'version_name' => $release->version_name,
                'download_url' => secure_url(Storage::url($release->apk_path)),
                'changelog' => $release->changelog,
                'is_active' => $release->is_active,
            ],
        ], 201);
    }
}
