<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class MediaController extends ApiController
{
    public function uploadUserPhoto(Request $request, $id)
    {
        if (!$request->hasFile('file')) {
            return $this->error('No file uploaded');
        }

        $path = $request->file('file')->store("uploads/users/{$id}/photo", 's3');

        return $this->success([
            'path' => $path,
            'url' => Storage::disk('s3')->temporaryUrl($path, now()->addMinutes(15)),
        ]);
    }

    public function uploadClassVideo(Request $request, $id)
    {
        if (!$request->hasFile('file')) {
            return $this->error('No file uploaded');
        }

        $path = $request->file('file')->store("uploads/classes/{$id}/videos", 's3');
        return $this->success([
            'path' => $path,
            'url' => Storage::disk('s3')->temporaryUrl($path, now()->addMinutes(15)),
        ]);
    }

    public function uploadClassPhoto(Request $request, $id)
    {
        if (!$request->hasFile('file')) {
            return $this->error('No file uploaded');
        }

        $path = $request->file('file')->store("uploads/classes/{$id}/photos", 's3');
        return $this->success([
            'path' => $path,
            'url' => Storage::disk('s3')->temporaryUrl($path, now()->addMinutes(15)),
        ]);
    }

    public function uploadCoursePhoto(Request $request, $id)
    {
        if (!$request->hasFile('file')) {
            return $this->error('No file uploaded');
        }

        $path = $request->file('file')->store("uploads/courses/{$id}/photos", 's3');
        return $this->success([
            'path' => $path,
            'url' => Storage::disk('s3')->temporaryUrl($path, now()->addMinutes(15)),
        ]);
    }

    public function getUserPhoto($id)
    {
        $path = "uploads/users/{$id}/photo";
        if (!Storage::disk('s3')->exists($path)) {
            return $this->error('Photo not found', 404);
        }

        $url = Storage::disk('s3')->temporaryUrl($path, now()->addMinutes(15));
        return $this->success(['url' => $url]);
    }

    public function getClassVideo($id)
    {
        $path = "uploads/classes/{$id}/videos";
        if (!Storage::disk('s3')->exists($path)) {
            return $this->error('Video not found', 404);
        }

        $url = Storage::disk('s3')->temporaryUrl($path, now()->addMinutes(15));
        return $this->success(['url' => $url]);
    }

    public function getClassPhoto($id)
    {
        $path = "uploads/classes/{$id}/photos";
        if (!Storage::disk('s3')->exists($path)) {
            return $this->error('Photo not found', 404);
        }

        $url = Storage::disk('s3')->temporaryUrl($path, now()->addMinutes(15));
        return $this->success(['url' => $url]);
    }

    public function getCoursePhoto($id)
    {
        $path = "uploads/courses/{$id}/photos";
        if (!Storage::disk('s3')->exists($path)) {
            return $this->error('Photo not found', 404);
        }

        $url = Storage::disk('s3')->temporaryUrl($path, now()->addMinutes(15));
        return $this->success(['url' => $url]);
    }

}
