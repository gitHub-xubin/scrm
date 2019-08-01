<?php
/**
 * 各平台账号授权管理
 * Created by PhpStorm.
 * User: xubin
 * Date: 2019/5/20
 * Time: 3:53 PM
 */
namespace app\merchant\controller;

use app\model\account\Basic;
use think\Config;

class Account extends \app\core\MerchantController{

    //添加微博授权
    //https://api.weibo.com/oauth2/authorize?client_id=162488333&redirect_uri=http://scrm-api.shifuhui.net/merchant/account/addweibo
    public function addWeiBo(){
        $storeId = $this -> store_id;
        $code = input('code');
        if(empty($code)){
            $this -> _response['code'] = 10001;
            $this -> _response['message'] = '未获取到授权信息';
            return $this -> _response;
        }
        $clientId = config('weibo')['client_id'];
        $clientSecret = config('weibo')['client_secret'];
        $url = 'https://api.weibo.com/oauth2/access_token';
        $param = http_build_query([
                    'client_id' => "$clientId",
                    'client_secret' => "$clientSecret",
                    'grant_type' => 'authorization_code',
                    'code' => "$code",
                    'redirect_uri' => 'http://scrm-web.shifuhui.net/manage/account'
                ]);
        $header[] = 'Content-Type:application/x-www-form-urlencoded';
        $res = curl_request($url,'POST',$param,$header);
        /*$res = "{\"access_token\":\"2.00Vgb2BC0ZdmzK7a8910c0f90KOoka\",\"remind_in\":\"2646245\",\"expires_in\":2646245,\"refresh_token\":\"2.00Vgb2BC0ZdmzKb4c1e22195Pia3YE\",\"uid\":\"1852191751\",\"isRealName\":\"true\"}";
        $res = json_decode($res,TRUE);*/
        if(empty($res['error'])){
            $url = 'https://api.weibo.com/2/users/show.json?access_token='.$res['access_token'].'&uid='.$res['uid'];
            $accountInfo = curl_request($url,"GET");
            /*$accountInfo = "{\"id\":1852191751,\"idstr\":\"1852191751\",\"class\":1,\"screen_name\":\"ArrayImplode\",\"name\":\"ArrayImplode\",\"province\":\"61\",\"city\":\"1\",\"location\":\"\u9655\u897f \u897f\u5b89\",\"description\":\"\",\"url\":\"\",\"profile_image_url\":\"http:\/\/tva4.sinaimg.cn\/crop.1.0.638.638.50\/6e663407jw8escdfa3cdej20hs0hq0sr.jpg\",\"cover_image_phone\":\"http:\/\/ww4.sinaimg.cn\/crop.0.0.640.640\/6cf8d7ebjw1ehfr60whp7j20hs0hsacf.jpg\",\"profile_url\":\"xbaill1314\",\"domain\":\"xbaill1314\",\"weihao\":\"\",\"gender\":\"m\",\"followers_count\":92,\"friends_count\":119,\"pagefriends_count\":0,\"statuses_count\":264,\"video_status_count\":0,\"favourites_count\":3,\"created_at\":\"Tue Nov 09 09:31:50 +0800 2010\",\"following\":false,\"allow_all_act_msg\":false,\"geo_enabled\":true,\"verified\":false,\"verified_type\":-1,\"remark\":\"\",\"insecurity\":{\"sexual_content\":false},\"status\":{\"created_at\":\"Wed Jul 11 13:20:44 +0800 2018\",\"id\":4260586512922745,\"idstr\":\"4260586512922745\",\"mid\":\"4260586512922745\",\"can_edit\":false,\"show_additional_indication\":0,\"text\":\"\u8f6c\u53d1\u5fae\u535a\",\"source_allowclick\":0,\"source_type\":1,\"source\":\"\u5fae\u535a weibo.com<\/a>\",\"favorited\":false,\"truncated\":false,\"in_reply_to_status_id\":\"\",\"in_reply_to_user_id\":\"\",\"in_reply_to_screen_name\":\"\",\"pic_urls\":[],\"geo\":null,\"is_paid\":false,\"mblog_vip_type\":0,\"reposts_count\":0,\"comments_count\":0,\"attitudes_count\":0,\"pending_approval_count\":0,\"isLongText\":false,\"reward_exhibition_type\":0,\"hide_flag\":0,\"mlevel\":0,\"visible\":{\"type\":0,\"list_id\":0},\"biz_feature\":0,\"hasActionTypeCard\":0,\"darwin_tags\":[],\"hot_weibo_tags\":[],\"text_tag_tips\":[],\"mblogtype\":0,\"rid\":\"0\",\"userType\":0,\"more_info_type\":0,\"positive_recom_flag\":0,\"content_auth\":0,\"gif_ids\":\"\",\"is_show_bulletin\":2,\"comment_manage_info\":{\"comment_permission_type\":-1,\"approval_comment_type\":0}},\"ptype\":0,\"allow_all_comment\":true,\"avatar_large\":\"http:\/\/tva4.sinaimg.cn\/crop.1.0.638.638.180\/6e663407jw8escdfa3cdej20hs0hq0sr.jpg\",\"avatar_hd\":\"http:\/\/tva4.sinaimg.cn\/crop.1.0.638.638.1024\/6e663407jw8escdfa3cdej20hs0hq0sr.jpg\",\"verified_reason\":\"\",\"verified_trade\":\"\",\"verified_reason_url\":\"\",\"verified_source\":\"\",\"verified_source_url\":\"\",\"follow_me\":false,\"like\":false,\"like_me\":false,\"online_status\":0,\"bi_followers_count\":10,\"lang\":\"zh-cn\",\"star\":0,\"mbtype\":0,\"mbrank\":0,\"block_word\":0,\"block_app\":0,\"credit_score\":80,\"user_ability\":0,\"urank\":11,\"story_read_state\":-1,\"vclub_member\":0,\"is_teenager\":0,\"is_guardian\":0,\"is_teenager_list\":0}";
            $accountInfo = json_decode($accountInfo,TRUE);*/
            $auth = json_encode($res);
            $expired_time = date('Y-m-d H:i:s',time() + $res['expires_in']);
            $accountBasicModel = new \app\model\account\Basic();
            $accountModel = new \app\model\account\Account();
            $value = ['uid' =>  $res['uid'], 'store_id' => $storeId];
            $column = 'account_id';
            $info = $accountBasicModel -> getAccount($value,$column);
            \think\Db::startTrans();
            try{
                if($info){
                    $accountId = $info['account_id'];
                    $accountParam = [
                        'a_name' => $accountInfo['screen_name'],
                        'gender' => $accountInfo['gender'],
                        'header_url' => $accountInfo['profile_image_url'],
                        'auth' => $auth,
                        'auth_status' => 1,
                        'expired_time' => $expired_time,
                        'date_modified' => date('Y-m-d H:i:s'),
                        'status' => 1
                    ];
                    $accountBasicModel -> edit($accountId,$accountParam);
                    $time = date('Y-m-d H:i:s');
                    $accountDetail = [
                        'followers_count' => $accountInfo['followers_count'],
                        'friends_count' => $accountInfo['friends_count'],
                        'statuses_count' => $accountInfo['statuses_count'],
                        'favourites_count' => $accountInfo['favourites_count'],
                        'updated' => $time
                    ];
                    $accountModel -> edit($accountId,$accountDetail);
                }else{
                    $accountParam = [
                        'uid' => $res['uid'],
                        'a_name' => $accountInfo['screen_name'],
                        'gender' => $accountInfo['gender'],
                        'header_url' => $accountInfo['profile_image_url'],
                        'account_platform_id' => 1,
                        'auth' => $auth,
                        'auth_status' => 1,
                        'store_id' => $storeId,
                        'expired_time' => $expired_time
                    ];
                    $accountId = $accountBasicModel -> addAccount($accountParam);
                    $accountDetail = [
                        'account_id' => $accountId,
                        'followers_count' => $accountInfo['followers_count'],
                        'friends_count' => $accountInfo['friends_count'],
                        'statuses_count' => $accountInfo['statuses_count'],
                        'favourites_count' => $accountInfo['favourites_count'],
                    ];
                    $accountModel -> addAccountWBDetail($accountDetail);
                }
                $info = [
                    'accountId' => $accountId,
                    'header_url' => $accountInfo['profile_image_url'],
                    'name' => $accountInfo['screen_name'],
                ];
                $this -> _response['code'] = 0;
                $this -> _response['message'] = 'ok';
                $this -> _response['data'] = $info;
                \think\Db::commit();
            }catch (\think\Exception $e){
                $this -> _response['code'] = -1;
                $this -> _response['message'] = 'fail';
                \think\Db::rollback();
            }
        }else{
            $this -> _response['code'] = -2;
            $this -> _response['message'] = $res['error'];
        }
        return $this -> _response;
    }

