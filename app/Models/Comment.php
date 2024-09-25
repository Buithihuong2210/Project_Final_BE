<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Comment extends Model
{
    protected $fillable = ['blog_id', 'user_id', 'content'];

    // Comment belongs to a blog
    public function blog()
    {
        return $this->belongsTo(Blog::class);
    }

    // Comment belongs to a user
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}

