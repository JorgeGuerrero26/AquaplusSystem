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
    //Cambiado
    public function listarVentas()
    {
        try {
            //Traer las ultimas 100 ventas con todos los datos de las ventas ademas hacer inner join con cliente para saber el nombre del cliente, hacer inner join con usuario para saber el nombre del usuario y hacer inner join con entrega para saber la zona_entrega
            $ventas = DB::select('SELECT TOP 100 V.*, C.nombre as cliente, U.nombre as usuario, E.zona_entrega FROM ventas V INNER JOIN clientes C ON V.cliente_id = C.id INNER JOIN usuarios U ON V.usuario_id = U.id INNER JOIN entregas E ON V.entrega_id = E.id ORDER BY V.id DESC');
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
                $detalle->material_id = (int) $detalle->material_id;

            }

            $venta->cliente_id = (int) $venta->cliente_id;
            $venta->usuario_id = (int) $venta->usuario_id;
            $venta->entrega_id = (int) $venta->entrega_id;

            return response()->json(['data' => $venta, 'status' => 'true'], 200);
        } catch (\Exception $e) {
            return response()->json(['data' => $e->getMessage(), 'status' => 'false'], 500);
        } catch (\Throwable $th) {
            return response()->json(['data' => $th->getMessage(), 'status' => 'false'], 500);
        }
    }

    //Cambiado
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
                return $this->listarVentas();
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
        } catch (\Exception $e) {
            return response()->json(['data' => $e->getMessage(), 'status' => 'false'], 500);
        } catch (\Throwable $th) {
            return response()->json(['data' => $th->getMessage(), 'status' => 'false'], 500);
        }
    }

    //Modularizando busqueda por fechas
    public function buscarFechas($fecha_inicio, $fecha_fin)
    {
        $ventas = DB::select('SELECT TOP 100 V.*, C.nombre as cliente, U.nombre as usuario, E.zona_entrega FROM ventas V INNER JOIN clientes C ON V.cliente_id = C.id INNER JOIN usuarios U ON V.usuario_id = U.id INNER JOIN entregas E ON V.entrega_id = E.id WHERE V.fecha BETWEEN ? AND ? ORDER BY V.id DESC', [$fecha_inicio, $fecha_fin]);
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

    //Cambiado
    public function buscarVentasDeUnCliente(Request $request)
    {
        try {
            $request->validate([
                'cliente_id' => 'nullable|integer',
            ]);
            if ($request->has('cliente_id') && $request->get('cliente_id') > 0) {
                $ventas = DB::select('SELECT TOP 100 V.*, C.nombre as cliente, U.nombre as usuario, E.zona_entrega FROM ventas V INNER JOIN clientes C ON V.cliente_id = C.id INNER JOIN usuarios U ON V.usuario_id = U.id INNER JOIN entregas E ON V.entrega_id = E.id WHERE V.cliente_id = ?', [$request->cliente_id]);
                return response()->json(['data' => $ventas, 'status' => 'true'], 200);
            }else{
                return $this->listarVentas();
            }
        } catch (\Exception $e) {
            return response()->json(['data' => $e->getMessage(), 'status' => 'false'], 500);
        } catch (\Throwable $th) {
            return response()->json(['data' => $th->getMessage(), 'status' => 'false'], 500);
        }
    }






    //NO CONSIDERAR ESTAS FUNCIONES


    public function arregalarVentas()
    {
        try {
            set_time_limit(0);
           //Hacer una consulta sql para obtener las fechas de las primeras ventas de cada cliente
            $ventas = DB::select('SELECT cliente_id, MIN(fecha) as fecha FROM ventas GROUP BY cliente_id');

            //Actualizar la cantidad_recibida de cada detalle de venta a 0 ubicando la fecha de la venta obtenida en la consulta anterior
            foreach ($ventas as $venta) {
                DB::update('UPDATE detalle_ventas SET cantidad_recibida = 0 WHERE venta_id IN (SELECT id FROM ventas WHERE cliente_id = ? AND fecha = ?)', [$venta->cliente_id, $venta->fecha]);
            }
        } catch (\Exception $e) {
            return response()->json(['data' => $e->getMessage(), 'status' => 'false'], 500);
        } catch (\Throwable $th) {
            return response()->json(['data' => $th->getMessage(), 'status' => 'false'], 500);
        }
    }

    public function agregarDetallesAVentas()
    {
        try {
          //Buscar las ventas que no tienen detalle de venta y almacenarlos en un json
            $ventas = DB::select('SELECT id FROM ventas WHERE id NOT IN (SELECT venta_id FROM detalle_ventas)');
            //Recorrer cada venta y agregarle el detalle de venta con una cantidad_entregada aleatoria entre 20 y 60, con una cantidad recibida aleatoria entre 20 y 60, con un precio_unitario aleatorio entre 7 y 10 y con un material_id aleatorio entre 1,2 y 3
            //Quitar el tiempo de ejecucion
            set_time_limit(0);
            //Realizar el seed en la BD en las ventas

            foreach ($ventas as $venta) {
                $detalle_venta = new Detalle_venta();
                $detalle_venta->venta_id = $venta->id;
                $detalle_venta->material_id = rand(1,3);
                $detalle_venta->precio_unitario = rand(7,10);
                $detalle_venta->cantidad_entregada = rand(1,5);
                $detalle_venta->cantidad_recibida = rand(1,5);
                $detalle_venta->save();
            }
            //Volver a limitar el tiempo de ejecucion a 60 segundos
            set_time_limit(60);

        } catch (\Exception $e) {
            return response()->json(['data' => $e->getMessage(), 'status' => 'false'], 500);
        } catch (\Throwable $th) {
            return response()->json(['data' => $th->getMessage(), 'status' => 'false'], 500);
        }
    }


    public function arreglarNegativos(){
        try {
            //Aumentar la memoria disponible
            ini_set('memory_limit', '4096M');
            //Desactivar el tiempo de ejecucion
            set_time_limit(0);
            //Hacer una consulta a la bd
            $ventas = DB::select('select v.fecha,dv.id as key_venta,cliente_id,cantidad_recibida,SUM(cantidad_entregada-cantidad_recibida) OVER (partition by cliente_id order by v.fecha,dv.id) as saldo_botellon
            from dbo.ventas V inner join dbo.detalle_ventas dv on V.id = dv.venta_id
            inner join dbo.clientes cl on cl.id = v.cliente_id
            inner join dbo.materiales ma on ma.id = dv.material_id
            group by
            dv.id,v.entrega_id,cliente_id,dv.material_id,cl.documento,cantidad_entregada,cantidad_recibida,v.fecha,precio_unitario*cantidad_entregada
            order by cliente_id,fecha');



            //Recorrar cada venta hasta encontrar una venta con saldo_botellon negativo
            foreach ($ventas as $venta) {
                if($venta->saldo_botellon < 0){
                    //Reducir la cantidad_recibida en 15
                    DB::update('UPDATE detalle_ventas SET cantidad_recibida = cantidad_recibida - 5 WHERE id = ?', [$venta->key_venta]);
                    //Limpiar ram y memoria
                    unset($venta);
                    //Liberar ram
                    gc_collect_cycles();
                    //llamar a la funcion cubrid_free_result($req) para liberar la memoria
                    //Liberar memoria
                    //Volver a llamar a la funcion
                    $this->arreglarNegativos();
                }
            }
            set_time_limit(60);
        } catch (\Exception $e) {
            return response()->json(['data' => $e->getMessage(), 'status' => 'false'], 500);
        } catch (\Throwable $th) {
            return response()->json(['data' => $th->getMessage(), 'status' => 'false'], 500);
        }
    }




   }


