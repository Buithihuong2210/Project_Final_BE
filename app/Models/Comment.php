<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Comment extends Model
{
    protected $primaryKey = 'comment_id';

    protected $fillable = ['blog_id', 'user_id', 'content', 'parent_id'];

    // Comment belongs to a blog
    public function blog()
    {
        return $this->belongsTo(Blog::class);
    }

    // Comment belongs to a user
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }
    // Quan hệ comment con (trả lời một comment khác)
    public function replies()
    {
        return $this->hasMany(Comment::class, 'parent_id', 'comment_id');
    }

    // Quan hệ comment cha (comment được trả lời)
    public function parent()
    {
        return $this->belongsTo(Comment::class, 'parent_id');
    }
}

