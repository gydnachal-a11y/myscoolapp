<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TauxChange extends Model
{
    protected $fillable = ['devise_source_id', 'devise_cible_id', 'taux'];

    public function deviseSource() { return $this->belongsTo(Devise::class, 'devise_source_id'); }
    public function deviseCible()  { return $this->belongsTo(Devise::class, 'devise_cible_id'); }
}