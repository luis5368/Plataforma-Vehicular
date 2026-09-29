<?php
namespace App\Models;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'google_id',
        'role_id',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
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