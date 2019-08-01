<?php
/**
 * Created by PhpStorm.
 * User: xubin
 * Date: 2019/5/16
 * Time: 5:11 PM
 */
namespace app\model\member;
use Firebase\JWT\JWT;
require_once ROOT_PATH.'extend/jwt/JWT.php';
class Basic extends \app\core\ApiModel{

    /**
     * JWT 生成加密token
     * @param $userInfo
     * @return string
     */
    public function getToken($userInfo){
        $key = config('jwt');  //这里是自定义的一个随机字串，应该写在config文件中的，解密时也会用，相当    于加密中常用的 盐  salt
        $token = [
            "iss"=>"milkyway",  //签发者 可以为空
            "aud"=>$userInfo['userName'], //面象的用户，可以为空
            "iat" => time(), //签发时间
            "nbf" => '', //非必须。not before。如果当前时间在nbf里的时间之前，则Token不被接受；一般都会留一些余地，比如几分钟。
            "exp" => time()+24*3600, //token 过期时间
            "user_id" => $userInfo['user_id'], //自定义字段，用户id
            "store_id" => $userInfo['store_id'],
        ];
        $jwt = JWT::encode($token,$key,"HS256"); //根据参数生成了 token
        return $jwt;
    }

    /**
     * JWT 解密token
     * @param $token
     * @return object
     */
    public function check($token){
        $key = config('jwt');
        $info = JWT::decode($token,$key,["HS256"]); //解密jwt
        return $info;
    }

    /**
     * 获取用户
     * @param $value  array
     * @param $column   string
     * @return array|false|\PDOStatement|string|\think\Model
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getUserByKey($value,$column,$where_s = ''){
        $where = [];
        foreach ($value as $k => $v){
            $where[] = "$k = '$v'";
        }
        $sql_where = implode( " and " , $where);
        if($where_s){
            $sql_where = $sql_where.' and '.$where_s;
        }
        $sql = "select $column from `user` where $sql_where";
        $result =  $this -> find($sql);
        return $result;
    }

    /**
     * 添加账户
     * @param $data
     * @return string
     */
    public function addUser($data){
        $time = date('Y-m-d H:i:s');
        $sql = "insert into `user` (store_id,username,password,salt,email,ip,date_added,master,team_id)
                values (?,?,?,?,?,?,?,?,?)";
        \think\Db::execute($sql,[$data['store_id'],$data['username'],$data['password'],$data['salt'],$data['email'],$data['ip'],$time,$data['master'],$data['team_id']]);
        return \think\Db::getLastInsID();
    }

    /**
     * 编辑User
     * @param $id
     * @param $data
     * @return int
     */
    public function editUser($id, $data) {

        $set = [];
        foreach ($data as $key => $value) {
            $set[] = "`{$key}`='{$value}'";
        }
        $set = implode(',', $set);

        $sql = "UPDATE `user` SET {$set} WHERE `user_id`=?";

        \think\Db::execute($sql, [$id]);
        return true;
    }

    /**
     * 删除User
     * @param $id
     * @return bool
     */
    public function delUser($id){
        $sql = "delete from `user` where user_id = ?";
        \think\Db::execute($sql,[$id]);
        return true;
    }

    /**
     * 添加公司
     * @param $name
     * @return string
     */
    public function addStore($name){
        $sql = 'insert into `store` (`name`) values (?)';
        \think\Db::execute($sql,[$name]);
        return \think\Db::getLastInsID();
    }

    /**
     * 根据公司名称获取
     * @param $name
     * @param $column
     * @return array|false|\PDOStatement|string|\think\Model
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getStoreByName($name,$column){
        $sql = "select $column from `store` where name = ?";
        $res = $this -> find($sql,[$name]);
        return $res;
    }

}