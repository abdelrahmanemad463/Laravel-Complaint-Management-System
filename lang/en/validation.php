<?php return [
    'required' => 'The :attribute field is required.',
    'string' => 'The :attribute must be a string.',
    'max' => [
        'string' => 'The :attribute may not be greater than :max characters.',
        'array' => 'The :attribute may not have more than :max items.',
    ],
    'min' => [
        'string' => 'The :attribute must be at least :min characters.',
    ],
    'email' => 'The :attribute must be a valid email address.',
    'date' => 'The :attribute must be a valid date.',
    'integer' => 'The :attribute must be an integer.',
    'boolean' => 'The :attribute field must be true or false.',
    'array' => 'The :attribute must be an array.',
    'exists' => 'The selected :attribute is invalid.',
    'unique' => 'The :attribute has already been taken.',
    'regex' => 'The :attribute format is invalid.',
    'in' => 'The selected :attribute is invalid.',
    'attributes' => [
        'name' => 'name', 'email' => 'email', 'password' => 'password',
        'customer_id' => 'customer', 'branch_id' => 'branch', 'service_id' => 'service',
        'source_id' => 'source', 'category_id' => 'category', 'type_id' => 'type',
        'priority_id' => 'priority', 'status_id' => 'status', 'short_description' => 'short description',
        'description' => 'description', 'complaint_date' => 'complaint date', 'role' => 'role',
    ],
];
