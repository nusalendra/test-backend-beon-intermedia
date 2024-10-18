<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Iuran extends Model
{
    use HasFactory;
    protected $table = 'iurans';
    protected $primarykey = 'id';
    protected $fillable = ['nama', 'biaya', 'bulan_tagihan'];

    public function pembayaran() {
        return $this->hasMany(Pembayaran::class, 'iuran_id');
    }
}
