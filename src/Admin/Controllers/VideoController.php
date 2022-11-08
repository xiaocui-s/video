<?php

namespace Ggss\Video\Admin\Controllers;

use Illuminate\Http\Request;
use Ggss\Video\Admin\Repositories\Video;
//use App\Enums\FlagEnum;
//use App\Enums\TopEnum;
use Ggss\Video\AliVodService;
use Dcat\Admin\Admin;
use Dcat\Admin\Form;
use Dcat\Admin\Grid;
use Dcat\Admin\Layout\Content;
use Dcat\Admin\Show;
use Dcat\Admin\Http\Controllers\AdminController;
use Dcat\Admin\Traits\HasUploadedFile;
use Dcat\Admin\Widgets\Card;
use Ggss\Video\Enums\VideoEnum;

class VideoController extends AdminController
{
    use HasUploadedFile;

    public function __construct() {
        $this->title = '视频';

        $this->description = [
            'index'  => '列表',
            'show'   => '显示',
            'edit'   => '编辑',
            'create' => '创建',
        ];
        $this->js = [
            '/../vendor/Ggss/video/src/Admin/Js/aliyun-upload-sdk/aliyun-upload-sdk-1.5.3.min.js',
            '/../vendor/Ggss/video/src/Admin/Js/liyun-upload-sdk/lib/aliyun-oss-sdk-6.17.1.min.js',
            '/../vendor/Ggss/video/src/Admin/Js/aliyun-upload-sdk/lib/es6-promise.min.js',
        ];
    }

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        return Grid::make(new Video(), function (Grid $grid) {

            $grid->column('id')->sortable();
            $grid->column('title', '标题');
            $grid->column('desc', '简介');
            $grid->column('content', '内容')
                ->expand(function (Grid\Displayers\Expand $expand) {
                    $expand->button('详情');
                    $card = new Card(null, $this->content);

                    return "<div style='padding:10px 10px 0'>$card</div>";
                });
            $grid->column('duration', '时长')
                ->display(function ($value) {
                    return Video::secToTime($value);
                });
            $grid->column('publish_by', '发布人');
            $grid->column('publish_at', '发布时间');
            $grid->column('status', '状态')->radio(VideoEnum::asSelectArray());
            //$grid->column('flag', '推荐')->radio(FlagEnum::asSelectArray());
            //$grid->column('top', '置顶')->radio(TopEnum::asSelectArray());
            $grid->column('weight', '权重排序(倒序)')->sortable()->editable(true);
            $grid->column('created_at');
            $grid->column('updated_at')->sortable();

            //筛选
            $grid->filter(function ($filter) {
                $filter->like('title', '标题');
                $filter->equal('status', '状态')->select(VideoEnum::asSelectArray());
            });

            $grid->filter(function (Grid\Filter $filter) {
                $filter->equal('id');

            });
        });
    }

    /**
     * Make a show builder.
     *
     * @param mixed $id
     *
     * @return Show
     */
    protected function detail($id)
    {
        return Show::make($id, new Video(['images', 'category']), function (Show $show) {

            //视频播放
            $videoId = $show->model()->video_id;
            $videoPlayHtml = AliVodService::getVideoPlayHtml($videoId);

            $show->field('id');
            $show->field('title', '标题');
            $show->field('desc', '简介');
            $show->field('content', '内容')->unescape();
            $show->field('image', '图片')
                ->as(function ($path) {
                    return HasUploadedFile::disk('oss')->url($path);
                })
                ->image();
            $show->field('video_id', '视频');

            //视频播放HTML
            $html = '<div class="show-field form-group row">
                <div class="col-sm-2 control-label"></div>
                <div class="col-sm-8">' . $videoPlayHtml['aliYunVideoHtml'] . '</div>
            </div>';
            $show->html($html);
            $show->html($videoPlayHtml['aliYunVideoPlayJs']);

            $show->field('duration', '时长')
                ->as(function ($value) {
                    return Video::secToTime($value);
                });
            $show->field('publish_by', '发布人');
            $show->field('publish_at', '发布时间');
            $show->field('status', '状态')->using(VideoEnum::asSelectArray());
            //$show->field('flag', '推荐')->using(FlagEnum::asSelectArray());
            //$show->field('top', '置顶')->using(TopEnum::asSelectArray());
            $show->field('weight', '权重排序(倒序)');
            $show->field('created_at');
            $show->field('updated_at');
        });
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        return Form::make(new Video(['images', 'category']), function (Form $form) {
            $videoRep = new Video();

//            //视频播放
//            $videoPlayHtml = [];
//            if ($form->isEditing() && !$form->_inline_edit_) {
//                $videoId = $form->model()->video_id;
//                $videoPlayHtml = AliVodService::getVideoPlayHtml($videoId);
//            }

            $form->display('id');
            $form->text('title', '标题')->required();
            $form->textarea('desc', '简介')
                ->saving(function ($desc) {
                    if (!$desc) {
                        return '';
                    }
                });
            $form->textarea('content', '内容');
            $form->image('image', '图片')
                ->accept('jpg,png,gif,jpeg')
                ->maxSize(1024)
                ->customFormat(function ($paths) use ($videoRep) {
                    //编辑表单处理图片格式
                    return $videoRep->getImagesUrl($paths);
                })
                ->url('public/uploadFile/video')
                ->rules('required', [
                    'required' => '请上传图片',
                ])->required();

//            //视频播放HTML
//            if ($form->isEditing() && !$form->_inline_edit_) {
//                $form->html($videoPlayHtml['aliYunVideoHtml']);
//                $form->html($videoPlayHtml['aliYunVideoPlayJs']);
//            }

            $form->file('video_id', '视频')
                ->accept('avi,wmv,mpeg,mp4,m4v,mov,asf,flv')
                ->maxSize(1024*1024*2)
                ->url('public/uploadFile/video/Video')
                ->rules('required', [
                    'required' => '请上传视频',
                ])->on('fileQueued',
                    <<<JS
                                function () {
                                     //console.log('文件开始上传...', this,this.uploader.getFiles());
                                    const file=this.uploader.getFiles()[0].source.source;
                                    //this.uploader.cancelFile( file );
                                    this.uploader.removeFile( this.uploader.getFiles()[0] );
                                     const uploader2 = createUploader()
                                      var userData = '{"Vod":{}}'
                                      uploader2.addFile(file, null, null, null, userData);
                                     $('.file').find('.statusBar').show();
                                      //uploader2.startUpload();
                                      $('.file').find('.upload-btn').on('click', function () {
                                        // 然后调用 startUpload 方法, 开始上传
                                        if (uploader2 !== null) {
                                          uploader2.startUpload()
                                        }
                                      })
                                }
JS
                )->required();
            Grid::make([])->rate->progressBar('success', 'sm', 100);
            $form->text('publish_by', '发布人')
                ->type('number')
                ->attribute('min', 1)
                ->default(1)
                ->required();
            $form->datetime('publish_at', '发布时间');
            $form->radio('status', '状态')->options(VideoEnum::asSelectArray())->default(VideoEnum::PEND)->required();
            //$form->radio('flag', '推荐')->options(FlagEnum::asSelectArray())->default(FlagEnum::NORMAL)->required();
            //$form->radio('top', '置顶')->options(TopEnum::asSelectArray())->default(TopEnum::NORMAL)->required();
            $form->number('weight', '权重排序(倒序)');

            $form->hidden('duration', '时长');

            $form->saving(function (Form $form) use ($videoRep) {
                if ($form->status == 2 && (empty($this->status) || $this->status == 1)) {
                    $form->publish_at = date('Y-m-d H:i:s');
                }

                if ($form->_inline_edit_) {
                    return false;
                }

                //获取视频时间长度
                $videoDuration = AliVodService::getPlayInfoDuration($form->video_id);
                $form->duration = $videoDuration;

                if ($form->isEditing()) {
                    //替换img值(因编辑customFormat处理显示图片需再去除域名)
                    $form->images = $videoRep->removeImagesUrl($form->images);
                }

            });

            $form->saved(function (Form $form, $result) {
                if ($form->_inline_edit_) {
                    return false;
                }

                dd($form->getKey());
            });

            $form->display('created_at');
            $form->display('updated_at');
        });
    }

    public function create(Content $content)
    {
        Admin::js($this->js);
        Admin::script($this->script());
        return parent::create($content);
    }
    public function edit($id, Content $content)
    {
        Admin::js($this->js);
        Admin::script($this->script());
        return parent::edit($id, $content);
    }

    public function script()
    {
        return <<<JS
            //console.log($(document).on('click','input[name="video_id"]'))
            // $('.video_upload').find(".upload-btn").click(function(){
            //     createUploader();return false;
            // })
            function createUploader () {
        var uploader = new AliyunUpload.Vod({
              timeout: 60000 ,
              partSize: 1048576 ,
              parallel: 5,
              retryCount: 3 ,
              retryDuration: 2,
              region: 'cn-shanghai',
              userId: '1665132279819638',
              // 添加文件成功
              addFileSuccess: function (uploadInfo) {
                $('#authUpload').attr('disabled', false)
                $('#resumeUpload').attr('disabled', false)
                $('#status').text('添加文件成功, 等待上传...')
                console.log("addFileSuccess: " + uploadInfo.file.name)
              },
              // 开始上传
              onUploadstarted: function (uploadInfo) {
                // 如果是 UploadAuth 上传方式, 需要调用 uploader.setUploadAuthAndAddress 方法
                // 如果是 UploadAuth 上传方式, 需要根据 uploadInfo.videoId是否有值，调用点播的不同接口获取uploadauth和uploadAddress
                // 如果 uploadInfo.videoId 有值，调用刷新视频上传凭证接口，否则调用创建视频上传凭证接口
                // 注意: 这里是测试 demo 所以直接调用了获取 UploadAuth 的测试接口, 用户在使用时需要判断 uploadInfo.videoId 存在与否从而调用 openApi
                // 如果 uploadInfo.videoId 存在, 调用 刷新视频上传凭证接口(https://help.aliyun.com/document_detail/55408.html)
                // 如果 uploadInfo.videoId 不存在,调用 获取视频上传地址和凭证接口(https://help.aliyun.com/document_detail/55407.html)
                if (!uploadInfo.videoId) {
                  //var createUrl = 'https://demo-vod.cn-shanghai.aliyuncs.com/voddemo/CreateUploadVideo?Title=testvod1&FileName=aa.mp4&BusinessType=vodai&TerminalType=pc&DeviceModel=iPhone9,2&UUID=59ECA-4193-4695-94DD-7E1247288&AppVersion=1.0.0&VideoId=5bfcc7864fc14b96972842172207c9e6'
                  var createUrl = "/admin/video/getUploadInit"
                  console.log(uploadInfo.file)
                  $.ajax({
                        url:createUrl,
                        type:'get',
                        'data':{
                            'filename': uploadInfo.file.name,
                            'type' : 2
                        },
                        dataType:'json',
                        success(data){
                            var uploadAuth = data.UploadAuth
                            var uploadAddress = data.UploadAddress
                            var videoId = data.VideoId
                            uploader.setUploadAuthAndAddress(uploadInfo, uploadAuth, uploadAddress,videoId)
                        }
                  })
                  $('#status').text('文件开始上传...')
                  console.log(uploadInfo.file)
                  console.log("onUploadStarted:" + uploadInfo.file.name + ", endpoint:" + uploadInfo.endpoint + ", bucket:" + uploadInfo.bucket + ", object:" + uploadInfo.object)
                } else {
                  // 如果videoId有值，根据videoId刷新上传凭证
                  // https://help.aliyun.com/document_detail/55408.html?spm=a2c4g.11186623.6.630.BoYYcY
                  //var refreshUrl = 'https://demo-vod.cn-shanghai.aliyuncs.com/voddemo/RefreshUploadVideo?BusinessType=vodai&TerminalType=pc&DeviceModel=iPhone9,2&UUID=59ECA-4193-4695-94DD-7E1247288&AppVersion=1.0.0&Title=haha1&FileName=xxx.mp4&VideoId=' + uploadInfo.videoId
                  var refreshUrl = '/admin/video/refreshUrl'
                  $.ajax({
                        url:refreshUrl,
                        type:'get',
                        'data':{
                            'videoId': uploadInfo.videoId
                        },
                        dataType:'json',
                        success(data){
                            var uploadAuth = data.UploadAuth
                            var uploadAddress = data.UploadAddress
                            var videoId = data.VideoId
                            uploader.setUploadAuthAndAddress(uploadInfo, uploadAuth, uploadAddress,videoId)
                        }
                  })

                }
              },
              // 文件上传成功
              onUploadSucceed: function (uploadInfo) {
                  console.log(uploadInfo)
                console.log("onUploadSucceed: " + uploadInfo.file.name + ", endpoint:" + uploadInfo.endpoint + ", bucket:" + uploadInfo.bucket + ", object:" + uploadInfo.object + ", videoId:" + uploadInfo.videoId)
                $( "input[name='video_id']").val(uploadInfo.videoId);
                //$('.file').find('.upload-btn').text('上传完成')
              },
              // 文件上传失败
              onUploadFailed: function (uploadInfo, code, message) {
                console.log("onUploadFailed: file:" + uploadInfo.file.name + ",code:" + code + ", message:" + message)
                $('#status').text('文件上传失败!')
              },
              // 取消文件上传
              onUploadCanceled: function (uploadInfo, code, message) {
                console.log("Canceled file: " + uploadInfo.file.name + ", code: " + code + ", message:" + message)
                $('#status').text('文件上传已暂停!')
              },
              // 文件上传进度，单位：字节, 可以在这个函数中拿到上传进度并显示在页面上
              onUploadProgress: function (uploadInfo, totalSize, progress) {
                console.log("onUploadProgress:file:" + uploadInfo.file.name + ", fileSize:" + totalSize + ", percent:" + Math.ceil(progress * 100) + "%")
                var progressPercent = Math.ceil(progress * 100)
                $('.file').find('.upload-progress').show();
                $('.file').find('.progresss').css('width','50%')
                $('.file').find('.upload-progress').attr('aria-valuenow',progressPercent)
                $('.file').find('.upload-progress').attr('aria-valuemax',100)
                $('.file').find('.upload-progress').css('width',progressPercent+200+'px')
                $('.file').find('.upload-progress').text(progressPercent+'%')
                $('.file').find('.info').text(progressPercent+'%')
                $('.file').find('.upload-btn').attr('disabled', false)
                //$('#status').text('文件上传中...')
              },
              // 上传凭证超时
              onUploadTokenExpired: function (uploadInfo) {
                // 上传大文件超时, 如果是上传方式一即根据 UploadAuth 上传时
                // 需要根据 uploadInfo.videoId 调用刷新视频上传凭证接口(https://help.aliyun.com/document_detail/55408.html)重新获取 UploadAuth
                // 然后调用 resumeUploadWithAuth 方法, 这里是测试接口, 所以我直接获取了 UploadAuth
                $('#status').text('文件上传超时!')

                var refreshUrl = '/admin/video/refreshUrl'
                  $.ajax({
                        url:refreshUrl,
                        type:'get',
                        'data':{
                            'videoId': uploadInfo.videoId
                        },
                        dataType:'json',
                        success(data){
                            var uploadAuth = data.UploadAuth
                            uploader.resumeUploadWithAuth(uploadAuth)
                            console.log('upload expired and resume upload with uploadauth ' + uploadAuth)
                        }
                  })
              },
              // 全部文件上传结束
              onUploadEnd: function (uploadInfo) {
                //$('#status').text('文件上传完毕!')
                console.log("onUploadEnd: uploaded all the files")
              }
            })
           return uploader
      }
JS;

    }

    /**
     *  视频上传凭证
     */
    public function getUploadInit(Request $request)
    {
        try{
            // 获取上传的文件
            $fileName = $request->input('filename');
            $type = $request->input('type');
            $result = AliVodService::createUploadVideo($fileName,$type);
            return response($result);
        }catch (\Throwable $e){
            return $this->responseErrorMessage('获取上传凭证失败');
        }
    }
    /**
     * 视频刷新凭证
     */
    public function refreshUrl(Request $request)
    {
        try{
            // 获取上传的文件
            $videoId = $request->input('videoId');
            $result = AliVodService::refreshUploadVideo($videoId);
            return response($result);
        }catch (\Throwable $e){
            return $this->responseErrorMessage('获取刷新凭证失败');
        }
    }

    /**
     * 文件上传
     *
     * @param $action
     * @param string $modelName
     * @return mixed
     * @throws \AlibabaCloud\Client\Exception\ClientException
     * @throws \AlibabaCloud\Client\Exception\ServerException
     * @throws \OSS\Core\OssException
     */
    public function uploadFile($action, $modelName = '')
    {
        // 判断是否是删除文件请求
        if ($this->isDeleteRequest()) {
            $column = \Request::get('_column');
            $key = \Request::get('key');

            if ($column == 'video_id') {
                //删除VOD资源
                AliVodService::deleteVideos($key);

                //更新表数据
                $modelName = "\App\Models\\" . $modelName;
                if (!class_exists($modelName)) {
                    return $this->responseErrorMessage('类名错误, 请联系管理员');
                }
                $model = new $modelName();
                $model->where(['video_id' => $key])->update(['video_id' => '']);
            }else {
                //删除OSS资源
                $url = HasUploadedFile::disk('oss')->url('');
                $path = str_replace($url, '', $key);
                $disk = HasUploadedFile::disk('oss');
                $this->deleteFile($disk, $path);
            }

            return $this->responseDeleted();
        }

        // 获取上传的文件
        $file = $this->file();

        // 获取上传的字段名称
        $column = $this->uploader()->upload_column;
        if ($column == 'video_id') {
            $title = $file->getClientOriginalName();
            $path = $file->getPathname();
            $result = AliVodService::UploadVideo($title, $title, $path);
            $url = '';
        }else {
            $path = 'images/' . $action;
            $result = HasUploadedFile::disk('oss')->putFile($path, $file);
            $url = HasUploadedFile::disk('oss')->url($result);
        }

        if ($result) {
            return $this->responseUploaded($result, $url);
        }

        return $this->responseErrorMessage('文件上传失败');
    }
}
