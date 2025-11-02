<?php

return [
    'required' => ':attribute 字段为必填项。',
    'string' => ':attribute 必须是字符串。',
    'email' => ':attribute 必须是有效的邮箱地址。',
    'max' => [
        'string' => ':attribute 不能超过 :max 个字符。',
    ],
    'min' => [
        'string' => ':attribute 至少需要 :min 个字符。',
    ],
    'unique' => ':attribute 已被占用。',
    'confirmed' => ':attribute 确认不匹配。',
    'in' => ':attribute 的选择无效。',
    'boolean' => ':attribute 必须是布尔值。',
    'exists' => ':attribute 的选择无效。',

    'attributes' => [
        'name' => '姓名',
        'email' => '邮箱',
        'password' => '密码',
        'password_confirmation' => '确认密码',
        'lang' => '语言',
        'remember' => '记住我',
        'token' => '令牌',
    ],
];


