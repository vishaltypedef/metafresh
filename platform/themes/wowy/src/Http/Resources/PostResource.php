<?php

namespace Theme\Wowy\Http\Resources;

use Botble\Blog\Http\Resources\CategoryResource;
use Botble\Blog\Models\Category;
use Illuminate\Http\Resources\Json\JsonResource;
use RvMedia;

class PostResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'url' => $this->url,
            'description' => $this->description,
            'image' => $this->image ? RvMedia::url($this->image) : null,
            'category' => $this->categories->count() > 0 ? new CategoryResource($this->categories->first()) : new CategoryResource(new Category()),
            'created_at' => $this->created_at->translatedFormat('M d, Y'),
            'views' => number_format($this->views),
        ];
    }
}
