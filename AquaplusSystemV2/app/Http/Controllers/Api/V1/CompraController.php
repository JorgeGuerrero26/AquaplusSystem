<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Compra;
use App\Models\Detalle_compra;
use App\Models\Proveedor;
use App\Models\Usuario;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

class CompraController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function listarCompras()
    {
        try {
            $compras = Compra::all();
            //Agregar el nombre del proveedor y el nombre del usuario
            foreach ($compras as $compra) {
                $compra->proveedor = Proveedor::find($compra->proveedor_id)->nombre;
                $compra->usuario = Usuario::find($compra->usuario_id)->nombre;
            }


            //Recorrer cada compra para obtener el detalle de la compra


            foreach ($compras as $compra) {
                $compra->detalle_compra = Detalle_compra::where('compra_id', $compra->id)->get();
                //Calcular el total de la compra
                $total = 0;
                foreach ($compra->detalle_compra as $detalle) {
                    $total += $detalle->precio_unitario * $detalle->cantidad_comprada;
                }
                $compra->total_compra = $total;
            }
            return response()->json(['data' => $compras, 'status' => 'true'], 200);
        } catch (\Exception $e) {
            return response()->json(['data' => $e->getMessage(), 'status' => 'false'], 500);
        } catch (\Throwable $th) {
            return response()->json(['data' => $th->getMessage(), 'status' => 'false'], 500);
        }
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function insertarCompras(Request $request)
    {
        try {
            //Validar que envie al menos un caracter
            $request->validate([
                'fecha' => 'required',
                'observacion' => 'nullable',
                'proveedor_id' => 'required',
                'usuario_id' => 'required',
                'detalle_compra' => 'required',
            ]);
            //Desactivar autocommit
            DB::beginTransaction();
            $compra = new Compra();
            $compra->fecha = $request->fecha;
            $compra->observacion = $request->observacion;
           
            $compra->proveedor_id = $request->proveedor_id;
            $compra->usuario_id = $request->usuario_id;
            $compra->save();
            $detalle_compra = $request->detalle_compra;
            $detalle_compra = json_decode($detalle_compra);

            //Recorrer cada detalle de la compra para insertarlo en la tabla detalle_compra
            foreach ($detalle_compra as $detalle) {
                $detalle_compra = new Detalle_compra();
                $detalle_compra->compra_id = $compra->id;
                $detalle_compra->material_id = $detalle->material_id;
                $detalle_compra->cantidad_comprada = $detalle->cantidad_comprada;
                $detalle_compra->precio_unitario = $detalle->precio_unitario;
                $detalle_compra->save();
            }
        
            DB::commit();
            return response()->json(['data' => 'Compra insertada correctamente', 'status' => 'true'], 200);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json(['data' => $e->getMessage(), 'status' => 'false'], 500);
        } catch (\Throwable $th) {
            DB::rollback();
            return response()->json(['data' => $th->getMessage(), 'status' => 'false'], 500);
        }

    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Compra  $compra
     * @return \Illuminate\Http\Response
     */
    public function buscarCompras_Id(Request $request)
    {        
        try {
            $request->validate([
                'id' => 'required',
            ]);
            $compra = Compra::find($request->id);
            $compra->detalle_compra = Detalle_compra::where('compra_id', $compra->id)->get();
            //Calcular el total de la compra
            $total = 0;
            foreach ($compra->detalle_compra as $detalle) {
                $total += $detalle->precio_unitario * $detalle->cantidad_comprada;
            }
            $compra->total_compra = $total;
            return response()->json(['data' => $compra, 'status' => 'true'], 200);
        } catch (\Exception $e) {
            return response()->json(['data' => $e->getMessage(), 'status' => 'false'], 500);
        } catch (\Throwable $th) {
            return response()->json(['data' => $th->getMessage(), 'status' => 'false'], 500);
        }
    }

    public function buscarComprasPorFechas(Request $request){
        try {
            $request->validate([
                'fecha_inicio' => 'required',
                'fecha_fin' => 'required',
            ]);
            $compras = Compra::whereBetween('fecha', [$request->fecha_inicio, $request->fecha_fin])->get();
            //Recorrer cada compra para obtener el detalle de la compra
            foreach ($compras as $compra) {
                $compra->detalle_compra = Detalle_compra::where('compra_id', $compra->id)->get();
                //Calcular el total de la compra
                $total = 0;
                foreach ($compra->detalle_compra as $detalle) {
                    $total += $detalle->precio_unitario * $detalle->cantidad_comprada;
                }
                $compra->total_compra = $total;
            }
            return response()->json(['data' => $compras, 'status' => 'true'], 200);
        } catch (\Exception $e) {
            return response()->json(['data' => $e->getMessage(), 'status' => 'false'], 500);
        } catch (\Throwable $th) {
            return response()->json(['data' => $th->getMessage(), 'status' => 'false'], 500);
        }
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Compra  $compra
     * @return \Illuminate\Http\Response
     */
    public function actualizarCompras(Request $request)
    {
        try {
            //Validar que envie al menos un caracter
            $request->validate([
                'id' => 'required',
                'fecha' => 'required',
                'observacion' => 'nullable',
                'proveedor_id' => 'required',
                'usuario_id' => 'required',
                'detalle_compra' => 'required',
            ]);
            //Desactivar autocommit
            DB::beginTransaction();

            $compra = Compra::find($request->id);
            $compra->fecha = $request->fecha;
            $compra->observacion = $request->observacion;
            $compra->proveedor_id = $request->proveedor_id;
            $compra->usuario_id = $request->usuario_id;
            $compra->save();
            $detalle_compra = $request->detalle_compra;
            $detalle_compra = json_decode($detalle_compra);
            //Recorrer cada detalle de la compra para actualizarlo en la tabla detalle_compra
            Detalle_compra::where('compra_id',$compra->id)->delete();
            foreach ($detalle_compra as $detalle) {
                $detalle_compra = new Detalle_compra();
                $detalle_compra->compra_id = $compra->id;
                $detalle_compra->material_id = $detalle->material_id;
                $detalle_compra->cantidad_comprada = $detalle->cantidad_comprada;
                $detalle_compra->precio_unitario = $detalle->precio_unitario;
                $detalle_compra->save();
            }
            DB::commit();
            return response()->json(['data' => 'Compra actualizada correctamente', 'status' => 'true'], 200);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json(['data' => $e->getMessage(), 'status' => 'false'], 500);
        } catch (\Throwable $th) {
            DB::rollback();
            return response()->json(['data' => $th->getMessage(), 'status' => 'false'], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Compra  $compra
     * @return \Illuminate\Http\Response
     */
    public function eliminarCompras(Request $request)
    {
        //Eliminar antes de la compra, todos los detalles de compra
        try {
            $request->validate([
                'id' => 'required',
            ]);
            $compra = Compra::find($request->id);
            Detalle_compra::where('compra_id', $compra->id)->delete();
            $compra->delete();
            return response()->json(['data' => 'Compra eliminada correctamente', 'status' => 'true'], 200);
        } catch (\Exception $e) {
            return response()->json(['data' => $e->getMessage(), 'status' => 'false'], 500);
        } catch (\Throwable $th) {
            return response()->json(['data' => $th->getMessage(), 'status' => 'false'], 500);
        }
    }

    public function buscarComprasDeUnProveedor(Request $request){
        try {
            $request->validate([
                'proveedor_id' => 'required',
            ]);
            $compras = Compra::where('proveedor_id', $request->proveedor_id)->get();
            //Recorrer cada compra para obtener el detalle de la compra
            foreach ($compras as $compra) {
                $compra->detalle_compra = Detalle_compra::where('compra_id', $compra->id)->get();
                //Calcular el total de la compra
                $total = 0;
                foreach ($compra->detalle_compra as $detalle) {
                    $total += $detalle->precio_unitario * $detalle->cantidad_comprada;
                }
                $compra->total_compra = $total;
            }
            return response()->json(['data' => $compras, 'status' => 'true'], 200);
        } catch (\Exception $e) {
            return response()->json(['data' => $e->getMessage(), 'status' => 'false'], 500);
        } catch (\Throwable $th) {
            return response()->json(['data' => $th->getMessage(), 'status' => 'false'], 500);
        }
    }
}
