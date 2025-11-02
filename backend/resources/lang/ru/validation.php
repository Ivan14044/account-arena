<?php

return [
    'required' => 'Поле :attribute обязательно для заполнения.',
    'string' => 'Поле :attribute должно быть строкой.',
    'email' => 'Поле :attribute должно быть действительным адресом электронной почты.',
    'max' => [
        'string' => 'Поле :attribute не должно превышать :max символов.',
    ],
    'min' => [
        'string' => 'Поле :attribute должно содержать не менее :min символов.',
    ],
    'unique' => 'Поле :attribute уже занято.',
    'confirmed' => 'Подтверждение поля :attribute не совпадает.',
    'in' => 'Выбранное значение для :attribute недопустимо.',
    'boolean' => 'Поле :attribute должно иметь значение true или false.',
    'exists' => 'Выбранное значение для :attribute недействительно.',

    'attributes' => [
        'name' => 'имя',
        'email' => 'электронная почта',
        'password' => 'пароль',
        'password_confirmation' => 'подтверждение пароля',
        'lang' => 'язык',
        'remember' => 'запомнить',
        'token' => 'токен',
    ],
];


