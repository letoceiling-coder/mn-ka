<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CaseResource extends JsonResource
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
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'html' => $this->html,
            'image_id' => $this->image_id,
            'image' => $this->whenLoaded('image', function() {
                return new MediaResource($this->image);
            }),
            'icon_id' => $this->icon_id,
            'icon' => $this->whenLoaded('icon', function() {
                return new MediaResource($this->icon);
            }),
            'chapter_id' => $this->chapter_id,
            'chapter' => $this->whenLoaded('chapter', function() {
                return [
                    'id' => $this->chapter->id,
                    'name' => $this->chapter->name,
                ];
            }),
            'services' => $this->whenLoaded('services', function() {
                return $this->services->pluck('id')->toArray();
            }),
            'servicesData' => $this->whenLoaded('services', function() {
                return $this->services->map(function($service) {
                    return [
                        'id' => $service->id,
                        'name' => $service->name,
                        'slug' => $service->slug,
                    ];
                })->toArray();
            }),
            'products' => $this->whenLoaded('products', function() {
                return $this->products->pluck('id')->toArray();
            }),
            'productsData' => $this->whenLoaded('products', function() {
                return $this->products->map(function($product) {
                    return [
                        'id' => $product->id,
                        'name' => $product->name,
                        'slug' => $product->slug,
                    ];
                })->toArray();
            }),
            'images' => $this->whenLoaded('images', function() {
                return MediaResource::collection($this->images);
            }),
            'is_active' => $this->is_active,
            'order' => $this->order,
            'published' => $this->created_at ? $this->created_at->format('d F Y H:i') : null,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
