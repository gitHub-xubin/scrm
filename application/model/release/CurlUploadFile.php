<?php
namespace app\model\release;
class CurlUploadFile
{
    protected static $url;
    protected static $delimiter;
    protected static $instance;

    public function __construct($url) {
        //上传地址
        //static::$url = 'https://c.api.weibo.com/2/statuses/upload_pic/biz.json';
        static::$url = $url;
        //分割符
        static::$delimiter = uniqid();
    }

    public function putFile($param) {
        $post_data = static::buildData($param);
        $curl = curl_init(static::$url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $post_data);
        curl_setopt($curl, CURLOPT_HTTPHEADER, [
            "Content-Type: multipart/form-data; boundary=" . static::$delimiter,
            "Content-Length: " . strlen($post_data)
        ]);
        $response = curl_exec($curl);
        curl_close($curl);
        $info = json_decode($response, true);
        return $response;
    }

    private static function buildData($param){
        $data = '';
        $eol = "\r\n";
        $upload = $param['file'];
        unset($param['file']);

        foreach ($param as $name => $content) {
            $data .= "--" . static::$delimiter . "\r\n"
                . 'Content-Disposition: form-data; name="' . $name . "\"\r\n\r\n"
                . $content . "\r\n";
        }
        // 拼接文件流
        $data .= "--" . static::$delimiter . $eol
            . 'Content-Disposition: form-data; name="pic"; filename="' . $param['filename'] . '"' . "\r\n"
            . 'Content-Type:application/x-www-form-urlencoded'."\r\n\r\n";

        $data .= $upload . "\r\n";
        $data .= "--" . static::$delimiter . "--\r\n";
        return $data;
    }

    public static function getInstance() {
        if(!static::$instance){
            static::$instance = new static();
        }
        return static::$instance;
    }

}


/*$data = array(
	'access_token' => '2.00b1E2wB0ZdmzKc3a3e7bb68wARDDD',
    'type' => 'image',
    'filename' => 'weibo.png',
    'offset' => 0,
    'filetype' => 'image/png',
    'originName' => 'weibo.png',
    'file'=>file_get_contents('/Applications/XAMPP/xamppfiles/htdocs/scrm/weibo.png')
);
$part = CurlUploadFile::getInstance()->putFile($data);

var_dump($part);*/