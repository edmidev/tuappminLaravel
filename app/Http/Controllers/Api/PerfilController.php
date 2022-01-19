<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/** JWT */
use JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;

/** Models */
use App\Models\User;

class PerfilController extends Controller
{
    public function update(Request $request)
    {
        $auth = JWTAuth::parseToken()->authenticate();

        if($auth->email != $request->email){
            $validator = Validator::make($request->all() , [
                'email' => 'required|string|max:255|unique:users'             
            ]);
    
            if($validator->fails()){
                return response()->json(['error' => 'El email ya esta registrado.'], 200);            
            }
        }

        $user = User::find($auth->id);
        $user->name = $request->name;
        $user->email = $request->email;
        $user->telefono = $request->telefono;
        
        /** Actualizamos en password en caso de que se haya enviado algun dato */
        if($request->password){
            $user->password = bcrypt($request->password);
        }

        $user->save();

        $data['message'] = 'Perfil modificado';
        $data['user'] = $user;

        return response()->json(compact('data'), 201);
    }

    public function update_avatar(Request $request)
    {        
        $auth = JWTAuth::parseToken()->authenticate();
        $user = User::find($auth->id);

        $img = substr($request->image, strpos($request->image, ',') + 1);            
        $img = base64_decode($img);
        
        $img_extension = pathinfo($request->name, PATHINFO_EXTENSION);
        $filename = time() . Str::random(5) . '.' . $img_extension;

        if($auth->avatar){
            $caracter = strripos($user->avatar, '/');
            $name_bd = substr($user->avatar, $caracter + 1, strlen($user->avatar));            
            if(Storage::disk('public')->exists('avatar/' . $name_bd))
                Storage::disk('public')->delete('avatar/' . $name_bd);
        }            

        if(Storage::disk('public')->put('avatar/' . $filename, $img)){
            $route = config('app.url') . '/storage/avatar/';
            $user->avatar = $route . $filename;
            $user->save();

            $data['message'] = 'Foto de perfil actualizada';
            $data['avatar'] = $user->avatar;
            return response()->json(compact('data'), 201);
        }
    }

    public function get_perfil()
    {
        $auth = JWTAuth::parseToken()->authenticate();
        $user = $auth;

        return response()->json( compact('user'), 201);
    }
}
