<?php



namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreOpportunityRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('manage_opportunities') ?? false;
    }

    public function rules(): array
    {
        return [
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'funding_type' => 'nullable|string|in:grant,equity,debt,prize,other',
            'deadline' => 'nullable|date|after:today',
            'funding_min' => 'nullable|numeric|min:0',
            'funding_max' => 'nullable|numeric|min:0|gte:funding_min',
            'subscription_required_id' => 'nullable|uuid|exists:subscriptions,id',
            'is_premium' => 'boolean',
        ];
    }
}
