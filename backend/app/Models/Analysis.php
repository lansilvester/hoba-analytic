<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Analysis extends Model
{
    use HasFactory;

    protected $fillable = [
        'article_id',
        'sentiment',
        'confidence',
        'topic',
        'entities',
        'analyzed_at',
    ];

    protected function casts(): array
    {
        return [
            'confidence' => 'decimal:4',
            'entities' => 'array',
            'analyzed_at' => 'datetime',
        ];
    }

    public function article(): BelongsTo
    {
        return $this->belongsTo(Article::class);
    }
}
