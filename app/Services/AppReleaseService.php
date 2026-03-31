<?php

namespace App\Services;

use App\Models\AppRelease;
use App\Models\User;
use App\Notifications\AppReleaseNotification;
use Illuminate\Database\QueryException;
use Illuminate\Http\UploadedFile;

class AppReleaseService
{
    public function buildDownloadUrl(): string
    {
        $downloadUrl = url('/api/app/download');
        $downloadHost = parse_url($downloadUrl, PHP_URL_HOST);

        if (is_string($downloadHost) && str_contains($downloadHost, 'azurewebsites.net')) {
            $downloadUrl = preg_replace('/^http:/', 'https:', $downloadUrl) ?? $downloadUrl;
        }

        return $downloadUrl;
    }

    public function createRelease(array $data, UploadedFile $apkFile): array
    {
        try {
            $release = AppRelease::query()->create([
                'platform' => $data['platform'] ?? 'android',
                'channel' => $data['channel'] ?? 'stable',
                'version_code' => $data['version_code'],
                'version_name' => $data['version_name'],
                'apk_path' => $apkFile->store('app_releases', 'public'),
                'changelog' => $data['changelog'] ?? null,
                'is_active' => $data['is_active'] ?? true,
            ]);
        } catch (QueryException) {
            return [
                'error' => true,
                'status' => 409,
                'message' => 'Release with this version already exists.',
            ];
        }

        User::query()
            ->chunkById(200, function ($users) use ($release) {
                foreach ($users as $user) {
                    $user->notify(new AppReleaseNotification($release));
                }
            });

        return ['release' => $release];
    }
}
