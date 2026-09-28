<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class StoreOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'integer', 'exists:products,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'currency' => ['nullable', 'string', 'in:usd,eur,gtq,mxn'],
        ];
    }

    public function messages(): array
    {
        return [
            'items.required' => 'Debe incluir al menos un producto en la orden.',
            'items.*.product_id.required' => 'Cada ítem debe indicar el producto.',
            'items.*.product_id.exists' => 'Uno de los productos indicados no existe.',
            'items.*.quantity.required' => 'Cada ítem debe indicar la cantidad.',
            'items.*.quantity.min' => 'La cantidad debe ser al menos 1.',
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(response()->json([
            'success' => false,
            'message' => 'Los datos enviados no son válidos.',
            'errors' => $validator->errors(),
        ], 422));
    }
}
