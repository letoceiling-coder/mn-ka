<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\FeedbackRequest;
use App\Models\User;
use App\Mail\FeedbackMail;
use App\Services\NotificationTool;
use App\Services\TelegramService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class FeedbackController extends Controller
{
    /**
     * Отправить форму обратной связи
     */
    public function submit(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'message' => 'required|string|max:5000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Ошибка валидации',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            // Создаем заявку
            $feedbackRequest = FeedbackRequest::create([
                'name' => $request->name,
                'phone' => $request->phone,
                'email' => $request->email,
                'message' => $request->message,
                'status' => FeedbackRequest::STATUS_NEW,
            ]);

            // Получаем всех администраторов и менеджеров для отправки уведомлений
            $adminUsers = User::whereHas('roles', function ($query) {
                $query->whereIn('slug', ['admin', 'manager']);
            })->get();

            // Формируем сообщение для уведомления
            $notificationTitle = "Новая обратная связь от: {$request->name}";
            $notificationMessage = "Имя: {$request->name}\n";
            if ($request->phone) {
                $notificationMessage .= "Телефон: {$request->phone}\n";
            }
            if ($request->email) {
                $notificationMessage .= "Email: {$request->email}\n";
            }
            $notificationMessage .= "\nСообщение:\n{$request->message}";

            // Создаем уведомления для всех администраторов и менеджеров
            $notificationTool = new NotificationTool();
            foreach ($adminUsers as $adminUser) {
                $notificationTool->addNotification(
                    $adminUser,
                    $notificationTitle,
                    $notificationMessage,
                    'info',
                    [
                        'request_id' => $feedbackRequest->id,
                        'type' => 'feedback',
                        'contact' => [
                            'name' => $request->name,
                            'phone' => $request->phone,
                            'email' => $request->email,
                        ],
                    ],
                    true // Отправлять в Telegram
                );
            }

            // Отправляем email администраторам
            try {
                $adminEmails = $adminUsers->pluck('email')->filter()->toArray();
                if (!empty($adminEmails)) {
                    Mail::to($adminEmails)->send(new FeedbackMail($feedbackRequest));
                }
            } catch (\Exception $e) {
                Log::error('Error sending feedback email: ' . $e->getMessage());
                // Не прерываем выполнение, если email не отправился
            }

            // Отправляем в Telegram бот через NotificationTool (он уже отправляет в Telegram)
            // Дополнительная отправка напрямую в Telegram для всех администраторов
            try {
                $telegramService = new TelegramService();
                $telegramMessage = "📝 <b>Новая обратная связь</b>\n\n";
                $telegramMessage .= "👤 <b>Имя:</b> {$request->name}\n";
                if ($request->phone) {
                    $telegramMessage .= "📞 <b>Телефон:</b> {$request->phone}\n";
                }
                if ($request->email) {
                    $telegramMessage .= "📧 <b>Email:</b> {$request->email}\n";
                }
                $telegramMessage .= "\n💬 <b>Сообщение:</b>\n{$request->message}";
                $telegramMessage .= "\n\n🔗 <a href=\"" . url('/admin/feedback-requests/' . $feedbackRequest->id) . "\">Просмотреть в админке</a>";
                
                // Отправляем всем администраторам с telegram_chat_id
                $telegramAdmins = User::whereNotNull('telegram_chat_id')
                    ->whereHas('roles', function ($query) {
                        $query->whereIn('slug', ['admin', 'manager']);
                    })
                    ->get();
                
                foreach ($telegramAdmins as $admin) {
                    $telegramService->sendMessage($telegramMessage, $admin->telegram_chat_id, ['parse_mode' => 'HTML']);
                }
            } catch (\Exception $e) {
                Log::error('Error sending feedback to Telegram: ' . $e->getMessage());
                // Не прерываем выполнение, если Telegram не отправился
            }

            return response()->json([
                'message' => 'Спасибо за ваше обращение! Мы свяжемся с вами в ближайшее время.',
                'success' => true,
            ]);
        } catch (\Exception $e) {
            Log::error('Error submitting feedback: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'request_data' => $request->all(),
            ]);

            return response()->json([
                'message' => 'Ошибка при отправке формы',
                'error' => config('app.debug') ? $e->getMessage() : 'Внутренняя ошибка сервера',
            ], 500);
        }
    }
}
