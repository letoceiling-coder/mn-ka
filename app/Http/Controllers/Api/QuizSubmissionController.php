<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Quiz;
use App\Models\User;
use App\Models\Notification;
use App\Mail\QuizCompletionMail;
use App\Services\NotificationTool;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class QuizSubmissionController extends Controller
{
    protected $notificationTool;

    public function __construct(NotificationTool $notificationTool)
    {
        $this->notificationTool = $notificationTool;
    }

    /**
     * Обработать прохождение квиза
     */
    public function submit(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'quiz_id' => 'required|exists:quizzes,id',
            'answers' => 'required|array',
            'contact' => 'required|array',
            'contact.name' => 'required|string|max:255',
            'contact.email' => 'required|email|max:255',
            'contact.phone' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Ошибка валидации',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $quiz = Quiz::with('questions')->findOrFail($request->quiz_id);
            
            // Получаем или создаем пользователя по email
            $user = User::firstOrCreate(
                ['email' => $request->contact['email']],
                [
                    'name' => $request->contact['name'],
                    'password' => bcrypt(str()->random(32)), // Случайный пароль
                ]
            );

            // Обновляем имя если оно изменилось
            if ($user->name !== $request->contact['name']) {
                $user->update(['name' => $request->contact['name']]);
            }

            // Формируем данные для сохранения (с детализацией вопросов и ответов)
            $detailedAnswers = [];
            $questions = $quiz->questions->sortBy('order')->values();
            
            foreach ($request->answers as $index => $answer) {
                if (isset($questions[$index])) {
                    $question = $questions[$index];
                    $detailedAnswers[] = [
                        'question_id' => $question->id,
                        'question_text' => $question->question_text,
                        'question_type' => $question->question_type,
                        'answer' => $answer,
                    ];
                } else {
                    // Если вопрос не найден, все равно сохраняем ответ
                    $detailedAnswers[] = [
                        'question_id' => null,
                        'question_text' => "Вопрос " . ($index + 1),
                        'question_type' => 'unknown',
                        'answer' => $answer,
                    ];
                }
            }

            $quizData = [
                'quiz_id' => $quiz->id,
                'quiz_title' => $quiz->title,
                'answers' => $detailedAnswers,
                'contact' => $request->contact,
                'completed_at' => now()->toDateTimeString(),
            ];

            // Создаем уведомление для администраторов
            $adminUsers = User::whereHas('roles', function($q) {
                $q->whereIn('slug', ['admin', 'manager']);
            })->get();

            foreach ($adminUsers as $admin) {
                // Добавляем ссылку на просмотр уведомления в данных
                $quizData['view_url'] = '/admin/notifications';
                
                Notification::create([
                    'user_id' => $admin->id,
                    'title' => 'Новое прохождение квиза',
                    'message' => "Пользователь {$request->contact['name']} ({$request->contact['email']}) прошел квиз \"{$quiz->title}\". Перейдите в уведомления для просмотра деталей.",
                    'type' => 'success',
                    'data' => $quizData,
                    'read' => false,
                ]);
            }

            // Отправляем email с результатами
            try {
                Mail::to($request->contact['email'])
                    ->send(new QuizCompletionMail($quiz, $request->answers, $request->contact));
            } catch (\Exception $e) {
                Log::error('Ошибка отправки email с результатами квиза', [
                    'email' => $request->contact['email'],
                    'error' => $e->getMessage(),
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Спасибо за прохождение квиза! Результаты отправлены на вашу почту.',
            ]);

        } catch (\Exception $e) {
            Log::error('Ошибка обработки прохождения квиза', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Ошибка обработки результатов квиза',
            ], 500);
        }
    }
}
