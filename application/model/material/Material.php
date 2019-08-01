<?php
/**
 * Created by PhpStorm.
 * User: xubin
 * Date: 2019/6/25
 * Time: 11:34 AM
 */
namespace app\model\material;
class Material extends \app\core\ApiModel{

    /**
     * 获取账号组列表
     * @param $store_id
     * @return mixed
     */
    public function getTeamList($store_id,$type){
        $sql = "select team_id,name from `material_team` where  store_id = ? and type = ?";
        $res = $this -> findAll($sql,[$store_id,$type]);
        return $res;
    }

    /*
     * 素材列表
     * @param $storeId
     * @param int $page
     * @param int $pageSize
     * @param string $name
     * @param string $type
     * @return array|mixed
     */
    public function getList($storeId,$page=0,$pageSize=10,$status=1,$type = '',$teamId = '',$name=''){
        $where[] = 'mc.store_id = '.$storeId;
        $where[] = 'mc.status = '.$status;
        if($name){
            $where[] = "mc.name like '%$name%'";
        }
        if($type){
            $where[] = 'mc.type = '.$type;
        }
        if($teamId){
            $where[] = 'mc.team_id = '.$teamId;
        }
        $order = ['mc.id desc'];
        $sql = "select mc.id,mc.team_id,mc.store_id,mc.type,mc.name,mc.created,mc.url,mc.mp_url,mc.mp_status from `material_content` as mc left join `material_team` as mt on mc.team_id = mt.team_id";
        $count_sql = "select count(*) as count from `material_content` as mc left join `material_team` as mt on mc.team_id = mt.team_id";
        $result = $this->_getList($sql, $count_sql, $where, $order, true, $page, $pageSize);
        $imgDomain = config('qiniu_conf')['scrm-image']['domain'];
        $videoDomain = config('qiniu_conf')['scrm-video']['domain'];
        foreach ($result['list'] as $k => $v){
            if($v['type'] == 1){
                $result['list'][$k]['url'] = $imgDomain.$v['url'];
            }
            if($v['type'] == 2){
                $result['list'][$k]['url'] = $videoDomain.$v['url'];
            }
        }
        return $result;
    }

    //定时任务，秒拍视频审核
    public function checkMpUrl(){
        $sql = "select count(*) as count from `material_content` where scid <> '' and mp_status = -1";
        $count = $this -> find($sql);
        $num = 20;//每次20个素材
        $frequency = ceil($count['count']/$num); //循环次数
        $materialBasicModel = new \app\model\material\Basic();
        $accountBasicModel = new \app\model\account\Basic();
        for($i = 1;$i <= $frequency; $i++){
            $sql = "select id,scid,mp_account_id from `material_content` where scid <> '' and mp_status = -1 order by id asc limit 20";
            $res = $this -> findAll($sql);
            if(!empty($res)){
                foreach ($res as $k => $v){
                    $value = ['account_id' => $v['mp_account_id']];
                    $column = 'auth';
                    $accountInfo = $accountBasicModel -> getAccount($value,$column);
                    $accountCookie = json_decode($accountInfo['auth'],TRUE)['cookie'];
                    $url = 'scrm_public_api.shifuhui.net/get_mp_video_url';
                    $param = [
                        'cookie' => $accountCookie,
                        'scid' => $v['scid']
                    ];
                    $header[] = "Content-Type:application/json";
                    $res = curl_request($url,'POST',$param,$header);
                    if($res['code'] == 200){
                        $data['mp_status'] = $res['data'][0]['status'];
                        $materialBasicModel -> edit($v['id'],$data);
                    }
                }
            }
        }
    }
}