<?php

namespace Yhdccc\Video\Model;

use App\Http\Traits\ModelWithCategoryTrait;
use App\Http\Traits\ModelWithSearchTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Video extends Model
{
    use HasFactory,SoftDeletes;
    protected $dates = ['deleted_at'];

    protected $table = 'video';
}
