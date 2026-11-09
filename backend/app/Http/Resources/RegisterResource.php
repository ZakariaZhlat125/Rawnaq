<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class RegisterResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'referral_code' => $this->referral_code,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'username' => $this->username,
            'mobile' => $this->mobile,
            'email' => $this->email,
            'api_token' => $this->api_token,
            'avatar' => $this->avatar,
            'login_type' => $this->login_type,
            'wheel_reward' => $this->wheel_reward ?? null,
        ];
    }
}
