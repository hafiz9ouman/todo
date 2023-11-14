<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Tymon\JWTAuth\Facades\JWTAuth;
use App\Models\User;
use DB;
use Mail;
use Auth;

class APIController extends Controller
{
    public function register(Request $req){
        ///Validations
        $validator = Validator::make($req->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|email',
            'password' => 'required|string|min:8',
        ]);
    
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()]);
        }

        ///Make Password Hash Form
        $req->password = Hash::make($req->password);

        ///Check User if it already Exist
        $user = DB::table('users')->where('email', $req->email)->first();
        if($user){
            return response()->json(['message' => 'User Already Exist']);
        }

        ////Verification Code for new User.
        $code = rand(1000, 9999);

        ///Add User to DB
        DB::table('users')->insert([
            'name'=>$req->name,
            'email'=>$req->email,
            'password'=>$req->password,
            'code'=>$code
        ]);
        $user = DB::table('users')->where('email', $req->email)->first();

        // Send Mail
        // Mail::send('emails.verify', ['user' => $user], function ($message) use ($user) {
        //     $message->to($user->email, $user->name)->subject('Verification Email');
        // });
        
        return response()->json(['message' => 'User Created Successfully and Verification Mail is Sent to user']);

    }

    public function login(Request $req){
        ///Validations
        $validator = Validator::make($req->all(), [
            'email' => 'required|email',
            'password' => 'required|string|min:8',
        ]);
    
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()]);
        }

        ///Check User
        $user = DB::table('users')->where('email', $req->email)->first();
        if(!$user){
            return response()->json(['message' => 'No User Found']);
        }
        if($user->is_varified==0){
            return response()->json(['message' => 'Account not Varified']);
        }

        // Attempt Authentication and Generate Token
        $token = auth('api')->attempt(['email' => $req->email, 'password' => $req->password]);

        if (!$token) {
            return response()->json(['message' => 'Authentication Failed']);
        }

        return response()->json([
            'message' => 'Login Successfully',
            'access_token' => $token,
            'User' => auth('api')->user(), 
        ]);

    }

    public function logout(){
        // check Auth
        if(!auth('api')->user()){
            return response()->json([
                'message' => 'User is not Authorized'
            ]);
        }
        else{
            // Logout user
            auth('api')->logout(); 
            return response()->json([
                'message' => 'User Logout Successfully'
            ]);
        }
    }

    public function create(Request $req){
        // Check Auth
        if(!auth('api')->user()){
            return response()->json([
                'message' => 'User is not Authorized'
            ]);
        }
        //Validation
        $validator = Validator::make($req->all(), [
            'title' => 'required',
            'description' => 'required',
        ]);
    
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()]);
        }

        //Insert Data in DB
        DB::table('todos')->insert([
            'title' => $req->title,
            'description' => $req->description,
            'user_id' => auth('api')->user()->id,
        ]);

        return response()->json([
            'message' => 'Todo Created Successfully'
        ]);
    }

    public function get_todos(){
        // Check User
        if(!auth('api')->user()){
            return response()->json([
                'message' => 'User is not Authorized'
            ]);
        }
        // get todo list of Authrized User from Database
        $todos = DB::table('todos')->where('user_id', auth('api')->user()->id)->get();

        return response()->json([
            'todos' => $todos
        ]);
    }

    public function update(Request $req){
        // check auth
        if(!auth('api')->user()){
            return response()->json([
                'message' => 'User is not Authorized'
            ]);
        }

        // validation
        $validator = Validator::make($req->all(), [
            'title' => 'required',
            'description' => 'required',
            'todo_id' => 'required',
        ]);
    
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()]);
        }

        // check the Existance of todo
        $exist =DB::table('todos')->where('id', $req->todo_id)->first();
        if(!$exist){
            return response()->json([
                'message' => 'Todo Not Found'
            ]);
        }

        if($exist->user_id != auth('api')->user()->id){
            return response()->json([
                'message' => 'You are not Authorized to Update this Todo.'
            ]);
        }

        // update todo
        DB::table('todos')->where('id', $req->todo_id)->update([
            'title' => $req->title,
            'description' => $req->description,
        ]);

        return response()->json([
            'message' => 'Todo Updated Successfully'
        ]);
    }

    public function delete($id){
        $todo_id = $id;
        // check auth
        if(!auth('api')->user()){
            return response()->json([
                'message' => 'User is not Authorized'
            ]);
        }

        // check the existance of todo in DB
        $exist =DB::table('todos')->where('id', $todo_id)->first();
        if(!$exist){
            return response()->json([
                'message' => 'Todo Not Found'
            ]);
        }

        if($exist->user_id != auth('api')->user()->id){
            return response()->json([
                'message' => 'You are not Authorized to Delete this Todo.'
            ]);
        }

        // Delete Todo
        DB::table('todos')->where('id', $todo_id)->delete();

        return response()->json([
            'message' => 'Todo Deleted Successfully'
        ]);
    }
}
