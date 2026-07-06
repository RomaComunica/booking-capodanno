<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    protected $fillable = ['group', 'key', 'value', 'type'];

    /**
     * Restituisce il valore di una determinata impostazione.
     */
    public static function getVal(string $key, $default = null)
    {
        $setting = static::where('key', $key)->first();
        if (!$setting) return $default;
        
        // Cast based on type
        switch ($setting->type) {
            case 'boolean':
            case 'bool':
                return filter_var($setting->value, FILTER_VALIDATE_BOOLEAN);
            case 'integer':
            case 'int':
                return (int) $setting->value;
            case 'json':
                return json_decode($setting->value, true) ?? [];
            default:
                return $setting->value;
        }
    }

    /**
     * Imposta il valore di un'impostazione.
     */
    public static function setVal(string $group, string $key, $value, string $type = 'string')
    {
        $stringValue = $value;
        
        if ($type === 'json' && is_array($value)) {
            $stringValue = json_encode($value);
        } elseif ($type === 'boolean' || $type === 'bool') {
            $stringValue = $value ? '1' : '0';
        }

        return static::updateOrCreate(
            ['key' => $key],
            [
                'group' => $group,
                'value' => (string)$stringValue,
                'type' => $type
            ]
        );
    }
}
