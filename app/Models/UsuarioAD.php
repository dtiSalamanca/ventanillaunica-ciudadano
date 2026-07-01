<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UsuarioAD extends Model
{
    protected $table = 'tbl_usuarios_ad';

    protected $primaryKey = 'id_usuario';

    protected $fillable = [
        'nombre_usuario',
        'fk_dependencia',
    ];
}
