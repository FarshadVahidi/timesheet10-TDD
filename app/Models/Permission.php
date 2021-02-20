<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Laratrust\Models\LaratrustPermission;

class Permission extends LaratrustPermission
{
    use HasFactory;

    public $guarded = [];
}
