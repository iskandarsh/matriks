<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TrainingMaterials extends Model
{
    use SoftDeletes;

    protected $table = 'training_materials';

    protected $fillable = [
        'kode',
        'title',
        'id_protap',
        'id_modul_parent',
        'durasi_soal',
        'description',
        'user_id',
        'prerequisite_id',
        'kategori',
        'template_certificate',
        'minimal_score',
        'refresh',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }


    public function prerequisite()
    {
        return $this->belongsTo(TrainingMaterials::class, 'prerequisite_id');
    }
}
