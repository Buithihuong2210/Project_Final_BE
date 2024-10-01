<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Survey extends Model
{
    use HasFactory;

    protected $table = 'surveys';
    protected $primaryKey = 'survey_id';

    // Mass assignable attributes
    protected $fillable = [
        'title',
        'description',
        'start_date',
        'end_date'
    ];

    // One-to-Many relationship with the Question table
    public function questions()
    {
        return $this->hasMany(Question::class, 'survey_id', 'survey_id');
    }
    public function responses()
    {
        return $this->hasMany(Response::class);
    }
}
