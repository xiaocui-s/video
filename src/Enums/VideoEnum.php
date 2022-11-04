<?php

namespace Yhdccc\Enums;

use BenSampo\Enum\Enum;
use URL;

/**
 * @method static static OptionOne()
 * @method static static OptionTwo()
 * @method static static OptionThree()
 */
final class VideoEnum extends Enum
{
    const NORMAL =   1; //未发布
    const PEND =   2; // 已发布

    public static function getNotifyUrl(){
        return 'https://jujiankang.yhdccc.net/api/notify/vod';
        //return URL::secureAsset('/api/notify/tts');
        //url()->previous().'/api/notify/tts';
    }
}
