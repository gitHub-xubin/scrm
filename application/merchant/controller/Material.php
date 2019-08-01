<?php
/**
 * Created by PhpStorm.
 * User: xubin
 * Date: 2019/6/25
 * Time: 11:09 AM
 */
namespace app\merchant\controller;

class Material extends \app\core\MerchantController{

    //上传素材
    public function upload(){
        $type = input('type');
        $name = input('name');
        $teamId = input('teamId');
        $fileName = input('fileName');
        $materialBasicModel = new \app\model\material\Basic();
        $value = ['team_id' => $teamId,'store_id' => $this ->store_id];
        $column = 'team_id';
        $info = $materialBasicModel -> getTeam($value,$column);
        if(empty($info)){
            $this -> _response['code'] = 10001;
            $this -> _response['message'] = '分组不存在';
            return $this -> _response;
        }
        //$image = request()->file('image');
        //文件上传
        $url = '';
        /*if(!empty($image)){
            if($type == 1){
                $qiniu = new \qiniu();
                $reslut = $qiniu->upload_image($image,'scrm-image');
                if($reslut['code']!='0'){
                    $this -> _response['code'] = $reslut['code'];
                    $this -> _response['message'] = $reslut['message'];
                    return $this -> _response;
                }
                $url = $reslut['data'];
            }
            if($type == 2){
                $qiniu = new \qiniuvideo();
                $reslut = $qiniu->upload_image($image,'scrm-video');
                if($reslut['code']!='0'){
                    $this -> _response['code'] = $reslut['code'];
                    $this -> _response['message'] = $reslut['message'];
                    return $this -> _response;
                }
                $url = $reslut['data'];
            }
        }else{
            $this -> _response['code'] = 10001;
            $this -> _response['message'] = '请选择文件！';
            return $this -> _response;
        }*/
        $param['store_id'] = $this -> store_id;
        $param['name'] = $name;
        $param['type'] = $type;
        $param['url'] = $fileName;
        $param['team_id'] = $teamId ? $teamId : 0;
        $materialBasicModel = new \app\model\material\Basic();
        $id = $materialBasicModel -> addMaterial($param);
        if($id){
            $this -> _response['code'] = 0;
            $this -> _response['message'] = 'ok';
        }else{
            $this -> _response['code'] = -1;
            $this -> _response['message'] = 'fail';
        }
        return $this -> _response;
    }

    //添加素材分组
    public function addteam(){
        $team = input('teamName');
        $type = input('type');
        if(empty($team) or empty($type)){
            $this -> _response['code'] = 10001;
            $this -> _response['message'] = '缺少参数';
            return $this -> _response;
        }
        $materialBasicModel = new \app\model\material\Basic();
        $column = 'team_id,store_id,name';
        $value = ['name' => $team, 'store_id' => $this ->store_id,'type' => $type];
        $teamInfo = $materialBasicModel -> getTeam($value,$column);
        if($teamInfo){
            $this -> _response['code'] = 10001;
            $this -> _response['message'] = '小组已存在';
            return $this -> _response;
        } 
        $id = $materialBasicModel -> addTeam($team,$this -> store_id,$type);
        if($id){
            $this -> _response['code'] = 0;
            $this -> _response['message'] ='ok';
        }else{
            $this -> _response['code'] = -1;
            $this -> _response['message'] = 'fail';
        }
        return $this -> _response;
    }

    //编辑组名
    public function  edit(){
        $teamName = input('teamName');
        $teamId = input('teamId');
        if(empty($teamName) or empty($teamId)){
            $this -> _response['code'] = 10001;
            $this -> _response['message'] = '参数缺失';
            return $this -> _response;
        }
        $materialBasicModel = new \app\model\material\Basic();
        $value = ['team_id' => $teamId, 'store_id' => $this ->store_id];
        $column = 'team_id,store_id,name';
        $teamInfo = $materialBasicModel -> getTeam($value,$column);
        if(empty($teamInfo)){
            $this -> _response['code'] = 10001;
            $this -> _response['message'] = '账号组不存在';
            return $this -> _response;
        }
        $value = ['name' => $teamName, 'store_id' => $this ->store_id];
        $column = 'team_id,store_id,name';
        $teamInfo = $materialBasicModel -> getTeam($value,$column);
        if($teamInfo){
            $this -> _response['code'] = 10001;
            $this -> _response['message'] = '账号组已存在';
            return $this -> _response;
        }
        $data['name'] = $teamName;
        $materialBasicModel -> editTeam($teamId,$data);
        $this -> _response['code'] = 0;
        $this -> _response['message'] = 'ok';
        return $this -> _response;
    }

