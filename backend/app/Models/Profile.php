<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Profile extends Model
{
    

    protected $fillable = [
        'headline',
        'bio',
        'location',
        'skills',
        'experience',
        'company_name',
        'website',
    ];

    protected $casts = [
        'skills' => 'array',
    ];

    protected $hidden = [
        'resume_path',
    ];

    public function user(){
        return $this->belongsTo(User::class);
    }
}
