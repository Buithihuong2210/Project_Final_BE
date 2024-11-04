<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Question extends Model
{
    use HasFactory;

    protected $table = 'questions';
    protected $primaryKey = 'question_id'; // Đảm bảo tên này đúng
    public $incrementing = false; // Nếu khóa chính không tự tăng

    protected $fillable = [
        'survey_id',
        'question_text',
        'type',
        'options',
        'category',
        'code'
    ];

    // Cast the options field to and from JSON
    protected $casts = [
        'options' => 'array',
    ];

    // Many-to-One relationship with the Survey table
    public function survey()
    {
        return $this->belongsTo(Survey::class, 'survey_id', 'survey_id');
    }

    // One-to-Many relationship with the Response table
    public function responses()
    {
        return $this->hasMany(Response::class);
    }
}
