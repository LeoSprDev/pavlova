<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BusinessRule extends Model
{
    use HasFactory;

    protected $fillable = [
        'key',
        'value',
        'description',
        'active',
    ];

    protected $casts = [
        'value' => 'array',
        'active' => 'boolean',
    ];

    public static function getValue(string $key): mixed
    {
        return static::where('key', $key)->value('value');
    }
}
