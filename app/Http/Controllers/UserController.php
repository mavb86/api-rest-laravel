<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\User;

class UserController extends Controller
{
    public function pruebas(Request $request){
        return "Acción de pruebas de USER-CONTROLLER";
    }
    
    public function register(Request $request){
        
        //Recoger los datos del usuario con POST
        $json = $request->input('json',null);
        
        
        //Decodificar JSON
        $params = json_decode($json); //objeto
        $params_array = json_decode($json,true); //array
        
        
        if(!empty($params) && !empty($params_array)) {
            //Limpiar datos
            $params_array = array_map('trim', $params_array);

            //Validar datos
            $validate = \Validator::make($params_array, [
                        'name' => 'required|alpha',
                        'surname' => 'required|alpha',
                        'email' => 'required|email|unique:users',
                        'password' => 'required'
            ]);

            if ($validate->fails()) {

                $data = array(
                    'status' => 'error',
                    'code' => 404,
                    'mensaje' => 'El usuario no se ha creado',
                    'errors' => $validate->errors()
                );
            } else {
                //Cifrar la contraseña
                $pwd = hash('sha256',$params->password);
                
                //Comprobar si el usuario existe
                $user = new User();
                $user->name = $params_array['name'];
                $user->surname = $params_array['surname'];
                $user->email = $params_array['email'];
                $user->password = $pwd;
                $user->role     = 'ROLE_USER';
                
                //Guardar el usuario
                $user->save();
        
                //Crear el usuario
                $data = array(
                    'status' => 'success',
                    'code' => 200,
                    'mensaje' => 'El usuario se ha creado correctamente',
                    'user' => $user
                );
            }
        }else{
            $data = array(
                    'status' => 'error',
                    'code' => 404,
                    'mensaje' => 'Los datos enviados no son correctos'
                );
        }

        return response()->json($data,$data['code']);
    }
    
    public function login(Request $request){
        $jwtAuth = new \JwtAuth();
        
        //Recibir post
        $json = $request->input('json',null);
        $params = json_decode($json);
        $params_array = json_decode($json,true);
        
        
        //Validar datos
        $validate = \Validator::make($params_array, [
                        'email' => 'required|email',
                        'password' => 'required'
            ]);

            if ($validate->fails()) {

                $data = array(
                    'status' => 'error',
                    'code' => 404,
                    'mensaje' => 'El usuario no se ha podido loguear',
                    'errors' => $validate->errors()
                );
            }else{
                //Cifrar contraseña
                $pwd = hash('sha256',$params->password);
                
                //Devolver token
                $signup = $jwtAuth->signup($params->email,$pwd);
                if(!empty($params->getToken)){
                    $signup = $jwtAuth->signup($params->email,$pwd,true);
                }
            }
       
        
        return response()->json($signup,200);
    }
    
    public function update(Request $request){
        
        //Comprobar si el usuario esta identificado
        $token = $request->header('Authorization');
        $jwtAuth = new \JwtAuth();
        $checktoken = $jwtAuth->checkToken($token);

        //Recoger los datos por post
        $json = $request->input('json',null);
        $params_array = json_decode($json,true);

        if ($checktoken && !empty($params_array)){
            
            //Sacar usuario identificado
            $user = $jwtAuth->checkToken($token,true);

            //Validar los datos
            $validate = \Validator::make($params_array,[
                'name' => 'required|alpha',
                'surname' => 'required|alpha',
                'email' => 'required|email|unique:users'.$user->sub
            ]);
            
            //Quitar los campos que no quiero actualizar
            unset($params_array['id']);
            unset($params_array['role']);
            unset($params_array['password']);
            unset($params_array['created_at']);
            unset($params_array['remember_token']);
            
                       
            //Actualizar la BD
            $user_update = User::where('id',$user->sub)->update($params_array);
            
            //Devolver array con el resultado
            $data = array(
                'code'    =>200,
                'status'  =>'success',
                'user' =>$user,
                'change'=>$params_array
            );
            
        }else{
            
            $data = array(
                'code'    =>400,
                'status'  =>'error',
                'message' =>'El usuario no esta identificado'
            );
        }
        return response()->json($data,$data['code']);
    }
    
    public function upload(Request $request) {
        //Recoger los datos de la petición
        $image = $request->file('file0');
        
        //Validar que es una imagen
        $validate = \Validator::make($request->all(),[
            'file0' => 'required|image|mimes:jpg,jpeg,png,gif',  
        ]);

        //Guardar imagen
        
        if (!$image || $validate->fails()) {
            $data = array(
                'code' => 400,
                'status' => 'error',
                'message' => 'Error al subir imagen',
            );
        } else {

            if ($image) {
                $image_name = time() . $image->getClientOriginalName();
                \Storage::disk('users')->put($image_name, \File::get($image));
                $data = array(
                    'code' => 200,
                    'status' => 'success',
                    'image' => $image_name,
                );
            }
        }

        return response()->json($data, $data['code']);
    }
    
    public function getImage($filename) {
        $isset = \Storage::disk('users')->exists($filename);

        if ($isset) {
            $file = \Storage::disk('users')->get($filename);
            return new Response($file, 200);
        } else {
            $data = array(
                'code' => 404,
                'status' => 'error',
                'message' => 'La imagen no existe'
            );
            return response()->json($data, $data['code']);
        }
    }
    
    public function detail($id) {
        $user = User::find($id);
        if (is_object($user)) {
            $data = array(
                'code' => 200,
                'status' => 'success',
                'user' => $user
            );
        } else {
            $data = array(
                'code' => 404,
                'status' => 'error',
                'message' => 'El usuario no existe'
            );
        }
        return response()->json($data, $data['code']);
    }

}
