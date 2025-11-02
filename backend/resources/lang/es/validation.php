<?php

return [
    'required' => 'El campo :attribute es obligatorio.',
    'string' => 'El campo :attribute debe ser una cadena.',
    'email' => 'El campo :attribute debe ser una dirección de correo válida.',
    'max' => [
        'string' => 'El campo :attribute no debe ser mayor que :max caracteres.',
    ],
    'min' => [
        'string' => 'El campo :attribute debe tener al menos :min caracteres.',
    ],
    'unique' => 'El campo :attribute ya está en uso.',
    'confirmed' => 'La confirmación de :attribute no coincide.',
    'in' => 'El valor seleccionado para :attribute no es válido.',
    'boolean' => 'El campo :attribute debe ser verdadero o falso.',
    'exists' => 'El valor seleccionado para :attribute no es válido.',

    'attributes' => [
        'name' => 'nombre',
        'email' => 'correo electrónico',
        'password' => 'contraseña',
        'password_confirmation' => 'confirmación de contraseña',
        'lang' => 'idioma',
        'remember' => 'recordar',
        'token' => 'token',
    ],
];


