<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Favorite;
use App\Models\Comment;

class Item extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'title',
        'brand',
        'description',
        'price',
        'status',
        'condition',
        'image',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function getConditionLabelAttribute()
    {
        $conditions = [
            'like_new' => '良好',
            'good' => '目立った傷や汚れなし',
            'fair' => 'やや傷や汚れあり',
            'damaged' => '状態が悪い',
        ];

        return $conditions[$this->condition] ?? $this->condition;
    }

    public function categories()
    {
        return $this->belongsToMany(Category::class, 'item_category');
    }

    public function getImageUrlAttribute()
    {
        return $this->image ? asset('storage/' . $this->image) : null;
    }

    public function favorites()
    {
        return $this->hasMany(Favorite::class);
    }

    public function comments()
    {
        return $this->hasMany(Comment::class)->latest();
    }

    public function getIsSoldAttribute(): bool
    {
        return ($this->stock - ($this->reserved_stock ?? 0)) <= 0;
    }
}
