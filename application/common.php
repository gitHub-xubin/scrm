<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
require EXTEND_PATH.'/PHPMailer/src/Exception.php';
require EXTEND_PATH.'/PHPMailer/src/PHPMailer.php';
require EXTEND_PATH.'/PHPMailer/src/SMTP.php';
/**
 * 系统邮件发送函数
 * @param string $tomail 接收邮件者邮箱
 * @param string $name 接收邮件者名称
 * @param string $subject 邮件主题
 * @param string $body 邮件内容
 * @param string $attachment 附件列表
 * @return boolean
 */
function send_mail($tomail, $name, $subject = '', $body = '', $attachment = null) {

    $mail = new PHPMailer();           //实例化PHPMailer对象
    $mail->CharSet = 'UTF-8';           //设定邮件编码，默认ISO-8859-1，如果发中文此项必须设置，否则乱码
    $mail->IsSMTP();                    // 设定使用SMTP服务
    $mail->SMTPDebug = 0;               // SMTP调试功能 0=关闭 1 = 错误和消息 2 = 消息
    $mail->SMTPAuth = true;             // 启用 SMTP 验证功能
    $mail->SMTPSecure = 'ssl';          // 使用安全协议
    $mail->Host = "smtp.qq.com";//"smtp.exmail.qq.com"; // SMTP 服务器
    $mail->Port = 465;                  // SMTP服务器的端口号
    $mail->Username = "765218161@qq.com";    // SMTP服务器用户名
    $mail->Password = "wneubnrzwhbjbccd";     // SMTP服务器密码
    $mail->SetFrom('765218161@qq.com', 'wneubnrzwhbjbccd');
    $replyEmail = '';                   //留空则为发件人EMAIL
    $replyName = '';                    //回复名称（留空则为发件人名称）
    $mail->AddReplyTo($replyEmail, $replyName);
    $mail->Subject = $subject;
    $mail->MsgHTML($body);
    $mail->AddAddress($tomail, $name);
    if (is_array($attachment)) { // 添加附件
        foreach ($attachment as $file) {
            is_file($file) && $mail->AddAttachment($file);
        }
    }
    return $mail->Send() ? true : $mail->ErrorInfo;
}

 function curl_request($url,$method = "GET",$params = '',$header=[],$auth = '',$cookie = '',$referer= '',$isStatus=1){
        //初始化CURL句柄
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);//设置请求的URL
        #curl_setopt($curl, CURLOPT_HEADER, false);// 不要http header 加快效率
        curl_setopt($curl, CURLOPT_RETURNTRANSFER,1); //设为TRUE把curl_exec()结果转化为字串，而不是直接输出
        //SSL验证
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);    // https请求时要设置为false 不验证证书和hosts  FALSE 禁止 cURL 验证对等证书（peer's certificate）, 自cURL 7.10开始默认为 TRUE。从 cURL 7.10开始默认绑定安装。
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE);//检查服务器SSL证书中是否存在一个公用名(common name)。
        if(!empty($header)){
            curl_setopt ( $curl, CURLOPT_HTTPHEADER, $header );//设置 HTTP 头字段的数组。格式： array('Content-type: text/plain', 'Content-length: 100')
        }
        if($cookie) {
            curl_setopt($curl, CURLOPT_COOKIE, $cookie);
        }
        curl_setopt ($curl, CURLOPT_REFERER, $referer);
        //请求时间
        $timeout = 10;
        curl_setopt ($curl, CURLOPT_CONNECTTIMEOUT, $timeout);//设置连接等待时间
        //不同请求方法的数据提交
        switch ($method){
            case "GET" :
                curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "GET");
                //curl_setopt($curl, CURLOPT_HTTPGET, true);//TRUE 时会设置 HTTP 的 method 为 GET，由于默认是 GET，所以只有 method 被修改时才需要这个选项。
                curl_setopt($curl, CURLOPT_POSTFIELDS,$params);
                break;
            case "POST":
                if($params){
                    if(is_array($params)){
                            $params = json_encode($params,JSON_UNESCAPED_UNICODE);
                            $params = str_replace("\\/", "/", $params);
                    }
                   //echo $params."\n".$params_s;die;
                    //curl_setopt($curl, CURLOPT_POST,true);//TRUE 时会发送 POST 请求，类型为：application/x-www-form-urlencoded，是 HTML 表单提交时最常见的一种。
                    //curl_setopt($curl, CURLOPT_NOBODY, true);//TRUE 时将不输出 BODY 部分。同时 Mehtod 变成了 HEAD。修改为 FALSE 时不会变成 GET。
                    curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "POST");//HTTP 请求时，使用自定义的 Method 来代替"GET"或"HEAD"。对 "DELETE" 或者其他更隐蔽的 HTTP 请求有用。 有效值如 "GET"，"POST"，"CONNECT"等等；
                    //设置提交的信息
                    curl_setopt($curl, CURLOPT_POSTFIELDS,$params);//全部数据使用HTTP协议中的 "POST" 操作来发送。
                }

                break;
            case "OPTIONS" :
                curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'OPTIONS');
                break;
            case "DELETE":
                curl_setopt ($curl, CURLOPT_CUSTOMREQUEST, "DELETE");
                if($params) {
                    curl_setopt($curl, CURLOPT_POSTFIELDS, $params);
                }
                break;
        }
        //var_dump($params);die;
        //传递一个连接中需要的用户名和密码，格式为："[username]:[password]"。
        if (!empty($auth) && isset($auth['username']) && isset($auth['password'])) {
            curl_setopt($curl, CURLOPT_USERPWD, "{$auth['username']}:{$auth['password']}");
        }
        $data = curl_exec($curl);//执行预定义的CURL
        if ($data === FALSE) {
            echo "cURL Error: " . curl_error($curl);
        }
        $status = curl_getinfo($curl, CURLINFO_HTTP_CODE);//获取http返回值,最后一个收到的HTTP代码
        curl_close($curl);//关闭cURL会话
     //var_dump($data);
        if($isStatus == 1){
            $res = json_decode($data,true);
        }else{
            $res = [
              'status'  => $status,
              'data'  => $data
            ];
        }
        return $res;
    }

    //设置账号token为过期
    function setAccountTokenInvalid($accountId){
            $sql = "update `account` set auth_status = 2 where account_id = ?";
            \think\Db::execute($sql,[$accountId]);
            return true;
    }

//数组转xml
 function arrayToXml($arr){
    $xml = "<xml>";
    foreach ($arr as $key=>$val){
        if (is_numeric($val)){
            $xml.="<".$key.">".$val."</".$key.">";
        }else{
            $xml.="<".$key."><![CDATA[".$val."]]></".$key.">";
        }
    }
    $xml.="</xml>";
    return $xml;
}

 //将xml转为array
 function xmlToArray($xml){
    //将XML转为array
    $array_data = json_decode(json_encode(simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA)), true);
    return $array_data;
}

//post  请求，data不转json
function doPost($url,$data){
    $ch  = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER , true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER , false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST , false);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    $res  = curl_exec($ch);
    curl_close($ch);
    return $res;
}