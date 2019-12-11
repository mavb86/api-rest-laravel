<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Post;
use App\Helpers\JwtAuth;

class PostController extends Controller
{
    
    public function __construct(){
        $this->middleware('api.auth',['except'=>[
            'index','show','getImage',
            'getPostsByCategory','getPostsByUser'
            ]]);
    }
    
    public function index(Request $request){
        $posts = Post::all()->load('category');
        
        return response()->json([
            'code' => 200,
            'status' => 'success',
            'posts'  => $posts
        ],200);
    }
    
    public function show($id){
        
        $post = Post::find($id)->load('category')
                               ->load('user');
        
        if(is_object($post)){
            $data = [
                'code' => 200,
                'status' => 'success',
                'posts'  => $post
            ];
        }else{
            $data = [
                'code' => 404,
                'status' => 'error',
                'message'  => 'La entrada no existe'
            ];
        }
        
        return response()->json($data,$data['code']);
    }
    
    public function store(Request $request) {
        //Recoger datos por post
        $json = $request->input('json', null);
        $params = json_decode($json);
        $params_array = json_decode($json, true);

        if (!empty($params_array)) {
            //Conseguir usuario identificado
            $user = $this->getIdentity($request);

            //Validar los datos
            $validate = \Validator::make($params_array, [
                        'title' => 'required',
                        'content' => 'required',
                        'category_id' => 'required',
                        'image' => 'required'
            ]);
            if ($validate->fails()) {
                $data = [
                    'code' => 400,
                    'status' => 'error',
                    'message' => 'No se ha guardado el post, faltan datos'
                ];
            } else {
                //Guardar el post
                $post = new Post();
                $post->user_id = $user->sub;
                $post->category_id = $params->category_id;
                $post->title = $params->title;
                $post->content = $params->content;
                $post->image = $params->image;
                $post->save();
                
                $data = [
                    'code' => 200,
                    'status' => 'success',
                    'post' => $post
                ];
            }
        } else {
            $data = [
                'code' => 400,
                'status' => 'error',
                'message' => 'No ha enviado datos'
            ];
        }

        //Devolver respuesta
        return response()->json($data,$data['code']);
    }
    public function update($id, Request $request) {
        //Recoger los datos por post
        $json = $request->input('json', null);
        $params = json_decode($json);
        $params_array = json_decode($json, true);

        if (!empty($params_array)) {
            //Validar los datos
            $validate = \Validator::make($params_array, [
                        'title' => 'required',
                        'content' => 'required',
                        'category_id' => 'required'
            ]);

            if ($validate->fails()) {
                $data = [
                    'code' => 400,
                    'status' => 'error',
                    'message' => 'No se ha actualizado el post, faltan datos'
                ];
            } else {
                //Eliminar lo que no queremos actualizar
                unset($params_array['id']);
                unset($params_array['user_id']);
                unset($params_array['created_at']);
                unset($params_array['user']);
                
                //Conseguir usuario identificado
                $user = $this->getIdentity($request);

                //Buscar el registro
                $post = Post::where('id',$id)
                            ->where('user_id',$user->sub)
                            ->first();
                
                if(!empty($post) && is_object($post)){
                    //Actualizar el registro
                    $post->update($params_array);
                    
                    $data = [
                        'code' => 200,
                        'status' => 'success',
                        'post'    => $post
                    ];
                }else{
                    $data = [
                        'code' => 400,
                        'status' => 'error',
                        'message' => 'No se ha encontrado el post que quiere modificar'
                    ];
                }
                /*$where = [
                    'id' => $id,
                    'user_id' => $user->sub
                ];
                
                $post = Post::updateOrCreate($where,$params_array);                           
                */
                
            }
        } else {
            $data = [
                'code' => 400,
                'status' => 'error',
                'message' => 'Datos enviados incorrectamente'
            ];
        }
        //Devolver la respuesta
        return response()->json($data, $data['code']);
    }
    
    public function destroy($id, Request $request) {
        
        //Conseguir usuario identificado
        $user = $this->getIdentity($request);

        //Conseguir el registro
        $post = Post::where('id',$id)
                    ->where('user_id',$user->sub)
                    ->first();
        
        if (!empty($post)) {
            //Borrarlo
            $post->delete();

            $data = [
                'code' => 200,
                'status' => 'success',
                'post' => $post
            ];
        } else {
            $data = [
                'code' => 404,
                'status' => 'success',
                'message' => 'El post que intenta borrar no existe'
            ];
        }
        //Devolver la respuesta
        return response()->json($data, $data['code']);
    }
    
    private function getIdentity(Request $request){
        $jwtAuth = new jwtAuth();
        $token = $request->header('Authorization', null);
        $user = $jwtAuth->checkToken($token, true);
        
        return $user;
    }
    
    public function upload(Request $request) {
        //Recoger la imagen de la petición
        $image = $request->file('file0');

        //Validar la imagen
        $validate = \Validator::make($request->all(), [
                    'file0' => 'required|image|mimes:jpg,jpeg,png,gif'
        ]);

        //Guardar la imagen en disco
        if (!$image || $validate->fails()) {
            $data = [
                'code' => 404,
                'status' => 'error',
                'message' => 'Error al subir la imagen'
            ];
        } else {
            $image_name = time().$image->getClientOriginalName();
            \Storage::disk('images')->put($image_name, \File::get($image));
            $data = [
                'code' => 200,
                'status' => 'success',
                'image' => $image_name
            ];
        }
        //Devolver respuesta
        return response()->json($data, $data['code']);
    }
    
    public function getImage($filename) {
        //Comprobar si existe el fichero
        $isset = \Storage::disk('images')->exists($filename);
               
        if ($isset) {
            //Conseguir la imagen
            $file = \Storage::disk('images')->get($filename);
            //Devolver la imagen
            return new Response($file, 200);
        } else {
            $data = [
                'code' => 404,
                'status' => 'error',
                'message' => 'La imagen no existe'
            ];
        }
        //Devolver respuesta
        return response()->json($data, $data['code']);
    }
    
    public function getPostsByCategory($id) {
        
        $posts = Post::where('category_id', $id)->get();
        
        return response()->json([
                    'status' => 'success',
                    'posts' => $posts
                        ], 200);
    }
    
     public function getPostsByUser($id) {
        
        $posts = Post::where('user_id', $id)->get();
        
        return response()->json([
                    'status' => 'success',
                    'posts' => $posts
                        ], 200);
    }

}
