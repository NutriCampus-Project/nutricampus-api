<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nombres'   => 'required|string|max:100',
            'apellidos' => 'required|string|max:100',
            'correo'    => ['required', 'email', 'ends_with:@unap.edu.pe', 'unique:users,email'],
            'rol'       => 'required|in:Estudiante,Docente,Administrativo,Encargado',
            'password'  => 'required|string|min:8',
        ], [
            'correo.unique'    => 'El correo ya está registrado.',
            'correo.ends_with' => 'Debe ingresar un correo institucional @unap.edu.pe.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'estado'  => 'ERROR',
                'mensaje' => $validator->errors()->first(),
            ], 422);
        }

        $usuario = User::create([
            'name'     => trim($request->nombres) . ' ' . trim($request->apellidos),
            'email'    => strtolower(trim($request->correo)),
            'rol'      => $request->rol,
            'password' => Hash::make($request->password),
        ]);

        return response()->json([
            'estado'  => 'OK',
            'mensaje' => 'Usuario registrado correctamente en NutriCampus.',
            'usuario' => [
                'nombre_completo' => $usuario->name,
                'correo'          => $usuario->email,
                'rol'             => $usuario->rol,
            ],
        ], 201);
    }
}
