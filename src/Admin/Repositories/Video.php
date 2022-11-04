<?php

namespace Yhdccc\Video\Admin\Repositories;

use Yhdccc\Video\Model\Video as Model;
use Dcat\Admin\Repositories\EloquentRepository;

class Video extends EloquentRepository
{
    /**
     * Model.
     *
     * @var string
     */
    public $eloquentClass = Model::class;

    //秒转 时分秒
    public static function secToTime($times){
        $result = '00:00:00';
        if ($times>0) {
            $hour = floor($times/3600);
            $minute = floor(($times-3600 * $hour)/60);
            $second = floor((($times-3600 * $hour)-60 * $minute)%60);

            $hour = str_pad($hour, 2, 0, STR_PAD_LEFT);
            $minute = str_pad($minute, 2, 0, STR_PAD_LEFT);
            $second = str_pad($second, 2, 0, STR_PAD_LEFT);

            $result = $hour.':'.$minute.':'.$second;
        }
        return $result;
    }
}
