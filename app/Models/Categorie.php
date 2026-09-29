<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Categorie extends Model
{
    protected $fillable = ['nom'];
    public function cours(): HasMany { return $this->hasMany(Cour::class, 'categorie_id'); }
}