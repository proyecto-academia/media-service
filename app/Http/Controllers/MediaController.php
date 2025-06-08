<?php

namespace App\Http\Controllers;

use Deployer\Logger\Logger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Models\S3Object;
use App\Http\Controllers\ApiController;
use Illuminate\Support\Facades\Http;

class MediaController extends ApiController
{
    private function removeRedisKey(string $redisKey){
        if (app()->bound('redis')) {
            app('redis')->del($redisKey);
        }
    }

    private function storeRedisKey(string $redisKey, $data, int $ttl = 1800){
        if (is_array($data) || is_object($data)) {
            $data = json_encode($data);
        }

        if (!is_string($data)) {
            throw new \InvalidArgumentException('Data must be a string, array, or object that can be JSON encoded.');
        }

        if (app()->bound('redis') && !empty($redisKey)) {
            app('redis')->setex($redisKey, $ttl, $data);
        }else{
            Logger::error("Redis is not bound or redisKey is empty: {$redisKey}");
        }


    }

    private function storeAndRespond(Request $request, string $relativePath)
    {
        $file = $request->file('file');
        $filename = $file->getClientOriginalName();
        $fullKey = $relativePath . $filename;

        $stored = $file->storeAs($relativePath, $filename, 's3');
        if (!$stored || !Storage::disk('s3')->exists($fullKey)) {
            return $this->error('File upload failed', 500);
        }

        S3Object::create([
            'path' => $relativePath,
            'filename' => $filename,
        ]);

        $url = Storage::disk('s3')->temporaryUrl($fullKey, now()->addMinutes(15));

        return $this->success([
            'path' => $fullKey,
            'url' => $url,
        ]);
    }

    private function fetchAndRespond(string $relativePath)
    {
        $record = S3Object::where('path', $relativePath)->latest()->first();

        if (!$record) {
            return $this->error('File not found in database', 404);
        }

        $fullKey = $record->path . $record->filename;

        if (!Storage::disk('s3')->exists($fullKey)) {
            return $this->error('File not found in S3', 404);
        }

        $url = Storage::disk('s3')->temporaryUrl($fullKey, now()->addMinutes(15));
        return $this->success(['url' => $url]);
    }

    private function checkPolicy(Request $request, string $policy, int $modelId): bool
    {
        $redisKey = "policy:{$policy}:{$modelId}:{$request->get('auth_user')['data']['id']}";

        // Check if the policy exists in Redis cache
        if (app()->bound('redis') && app('redis')->exists($redisKey)) {
            $cachedResponse = app('redis')->get($redisKey);
            return json_decode($cachedResponse, true)['authorized'] === true;
        }

        $authUrl = env('AUTH_SERVICE_URL') . '/check-policy';

        $response = Http::withToken($request->bearerToken())
            ->post($authUrl, [
                'policy' => $policy,
                'model_id' => $modelId,
            ]);

        if ($response->ok() && $response->json('data')['authorized'] === true) {
            // Cache the response in Redis
            $this->storeRedisKey($redisKey, $response->json('data'), 1800);
            return true;
        }

        return false;
    }



    public function uploadUserPhoto(Request $request, $id)
    {
        // $user = $request->get('auth_user');
        // if (!$user || !isset($user['id'])) {
        //     return $this->error('User not authenticated', 401);
        // }

        if (!$request->hasFile('file')) {
            return $this->error('No file uploaded');
        }

        // if ($id != $user['id']) {
        //     return $this->error('You cannot update another user photo', 403);
        // }

        return $this->storeAndRespond($request, "uploads/users/{$id}/photo/");
    }

    public function uploadClassVideo(Request $request, $id)
    {
        if (!$request->hasFile('file')) {
            return $this->error('No file uploaded');
        }

        return $this->storeAndRespond($request, "uploads/classes/{$id}/video/");

    }


    public function uploadClassPhoto(Request $request, $id)
    {
        if (!$request->hasFile('file')) {
            return $this->error('No file uploaded');
        }

        return $this->storeAndRespond($request, "uploads/classes/{$id}/photo/");


    }

    public function uploadCoursePhoto(Request $request, $id)
    {
        if (!$request->hasFile('file')) {
            return $this->error('No file uploaded');
        }

        return $this->storeAndRespond($request, "uploads/courses/{$id}/photo/");

    }

    public function getUserPhoto($id)
    {
        return $this->fetchAndRespond("uploads/users/{$id}/photo/");
    }

    public function getClassVideo(Request $request, $id)
    {
        if (!$this->checkPolicy($request, 'showClass', $id)) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        return $this->fetchAndRespond("uploads/classes/{$id}/video/");
    }

    public function getClassPhoto(Request $request, $id)
    {
        if (!$this->checkPolicy($request, 'showClass', $id)) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        return $this->fetchAndRespond("uploads/classes/{$id}/photo/");
    }

    public function getCoursePhoto($id)
    {
        return $this->fetchAndRespond("uploads/courses/{$id}/photo/");
    }


    public function getMultipleCoursePhotos(Request $request)
    {
        $ids = $request->input('ids');

        if (!is_array($ids) || empty($ids)) {
            return $this->error('Invalid or empty list of course IDs', 400);
        }

        if(count($ids) > 25) {
            return $this->error('Too many course IDs provided. Maximum is 25.', 400);
        }

        $results = [];

        foreach ($ids as $id) {
            $relativePath = "uploads/courses/{$id}/photo/";

            $record = S3Object::where('path', $relativePath)->latest()->first();

            if ($record) {
                $fullKey = $record->path . $record->filename;

                if (Storage::disk('s3')->exists($fullKey)) {
                    $results[$id] = Storage::disk('s3')->temporaryUrl($fullKey, now()->addMinutes(15));
                    continue;
                }
            }

            // Si no existe en DB o S3
            $results[$id] = null;
        }

        return $this->success($results);
    }






}