    //素材组列表
    public function teamList(){
        $type = input('type');
        if(empty($type)){
            $this  -> _response['code'] = 10001;
            $this  -> _response['message'] = '缺少参数';
            return $this -> _response;
        }
        $materialModel = new \app\model\material\Material();
        $res = $materialModel -> getTeamList($this -> store_id,$type);
        $this -> _response['code'] = 0;
        $this -> _response['data'] = $res;
        return $this -> _response;
    }

    //删除组
    public function del(){
        $teamId = input('teamId');
        if(empty($teamId)){
            $this -> _response['code'] = 10001;
            $this -> _response['message'] = '缺少参数';
            return $this -> _response;
        }
        $materialBasicModel = new \app\model\material\Basic();
        $materialModel = new \app\model\material\Material();
        $list = $materialModel -> getList($this -> store_id,0,10,1,'',$teamId);
        if($list['count'] > 0){
            $this -> _response['code'] = '10001';
            $this -> _response['message'] = '分组下有素材，不能删除';
            return $this -> _response;
        }
        $materialBasicModel -> del($teamId);
        $this -> _response['code'] = 0;
        $this -> _response['message'] = 'ok';
        return $this -> _response;
    }

    //修改素材
    public function changeMaterialTeam(){
        $materialId = input('materialId');
        $teamId = input('teamId');
        $oldTeamId = input('oldTeamId');
        $name = input('name');
        $status = input('status');
        if(!empty($oldTeamId) or !empty($teamId)){
            if(empty($oldTeamId) or empty($teamId)){
                $this -> _response['code'] = 10001;
                $this -> _response['message'] = '缺少参数';
                return $this -> _response;
            }
        }
        $materialBasicModel = new \app\model\material\Basic();
        if($oldTeamId){
            $value = ['store_id' => $this -> store_id,'team_id'=> $oldTeamId,'id' => $materialId];
            $column = 'id';
            $oldInfo = $materialBasicModel -> getMaterial($value,$column);
            if(empty($oldInfo)){
                $this -> _response['code'] = 1000;
                $this -> _response['message'] = '分组关系不存在';
                return $this -> _response;
            }
        }
        if($name){
            $data['name'] = $name;
        }
        if($teamId){
            $data['team_id'] =$teamId;
        }
        if($status){
            $data['status'] = $status;
        }
        if(empty($data)){
            $this -> _response['code'] = 1000;
            $this -> _response['message'] = '缺少参数';
            return $this -> _response;
        }
        $materialBasicModel  -> edit($materialId,$data);
        $this -> _response['code']=0;
        $this -> _response['message'] = 'ok';
        return $this -> _response;
    }

    //素材列表
    public function materialLists(){
        $status = input('status/d',1);
        $page = input('page/d',1);
        $pageSize = input('pageSize/d',10);
        $page = max($page - 1,0);
        $name = input('name');
        $teamId = input('teamId');
        $type = input('type/d');
        $materialModel = new \app\model\material\Material();
        $lists = $materialModel -> getList($this -> store_id,$page,$pageSize,$status,$type,$teamId,$name);
        $this -> _response['code'] = 0;
        $this -> _response['data'] = $lists;
        return $this -> _response;
    }

    public function getQiniuToken(){
        $type = input('type');
        if(empty($type)){
            $this -> _response['code'] =10001;
            $this -> _response['message'] = '缺少参数';
            return $this -> _response;
        }
        if($type == 1){
            $bucket = 'scrm-image';
        }
        if($type == 2){
            $bucket = 'scrm-video';
        }
        require_once  EXTEND_PATH. '/Qiniu/functions.php';
        $accessKey = Config('qiniu_conf')['accessKey'];
        $secretKey = Config('qiniu_conf')['secretKey'];
        $auth = new \Qiniu\Auth($accessKey, $secretKey);
        $token = $auth->uploadToken($bucket);
        $this -> _response['code'] = 0;
        $this -> _response['data'] = $token;
        return $this -> _response;
    }
}