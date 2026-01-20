<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\SmtpSettingsResource;
use App\Models\SmtpSettings;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class SmtpSettingsController extends Controller
{
    /**
     * Получить настройки SMTP
     */
    public function show()
    {
        try {
            $settings = SmtpSettings::getSettings();
            
            return response()->json([
                'data' => new SmtpSettingsResource($settings),
            ]);
        } catch (\Exception $e) {
            \Log::error('Error in SmtpSettingsController::show: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
            
            return response()->json([
                'message' => 'Ошибка при загрузке настроек SMTP',
                'error' => config('app.debug') ? [
                    'message' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                ] : null,
            ], 500);
        }
    }

    /**
     * Обновить настройки SMTP
     */
    public function update(Request $request)
    {
        $settings = SmtpSettings::getSettings();

        $validator = Validator::make($request->all(), [
            'mailer' => 'required|string|max:50',
            'host' => 'nullable|string|max:255',
            'port' => 'nullable|integer|min:1|max:65535',
            'username' => 'nullable|string|max:255',
            'password' => 'nullable|string|max:255',
            'encryption' => 'nullable|string|in:ssl,tls,null',
            'from_email' => 'nullable|email|max:255',
            'from_name' => 'nullable|string|max:255',
            'is_active' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Ошибка валидации',
                'errors' => $validator->errors(),
            ], 422);
        }

        $data = $request->only([
            'mailer',
            'host',
            'port',
            'username',
            'encryption',
            'from_email',
            'from_name',
            'is_active',
        ]);

        // Обновляем пароль только если он передан и не пустой
        if ($request->has('password') && !empty($request->password)) {
            $data['password'] = $request->password;
        }

        $settings->update($data);

        return response()->json([
            'message' => 'Настройки SMTP успешно обновлены',
            'data' => new SmtpSettingsResource($settings->fresh()),
        ]);
    }
}
