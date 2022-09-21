<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Venta;
use App\Models\Detalle_venta;
use App\Models\Entrega;
use App\Models\Cliente;
use App\Models\Material;
use App\Models\Usuario;

use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

class VentaController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function listarVentas()
    {
        try {
            $ventas = Venta::all();
            //Agregar el nombre del cliente y el nombre del usuario
            foreach ($ventas as $venta) {
                $venta->cliente = Cliente::find($venta->cliente_id)->nombre;
                $venta->usuario = Usuario::find($venta->usuario_id)->nombre;
                $venta->entrega = Entrega::find($venta->entrega_id)->zona_entrega;
            }


            //Recorrer cada venta para obtener el detalle de la venta
            foreach ($ventas as $venta) {
                $venta->detalle_venta = Detalle_venta::where('venta_id', $venta->id)->get();
                //Calcular el total de la venta
                $total = 0;
                foreach ($venta->detalle_venta as $detalle) {
                    $total += $detalle->precio_unitario * $detalle->cantidad_entregada;
                }
                $venta->total_venta = $total;
                //Agregar el nombre del material
                foreach ($venta->detalle_venta as $detalle) {
                    $detalle->material = Material::find($detalle->material_id)->descripcion;
                }
            }
            return response()->json(['data' => $ventas, 'status' => 'true'], 200);
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
    public function insertarVentas(Request $request)
    {
        try {
            //Validar que envie al menos un caracter
            $request->validate([
                'fecha' => 'required',
                'observacion' => 'nullable',
                'numero_guia' => 'required',
                'cliente_id' => 'required',
                'usuario_id' => 'required',
                'detalle_venta' => 'required',
                'entrega_id' => 'required'
            ]);
            //Desactivar autocommit
            DB::beginTransaction();
            $venta = new Venta();
            $venta->fecha = $request->fecha;
            $venta->observacion = $request->observacion;
            $venta->numero_guia = $request->numero_guia;
            $venta->cliente_id = $request->cliente_id;
            $venta->usuario_id = $request->usuario_id;
            $venta->entrega_id = $request->entrega_id;
            $venta->save();
            $detalle_venta = $request->detalle_venta;
            $detalle_venta = json_decode($detalle_venta);


            //Recorrer el json de detalle_Venta y validar que envie materiaul_id, que envie el precio_unitario y sea un numero, que envie la cantidad_entregada y sea un numero entero y que envie la cantidad recibida y sea un numero entero
            foreach ($detalle_venta as $detalle) {
                if (!is_numeric($detalle->material_id)) {
                    return response()->json(['data' => 'El material id debe ser un numero', 'status' => 'false'], 500);
                }
                if (!is_numeric($detalle->precio_unitario)) {
                    return response()->json(['data' => 'El precio unitario debe ser un numero', 'status' => 'false'], 500);
                }
                if (!is_int($detalle->cantidad_entregada)) {
                    //validar que la cantidad entregada sea un numero entero                    
                    return response()->json(['data' => 'La cantidad entregada debe ser un numero entero', 'status' => 'false'], 500);
                }
                if (!is_int($detalle->cantidad_recibida)) {
                    return response()->json(['data' => 'La cantidad recibida debe ser un numero entero', 'status' => 'false'], 500);
                }
            }

            //Insertar el detalle de la venta        
            foreach ($detalle_venta as $detalle) {
                $objdetalle_venta = new Detalle_venta();
                $objdetalle_venta->venta_id = $venta->id;
                $objdetalle_venta->material_id = $detalle->material_id;
                $objdetalle_venta->precio_unitario = $detalle->precio_unitario;
                $objdetalle_venta->cantidad_entregada = $detalle->cantidad_entregada;
                $objdetalle_venta->cantidad_recibida = $detalle->cantidad_recibida;
                $objdetalle_venta->save();
            }
            //Hacer commit
            DB::commit();

            return response()->json(['data' => 'Venta insertada', 'status' => 'true'], 200);
        } catch (\Exception $e) {
            //Hacer rollback
            DB::rollback();
            return response()->json(['data' => $e->getMessage(), 'status' => 'false'], 500);
        } catch (\Throwable $th) {
            //Hacer rollback
            DB::rollback();
            return response()->json(['data' => $th->getMessage(), 'status' => 'false'], 500);
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Venta  $venta
     * @return \Illuminate\Http\Response
     */
    public function buscarVentas_Id(Request $request) //Se llamara a esta funcion cuando le de a "ver mas"
    {
        try {
            $venta = Venta::find($request->id);
            $venta->detalle_venta = Detalle_venta::where('venta_id', $venta->id)->get();
            //Agregar el nombre del cliente y el nombre del usuario
            $venta->cliente = Cliente::find($venta->cliente_id)->nombre;
            $venta->usuario = Usuario::find($venta->usuario_id)->nombre;
            $venta->entrega = Entrega::find($venta->entrega_id)->zona_entrega;
            //Calcular el total de la venta
            $total = 0;
            foreach ($venta->detalle_venta as $detalle) {
                $total += $detalle->precio_unitario * $detalle->cantidad_entregada;
            }
            $venta->total_venta = $total;
            //Agregar el nombre del material
            foreach ($venta->detalle_venta as $detalle) {
                $detalle->material = Material::find($detalle->material_id)->descripcion;
            }
            return response()->json(['data' => $venta, 'status' => 'true'], 200);
        } catch (\Exception $e) {
            return response()->json(['data' => $e->getMessage(), 'status' => 'false'], 500);
        } catch (\Throwable $th) {
            return response()->json(['data' => $th->getMessage(), 'status' => 'false'], 500);
        }
    }

    public function buscarVentasPorFechas(Request $request) //Preguntar si se deberia buscar tambien por cliente
    {
        try {
            //Validar que envie un rango de fechas y que sean fechas
            $request->validate([
                'fecha_inicio' => 'nullable|date',
                'fecha_fin' => 'nullable|date',
            ]);
            $fecha_inicio = $request->fecha_inicio;
            $fecha_fin = $request->fecha_fin;
            //Validar que haya enviado la fecha inicio y fecha fin y si no lo envió traer todas las fechas
            if ($fecha_inicio == '' && $fecha_fin == '') {
                $ventas = Venta::all();
                //Recorrer cada venta para obtener el detalle de la venta
                foreach ($ventas as $venta) {
                    $venta->detalle_venta = Detalle_venta::where('venta_id', $venta->id)->get();
                    //Calcular el total de la venta
                    $total = 0;
                    foreach ($venta->detalle_venta as $detalle) {
                        $total += $detalle->precio_unitario * $detalle->cantidad_entregada;
                    }
                    $venta->total_venta = $total;
                    //Agregar el nombre del cliente y el nombre del usuario
                    $venta->cliente = Cliente::find($venta->cliente_id)->nombre;
                    $venta->usuario = Usuario::find($venta->usuario_id)->nombre;
                    $venta->entrega = Entrega::find($venta->entrega_id)->zona_entrega;
                    //Agregar el nombre del material
                    foreach ($venta->detalle_venta as $detalle) {
                        $detalle->material = Material::find($detalle->material_id)->descripcion;
                    }
                }
            } else {
                if ($fecha_inicio == '') {
                    //Validar que la fecha fin sea mayor a la fecha inicio
                    if ($fecha_fin < $fecha_inicio) {
                        return response()->json(['data' => 'La fecha final debe ser mayor a la fecha de inicio', 'status' => 'false'], 500);
                    }
                    //Crear una fecha de inicio que sea igual a la fecha de la primera venta
                    $fecha_inicio = Venta::orderBy('fecha', 'asc')->first()->fecha;
                    return $this->buscarFechas($fecha_inicio, $fecha_fin);
                } else {
                    if ($fecha_fin == '') {
                        //Crear una fecha de fin que sea igual a la fecha de la ultima venta
                        $fecha_fin = Venta::orderBy('fecha', 'desc')->first()->fecha;
                        return $this->buscarFechas($fecha_inicio, $fecha_fin);
                    } else {
                        //Validar que la fecha fin sea mayor a la fecha inicio
                        if ($fecha_fin < $fecha_inicio) {
                            return response()->json(['data' => 'La fecha final debe ser mayor a la fecha de inicio', 'status' => 'false'], 500);
                        }                     
                        return $this->buscarFechas($fecha_inicio, $fecha_fin);
                    }
                }
            }
            return response()->json(['data' => $ventas, 'status' => 'true'], 200);
        } catch (\Exception $e) {
            return response()->json(['data' => $e->getMessage(), 'status' => 'false'], 500);
        } catch (\Throwable $th) {
            return response()->json(['data' => $th->getMessage(), 'status' => 'false'], 500);
        }
    }

    //Modularizando busqueda por fechas
    public function buscarFechas($fecha_inicio, $fecha_fin)
    {
        $ventas = Venta::whereBetween('fecha', [$fecha_inicio, $fecha_fin])->get();
        
        //Recorrer cada venta para obtener el detalle de la venta
        foreach ($ventas as $venta) {
            $venta->detalle_venta = Detalle_venta::where('venta_id', $venta->id)->get();
            //Calcular el total de la venta
            $total = 0;
            foreach ($venta->detalle_venta as $detalle) {
                $total += $detalle->precio_unitario * $detalle->cantidad_entregada;
            }
            $venta->total_venta = $total;
            //Agregar el nombre del cliente y el nombre del usuario
            $venta->cliente = Cliente::find($venta->cliente_id)->nombre;
            $venta->usuario = Usuario::find($venta->usuario_id)->nombre;
            $venta->entrega = Entrega::find($venta->entrega_id)->zona_entrega;
            //Agregar el nombre del material
            foreach ($venta->detalle_venta as $detalle) {
                $detalle->material = Material::find($detalle->material_id)->descripcion;
            }
        }
        return response()->json(['data' => $ventas, 'status' => 'true'], 200);
    }




    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Venta  $venta
     * @return \Illuminate\Http\Response
     */
    public function actualizarVentas(Request $request)
    {
        try {
            //Validar que envie un rango de fechas y que sean fechas
            $request->validate([
                'id' => 'required|integer',
                'numero_guia' => 'required|string',
                'cliente_id' => 'required|integer',
                'usuario_id' => 'required|integer',
                'observacion' => 'nullable|string',
                'detalle_venta' => 'required|string',
                'entrega_id' => 'required|integer',
            ]);
            $venta = Venta::find($request->id);
            $venta->numero_guia = $request->numero_guia;
            $venta->cliente_id = $request->cliente_id;
            $venta->usuario_id = $request->usuario_id;
            $venta->observacion = $request->observacion;
            $venta->save();
            $detalle_venta = $request->detalle_venta;
            $detalle_venta = json_decode($detalle_venta);
            //Recorrer el json de detalle_venta y validar que no hayan valores vacios        
            foreach ($detalle_venta as $detalle) {
                if (!is_numeric($detalle->material_id)) {
                    return response()->json(['data' => 'El material id debe ser un numero', 'status' => 'false'], 500);
                }
                if (!is_numeric($detalle->precio_unitario)) {
                    return response()->json(['data' => 'El precio unitario debe ser un numero', 'status' => 'false'], 500);
                }
                if (!is_int($detalle->cantidad_entregada)) {
                    //validar que la cantidad entregada sea un numero entero                    
                    return response()->json(['data' => 'La cantidad entregada debe ser un numero entero', 'status' => 'false'], 500);
                }
                if (!is_int($detalle->cantidad_recibida)) {
                    return response()->json(['data' => 'La cantidad recibida debe ser un numero entero', 'status' => 'false'], 500);
                }
            }
            //Eliminar el detalle de la venta
            Detalle_venta::where('venta_id', $venta->id)->delete();
            //Insertar el detalle de la venta        
            foreach ($detalle_venta as $detalle) {
                $objdetalle_venta = new Detalle_venta();
                $objdetalle_venta->venta_id = $venta->id;
                $objdetalle_venta->material_id = $detalle->material_id;
                $objdetalle_venta->precio_unitario = $detalle->precio_unitario;
                $objdetalle_venta->cantidad_entregada = $detalle->cantidad_entregada;
                $objdetalle_venta->cantidad_recibida = $detalle->cantidad_recibida;
                $objdetalle_venta->save();
            }
            //Hacer commit
            DB::commit();
            return response()->json(['data' => 'Venta actualizada', 'status' => 'true'], 200);
        } catch (\Exception $e) {
            return response()->json(['data' => $e->getMessage(), 'status' => 'false'], 500);
        } catch (\Throwable $th) {
            return response()->json(['data' => $th->getMessage(), 'status' => 'false'], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Venta  $venta
     * @return \Illuminate\Http\Response
     */
    public function eliminarVentas(Request $request)
    {
        try {
            $request->validate([
                'id' => 'required|integer',
            ]);
            $venta = Venta::find($request->id);
            //Eliminar el detalle de venta
            Detalle_venta::where('venta_id', $venta->id)->delete();
            $venta->delete();
            return response()->json(['data' => 'Venta eliminada', 'status' => 'true'], 200);
        } catch (\Exception $e) {
            return response()->json(['data' => $e->getMessage(), 'status' => 'false'], 500);
        } catch (\Throwable $th) {
            return response()->json(['data' => $th->getMessage(), 'status' => 'false'], 500);
        }
    }

    public function buscarVentasDeUnCliente(Request $request)
    {
        try {
            $request->validate([
                'cliente_id' => 'required|integer',
            ]);
            $ventas = Venta::where('cliente_id', $request->cliente_id)->get();
            //Recorrer cada venta para obtener el detalle de la venta
            foreach ($ventas as $venta) {
                $venta->detalle_venta = Detalle_venta::where('venta_id', $venta->id)->get();
                //Calcular el total de la venta
                $total = 0;
                foreach ($venta->detalle_venta as $detalle) {
                    $total += $detalle->precio_unitario * $detalle->cantidad_entregada;
                }
                $venta->total_venta = $total;
                //Agregar el nombre del usuario y el nombre del cliente
                $venta->usuario = Usuario::find($venta->usuario_id)->nombre;
                $venta->cliente = Cliente::find($venta->cliente_id)->nombre;
                $venta->entrega = Entrega::find($venta->entrega_id)->zona_entrega;
                //Agregar el nombre del material
                foreach ($venta->detalle_venta as $detalle) {
                    $detalle->material = Material::find($detalle->material_id)->descripcion;
                }
            }
            return response()->json(['data' => $ventas, 'status' => 'true'], 200);
        } catch (\Exception $e) {
            return response()->json(['data' => $e->getMessage(), 'status' => 'false'], 500);
        } catch (\Throwable $th) {
            return response()->json(['data' => $th->getMessage(), 'status' => 'false'], 500);
        }
    }
}
