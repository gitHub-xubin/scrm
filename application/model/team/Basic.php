<?php
/**
 * Created by PhpStorm.
 * User: xubin
 * Date: 2019/6/17
 * Time: 4:06 PM
 */
namespace app\model\team;
class Basic extends \app\core\ApiModel{
    /**
     * 添加账号组
     * @param $name
     * @param $store_id
     * @return string
     */
    public function addTeam($name,$store_id,$user_id){
        $sql = 'insert into `team` (store_id,user_id,name) values (?,?,?)';
        \think\Db::execute($sql,[$store_id,$user_id,$name]);
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
        $sql = "select $column from `team` where $sql_where";
        $res = $this -> find($sql);
        return $res;
    }

    public function editTeam($id, $data) {
        $set = [];
        foreach ($data as $key => $value) {
            $set[] = "`{$key}`='{$value}'";
        }
        $set = implode(',', $set);

        $sql = "UPDATE `team` SET {$set} WHERE `team_id`=?";

        \think\Db::execute($sql, [$id]);
        return true;
    }


    public function del($teamId){
        $sql = 'delete from `team` where team_id = ?';
        \think\Db::execute($sql,[$teamId]);
        return true;
    }

}