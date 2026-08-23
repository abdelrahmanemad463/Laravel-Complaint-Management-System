<?php return [
    'required' => 'حقل :attribute مطلوب.',
    'string' => 'يجب أن يكون :attribute نصاً.',
    'max' => [
        'string' => 'يجب ألا يزيد :attribute عن :max حرفاً.',
        'array' => 'يجب ألا يحتوي :attribute على أكثر من :max عناصر.',
    ],
    'min' => [
        'string' => 'يجب ألا يقل :attribute عن :min أحرف.',
    ],
    'email' => 'يجب أن يكون :attribute عنوان بريد إلكتروني صحيحاً.',
    'date' => 'يجب أن يكون :attribute تاريخاً صحيحاً.',
    'integer' => 'يجب أن يكون :attribute رقماً صحيحاً.',
    'boolean' => 'يجب أن تكون قيمة :attribute صحيحة أو خاطئة.',
    'array' => 'يجب أن يكون :attribute مصفوفة.',
    'exists' => 'القيمة المختارة لـ :attribute غير صحيحة.',
    'unique' => ':attribute مستخدم بالفعل.',
    'regex' => 'تنسيق :attribute غير صحيح.',
    'in' => 'القيمة المختارة لـ :attribute غير صحيحة.',
    'attributes' => [
        'name' => 'الاسم', 'email' => 'البريد الإلكتروني', 'password' => 'كلمة المرور',
        'customer_id' => 'العميل', 'branch_id' => 'الفرع', 'service_id' => 'الخدمة',
        'source_id' => 'المصدر', 'category_id' => 'التصنيف', 'type_id' => 'النوع',
        'priority_id' => 'الأولوية', 'status_id' => 'الحالة', 'short_description' => 'الوصف المختصر',
        'description' => 'الوصف', 'complaint_date' => 'تاريخ الشكوى', 'role' => 'الدور',
    ],
];
