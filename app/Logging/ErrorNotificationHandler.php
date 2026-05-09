<?php

namespace App\Logging;

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use Monolog\Handler\AbstractProcessingHandler;
use Monolog\Level;
use Monolog\LogRecord;

class ErrorNotificationHandler extends AbstractProcessingHandler
{
    protected function write(LogRecord $record): void
    {
        try {
            $settings = Cache::remember('log_notify_settings', 300, function () {
                return Setting::whereIn('key', [
                    'log_notify_level',
                    'log_notify_email_enabled',
                    'log_notify_email',
                    'log_notify_telegram_enabled',
                    'log_notify_telegram_token',
                    'log_notify_telegram_chat_id',
                ])->pluck('value', 'key')->toArray();
            });

            $emailEnabled = !empty($settings['log_notify_email_enabled']) && $settings['log_notify_email_enabled'] === '1';
            $telegramEnabled = !empty($settings['log_notify_telegram_enabled']) && $settings['log_notify_telegram_enabled'] === '1';

            if (!$emailEnabled && !$telegramEnabled) {
                return;
            }

            $minLevelName = $settings['log_notify_level'] ?? 'error';
            $minLevel = Level::fromName($minLevelName);

            if ($record->level->value < $minLevel->value) {
                return;
            }

            $text = $this->buildMessage($record);

            if ($emailEnabled && !empty($settings['log_notify_email'])) {
                $this->sendEmail($settings['log_notify_email'], $text, $record->level->name);
            }

            if ($telegramEnabled
                && !empty($settings['log_notify_telegram_token'])
                && !empty($settings['log_notify_telegram_chat_id'])
            ) {
                $this->sendTelegram(
                    $settings['log_notify_telegram_token'],
                    $settings['log_notify_telegram_chat_id'],
                    $text,
                );
            }
        } catch (\Throwable) {
        }
    }

    private function buildMessage(LogRecord $record): string
    {
        $lines = [
            '🔴 [' . $record->level->name . '] ' . $record->message,
        ];

        if (!empty($record->context['exception']) && $record->context['exception'] instanceof \Throwable) {
            $e = $record->context['exception'];
            $lines[] = get_class($e) . ': ' . $e->getMessage();
            $lines[] = $e->getFile() . ':' . $e->getLine();
        }

        $lines[] = 'Время: ' . $record->datetime->format('Y-m-d H:i:s');
        $lines[] = 'Сайт: ' . config('app.url');

        return implode("\n", $lines);
    }

    private function sendEmail(string $to, string $text, string $level): void
    {
        Mail::raw($text, function ($m) use ($to, $level) {
            $m->to($to)->subject('[' . config('app.name') . '] Ошибка: ' . $level);
        });
    }

    private function sendTelegram(string $token, string $chatId, string $text): void
    {
        $context = stream_context_create(['http' => [
            'method'  => 'POST',
            'header'  => "Content-Type: application/json\r\n",
            'content' => json_encode(['chat_id' => $chatId, 'text' => $text]),
            'timeout' => 5,
        ]]);
        @file_get_contents("https://api.telegram.org/bot{$token}/sendMessage", false, $context);
    }
}
