<?php
/**
 * Created by PhpStorm.
 * User: xubin
 * Date: 2019/6/25
 * Time: 11:27 AM
 */
namespace app\model\material;
class Basic extends \app\core\ApiModel{

    /**
     * 添加账号组
     * @param $name
     * @param $store_id
     * @return string
     */
    public function addTeam($name,$store_id,$type){
        $sql = 'insert into `material_team` (store_id,name,type) values (?,?,?)';
        \think\Db::execute($sql,[$store_id,$name,$type]);
        return \think\Db::getLastInsID();
    }

    /**
     * 获取小组信息
     * @param $value
     * @param $key
     * @param $column
     * @return array|false|\PDOStatement|string|\think\Model
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getTeam($value,$column){
        $where = [];
        foreach ($value as $k => $v){
            $where[] = "$k = '$v'";
        }
        $sql_where = implode( " and " , $where);
        $sql = "select $column from `material_team` where $sql_where";
        $res = $this -> find($sql);
        return $res;
    }

    /**
     * 获取素材详情
     * @param $value
     * @param $column
     * @return array|false|\PDOStatement|string|\think\Model
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getMaterial($value,$column){
        $where = [];
        foreach ($value as $k => $v){
            $where[] = "$k = '$v'";
        }
        $sql_where = implode( " and " , $where);
        $sql = "select $column from `material_content` where $sql_where";
        $res = $this -> find($sql);
        return $res;
    }

    /**
     * 编辑分组
     * @param $id
     * @param $data
     * @return bool
     */
    public function editTeam($id, $data) {
        $set = [];
        foreach ($data as $key => $value) {
            $set[] = "`{$key}`='{$value}'";
        }
        $set = implode(',', $set);

        $sql = "UPDATE `material_team` SET {$set} WHERE `team_id`=?";

        \think\Db::execute($sql, [$id]);
        return true;
    }

    /**
     * 编辑素材
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
        $sql = "UPDATE `material_content` SET {$set} WHERE `id`=?";
        \think\Db::execute($sql, [$id]);
        return true;
    }


    /**
     * 删除分组
     * @param $teamId
     * @return bool
     */
    public function del($teamId){
        $sql = 'delete from `material_team` where team_id = ?';
        \think\Db::execute($sql,[$teamId]);
        return true;
    }

    /**
     * 添加素材
     * @param $data
     */
    public function addMaterial($data){
            $time = date("Y-m-d H:i:s");
            $sql = "insert into `material_content`(team_id,store_id,type,name,url,created)values (?,?,?,?,?,?)";
            \think\Db::execute($sql,[$data['team_id'],$data['store_id'],$data['type'],$data['name'],$data['url'],$time]);
            return \think\Db::getLastInsID();
    }

    //刷新秒拍地址
    public function refreshMPUrl(){
        $sql = "select count(*) as count from `material_content`  where mp_account_id > 0 and mp_url is null";
        $count = $this -> find($sql);
        $num = 20;//每次20个账号
        $frequency = ceil($count['count']/$num); //循环次数
        $materialBasicModel = new \app\model\material\Basic();
        for($i = 1;$i <= $frequency; $i++){
            $sql = "select mc.id, mc.scid, a.auth from `material_content` as mc left join `account` as a ON mc.mp_account_id = a.account_id 
                    where  mc.mp_account_id > 0 and mc.mp_url is null limit ".$num;
            $res = $this -> findAll($sql);
            foreach ($res as $k => $v){
                $accountCookie = json_decode($v['auth'],TRUE)['cookie'];
                $url = 'http://scrm_public_api.shifuhui.net/get_mp_video_url';
                $getMPUrlParam = [
                    'cookie' => $accountCookie,
                    'scid' => $v['scid']
                ];
                $header[] = "Content-Type:application/json";
                $result = curl_request($url,'POST',$getMPUrlParam,$header);
                if($result['code'] == 200){
                    $data['mp_url'] = $result['data'][0]['video_url'];
                    $data['mp_status'] = $result['data'][0]['status'];
                    $materialBasicModel -> edit($v['id'],$data);
                }
            }
        }
        echo 'ok';
    }

}