    public function updateStaus(){
        $accountId = input('accountId');
        $status = input('status');
        if(empty($accountId)){
            $this ->  _response['code'] = 10001;
            $this ->  _response['message'] = '账号id不能为空';
            return $this -> _response;
        }
        $accountBasicModel = new \app\model\account\Basic();
        $data['status'] = $status;
        $accountBasicModel -> edit($accountId,$data);
        $this -> _response['code'] = 0;
        $this -> _response['message'] = 'ok';
        return $this -> _response;
    }

    public function changeAccountTeam(){
        $accountId = input('accountId');
        $teamId = input('teamId');
        $oldTeamId = input('oldTeamId');
        if(empty($accountId) or empty($teamId)){
            $this -> _response['code'] = 10001;
            $this -> _response['message'] = '缺少参数';
            return $this -> _response;
        }
        $accountModel = new \app\model\account\Account();
        /*$info = $accountModel -> getTeamAccount($this -> _user_id,$teamId,$accountId);
        if($info){
            $this -> _response['code'] = 10003;
            $this -> _response['message'] = '已更改';
            return $this -> _response;
        }*/
        if($oldTeamId){
            $oldInfo = $accountModel -> getTeamAccount($this -> _user_id,$oldTeamId,$accountId);
            if(empty($oldInfo)){
                $this -> _response['code'] = 10003;
                $this -> _response['message'] = '分组关系不存在';
                return $this -> _response;
            }
        }
        \think\Db::startTrans();
        try{
            $data['team_id'] = $teamId;
            $data['account_id'] = $accountId;
            if($oldTeamId){
                $accountModel -> delTeamAccount($data['account_id'],$oldTeamId);
            }
            $accountModel -> addTeamAccount($data);
            $this -> _response['code'] = 0;
            $this ->  _response['message']= 'ok';
            \think\Db::commit();
        }catch (\think\Exception $e){
            $this -> _response['code'] = -1;
            $this -> _response['message'] = 'fail';
            \think\Db::rollback();
        }

        return $this -> _response;
    }
    //账号列表
    public function accountLists(){
        $status = input('status/d',1);
        $page = input('page/d',1);
        $pageSize = input('pageSize/d',10);
        $page = max($page - 1,0);
        $name = input('name');
        $teamId = input('teamId');
        $isAll = input('isAll');
        $account_platform_id = input('accountPlatformId/d');
        $accountModel = new \app\model\account\Account();
        $lists = $accountModel -> getLists($this->store_id, $this->_user_id, $page,$pageSize,$status,$account_platform_id,$teamId,$name,$isAll);
        $this -> _response['code'] = 0;
        $this -> _response['data'] = $lists;
        return $this -> _response;
    }

