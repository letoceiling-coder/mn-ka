<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ServiceResource extends JsonResource
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
            'image_id' => $this->image_id,
            'image' => $this->whenLoaded('image', function() {
                return new MediaResource($this->image);
            }),
            'icon_id' => $this->icon_id,
            'icon' => $this->whenLoaded('icon', function() {
                return new MediaResource($this->icon);
            }),
            'chapter_id' => $this->chapter_id,
            'order' => $this->order,
            'is_active' => $this->is_active,
            'category' => 'services', // Для фронтенда
            'products' => ProductResource::collection($this->whenLoaded('products')),
            'options' => $this->whenLoaded('options', function() {
                return $this->options->map(function($option) {
                    return [
                        'id' => $option->id,
                        'name' => $option->name,
                    ];
                })->toArray();
            }),
            'option_trees' => $this->whenLoaded('optionTrees', function() {
                return $this->optionTrees->map(function($tree) {
                    $items = [];
                    if ($tree->relationLoaded('items') && $tree->items) {
                        $items = $tree->items->map(function($item) {
                            return [
                                'id' => $item->id,
                                'name' => $item->name,
                                'parent' => $item->parent,
                                'sort' => $item->sort,
                            ];
                        })->toArray();
                    }
                    return [
                        'id' => $tree->id,
                        'name' => $tree->name,
                        'parent' => $tree->parent,
                        'sort' => $tree->sort,
                        'items' => $items,
                    ];
                })->toArray();
            }),
            'instances' => $this->whenLoaded('instances', function() {
                return $this->instances->map(function($instance) {
                    return [
                        'id' => $instance->id,
                        'name' => $instance->name,
                    ];
                })->toArray();
            }),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
