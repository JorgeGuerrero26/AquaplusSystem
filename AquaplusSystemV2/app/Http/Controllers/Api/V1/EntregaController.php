<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Entrega;
use Illuminate\Http\Request;

class EntregaController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function buscarEntregasDeUnClienteDadoSuID(Request $request)
    {
        $entregas = Entrega::where('cliente_id', $request->id)->get();
        return response()->json(['data' => $entregas, 'status' => 'true'], 200);
    }
}
