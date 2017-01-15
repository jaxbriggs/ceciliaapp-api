<?php
/**
 * Created by PhpStorm.
 * User: Carlos Henrique
 * Date: 1/7/2017
 * Time: 12:26 AM
 */

namespace Transformer;


use League\Fractal\TransformerAbstract;
use Model\Usuario;

class UsuarioTransformer extends TransformerAbstract
{
    public function transform(Usuario $usuario)
    {
        return [
            'id'      => (int) $usuario->id,
            'nome'   => $usuario->nome,
            'login'    => $usuario->login
        ];
    }
}