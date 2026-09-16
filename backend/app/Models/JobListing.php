<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class JobListing extends Model
{
    protected $fillable = [
        'title',
        'description',
        'location',
        'type',
        'salary_min',
        'salary_max',
        'currency',
        'status',
    ];

    public function user() {

        return $this->belongsTo(User::class);
    }
}
