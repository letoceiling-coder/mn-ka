<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CaseResource extends JsonResource
{
    /**
     * Получить массив изображений для кейса
     */
    protected function getCaseImages(): array
    {
        $images = [];
        
        // Сначала добавляем основное изображение, если оно есть (для карточек)
        if ($this->relationLoaded('image') && $this->image) {
            $resource = new MediaResource($this->image);
            $imageData = $resource->resolve(request());
            $imageData['webp'] = $imageData['url'] ?? null;
            $images[] = $imageData;
        }
        
        // Затем добавляем изображения из галереи (если загружены и не дублируются)
        if ($this->relationLoaded('images') && $this->images && !$this->images->isEmpty()) {
            foreach ($this->images as $media) {
                // Пропускаем, если это уже основное изображение
                if ($this->image_id && $media->id === $this->image_id) {
                    continue;
                }
                $resource = new MediaResource($media);
                $imageData = $resource->resolve(request());
                $imageData['webp'] = $imageData['url'] ?? null;
                $images[] = $imageData;
            }
        }
        
        return $images;
    }

    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        // Обрабатываем description и html - они могут быть массивами, строками или null
        $description = $this->description;
        if ($description === null) {
            $description = '';
        } elseif (is_array($description)) {
            $description = $description['ru'] ?? $description['en'] ?? (string) reset($description) ?? '';
        } else {
            $description = (string) $description;
        }
        
        $html = $this->html;
        if ($html === null) {
            $html = '';
        } elseif (is_array($html)) {
            $html = $html['ru'] ?? $html['en'] ?? (string) reset($html) ?? '';
        } else {
            $html = (string) $html;
        }

        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $description,
            'html' => $html,
            'image_id' => $this->image_id,
            'image' => $this->when($this->relationLoaded('image') && $this->image, function() {
                $resource = new MediaResource($this->image);
                $imageData = $resource->resolve(request());
                // Добавляем webp для совместимости
                $imageData['webp'] = $imageData['url'] ?? null;
                return $imageData;
            }, null),
            'icon_id' => $this->icon_id,
            'icon' => $this->when($this->relationLoaded('icon') && $this->icon, function() {
                return new MediaResource($this->icon);
            }, null),
            'chapter_id' => $this->chapter_id,
            'chapter' => $this->when($this->relationLoaded('chapter') && $this->chapter, function() {
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
            'images' => $this->getCaseImages(),
            'is_active' => $this->is_active,
            'order' => $this->order,
            'published' => $this->created_at ? $this->created_at->format('d F Y H:i') : null,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
