<?php

namespace App\Http\Resources;

use App\Models\QuestionOption;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin QuestionOption
 */
class QuestionOptionResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'question_id' => $this->question_id,
            'label' => $this->label,
            'value' => $this->value,
        ];
    }
}
