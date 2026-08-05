<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Project extends Model
{
    use BelongsToTenant, HasFactory;

    protected $fillable = ['tenant_id', 'name', 'description'];

    public function keywords(): HasMany
    {
        return $this->hasMany(Keyword::class);
    }

    public function sources(): BelongsToMany
    {
        return $this->belongsToMany(Source::class);
    }

    public function articles(): HasMany
    {
        return $this->hasMany(Article::class);
    }

    public function reports(): HasMany
    {
        return $this->hasMany(Report::class);
    }

    public function articleCount(): int
    {
        return $this->articles()->count();
    }
}
