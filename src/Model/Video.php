<?php

namespace Ggss\Video\Model;

use App\Http\Traits\ModelWithCategoryTrait;
use App\Http\Traits\ModelWithSearchTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\InteractsWithMedia;

class Video extends Model
{
    use InteractsWithMedia;
    use HasFactory,SoftDeletes;
    protected $dates = ['deleted_at'];

    protected $table = 'video';
}
