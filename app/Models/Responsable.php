<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Responsable extends Model
{
    protected $fillable = [
        'eleve_id', 'type', 'nom', 'profession', 'telephone', 'vivant'
    ];

    protected $casts = [
        'vivant' => 'boolean',
    ];

    public function eleve(): BelongsTo
    {
        return $this->belongsTo(Eleve::class);
    }
}