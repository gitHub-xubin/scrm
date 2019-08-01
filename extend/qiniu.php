<?php
/**
* Class qiniu
*/

class qiniu
{

    var $image_size = 5;        //图片大小
    var $image_size_format = 'mb';        //图片大小单位
    var $image_type = ['jpg','jpeg','gif','png'];        //图片类型
    //上传图片
    public function upload_image($file,$bucket)
    {

        // 要上传图片的本地路径
        $filePath = $file->getRealPath();


        if(!$this->checke_image_size($filePath)){
            return ["code"=>10010,"message"=>'图片大小过大！',"data"=>""];
        }
        if(!$this->checke_image_type($file)){
            return ["code"=>10011,"message"=>'图片类型有误！',"data"=>""];
        }

        $ext = strtolower(pathinfo($file->getInfo('name'), PATHINFO_EXTENSION));  //后缀
        // 上传到七牛后保存的文件名
        $key =substr(md5($file->getRealPath()) , 0, 5). date('YmdHis') . rand(0, 9999) . '.' . $ext;

        require_once  EXTEND_PATH. '/Qiniu/functions.php';

        // 需要填写你的 Access Key 和 Secret Key
        $accessKey = Config('qiniu_conf')['accessKey'];
        $secretKey = Config('qiniu_conf')['secretKey'];
        // 构建鉴权对象
        $auth = new \Qiniu\Auth($accessKey, $secretKey);

        // 要上传的空间
        $token = $auth->uploadToken($bucket);
        // 初始化 UploadManager 对象并进行文件的上传
        $uploadMgr = new \Qiniu\Storage\UploadManager();

        // 调用 UploadManager 的 putFile 方法进行文件的上传
        list($ret, $err) = $uploadMgr->putFile($token, $key, $filePath);
        if ($err !== null) {
            return ["code"=>1,"message"=>$err,"data"=>""];
        } else {
            return ["code"=>0,"message"=>"上传完成","data"=> $ret['key']];
        }
    }
    //删除
    public function delete_image($bucket,$key){
        // 要上传图片的本地路径
        require_once  EXTEND_PATH. '/Qiniu/functions.php';

        // 需要填写你的 Access Key 和 Secret Key
        $accessKey = Config('qiniu_conf')['accessKey'];
        $secretKey = Config('qiniu_conf')['secretKey'];
        // 构建鉴权对象
        $auth = new \Qiniu\Auth($accessKey, $secretKey);

        $config = new \Qiniu\Config();
        $bucketManager = new \Qiniu\Storage\BucketManager($auth,$config);

        $err = $bucketManager->delete($bucket, $key);

        if ($err) {
            return false;
        }
        return true;
    }
    //大小
    public function checke_image_size($file){
        $size = $this->getsize(filesize($file),$this->image_size_format);
        if($size > $this->image_size){
            return false;
        }
        return true;
    }
    //类型
    public function checke_image_type($file){

        $file_type = \think\Validate::is($file, 'image');
        if(!$file_type){
            return false;
        }
        $img_data = getimagesize($file->getRealPath());
        $img_type = trim(strrchr(end($img_data), '/'),'/');
        if(!in_array($img_type,$this->image_type)){
            return false;
        }
        return true;
    }
    function getsize($size, $format) {
        $p = 0;
        if ($format == 'kb') {
            $p = 1;
        } elseif ($format == 'mb') {
            $p = 2;
        } elseif ($format == 'gb') {
            $p = 3;
        }
        $size /= pow(1024, $p);
        return number_format($size, 3);
    }
}
?>