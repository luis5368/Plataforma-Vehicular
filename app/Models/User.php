<?php

namespace App\Models;

use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasFactory, Notifiable;

    public function sendEmailVerificationNotification()
    {
        // Preserve Laravel's signed URL, expiration and mail channel, in Spanish.
        $this->notify((new VerifyEmail)->locale('es'));
    }

    protected $fillable = [
        'name',
        'email',
        'password',
        'google_id',
        'role_id',
        'is_active',
    ];

    protected $hidden = [
        'active_session_hash',
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'is_active' => 'boolean',
            'password' => 'hashed',
        ];
    }

    // Un usuario pertenece a un rol
    public function role()
    {
        return $this->belongsTo(Role::class);
    }

    // Un usuario registra muchos vehículos
    public function registeredVehicles()
    {
        return $this->hasMany(Vehicle::class, 'registered_by');
    }

    // Un usuario crea muchos reportes
    public function theftReports()
    {
        return $this->hasMany(TheftReport::class, 'reported_by');
    }

    // Un usuario hace muchas consultas
    public function consultations()
    {
        return $this->hasMany(Consultation::class);
    }

    // Un usuario tiene muchos registros en la bitácora
    public function auditLogs()
    {
        return $this->hasMany(AuditLog::class);
    }
}
