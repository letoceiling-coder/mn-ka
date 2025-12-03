<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FooterSettings extends Model
{
    use HasFactory;

    protected $table = 'footer_settings';

    protected $fillable = [
        'title',
        'department_label',
        'department_phone',
        'objects_label',
        'objects_phone',
        'issues_label',
        'issues_email',
        'social_networks',
        'menu_items',
        'privacy_policy_link',
        'copyright',
    ];

    protected $casts = [
        'social_networks' => 'array',
        'menu_items' => 'array',
    ];

    /**
     * Получить настройки футера (singleton)
     */
    public static function getSettings(): self
    {
        $settings = static::first();
        
        if (!$settings) {
            $settings = static::create([
                'title' => 'Контакты',
                'department_label' => 'Отдел',
                'department_phone' => null,
                'objects_label' => 'Объекты',
                'objects_phone' => null,
                'issues_label' => 'Вопросы',
                'issues_email' => null,
                'social_networks' => [
                    'vk' => null,
                    'instagram' => null,
                    'telegram' => null,
                ],
                'menu_items' => [],
                'privacy_policy_link' => '/police',
                'copyright' => 'MNKA 2025. Все права защищены',
            ]);
        }
        
        return $settings;
    }
}

