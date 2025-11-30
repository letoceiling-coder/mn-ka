<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\FaqBlockSettings;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class FaqBlockSettingsController extends Controller
{
    /**
     * Получить настройки блока
     */
    public function show()
    {
        $settings = FaqBlockSettings::getSettings();
        
        return response()->json([
            'data' => $settings,
        ]);
    }

    /**
     * Обновить настройки блока
     */
    public function update(Request $request)
    {
        $settings = FaqBlockSettings::getSettings();

        $validator = Validator::make($request->all(), [
            'title' => 'nullable|string|max:255',
            'is_active' => 'nullable|boolean',
            'faq_items' => 'nullable|array',
            'faq_items.*.question' => 'required_with:faq_items|string|max:500',
            'faq_items.*.answer' => 'required_with:faq_items|string|max:2000',
            'additional_settings' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Ошибка валидации',
                'errors' => $validator->errors(),
            ], 422);
        }

        $settings->update($request->only([
            'title',
            'is_active',
            'faq_items',
            'additional_settings',
        ]));

        return response()->json([
            'message' => 'Настройки блока успешно обновлены',
            'data' => $settings,
        ]);
    }
}