    //主账号未分组列表
    public function unclassiFiedList(){
        $status = input('status/d',1);
        $page = input('page/d',1);
        $pageSize = input('pageSize/d',10);
        $page = max($page - 1,0);
        $name = input('name');
        $account_platform_id = input('accountPlatformId/d');
        $accountModel = new \app\model\account\Account();
        $userBasicModel = new \app\model\member\Basic();
        $value = ['user_id' => $this -> _user_id];
        $col = 'master';
        $userInfo = $userBasicModel -> getUserByKey($value,$col);
        if($userInfo['master'] == 2){
            $this -> _response['code'] = -1;
            $this -> _response['code'] = '不是主账号';
            return $this -> _response;
        }
        $lists = $accountModel -> getUnclassiFiedLists($this -> store_id,$page,$pageSize,$status,$account_platform_id,$name);
        $this -> _response['code'] = 0;
        $this -> _response['data'] = $lists;
        return $this -> _response;
    }

    //子账号未分组列表
    public function childUnclassiFiedList(){
        $page = input('page/d',0);
        $pageSize = input('pageSize/d',10);
        $page = max($page - 1,0);
        $account_platform_id = input('accountPlatformId/d');
        $userBasicModel = new \app\model\member\Basic();
        $value = ['user_id' => $this -> _user_id];
        $col = 'user_id,master';
        $userInfo = $userBasicModel -> getUserByKey($value,$col);
        if($userInfo['master'] == 1){
            $this -> _response['code'] = -1;
            $this -> _response['code'] = '不是子账号';
            return $this -> _response;
        }
        $model = new \app\model\team\Team();
        $res = $model -> childUnclassiFiedList($this -> _user_id,$account_platform_id,$page,$pageSize);
        $this -> _response['code'] = 0;
        $this -> _response['message'] = 'ok';
        $this -> _response['data'] = $res;
        return $this -> _response;
    }

