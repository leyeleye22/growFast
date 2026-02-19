<?php



namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreOpportunitySuggestionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Public (user_id optional) or authenticated
    }

    public function rules(): array
    {
        return [
            'grant_name' => 'required|string|max:255',
            'award_amount_min' => 'nullable|numeric|min:0',
            'award_amount_max' => 'nullable|numeric|min:0|gte:award_amount_min',
            'application_link' => 'nullable|url|max:500',
            'deadline' => 'nullable|date|after:today',
            'location_eligibility' => 'nullable|string|max:255',
            'description' => 'nullable|string|max:5000',
        ];
    }
}
