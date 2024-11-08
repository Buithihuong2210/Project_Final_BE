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

    // Quan hệ comment cha (comment được trả lời)
    public function parent()
    {
        return $this->belongsTo(Comment::class, 'parent_id');
    }

    public function replies()
    {
        return $this->hasMany(Comment::class, 'parent_id');
    }

    // Phương thức để lấy bình luận con đệ quy
    public function getRepliesWithUsers()
    {
        return $this->replies()
            ->with('user:id,name,image,dob,role,phone,gender,email', 'replies.user:id,name,image,dob,role,phone,gender,email')
            ->get()
            ->each(function($reply) {
                // Đệ quy cho các bình luận con của replies
                $reply->setRelation('replies', $reply->getRepliesWithUsers());
            });
    }

}

