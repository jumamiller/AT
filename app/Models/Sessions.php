<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Sessions extends Model
{
    use HasFactory;

    protected $fillable = [
        'phone_number',
        'session_id',
        'level'
    ];
    public $timestamps=false;
}
