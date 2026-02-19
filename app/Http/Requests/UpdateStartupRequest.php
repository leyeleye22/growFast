<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateStartupRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->id === $this->route('startup')?->user_id;
    }

    public function rules(): array
    {
        return [
            'name' => 'sometimes|required|string|max:255',
            'tagline' => 'nullable|string|max:150',
            'description' => 'nullable|string|max:5000',
            'founding_date' => 'nullable|date',
            'pitch_video_url' => 'nullable|url|max:500',
            'website' => 'nullable|url|max:255',
            'phone' => 'nullable|string|max:30',
            'social_media' => 'nullable|url|max:500',
            'industry' => 'nullable|string|exists:industries,slug',
            'customer_type' => 'nullable|string|in:B2B,B2C,B2B2B,B2B2C,B2G,nonprofit',
            'stage' => 'nullable|string|exists:stages,slug',
            'country' => 'nullable|string|max:3',
            'revenue_min' => 'nullable|numeric|min:0',
            'revenue_max' => 'nullable|numeric|min:0|gte:revenue_min',
            'ownership_type' => 'nullable|string|in:minority,women,veteran,diverse',
            'funding_min' => 'nullable|numeric|min:0',
            'funding_max' => 'nullable|numeric|min:0|gte:funding_min',
            'funding_types' => 'nullable|array',
            'funding_types.*' => 'string|in:grant,equity,debt,prize,other',
            'preferred_industries' => 'nullable|array',
            'preferred_industries.*' => 'string|exists:industries,slug',
            'preferred_stages' => 'nullable|array',
            'preferred_stages.*' => 'string|exists:stages,slug',
            'preferred_countries' => 'nullable|array',
            'preferred_countries.*' => 'string|max:3',
            'deadline_min' => 'nullable|date',
            'deadline_max' => 'nullable|date|after_or_equal:deadline_min',
        ];
    }
}
