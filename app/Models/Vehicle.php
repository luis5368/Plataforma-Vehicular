<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Vehicle extends Model
{
    use HasFactory;
    protected $fillable = [
        'vin', 'plate', 'brand', 'model', 'year', 'color', 
        'department', 'municipality', 'zone',
        'theft_report_address', 'theft_latitude', 'theft_longitude', 'observations', 'status', 'registered_by'
    ];

    public function registeredBy()
    {
        return $this->belongsTo(User::class, 'registered_by');
    }

    public function theftReport()
    {
        return $this->hasOne(TheftReport::class);
    }

    public function consultations()
    {
        return $this->hasMany(Consultation::class);
    }
}