    public function addMP(){
        $mpCookie = input('mpCookie');
        if(empty($mpCookie)){
            $this -> _response['code'] = 10001;
            $this -> _response['message'] = "缺少参数";
            return $this -> _response;
        }
        $header[] = "Cookie:".$mpCookie;
        $header[] = "X-Requested-With: XMLHttpRequest";
        $url = "http://creator.miaopai.com/profile/getInfo?type=full&ptype=0";
        $res = curl_request($url,'GET','',$header);
        if($res['code'] == 200){
            $auth = json_encode(['cookie' => $mpCookie]);
            $storeId = $this -> store_id;
            $accountBasicModel = new \app\model\account\Basic();
            $value = ['uid' =>  $res['data']['uid'], 'store_id' => $storeId];
            $column = 'account_id';
            $info = $accountBasicModel -> getAccount($value,$column);
            if(!empty($info)){
                $accountId = $info['account_id'];
                $accountParam = [
                    'a_name' => $res['data']['nickName'],
                    'gender' => $res['data']['sex'],
                    'header_url' => $res['data']['icon'],
                    'auth' => $auth,
                    'auth_status' => 1,
                    'date_modified' => date('Y-m-d H:i:s'),
                    'status' => 1
                ];
                $accountBasicModel -> edit($accountId,$accountParam);
            }else{
                $accountParam = [
                    'uid' => $res['data']['uid'],
                    'a_name' => $res['data']['nickName'],
                    'gender' => $res['data']['sex'],
                    'header_url' => $res['data']['icon'],
                    'account_platform_id' => 2,
                    'auth' => $auth,
                    'auth_status' => 1,
                    'store_id' => $storeId,
                    'expired_time' => null
                ];
                $accountId = $accountBasicModel -> addAccount($accountParam);
            }
            $accountInfo = [
                'a_name' => $res['data']['nickName'],
                'header_url' => $res['data']['icon'],
                'account_platform_id' => 2,
                'accountId' =>$accountId
            ];
            $this -> _response['code'] = 0;
            $this -> _response['message'] = 'ok';
            $this -> _response['data'] = $accountInfo;
        }else{
            $this -> _response['code'] = -2;
            $this -> _response['message'] = 'cookie不正确';
        }
        return $this -> _response;
    }

    //获取各平台账号列表（不分页）
    public function getAccountList(){
        $accountModel = new \app\model\account\Account();
        $lists = $accountModel -> getAccountList($this -> store_id,$this -> _user_id,1);
        $this -> _response['code'] = 0;
        $this -> _response['data'] = $lists;
        return $this -> _response;
    }

    //删除子账号
    public function delChildAccount(){
        $userId = input('userId');
        if(empty($userId)){
            $this -> _response['code'] = 10001;
            $this -> _response['message'] = '缺少参数';
            return $this -> _response;
        }
        $accountModel = new \app\model\account\Basic();
        $accountModel -> del($userId);
        $this -> _response['code'] = 0;
        $this -> _response['message'] = 'ok';
        return $this -> _response;
    }
}