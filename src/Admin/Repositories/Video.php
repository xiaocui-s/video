<?php

namespace Ggss\Video\Admin\Repositories;

use Ggss\Video\Model\Video as Model;
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

    /**
     * 获取图片完整url
     *
     * @param $paths
     * @return array|mixed|string
     */
    public function getImagesUrl($paths)
    {
        if (is_array($paths)) {
            foreach ($paths as $key => $path) {
                $paths[$key] = \Storage::disk('oss')->url($path);
            }
        }else {
            $paths = \Storage::disk('oss')->url($paths);
        }

        return $paths;
    }

    /**
     * 去除图片url
     *
     * @param $paths
     * @return array|mixed|string
     */
    public function removeImagesUrl($paths)
    {
        $url = \Storage::disk('oss')->url('');
        if (is_array($paths)) {
            foreach ($paths as $key => $path) {
                $paths[$key] = str_replace($url, '', $path);
            }
        }else {
            $paths = str_replace($url, '', $paths);
        }

        return $paths;
    }
}
