<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\{Av_User,UserMeta};
use Illuminate\Support\Facades\Auth;


class LoginController extends Controller
{
  public function login(Request $request)
  {
    $loginDet = Av_User::where(
      [
        'email'     => $request->username,
        'password'  => md5($request->password),
        'type'      =>  3
      ]
    )->first();

    if ($loginDet) {

       Auth::login($loginDet);
       $user     =  Auth::user();
       $userSub  =  $user->subscription_status;
      if ($user) {
        $usermeta = UserMeta::where(['user_id' => $user->id, 'user_key' =>'av_campaign_status'])->first();
        if ($usermeta->user_meta === 'approve') {
            if($userSub === 'active')
            {
              $success['token'] =  $user->createToken('token')->plainTextToken;
              return  response()->json(['mid'=> $user->id,'token' => $success['token'], 'success' => true,'message' => 'Login successfully.']);
            } else {
              return response()->json(['success' => false, 'message' => 'Your Account has been SUSPENDED due to subscription cancelled.']);
            }
        } else if($usermeta->user_meta === 'waiting') {
              return response()->json(['success' => false, 'message' => 'Your account is pending approval please wait.']);
        } else if($usermeta->user_meta === 'suspend') {
             return response()->json(['success' => false, 'message' => 'Your Account has been SUSPENDED please contact us.']);
        }
      } else {

        return response()->json(["message" => "Unauthorised" ,'success' => false]);

      }
    } else {

      return  response()->json(["message" => "Invalid Username and Password!",'success' => false]);

    }
  }
  
}
