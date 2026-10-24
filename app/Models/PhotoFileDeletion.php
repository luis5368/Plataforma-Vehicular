<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PhotoFileDeletion extends Model
{
    protected $guarded = ['id'];

    protected $hidden = ['disk', 'path'];
}
