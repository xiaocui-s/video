<?php

namespace App\Enums;

use BenSampo\Enum\Enum;
use URL;

/**
 * @method static static OptionOne()
 * @method static static OptionTwo()
 * @method static static OptionThree()
 */
final class VideoEnum extends Enum
{
    public static function getNotifyUrl(){
        return 'https://jujiankang.yhdccc.net/api/notify/vod';
        //return URL::secureAsset('/api/notify/tts');
        //url()->previous().'/api/notify/tts';
    }
}
