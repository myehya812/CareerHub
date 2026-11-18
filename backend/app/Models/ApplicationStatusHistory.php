<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ApplicationStatusHistory extends Model
{
    protected $table = 'application_status_history';

    public $timestamps = false;

    // Entries are written by the database, not mass-assigned by API requests.
    protected $guarded = ['*'];

    protected function casts(): array
    {
        return ['changed_at' => 'datetime'];
    }

    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class);
    }
}
