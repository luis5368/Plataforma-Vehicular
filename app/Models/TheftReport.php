<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TheftReport extends Model
{
    use HasFactory;

    // Review/case state and validation metadata can only be assigned explicitly by the server.
    protected $fillable = ['vehicle_id', 'report_date', 'description', 'reported_by', 'updated_by'];

    protected $casts = [
        'report_date' => 'date',
        'validated_at' => 'datetime',
    ];

    public function scopeAccepted($query)
    {
        return $query->where('validation_status', 'validado');
    }

    public function scopeActive($query)
    {
        return $query->accepted()->where('case_status', 'abierto');
    }

    public function displayStatus(): string
    {
        if ($this->case_status === 'resuelto') {
            return 'resuelto';
        }
        if ($this->case_status === 'retirado') {
            return 'retirado';
        }

        return match ($this->validation_status) {
            'validado' => 'validado', 'rechazado' => 'rechazado', default => 'pendiente_validacion',
        };
    }

    public function sourceLabel(): string
    {
        return match ($this->validation_method) {
            'automatica' => 'Fuente: usuario autorizado; validación automática de integridad',
            'administrativa' => 'Fuente: usuario reportante; revisión administrativa',
            'legado' => 'Fuente: registro histórico; validación previa sin trazabilidad completa',
            default => 'Fuente: registro histórico o pendiente; validación no acreditada',
        };
    }

    public function vehicle()
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function reportedBy()
    {
        return $this->belongsTo(User::class, 'reported_by');
    }
}
