<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class S3Object extends Model
{
    protected $fillable = ['path', 'filename'];

    public function getFullKeyAttribute()
    {
        return rtrim($this->path, '/') . '/' . $this->filename;
    }
}
