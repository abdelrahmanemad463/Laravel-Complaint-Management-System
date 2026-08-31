<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VisitorImportRow extends Model
{
    protected $table = 'visitors_import_rows';

    protected $fillable = [
        'import_id', 'row_number', 'code', 'inspection_type',
        'valid', 'errors', 'data', 'created', 'updated',
    ];

    protected function casts(): array
    {
        return [
            'valid' => 'boolean',
            'created' => 'boolean',
            'updated' => 'boolean',
        ];
    }

    /**
     * Validation errors as an array (decoded from the stored JSON string).
     */
    public function errorsList(): array
    {
        if (!$this->errors) {
            return [];
        }
        $decoded = json_decode($this->errors, true);

        return is_array($decoded) ? $decoded : [$this->errors];
    }

    /**
     * Stripped normalized row data (decoded from the stored JSON string).
     */
    public function dataArray(): array
    {
        $decoded = $this->data ? json_decode($this->data, true) : [];

        return is_array($decoded) ? $decoded : [];
    }

    public function import()
    {
        return $this->belongsTo(VisitorImport::class, 'import_id');
    }
}
