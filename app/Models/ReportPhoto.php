<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReportPhoto extends Model
{
    protected $guarded = ['id'];

    protected $hidden = ['disk', 'path'];

    public function theftReport()
    {
        return $this->belongsTo(TheftReport::class);
    }
}
