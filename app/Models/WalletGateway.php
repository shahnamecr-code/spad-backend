<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;

class WalletGateway extends Model
{
    use HasFactory;

    protected $fillable = ['driver', 'title', 'enabled', 'is_default', 'config_encrypted'];
    protected $casts = ['enabled' => 'boolean', 'is_default' => 'boolean'];

    public function topups()
    {
        return $this->hasMany(WalletTopup::class, 'gateway_id');
    }

    public function setSecureConfig(array $config)
    {
        $this->config_encrypted = Crypt::encryptString(json_encode($config, JSON_UNESCAPED_UNICODE));
        return $this;
    }

    public function secureConfig()
    {
        if (!$this->config_encrypted) return [];
        try {
            return (array) json_decode(Crypt::decryptString($this->config_encrypted), true);
        } catch (\Throwable $e) {
            return [];
        }
    }

    public function maskedConfig()
    {
        $result = [];
        foreach ($this->secureConfig() as $key => $value) {
            if (in_array($key, ['mode', 'currency', 'sandbox'])) {
                $result[$key] = $value;
            } elseif ($value !== null && $value !== '') {
                $string = (string) $value;
                $result[$key] = strlen($string) > 6
                    ? substr($string, 0, 2) . '••••' . substr($string, -2)
                    : '••••••';
            } else {
                $result[$key] = '';
            }
        }
        return $result;
    }
}
