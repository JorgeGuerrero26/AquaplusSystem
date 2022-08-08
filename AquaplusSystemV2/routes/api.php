<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

//Clientes
Route::post('/buscarClientes','App\Http\Controllers\Api\V1\ClienteController@buscarClientes');
Route::get('/listarClientes','App\Http\Controllers\Api\V1\ClienteController@listarClientes');
Route::post('/insertarClientes','App\Http\Controllers\Api\V1\ClienteController@insertarClientes');
Route::post('/actualizarClientes','App\Http\Controllers\Api\V1\ClienteController@actualizarClientes');
Route::post('/eliminarClientes','App\Http\Controllers\Api\V1\ClienteController@eliminarClientes');
//Proveedores
Route::get('/listarProveedores','App\Http\Controllers\Api\V1\ProveedorController@listarProveedores');
Route::post('/insertarProveedores','App\Http\Controllers\Api\V1\ProveedorController@insertarProveedores');
Route::post('/buscarProveedores','App\Http\Controllers\Api\V1\ProveedorController@buscarProveedores');
Route::post('/actualizarProveedores','App\Http\Controllers\Api\V1\ProveedorController@actualizarProveedores');
Route::post('/eliminarProveedores','App\Http\Controllers\Api\V1\ProveedorController@eliminarProveedores');
//Usuarios (Esta por ver si usaremos estas rutas o las que nos proporciona laravel)
Route::post('/buscarUsuarios','App\Http\Controllers\Api\V1\UsuarioController@buscarUsuarios');
Route::get('/listarUsuarios','App\Http\Controllers\Api\V1\UsuarioController@listarUsuarios');
Route::post('/insertarUsuarios','App\Http\Controllers\Api\V1\UsuarioController@insertarUsuarios');
Route::post('/actualizarUsuarios','App\Http\Controllers\Api\V1\UsuarioController@actualizarUsuarios');
Route::post('/eliminarUsuarios','App\Http\Controllers\Api\V1\UsuarioController@eliminarUsuarios');
//Materiales
Route::post('/buscarMateriales','App\Http\Controllers\Api\V1\MaterialController@buscarMateriales');
Route::get('/listarMateriales','App\Http\Controllers\Api\V1\MaterialController@listarMateriales');
Route::post('/insertarMateriales','App\Http\Controllers\Api\V1\MaterialController@insertarMateriales');
Route::post('/actualizarMateriales','App\Http\Controllers\Api\V1\MaterialController@actualizarMateriales');
Route::post('/eliminarMateriales','App\Http\Controllers\Api\V1\MaterialController@eliminarMateriales');
