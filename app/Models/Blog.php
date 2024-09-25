<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Blog extends Model
{
    use HasFactory;

    protected $primaryKey = 'blog_id'; // Define primary key

    protected $fillable = [
        'title',
        'user_id',
        'thumbnail',
        'content',
        'status',
    ];


    // Define the relationship to the User model
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }


    public function hashtags()
    {
        return $this->belongsToMany(Hashtag::class, 'hashtag_blog','blog_id','hashtag_id');
    }
}
