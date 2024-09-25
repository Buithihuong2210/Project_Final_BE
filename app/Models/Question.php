<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Question extends Model
{
    use HasFactory;

    protected $fillable = [
        'survey_id',
        'question_text',
        'question_type',
        'options', // Include options in the fillable array
    ];

    protected $casts = [
        'options' => 'array', // Cast options to an array
    ];

    public function answers()
    {
        return $this->hasMany(Answer::class);
    }
}
