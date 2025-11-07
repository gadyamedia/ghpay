<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Position extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'title',
        'slug',
        'department',
        'employment_type',
        'location_type',
        'location',
        'description',
        'requirements',
        'responsibilities',
        'benefits',
        'salary_min',
        'salary_max',
        'show_salary',
        'status',
        'application_deadline',
        'require_typing_test',
        'auto_send_typing_test',
        'minimum_wpm',
        'typing_text_sample_id',
        'notify_admin_on_application',
        'notification_email',
        'form_settings',
        'views_count',
        'applications_count',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'application_deadline' => 'date',
            'show_salary' => 'boolean',
            'require_typing_test' => 'boolean',
            'auto_send_typing_test' => 'boolean',
            'notify_admin_on_application' => 'boolean',
            'form_settings' => 'array',
            'salary_min' => 'integer',
            'salary_max' => 'integer',
            'minimum_wpm' => 'integer',
            'views_count' => 'integer',
            'applications_count' => 'integer',
        ];
    }

    public function applications(): HasMany
    {
        return $this->hasMany(Application::class);
    }

    public function questions(): HasMany
    {
        return $this->hasMany(PositionQuestion::class)->orderBy('order');
    }

    public function typingTextSample(): BelongsTo
    {
        return $this->belongsTo(TypingTextSample::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function isOpen(): bool
    {
        return $this->status === 'open' &&
            ($this->application_deadline === null || $this->application_deadline > now());
    }

    public function incrementViews(): void
    {
        $this->increment('views_count');
    }

    public function incrementApplications(): void
    {
        $this->increment('applications_count');
    }

    public function scopeOpen($query)
    {
        return $query->where('status', 'open')
            ->where(function ($q) {
                $q->whereNull('application_deadline')
                    ->orWhere('application_deadline', '>=', now());
            });
    }

    public function scopeByDepartment($query, ?string $department)
    {
        return $department ? $query->where('department', $department) : $query;
    }

    public function scopeByEmploymentType($query, ?string $type)
    {
        return $type ? $query->where('employment_type', $type) : $query;
    }

    public function scopeByLocationType($query, ?string $type)
    {
        return $type ? $query->where('location_type', $type) : $query;
    }

    public function scopeSearch($query, ?string $search)
    {
        return $search
            ? $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhere('department', 'like', "%{$search}%");
            })
            : $query;
    }

    /**
     * Get the route key for the model.
     */
    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /**
     * Generate a unique slug from title
     */
    public static function generateSlug(string $title, ?int $id = null): string
    {
        $slug = \Illuminate\Support\Str::slug($title);
        $originalSlug = $slug;
        $count = 1;

        while (static::where('slug', $slug)->when($id, fn ($q) => $q->where('id', '!=', $id))->exists()) {
            $slug = $originalSlug.'-'.$count;
            $count++;
        }

        return $slug;
    }

    /**
     * Get form settings with defaults
     */
    public function getFormSettingsAttribute($value): array
    {
        $defaults = [
            'show_cover_letter' => true,
            'require_cover_letter' => false,
            'show_portfolio_url' => false,
            'require_portfolio_url' => false,
            'show_linkedin_url' => true,
            'require_linkedin_url' => false,
            'show_github_url' => false,
            'require_github_url' => false,
            'show_location' => true,
            'require_location' => false,
        ];

        $settings = $value ? json_decode($value, true) : [];

        return array_merge($defaults, $settings);
    }

    /**
     * Boot the model
     */
    protected static function boot(): void
    {
        parent::boot();

        // Auto-generate slug on creating
        static::creating(function ($position) {
            if (empty($position->slug)) {
                $position->slug = static::generateSlug($position->title);
            }
        });

        // Auto-update slug on updating if title changed
        static::updating(function ($position) {
            if ($position->isDirty('title') && empty($position->slug)) {
                $position->slug = static::generateSlug($position->title, $position->id);
            }
        });
    }
}
