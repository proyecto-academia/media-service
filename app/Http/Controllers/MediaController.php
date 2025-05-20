<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Models\S3Object;

class MediaController extends ApiController
{
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



    public function uploadUserPhoto(Request $request, $id)
    {
        $user = $request->get('auth_user');
        if (!$user || !isset($user['id'])) {
            return $this->error('User not authenticated', 401);
        }

        if (!$request->hasFile('file')) {
            return $this->error('No file uploaded');
        }

        if ($id != $user['id']) {
            return $this->error('You cannot update another user photo', 403);
        }

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

    public function getClassVideo($id)
    {
        return $this->fetchAndRespond("uploads/classes/{$id}/video/");
    }

    public function getClassPhoto($id)
    {
        return $this->fetchAndRespond("uploads/classes/{$id}/photo/");
    }

    public function getCoursePhoto($id)
    {
        return $this->fetchAndRespond("uploads/courses/{$id}/photo/");
    }


}
