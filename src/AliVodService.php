<?php

namespace Ggss\Video;

use AlibabaCloud\Client\AlibabaCloud;
use AlibabaCloud\Vod\Vod;
use App\Enums\VideoEnum;
use OSS\OssClient;

class AliVodService
{
    private static $client;
    private static $callback;

    /**
     * 初始化
     *
     * @throws \AlibabaCloud\Client\Exception\ClientException
     */
    private static function initVodClient()
    {
        $accessKeyId = env('ALIYUN_ACCESS_KEY');
        $accessKeySecret = env('ALIYUN_ACCESS_KEY_SECRET');
        $regionId = 'cn-shanghai';

        self::$client = AlibabaCloud::accessKeyClient($accessKeyId, $accessKeySecret)
            ->regionId($regionId)
            ->connectTimeout(1)
            ->timeout(3)
            ->asDefaultClient();
    }

    /**
     * 初始化点播
     *
     * @param $uploadAuth
     * @param $uploadAddress
     * @return OssClient
     * @throws \OSS\Core\OssException
     */
    public static function initOssClient($uploadAuth, $uploadAddress)
    {
        $ossClient = new OssClient($uploadAuth['AccessKeyId'], $uploadAuth['AccessKeySecret'], $uploadAddress['Endpoint'],
            false, $uploadAuth['SecurityToken']);
        $ossClient->setTimeout(86400 * 7);    // 设置请求超时时间，单位秒，默认是5184000秒, 建议不要设置太小，如果上传文件很大，消耗的时间会比较长
        $ossClient->setConnectTimeout(10);  // 设置连接超时时间，单位秒，默认是10秒

        return $ossClient;
    }

    /**
     * 获取视频信息
     *
     * @param $videoId
     * @param int $timeout
     * @return array
     * @throws \AlibabaCloud\Client\Exception\ClientException
     * @throws \AlibabaCloud\Client\Exception\ServerException
     */
    public static function getPlayInfo($videoId, $timeout = 3000)
    {
        self::initVodClient();
        $info = Vod::v20170321()->getPlayInfo()
            ->withVideoId($videoId)
            ->withAuthTimeout($timeout)
            ->format('JSON')
            ->debug(false)
            ->request();

        return $info->toArray();
    }

    /**
     * 获取视频播放地址
     *
     * @param $videoId
     * @return mixed
     * @throws \AlibabaCloud\Client\Exception\ClientException
     * @throws \AlibabaCloud\Client\Exception\ServerException
     */
    public static function getPlayInfoUrl($videoId)
    {
        $video = self::getPlayInfo($videoId);

        return $video['PlayInfoList']['PlayInfo'][0]['PlayURL'];
    }

    /**
     * 获取视频时长
     *
     * @param $videoId
     * @return int
     * @throws \AlibabaCloud\Client\Exception\ClientException
     * @throws \AlibabaCloud\Client\Exception\ServerException
     */
    public static function getPlayInfoDuration($videoId)
    {
        try{
            $video = self::getPlayInfo($videoId);

            return (int)$video['VideoBase']['Duration'];
        }catch (\Throwable $e){

            return false;
        }

    }

    /**
     * 获取上传凭证
     */
    public static function createUploadVideo($videoTitle , $type = 2)
    {
        //初始化
        self::initVodClient();
        $request = Vod::v20170321()->createUploadVideo();
        $request->withTitle($videoTitle);//标题，UTF8,128大小
        $request->withFileName($videoTitle);//视频源文件名
        $request->withUserData(json_encode([
            "MessageCallback" => [
                "CallbackURL" => VideoEnum::getNotifyUrl(),
                "CallbackType" => "http"
            ],
            "Extend" =>['type' => $type],
            "AccelerateConfig" =>[]
        ]));
        $request->debug(false);

        return $request->request();
    }

