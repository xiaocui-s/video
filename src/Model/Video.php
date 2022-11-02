<?php

namespace App\Models;

use App\Http\Traits\ModelWithCategoryTrait;
use App\Http\Traits\ModelWithSearchTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Video extends Model
{
    use HasFactory,SoftDeletes;
    use ModelWithCategoryTrait,ModelWithSearchTrait;
    protected $dates = ['deleted_at'];

    protected $table = 'video';

    public function images(){
        return $this->morphOne(Image::class,'imagetable' ,'target_name','target_id');
    }
    public function category(){
        return $this->morphMany(CategoryRelation::class,'categorytable' ,'target_name','target_id');
    }

    public function statistic(){
        return $this->morphOne(Statistic::class,'statistic' ,'target_name','target_id');
    }

    public function comments(){
        return $this->morphMany(Comment::class,'comments' ,'target_name','target_id');
    }
    public function getSearchTitle()
    {
        return strip_tags($this->title);
    }

    public function getSearchContent()
    {
        return strip_tags($this->content);
    }
}
