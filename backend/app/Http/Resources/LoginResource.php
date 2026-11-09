<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class LoginResource extends JsonResource
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
            'mobile' => $this->mobile,
            'email' => $this->email,
            'gender' => $this->gender,
            'user_role' => $this->getRoleNames() ?? [],
            'api_token' => $this->api_token,
            'profile_image' => $this->avatar,
            'login_type' => $this->login_type,
            'profile_image' => $this->media->pluck('original_url')->first(),
            'wheel_reward' => $this->wheel_reward ?? null,

        ];
    }
}
