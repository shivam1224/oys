<?php

namespace App\Http\Controllers\API\V1;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Exception;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use \Illuminate\Support\Facades\Mail;

class AuthController extends Controller
{

    /**
     * Manual Sidgnup user
     */

    public function userSignup(Request $request){

        try{
            $validation = Validator::make($request->all(), [
                'name'              => 'required|max:255',
                'email'             => 'required|max:100|email|unique:users',
                'phone'             => 'required|min:7|max:15',
                'password'          => 'required|min:6',
                'confirm_password'  => 'required|min:6|same:password'
            ]);

            if ($validation->fails()) {

                $result['code']     = config('messages.http_codes.validation');
                $result['error']    = true;
                $result['message']  = $validation->messages()->first();
                return response()->json($result);
            }

            $requestData              = $request->all();
            $requestData['password']  = app('hash')->make($request->password);
            $requestData['parent_id'] = 1;
            $requestData['referrer_id'] = 1;
            $requestData['is_admin'] = 0;


            $user     = User::create($requestData);

            $result['id']          = $user->id;
            $result['name']        = $user->name;
            $result['email']       = $user->email;
            $result['phone']       = $user->phone;
            $result['parent_id']   = $user->parent_id;
            $result['referrer_id'] = 1;
            $result['type']        = $user->type;

            /*if ($user->referrer !== null) {
                Notification::send($user->referrer, new ReferrerBonus($user));
            }*/

            return response()->json([

                'code' => config('messages.http_codes.success'),
                'error' => false,
                'message'=> config('messages.error_messages.user_register_success'),
                'signupResult' => $result
            ]);

        /* } catch (BadResponseException $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()]);
        } */
        }catch(Exception $e){

            $exception['code'] = config('messages.http_codes.server');
            $exception['error'] = true;
            $exception['message'] = config('messages.error_messages.server_error');
            return response()->json($exception);
        }
    } 
    /**
     * User Signup API
     */
    public function register(Request $request){

        try{

            $validation = Validator::make($request->all(), [
                'name'              => 'required|max:255',
                'email'             => 'required|max:100|email|unique:users',
                'added_by'             => 'required',
            ]);

            if ($validation->fails()) {

                $result['code']     = config('messages.http_codes.validation');
                $result['error']    = true;
                $result['message']  = $validation->messages()->first();
                return response()->json($result);
            }

         

            $to_name  = $request->name;
            $to_email = $request->email;

           
            
            $body = 'Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod
                tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam,
                quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo
                consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse
                cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non
                proident, sunt in culpa qui officia deserunt mollit anim id est laborum.';
            $data = array('name' => $to_name, 'body' => $body);
            $subject = "User Registration";

            //send otp via email
            //$sendMail = sendMail($data, $subject, $to_name, $to_email);

            $requestData              =  $request->only(['name', 'email']);
            $added_by = $request->added_by;
            $request->request->remove('added_by');
            $requestData['password']  = app('hash')->make('123456');
            $requestData['parent_id'] = $added_by;
            $requestData['referrer_id']= $added_by;
            $requestData['status'] = 0; 
            $requestData['phone']='43543534';

            //dd($requestData);


            $user     = User::create($requestData);

          
            /*if ($user->referrer !== null) {
                Notification::send($user->referrer, new ReferrerBonus($user));
            }*/

            return response()->json([

                'code' => config('messages.http_codes.success'),
                'error' => false,
                'message'=> config('messages.error_messages.user_register_success'),
                'signupResult' => 'User added successfully'
            ]);

        /* } catch (BadResponseException $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()]);
        } */
        }catch(Exception $e){

            $exception['code'] = config('messages.http_codes.server');
            $exception['error'] = true;
            $exception['message'] = config('messages.error_messages.server_error');
            return response()->json($exception);
        }

    }


    /**
     * Get Network
     */

    public function getNetwork(Request $request) {
        $user = auth()->user()->parent_id;
        $network = User::all();
        $result['code'] = config('messages.http_codes.success');
        $result['error'] = false;
        $result['networkList'] = $network;
        return response()->json($result);
    }

   

    /**
     * User Login API
     */

    public function userLogin(Request $request){

        try {

                $validation = Validator::make($request->all(), [
                    'email'         => 'required',
                    'password'      => 'required'
                ]);

                if ($validation->fails()) {

                    $result['code'] = config('messages.http_codes.validation');
                    $result['error'] = true;
                    $result['message'] = $validation->messages()->first();
                    return response()->json($result);
                }

                $checkUser = User::query()->where(['email'=> $request->email])->first();
               // dd($checkUser);

                if (@count($checkUser->toArray())>0) {

                    $password = $request->password;
                    if(Hash::check($password, $checkUser['password'])){

                        // Check if User is deactivated from admin.
                        if($checkUser->status == 0){
                           dd("status");
                            $result['code']    = config('messages.http_codes.unauthorized');
                            $result['error']   = true;
                            $result['message'] = config('messages.error_messages.account_suspend');
                            return response()->json($result);
                        }


                        // Deleting all passport tokens for User
                        $checkUser->tokens()->delete();
                        $token = $checkUser->createToken('cartvy')->accessToken;

                        $loginresult['id']            = $checkUser->id;
                        $loginresult['name']          = $checkUser->name;
                        $loginresult['email']         = $checkUser->email;
                        $loginresult['phone']         = $checkUser->phone;
                        $loginresult['type']          = $checkUser->type;
                        $loginresult['profile_image'] = $checkUser->profile_image ?? env('APP_URL')."public/profile_pictures/default-user.png";


                        $result['code']              = config('messages.http_codes.success');
                        $result['error']             = false;
                        $result['message']           = config('messages.error_messages.login_success');
                        $result['userInfo']          = $loginresult;
                        $result['userInfo']['token'] = $token;
                        return response()->json($result);

                    } else {

                        $result['code'] = config('messages.http_codes.unauthorized');
                        $result['error'] = true;
                        $result['message'] = config('messages.error_messages.credential_error');
                        return response()->json($result);
                    }

                } else {

                        $result['code'] = config('messages.http_codes.not_found');
                        $result['error'] = true;
                        $result['message'] = config('messages.error_messages.email_not_exist');
                        return response()->json($result);
                }

            } catch (Exception $e) {

                $exception['code'] = config('messages.http_codes.server');
                $exception['error'] = true;
                $exception['message'] = config('messages.error_messages.server_error');
                return response()->json($exception);
            }

    }


