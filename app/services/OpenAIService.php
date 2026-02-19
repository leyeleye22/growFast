<?php

namespace App\Services;

use GuzzleHttp\Client;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

class OpenAIService
{
    protected $baseUrl;

    protected $apiKey;

    protected $faker;

    public function __construct()
    {
        $this->baseUrl = 'https://api.openai.com/v1/';
        $this->apiKey = config('openai.api_key');
    }

    public function analyzeImage($imagePath)
    {
        try {
            $url = $this->baseUrl.'responses';

            $base64Image = base64_encode(file_get_contents($imagePath));

            $type = pathinfo($imagePath, PATHINFO_EXTENSION);
            $base64ImageWithMime = 'data:image/'.$type.';base64,'.$base64Image;

            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer '.$this->apiKey,
            ])->timeout(120)->post($url, [
                'model' => 'gpt-4.1',
                'input' => [
                    [
                        'role' => 'user',
                        'content' => [
                            [
                                'type' => 'input_text',
                                'text' => 'Extract all the menu items from the image and return them in a structured format.',
                            ],
                            [
                                'type' => 'input_image',
                                'image_url' => $base64ImageWithMime,
                            ],
                        ],
                    ],
                ],
            ]);

            if ($response->ok()) {
                return $response->json();
            } else {
                Log::error('OpenAI analyzeImage failed', [
                    'status' => $response->status(),
                    'body' => $response->json(),
                ]);

                return 'Request failed with status: '.$response->status();
            }
        } catch (\Exception $e) {
            Log::error('OpenAI analyzeImage exception', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);

            return 'Error generating thread request: '.$e->getMessage();
        }
    }

    public function generateStructuredOutput($system_message, $user_message, $schema, $name)
    {
        try {
            $url = $this->baseUrl.'chat/completions';

            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer '.$this->apiKey,
            ])->timeout(120)->post($url, [
                'model' => 'gpt-4.1',
                'response_format' => [
                    'type' => 'json_schema',
                    'json_schema' => [
                        'name' => $name,
                        'schema' => $schema,
                        'strict' => true,
                    ],
                ],
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => $system_message,
                    ],
                    [
                        'role' => 'user',
                        'content' => $user_message,
                    ],
                ],
            ]);
            if ($response->ok()) {
                return $response->json();
            } else {
                Log::error('OpenAI generateStructuredOutput failed', [
                    'status' => $response->status(),
                ]);

                return 'Request failed with status: '.$response->status();
            }
        } catch (\Exception $e) {
            Log::error('OpenAI generateStructuredOutput exception', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);

            return 'Error generating thread request: '.$e->getMessage();
        }
    }

    public function createChatCompletion($jsonData)
    {
        try {
            $url = $this->baseUrl.'chat/completions';
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer '.$this->apiKey,
            ])->post($url, [
                'model' => 'gpt-4o',
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => 'Tu es un assistant virtuel intelligent intégré au Musée des Civilisations Noires d’Afrique.
Ton rôle est d’accompagner les visiteurs pendant leur expérience de visite — sur place ou en ligne — en répondant à leurs questions sur :

les œuvres exposées (origine, signification, histoire, matériaux, artiste, etc.) ;

les thématiques culturelles du musée (civilisations africaines, patrimoine, traditions, spiritualité, etc.) ;

ainsi que les informations pratiques (horaires, accès, événements, expositions temporaires).

Tu t’exprimes de manière chaleureuse, claire et pédagogique, en valorisant la richesse du patrimoine africain.
Tu peux répondre en français, anglais ou wolof selon la langue du visiteur.

Ton objectif est de rendre la découverte du musée plus interactive, accessible et inspirante, tout en transmettant la fierté du patrimoine africain au monde entier.',
                    ],
                    [
                        'role' => 'user',
                        'content' => $jsonData,
                    ],
                ],
            ]);
            if ($response->ok()) {
                return $response->json();
            } else {
                Log::error('OpenAI createChatCompletion failed', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return 'Request failed with status: '.$response->status();
            }
        } catch (\Exception $e) {
            Log::error('OpenAI createChatCompletion exception', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);

            return 'Error Creating Thread Request: '.$e->getMessage();
        }
    }

    public function textToSpeech(string $text, string $voice = 'alloy', string $model = 'gpt-4o-mini-tts'): array
    {
        $apiKey = $this->apiKey;
        $baseUrl = 'https://api.openai.com';

        $url = $baseUrl.'/v1/audio/speech';

        $payload = [
            'model' => $model,  // ex: 'gpt-4o-mini-tts' (recommandé)
            'voice' => $voice,  // ex: 'alloy'
            'input' => $text,   // texte à synthétiser
        ];

        $response = Http::withToken($apiKey)
            ->accept('audio/mpeg')          // on veut un MP3 en sortie
            ->asJson()
            ->post($url, $payload);

        if (! $response->ok()) {
            throw new RuntimeException('OpenAI TTS error: '.$response->body());
        }

        $audioBytes = $response->body();    // octets du MP3

        return [$audioBytes, 'audio/mpeg', 'mp3'];
    }

    /**
     * Sauvegarde l'audio dans storage/app/public/tts et retourne le chemin relatif "tts/xxx.mp3"
     */
    public function saveAudioToPublic(string $bytes, string $ext = 'mp3'): string
    {
        $filename = 'tts/'.now()->format('Ymd_His').'_'.Str::random(8).'.'.$ext;
        Storage::disk('public')->put($filename, $bytes);

        return $filename;
    }

    public function translateAudioToEnglish(
        UploadedFile $file,
        string $model = 'gpt-4o-transcribe',
        string $responseFormat = 'json',
        ?string $prompt = null,
        ?float $temperature = null,
    ): array {
        $apiKey = $this->apiKey;
        $baseUrl = 'https://api.openai.com';
        $url = $baseUrl.'/v1/audio/translations'; // endpoint officiel createTranslation

        // Construction multipart/form-data
        $req = Http::withToken($apiKey)
            ->asMultipart()
            ->attach(
                'file',
                fopen($file->getRealPath(), 'r'),
                $file->getClientOriginalName()
            );

        $payload = [
            'model' => $model,
            'response_format' => $responseFormat, // 'json' ou 'text' (pour gpt-4o(-mini)-transcribe)
        ];

        if ($prompt !== null) {
            $payload['prompt'] = $prompt;
        }
        if ($temperature !== null) {
            $payload['temperature'] = $temperature;
        }

        $response = $req->post($url, $payload);

        if (! $response->ok()) {
            throw new RuntimeException('OpenAI Translation error: '.$response->body());
        }

        // gpt-4o(-mini)-transcribe: 'json' => {"text":"..."} ; 'text' => corps brut
        if ($responseFormat === 'text') {
            return ['raw' => $response->body()];
        }

        $json = $response->json();

        return ['text' => $json['text'] ?? ''];
    }

    public function createModelResponse($data = [], $stream = false)
    {
        try {
            $client = new Client;
            $headers = ['Content-Type' => 'application/json', 'Authorization' => 'Bearer '.$this->apiKey, 'OpenAI-Beta' => 'assistants=v2'];
            $url = $this->baseUrl.'responses';
            $payload = [
                'model' => $data['model'] ?? 'gpt-4.1',
                'stream' => false,
                'previous_response_id' => $data['previous_response_id'] ?? null,
                'input' => [['role' => 'user', 'content' => $data['user_input'] ?? null]],
            ];
            if (! empty($data['tools'])) {
                $payload['tools'] = $data['tools'];
            }
            $response = $client->request('POST', $url, ['headers' => $headers, 'json' => $payload, 'stream' => $stream]);
            if ($response->getStatusCode() >= 200 && $response->getStatusCode() < 300) {
                if ($stream) {
                    return $response->getBody();
                } else {
                    $responseData = $response->getBody()->getContents();

                    return $responseData ? json_decode($responseData, true) : null;
                }
            } else {
                Log::error('OpenAI createModelResponse failed', [
                    'status' => $response->getStatusCode(),
                    'body' => $response->getBody()->getContents(),
                ]);

                return 'Request failed with status: '.$response->getStatusCode();
            }
        } catch (\Exception $e) {
            Log::error('OpenAI createModelResponse exception', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);

            return 'Error Creating Model Response: '.$e->getMessage();
        }
    }
}
