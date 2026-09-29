<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TheftReport extends Model
{
    use HasFactory;
    protected $fillable = ['vehicle_id', 'report_date', 'description', 'status', 'reported_by'];

    protected $casts = [
        'report_date' => 'date',
    ];

    public function vehicle()
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function reportedBy()
    {   
        return $this->belongsTo(User::class, 'reported_by');
    }
}