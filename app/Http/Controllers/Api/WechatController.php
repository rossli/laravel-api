<?php

namespace App\Http\Controllers\Api;

use App\Utils\Utils;
use EasyWeChat\Factory;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;
use App\Http\Controllers\Controller;
use App\Models\User;
class WechatController extends BaseController
{
     public function wechatLogin(){
        $app=Factory::officialAccount(config('wechat'));
        return $app->oauth->scopes(['snsapi_userinfo'])
             ->redirect();
     }
    public function auth(){

        $app=Factory::officialAccount(config('wechat'));
        $user_info=$app->oauth->user()->toArray(); //获取用户授权信息
        dd($user_info);
        $request=Request::all();

        //判断在数据库当中是否存在openid
        $user=User::where('openid','=',$user_info['openid'])->first();
        if(!$user){
            //如果user表不存在openid，则为新用户
            $create_data=[
                'register_type'=>1,//1表示注册来源为pc扫码注册
                'wechat_name'=>$user_info['nickname'],
                'avatar'=>$user_info['headimgurl'],
                'openid'=>$user_info['openid'],
                'sex'=>$user_info['sex'],
                'province'=>$user_info['province'],
                'city'=>$user_info['city'],
                'login_time'=>now(),
            ];
            $user=User::create($create_data);
        }else{
            //如果微信个人信息有修改的情况，update user表的数据
            $update_data=[
                'register_type'=>1,//1表示注册来源为pc扫码注册
                'wechat_name'=>$user_info['nickname'],
                'avatar'=>$user_info['headimgurl'],
                'openid'=>$user_info['openid'],
                'sex'=>$user_info['sex'],
                'province'=>$user_info['province'],
                'city'=>$user_info['city'],
                'login_time'=>now(),
            ];
            User::where('openid',$user_info['openid'])->update($update_data);
        }
        //生成token
        Auth::login($user, TRUE);
        return redirect()->route('web.index.index');

        return redirect()->to($request['state'].'?token='.$res['token']); //授权完成之后跳转会去
    }
  /**
   * 微信登录
   */
//   public function wechatLogin(){
//      $redirect_url="http://jkwedu-api.com/api/v1/wechat-info";
//      $url="https://open.weixin.qq.com/connect/oauth2/authorize?appid=".config('wechat.official_account.app_id')."&".$redirect_url."&response_type=code&scope=snsapi_userinfo&state=STATE#wechat_redirect";
//      Utils::curl($url);
//   }
   public function wechatInfo(Request $request){
       dd($request);
   }

}
