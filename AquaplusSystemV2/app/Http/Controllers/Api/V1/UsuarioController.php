<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Usuario;
use Illuminate\Http\Request;

class UsuarioController extends Controller
{

    public function listarUsuarios()
    {
        try {
            $usuarios = Usuario::all();
            return response()->json(['data' => $usuarios,'status' => 'true'],200);            
        } catch (\Exception $e) {
            return response()->json(['data' => $e->getMessage(),'status' => 'false'], 500);
        } catch (\Throwable $th) {
            return response()->json(['data' => $th->getMessage(),'status' => 'false'], 500);
        }
    }


    public function insertarUsuarios(Request $request)
    {
        try {
            $request->validate([
                'tipo_usuario_id' => 'required',
                'nombre' => 'required|unique:usuarios',                
                'email' => 'required|email|unique:usuarios',
                'clave' => 'required',
            ]);
            $usuario = Usuario::create($request->all());
            return response()->json(['data' =>'Usuario registrado con exito','status' => 'true'],200);            
        } catch (\Exception $e) {
            return response()->json(['data' => $e->getMessage(),'status' => 'false'], 500);
        } catch (\Throwable $th) {
            return response()->json(['data' => $th->getMessage(),'status' => 'false'], 500);
        }
    }

    public function buscarUsuarios(Request $request)
    {
       
        try {
             //Validar que pueda buscar por nombre o email
            $request->validate([
                'nombre' => 'nullable|string',
                'email' => 'nullable|string',
            ]);
            //Si envio por el nombre buscar los nombres parecidos, si envio por email buscar los emails parecidos y si no envio nada traer todos los usuarios
            if ($request->nombre) {
                //Buscar nombres parecidos
                $usuarios = Usuario::where('nombre', 'like', '%' . $request->nombre . '%')->get();           
                //Si no lo encuentra
                if (count($usuarios) == 0) {
                    return response()->json(['data' => 'No se encontraron usuarios','status' => 'false'],404);
                }    
            } elseif ($request->email) {
                //Buscar emails parecidos
                $usuarios = Usuario::where('email', 'like', '%' . $request->email . '%')->get();   
                //Si no lo encuentra
                if (count($usuarios) == 0) {
                    return response()->json(['data' => 'No se encontraron usuarios','status' => 'false'],404);
                }
            } else {
                $usuarios = Usuario::all();
            }
            return response()->json(['data' => $usuarios,'status' => 'true'],200);
        } catch (\Throwable $th) {
            return response()->json(['data' => $th->getMessage(),'status' => 'false'], 500);
        }
    }

 
    public function actualizarUsuarios(Request $request)
    {
        try {
            $request->validate([              
                'id' =>'required',  
                'tipo_usuario_id' => 'required',
                'nombre' => 'required|unique:usuarios,nombre,' . $request->id,
                'email' => 'required|email|unique:usuarios,email,' . $request->id,
                'clave' => 'required',
            ]);
            $usuario = Usuario::find($request->id);
            $usuario->update($request->all());

            return response()->json(['data' =>'Usuario actualizado con exito','status' => 'true'],200);            
        } catch (\Exception $e) {
            return response()->json(['data' => $e->getMessage(),'status' => 'false'], 500);
        } catch (\Throwable $th) {
            return response()->json(['data' => $th->getMessage(),'status' => 'false'], 500);            
        } 
    }


    public function eliminarUsuarios(Request $request)
    {
        try {
            $request->validate([
                'id' => 'required',
            ]);
            $usuario = Usuario::find($request->id);
            $usuario->delete();
            return response()->json(['data' =>'Usuario eliminado con exito','status' => 'true'],200);            
        } catch (\Exception $e) {
            return response()->json(['data' => $e->getMessage(),'status' => 'false'], 500);
        } catch (\Throwable $th) {
            return response()->json(['data' => $th->getMessage(),'status' => 'false'], 500);
        }
    }      
    
    public function loginUsuario(Request $request){
        try {
            $request->validate([
                'email' => 'required|email',
                'clave' => 'required',
            ]);
            $usuario = Usuario::where('email', $request->email)->first();
            if ($usuario) {
                if ($usuario->clave == $request->clave) {
                    return response()->json(['data' => $usuario,'status' => 'true'],200);
                } else {
                    return response()->json(['data' => 'Clave incorrecta','status' => 'false'],401);
                }
            } else {
                return response()->json(['data' => 'Usuario no encontrado','status' => 'false'],404);
            }
        } catch (\Exception $e) {
            return response()->json(['data' => $e->getMessage(),'status' => 'false'], 500);
        } catch (\Throwable $th) {
            return response()->json(['data' => $th->getMessage(),'status' => 'false'], 500);
        }
    }
       
}
