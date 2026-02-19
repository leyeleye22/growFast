<?php



namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UploadDocumentRequest extends FormRequest
{
    protected const MAX_SIZE_KB = 10240;

    protected const ALLOWED_MIMES = [
        'application/pdf',
        'image/jpeg',
        'image/png',
        'image/gif',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'text/plain',
    ];

    public function authorize(): bool
    {
        $startup = $this->route('startup');
        return $startup && $this->user()?->id === $startup->user_id;
    }

    public function rules(): array
    {
        return [
            'file' => [
                'required',
                'file',
                'max:' . self::MAX_SIZE_KB,
                'mimetypes:' . implode(',', self::ALLOWED_MIMES),
            ],
        ];
    }
}
