<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\Http\Request;

class AuditLogController extends Controller
{
    public function index(Request $request)
    {
        // Obtener las bitácoras con la relación del usuario, ordenadas de la más reciente a la más antigua
        $query = AuditLog::with('user')->orderBy('created_at', 'desc');

        // Filtro opcional por acción (ej: login, create, update, delete, consult)
        if ($request->filled('action')) {
            $query->where('action', $request->action);
        }

        // Filtro opcional por usuario
        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        $logs = $query->paginate(20);

        // Obtener listas para los filtros del formulario
        $actions = AuditLog::distinct()->pluck('action');
        $users = \App\Models\User::select('id', 'name')->get();

        return view('admin.audit', compact('logs', 'actions', 'users'));
    }
}