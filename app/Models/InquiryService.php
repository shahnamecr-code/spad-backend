<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;

class InquiryService extends Model
{
    use HasFactory;

    const HANDLER_NOTARY_OFFICE = 'notary_office';
    const HANDLER_ENGINEERING_ORGANIZATION = 'engineering_organization';

    protected $fillable = [
        'title', 'slug', 'category', 'icon', 'accent', 'short_description', 'description',
        'price', 'partner_payout', 'execution_type', 'partner_handler_type', 'estimated_minutes', 'form_schema', 'api_config_encrypted',
        'enabled', 'featured', 'sort_order',
    ];

    protected $casts = [
        'price' => 'integer',
        'partner_payout' => 'integer',
        'estimated_minutes' => 'integer',
        'form_schema' => 'array',
        'enabled' => 'boolean',
        'featured' => 'boolean',
        'sort_order' => 'integer',
    ];

    public static function partnerHandlerTypes()
    {
        return [
            self::HANDLER_NOTARY_OFFICE => 'دفاتر اسناد رسمی',
            self::HANDLER_ENGINEERING_ORGANIZATION => 'دفاتر نظام مهندسی',
        ];
    }

    public function requests()
    {
        return $this->hasMany(InquiryRequest::class);
    }

    /**
     * Return a safe, deterministic schema for public forms and runtime validation.
     * Legacy rows may contain numeric/invalid field keys (for example "0"), which
     * Laravel treats as numeric validation attributes and surfaces as "field 0".
     */
    public function normalizedFormSchema()
    {
        $schema = is_array($this->form_schema) ? array_values($this->form_schema) : [];
        $allowedTypes = ['text', 'number', 'email', 'mobile', 'national_code', 'date', 'textarea', 'select'];
        $used = [];
        $normalized = [];

        foreach ($schema as $index => $field) {
            if (!is_array($field)) continue;

            $label = trim((string) ($field['label'] ?? ''));
            $rawKey = trim((string) ($field['key'] ?? ''));
            $key = $rawKey;

            // Keys must be machine-safe and may not be numeric-only.
            if (!preg_match('/^[A-Za-z_][A-Za-z0-9_-]*$/', $key) || isset($used[$key])) {
                $base = 'field_' . ($index + 1);
                $key = $base;
                $suffix = 2;
                while (isset($used[$key])) {
                    $key = $base . '_' . $suffix++;
                }
            }
            $used[$key] = true;

            $type = (string) ($field['type'] ?? 'text');
            if (!in_array($type, $allowedTypes, true)) $type = 'text';

            $options = $field['options'] ?? [];
            if (!is_array($options)) $options = [];
            $options = array_values(array_filter(array_map(function ($value) {
                return trim((string) $value);
            }, $options), function ($value) { return $value !== ''; }));

            $normalized[] = [
                'key' => $key,
                'label' => $label !== '' ? $label : ('فیلد ' . ($index + 1)),
                'type' => $type,
                'required' => !empty($field['required']),
                'placeholder' => isset($field['placeholder']) ? (string) $field['placeholder'] : '',
                'help' => isset($field['help']) ? (string) $field['help'] : '',
                'options' => $options,
                '_legacy_key' => $rawKey,
            ];
        }

        return $normalized;
    }

    /**
     * Accept both the repaired key sent by new clients and the legacy raw key sent
     * by a cached/PWA client that loaded the service before this hotfix.
     */
    public function normalizeSubmittedInputs(array $inputs)
    {
        $result = $inputs;
        foreach ($this->normalizedFormSchema() as $field) {
            $key = $field['key'];
            $legacyKey = (string) ($field['_legacy_key'] ?? '');

            if (!array_key_exists($key, $result) && $legacyKey !== '' && array_key_exists($legacyKey, $inputs)) {
                $result[$key] = $inputs[$legacyKey];
            }
        }
        return $result;
    }

    public function setApiConfig(array $config)
    {
        $this->api_config_encrypted = Crypt::encryptString(json_encode($config, JSON_UNESCAPED_UNICODE));
        return $this;
    }

    public function secureApiConfig()
    {
        if (!$this->api_config_encrypted) return [];
        try {
            return (array) json_decode(Crypt::decryptString($this->api_config_encrypted), true);
        } catch (\Throwable $e) {
            return [];
        }
    }

    public function maskedApiConfig()
    {
        $config = $this->secureApiConfig();
        if (!empty($config['headers']) && is_array($config['headers'])) {
            foreach ($config['headers'] as $key => $value) {
                $string = (string) $value;
                $config['headers'][$key] = $string === '' ? '' : (strlen($string) > 7 ? substr($string, 0, 2) . '••••' . substr($string, -2) : '••••••');
            }
        }
        if (!empty($config['static_payload']) && is_array($config['static_payload'])) {
            foreach ($config['static_payload'] as $key => $value) {
                if (preg_match('/token|secret|key|password/i', (string) $key)) {
                    $config['static_payload'][$key] = '••••••';
                }
            }
        }
        return $config;
    }

    public function publicPayload()
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'slug' => $this->slug,
            'category' => $this->category,
            'icon' => $this->icon,
            'accent' => $this->accent,
            'short_description' => $this->short_description,
            'description' => $this->description,
            'price' => (int) $this->price,
            'execution_type' => $this->execution_type,
            'execution_type_text' => $this->execution_type === 'api' ? 'اتوماتیک از طریق API' : 'اپراتوری / ارجاع به همکار',
            'estimated_minutes' => $this->estimated_minutes,
            'form_schema' => array_map(function ($field) { unset($field['_legacy_key']); return $field; }, $this->normalizedFormSchema()),
            'featured' => (bool) $this->featured,
        ];
    }
}
