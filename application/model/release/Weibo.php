<?php
/**
 * Created by PhpStorm.
 * User: xubin
 * Date: 2019/6/26
 * Time: 11:45 AM
 */
namespace app\model\release;
class Weibo extends \app\core\ApiModel{

    /**
     * 添加微博发布
     * @param $data
     * @return string
     */
    public function add($data){
        $created = date('Y-m-d H:i:s');
        $sql = "insert into `weibo_release` (account_id,content,created,timing,release_time,user_id,status,mid,type)values (?,?,?,?,?,?,?,?,?)";
        \think\Db::execute($sql,[$data['account_id'],$data['content'],$created,$data['timing'],$data['release_time'],$data['user_id'],$data['status'],$data['mid'],$data['type']]);
        return \think\Db::getLastInsID();
    }

    /**
     * 下载图片到服务器
     * @param $url
     * @param string $path
     */
    public function download($url, $path = '')
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        //curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
        $filename = pathinfo($url, PATHINFO_BASENAME);
        $ext = pathinfo($url, PATHINFO_EXTENSION);
        $filename = md5($filename.rand(111,999)).'.'.$ext;
        $fp = fopen ($path. $filename, 'w+');
        curl_setopt($ch, CURLOPT_FILE, $fp);
        //这个选项是意思是跳转，如果你访问的页面跳转到另一个页面，也会模拟访问。
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch,CURLOPT_TIMEOUT,50);
        curl_exec($ch);
        curl_close($ch);
      //  $resource = fopen($path . $filename, 'a');
        //fwrite($resource, $file);
        fclose($fp);
        return $filename;
    }

    /**
     * 给微博上传文件
     * @param $access_token
     * @param $path
     * @return string
     */
    public function uploadFile($access_token,$url,$file_path){
        $filename = pathinfo($file_path,PATHINFO_BASENAME);
        $data = array(
            'access_token' => "$access_token",
            'type' => 'image',
            'filename' => "$filename",
            'offset' => 0,
            'filetype' => 'image/png',
            'originName' => "$filename",
            'file'=>file_get_contents($file_path)
        );
        $curlUploadModel = new \app\model\release\CurlUploadFile($url);
        $resultJson = $curlUploadModel->putFile($data);
        $res = json_decode($resultJson,TRUE);
        if(!empty($res['pic_id'])){
            $data = $res['pic_id'];
        }else{
            $data = '';
        }
        return  $data;
    }

    //处理延时发布的微博
    public function delayRelease(){
        $sql = "select a.auth, wr.id, wr.content, wr.type from `weibo_release` as wr left join `account` as a ON wr.account_id = a.account_id
                where unix_timestamp(wr.release_time) <= unix_timestamp(now()) and wr.status = 0";
        $res = $this -> findAll($sql);
        if(empty($res)){
            return true;
        }
        foreach ($res as $k => $v){
            $auth = json_decode($v['auth'],TRUE);
            $access_token = $auth['access_token'];
            $content = json_decode($v['content'],TRUE);
            $id = $v['id'];
            if($v['type'] == 1){
                //处理纯文本
                $param = http_build_query([
                    'access_token' => "$access_token",
                    'status' => $content['content']
                ]);
                $url = "https://c.api.weibo.com/2/statuses/update/biz.json";
                $res = curl_request($url,"POST",$param);
                if(!empty($res['error_code'])){
                    $status = -1;
                    $mid = '';
                }else{
                    $mid = $res['mid'];
                    $status = 1;
                }
                $data['status'] = $status;
                $data['mid'] = $mid;
                $this -> edit($id,$data);
            }elseif ($v['type']==2){
                $picArr = [];
                $imgArr = json_decode($content['img'],TRUE);
                $url = 'https://c.api.weibo.com/2/statuses/upload_pic/biz.json';
                foreach ($imgArr as $val){
                    $fileName = $this -> download($val,'/home/scrm_api/public/cache_file/');
                    if($fileName){
                        $tmpFile[] = '/home/scrm_api/public/cache_file/'.$fileName;
                        $picArr[] = $this -> uploadFile($access_token,$url,"/home/scrm_api/public/cache_file/$fileName");
                    }
                }
                if(empty($picArr)){
                    $data['status'] = -1;
                    $this -> edit($id,$data);
                }
                $picId = implode(',',$picArr);
                $param = http_build_query([
                    'access_token' => "$access_token",
                    'status' => $content['content'],
                    'pic_id' => "$picId",
                ]);
                $url = "https://c.api.weibo.com/2/statuses/upload_url_text/biz.json";
                $res = curl_request($url,"POST",$param);
                if(!empty($res['error_code'])){
                    $status = -1;
                    $mid = '';
                }else{
                    $mid = $res['mid'];
                    $status = 1;
                }
                $data['status'] = $status;
                $data['mid'] = $mid;
                $this -> edit($id,$data);
                if(!empty($tmpFile)){
                    foreach ($tmpFile as $value){
                        unlink($value);
                    }
                }
            }
        }
    }

    /**
     *  编辑微博发布表
     * @param $id
     * @param $data
     * @return bool
     */
    public function edit($id, $data) {
        $set = [];
        foreach ($data as $key => $value) {
            $set[] = "`{$key}`='{$value}'";
        }
        $set = implode(',', $set);
        $sql = "UPDATE `weibo_release` SET {$set} WHERE `id`=?";
        \think\Db::execute($sql, [$id]);
        return true;
    }

    //获取公司已授权微博账户的accessToken
    public function getStoreAccessToken($storeId){
        $sql = "select account_id,auth from `account` where store_id = ? and auth_status = 1";
        $res = $this -> find($sql,[$storeId]);
        return $res;
    }

    //已发布的微博
    public function getList($storeId,$page = 0,$pageSize = 10, $keywords = ''){
        $where[] = "a.store_id = $storeId";
        if(!empty($keywords)){
            $where[] = "wr.content like '%$keywords%'";
        }
        $order[] = "wr.created desc";
        $sql = "select wr.id, a.a_name, u.username, wr.content, wr.timing, wr.release_time, wr.status, wr.type from `weibo_release`
                as wr left join `account` as a ON wr.account_id = a.account_id left join `user` as u ON wr.user_id = u.user_id";
        $sql_count = "select count(*) as count from `weibo_release`
                as wr left join `account` as a ON wr.account_id = a.account_id left join `user` as u ON wr.user_id = u.user_id";
        $res = $this -> _getList($sql,$sql_count,$where,$order,true,$page,$pageSize);
        return $res;
    }

    //获取最新添加的微博
    public function getNewWb($userId){
        $sql = 'select content,type from `weibo_release` where user_id = ? order by created desc limit 1';
        return $this -> find($sql,[$userId]);
    }
}