    /**
     * 获取刷新凭证
     */
    public static function refreshUploadVideo($videoId)
    {
        self::initVodClient();
        $request = Vod::v20170321()->refreshUploadVideo();
        $request->withVideoId($videoId);
        return $request->request();
    }
    /**
     * 上传视频
     *
     * @param $videoTitle
     * @param $fileName
     * @param $videoPath
     * @return mixed|null
     * @throws \AlibabaCloud\Client\Exception\ClientException
     * @throws \AlibabaCloud\Client\Exception\ServerException
     * @throws \OSS\Core\OssException
     */
    public static function UploadVideo($videoTitle, $fileName, $videoPath)
    {
        //$request->withDescription(isset($data['description']) ? $data['description'] : '');//描述,utf-8
        //$request->withCoverURL(isset($data['coverURL']) ? $data['coverURL'] : '');//封面url
        //$request->withCateId(isset($data['cateId']) ? $data['cateId'] : ''); //分类id
        //$request->withTags(isset($data['tags']) ? $data['tags'] : '');      //标签，隔开
        //$request->withTemplateGroupId(isset($data['templateGroupId']) ? $data['templateGroupId'] : ''); //转码模板组ID
        //$request->withStorageLocation(isset($data['storageLocation']) ? $data['storageLocation'] : ''); //存储地址
        //if($isCallback == 1)
        //{
        //    $userData = array(
        //        "MessageCallback" => array("CallbackURL" => $this->callback),
        //        "Extend" => array("title" => $data['title']),
        //        'EventType' => 'TranscodeComplete',
        //    );
        //    $request->withUserData(json_encode($userData));
        //}
        //echo '<pre>';print_r($result);exit;
        $result = static ::createUploadVideo($videoTitle, $fileName);
        //上传视频
        $uploadAddress = json_decode(base64_decode($result->UploadAddress), true);
        $uploadAddress['Endpoint'] = str_replace("https:", "http:", $uploadAddress['Endpoint']);
        $uploadAuth = json_decode(base64_decode($result->UploadAuth), true);

        //TODO::partSize --- vendor/aliyuncs/oss-sdk-php/src/OSS/OssClient.php-2029-1841-encodePath方法BUG
        $ossClient = self::initOssClient($uploadAuth, $uploadAddress);
        $ossClient->multiuploadFile($uploadAddress['Bucket'], $uploadAddress['FileName'], $videoPath, ['partSize' => 1024815]);

        return $result->VideoId;
    }

    /**
     * 删除视频
     *
     * @param $videoIds
     * @return array
     * @throws \AlibabaCloud\Client\Exception\ClientException
     * @throws \AlibabaCloud\Client\Exception\ServerException
     */
    public static function deleteVideos($videoIds)
    {
        self::initVodClient();
        $info = Vod::v20170321()->deleteVideo()
            ->withVideoIds($videoIds)
            ->format('JSON')
            ->debug(false)
            ->request();

        return $info->toArray();
    }

    /**
     * 获取视频播放html
     *
     * @param $videoId
     * @return string[]
     * @throws \AlibabaCloud\Client\Exception\ClientException
     * @throws \AlibabaCloud\Client\Exception\ServerException
     */
    public function getVideoPlayHtml($videoId)
    {
        $videoPlayURL = AliVodService::getPlayInfoUrl($videoId);

        $aliYunVideoHtml = '<div id="J_prismPlayer"></div>';

        $aliYunVideoJs = '<head>
            <link rel="stylesheet" href="/vendor/dcat-admin/dcat/css/aliplayer-min.css" />
            <script charset="utf-8" type="text/javascript" src="/vendor/dcat-admin/dcat/js/aliplayer-h5-min.js"></script>
        </head>';

        $aliYunVideoPlayJs = "<script>
            var player = new Aliplayer({
                id: 'J_prismPlayer',
                source: '" . $videoPlayURL . "',//播放地址，可以是第三方点播地址，或阿里云点播服务中的播放地址。
                autoplay: false,
                width: '65%',
                height: '350px',
            },function(player){
                console.log('The player is created.')
            });
        </script>";

        $html = [
            'aliYunVideoPlayJs' => $aliYunVideoJs . $aliYunVideoPlayJs,
            'aliYunVideoHtml' => $aliYunVideoHtml,
        ];

        return $html;
    }
}
