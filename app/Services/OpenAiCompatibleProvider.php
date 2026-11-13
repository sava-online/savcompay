<?php
namespace App\Services;

use App\Core\Env;

/**
 * OpenAI-compatible chat completion provider (OpenRouter, Groq, self-hosted).
 * The API key lives only on the server; the browser never sees it.
 */
class OpenAiCompatibleProvider implements AiProvider
{
    private string $baseUrl;
    private string $apiKey;
    private string $model;

    public function __construct(string $baseUrl, string $apiKey, string $model)
    {
        $this->baseUrl = rtrim($baseUrl, '/');
        $this->apiKey  = $apiKey;
        $this->model   = $model;
    }

    public function name(): string
    {
        return 'openai_compatible';
    }

    public function generate(string $system, string $user, string $task = ''): string
    {
        if ($this->apiKey === '') {
            throw new \RuntimeException('AI_API_KEY не задан.');
        }
        $url = $this->baseUrl . '/chat/completions';
        $payload = [
            'model'    => $this->model,
            'messages' => [
                ['role' => 'system', 'content' => $system],
                ['role' => 'user', 'content' => $user],
            ],
            'temperature' => Env::get('AI_TEMPERATURE', 0.4),
            'max_tokens'  => Env::int('AI_MAX_TOKENS', 700),
        ];

        $ch = curl_init($url);
        if ($ch === false) {
            throw new \RuntimeException('cURL недоступен.');
        }
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => Env::int('AI_TIMEOUT', 20),
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $this->apiKey,
            ],
            CURLOPT_POST       => true,
            CURLOPT_POSTFIELDS => json_encode($payload),
        ]);
        $response = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err = curl_error($ch);
        curl_close($ch);

        if ($response === false) {
            throw new \RuntimeException('Ошибка сети ИИ: ' . $err);
        }
        if ($code >= 400) {
            throw new \RuntimeException("Ошибка провайдера ИИ (HTTP $code).");
        }
        $decoded = json_decode($response, true);
        if (!is_array($decoded) || !isset($decoded['choices'][0]['message']['content'])) {
            throw new \RuntimeException('Некорректный ответ провайдера ИИ.');
        }
        return (string)$decoded['choices'][0]['message']['content'];
    }
}
