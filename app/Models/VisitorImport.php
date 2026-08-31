<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VisitorImport extends Model
{
    protected $table = 'visitors_imports';

    protected $fillable = [
        'user_id', 'file_name', 'file_size', 'extension', 'status',
        'total_rows', 'valid_rows', 'invalid_rows',
        'created_records', 'updated_records', 'failed_records',
        'error_message', 'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'file_size' => 'integer',
            'total_rows' => 'integer',
            'valid_rows' => 'integer',
            'invalid_rows' => 'integer',
            'created_records' => 'integer',
            'updated_records' => 'integer',
            'failed_records' => 'integer',
            'completed_at' => 'datetime',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function rows()
    {
        return $this->hasMany(VisitorImportRow::class, 'import_id');
    }

    public function validRows()
    {
        return $this->rows()->where('valid', true);
    }

    public function invalidRows()
    {
        return $this->rows()->where('valid', false);
    }

    public function isPending(): bool
    {
        return $this->status === 'pending' || $this->status === 'validating';
    }

    public function isReady(): bool
    {
        return $this->status === 'ready';
    }

    public function isImported(): bool
    {
        return $this->status === 'imported';
    }
}
