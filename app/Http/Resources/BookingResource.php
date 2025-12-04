<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use App\Http\Resources\TicketResource;
use Illuminate\Http\Resources\Json\JsonResource;

class BookingResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [

            // BASIC INFO
            'id' => $this->id,
            'user_id' => $this->user_id,
            'showtime_id' => $this->showtime_id,

            // USER INFO
            'user' => new UserResource($this->whenLoaded('user')),

            // SHOWTIME INFO
            'showtime' => new ShowtimeResource($this->whenLoaded('showtime')),

            // PRICE INFO
            'discount_code' => $this->discount_code,
            'total_amount'  => (float) $this->total_amount,
            'discount'      => (float) $this->discount,
            'final_amount'  => (float) $this->final_amount,

            // STATUS
            'payment_status' => $this->payment_status,  // pending / paid / failed...
            'booking_status' => $this->booking_status,  // pending / confirmed / canceled...
            'payment_method' => $this->payment_method,  // vnpay, momo...

            // TICKETS
            'tickets' => TicketResource::collection(
                $this->whenLoaded('tickets')
            ),

            // PRODUCTS / COMBOS
            'products' => BookingProductResource::collection(
                $this->whenLoaded('products')
            ),

            // TIMESTAMP
            'created_at' => $this->created_at
                ? $this->created_at->timezone('Asia/Ho_Chi_Minh')->format('Y-m-d H:i')
                : null,

            'updated_at' => $this->updated_at
                ? $this->updated_at->timezone('Asia/Ho_Chi_Minh')->format('Y-m-d H:i')
                : null,
        ];
    }
}