    /**
     * User GetProfile API
     */

    public function getProfile(){

        try{

            $user = auth()->user();

            if (isset($user)) {

                $data['code'] = config('messages.http_codes.success');
                $data['error'] = false;
                $data['userInfo'] = $user;
                return response()->json($data)->withHeaders([
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                ]);

            } else {

                return response()->json([
                    'code' => config('messages.http_codes.unauthorized'),
                    'error' => true,
                    'message' => config('messages.error_messages.unauthorized'),
                ])->withHeaders([
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                ]);
            }

        }catch(Exception $e){

            $exception['code'] = config('messages.http_codes.server');
            $exception['error'] = true;
            $exception['message'] = config('messages.error_messages.server_error');
            return response()->json($exception);
        }
    }

    /**
     * User UpdateProfile API
    */

    public function updateProfile(Request $request)
    {

        try {

            $validation = Validator::make($request->all(), [

                'name'          => 'required|max:255',
                'phone'         => 'required|numeric|min:10',
                'profile_image' => 'sometimes|mimes:jpg,jpeg,gif,png'
            ]);

            if ($validation->fails()) {
                
                $result['code'] = config('messages.http_codes.validation');
                $result['error'] = true;
                $result['message'] = $validation->messages()->first();
                return response()->json($result);
            }

            $user = auth()->user();
            $requestData = $request->only('name','phone','profile_image');

            if ($request->hasFile('profile_image')) {

                try {

                    $profile_image = $request->file('profile_image');
                    $name = time()."_".preg_replace('/\s+/', '',$profile_image->getClientOriginalName());
                    $destinationFolder = public_path('/profile_pictures/users/' . $user->id . '/');

                    // Delete file
                    if (file_exists(public_path('profile_pictures/users/' . $user->id . '/' . $user->profile_image))) {
                        unlink(public_path('profile_pictures/users/' . $user->id . '/' . $user->profile_image));
                    }

                    $profile_image->move($destinationFolder, $name);
                    $requestData['profile_image'] = env('APP_URL') . "public/profile_pictures/users/" . $user->id . '/' . $name;
                
                } catch (Exception $e) {

                    $exception['code'] = config('messages.http_codes.server');
                    $exception['error'] = true;
                    //$exception['message'] = config('messages.error_messages.server_error');
                    $exception['message'] =$e->getMessage();
                    return response()->json($exception);
                }
            }

            if ($user->update($requestData)) {

                $result['code'] = config('messages.http_codes.success');;
                $result['error'] = false;
                $result['message'] = config('messages.error_messages.profile_updated');
                $result['userInfo'] = $user;
                return response()->json($result);

            } else {

                $exception['code'] = config('messages.http_codes.server');
                $exception['error'] = true;
                $exception['message'] = config('messages.error_messages.server_error');
                return response()->json($exception);
            }

        } catch (Exception $e) {

            $exception['code'] = config('messages.http_codes.server');
            $exception['error'] = true;
            $exception['message'] = config('messages.error_messages.server_error');
            return response()->json($exception);
        }
    }

    public function changePassword(Request $request)
    {
    
        try{

            $validation = Validator::make($request->all(),
                [
                    'old_password'      => 'required',
                    'password'          => 'required|min:6',
                    'confirm_password'  => 'required|same:password',
                ]
            );

            if ($validation->fails()) {

                $result['code'] = config('messages.http_codes.validation');
                $result['error'] = true;
                $result['message'] = $validation->messages()->first();
                return response()->json($result);
            } 

            $user = auth()->user();

            if (isset($user)) {

                if (Hash::check($request->old_password, $user->password)) {

                    $user->fill([
                        'password' => Hash::make($request->password)
                    ])->save();

                    $result['code'] = config('messages.http_codes.success');
                    $result['error'] = false;                    
                    $result['message'] = config('messages.error_messages.password_updated');
                    return response()->json($result);
                        
                } else {

                    $result['code'] = config('messages.http_codes.validation');
                    $result['error'] = true;
                    $result['message'] = config('messages.error_messages.old_password_not_match');
                    return response()->json($result);
                }

            } else {

                $result['code'] = config('messages.http_codes.unauthorized');
                $result['error'] = true;
                $result['message'] = config('messages.error_messages.unauthorized');
                return response()->json($result);
            }
        
        } catch (Exception $e) {

            $exception['code'] = config('messages.http_codes.server');
            $exception['error'] = true;
            $exception['message'] = config('messages.error_messages.server_error');
            return response()->json($exception);
        }    
    }

    /**
     * User Log Out API
    */

    public function logout(Request $request){
    
        try {
            
            $user = Auth::user()->tokens()->each(function ($token) {
                $token->delete();
            });

        }catch(Exception $e){

            $exception['code']      = config('messages.http_codes.server');
            $exception['error']     = true;
            //$exception['message'] = config('messages.error_messages.server_error');
            $exception['message']   = $e->getMessage();
            return response()->json($exception);
        }

    }
}
