<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Option extends Model
{
    protected $fillable = ['nom'];

    public function sallesDeClasse(): HasMany
    {
        return $this->hasMany(SalleDeClasse::class);
    }
}