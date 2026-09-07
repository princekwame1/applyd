<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasRoles, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'phone',
        'avatar',
        'password',
        'student_id',
        'must_change_password',
        'credentials_sent_at',
    ];

    /** The role that opens the portal's instructor side. */
    public const FACILITATOR_ROLE = 'lecturer';

    /** Everyone who can teach. The portal calls them instructors; we say facilitator. */
    public function scopeFacilitators($query)
    {
        return $query->whereHas('roles', fn ($q) => $q->where('name', self::FACILITATOR_ROLE));
    }

    public function isFacilitator(): bool
    {
        return $this->hasRole(self::FACILITATOR_ROLE);
    }

    /**
     * Where this account stands on getting into the portal. Three states, and
     * the difference between the last two matters: an account still on the
     * password we generated has never been signed into, which is exactly who
     * a chase-up is for.
     */
    public function getAccessStateAttribute(): string
    {
        if (! $this->credentials_sent_at) {
            return 'pending';
        }

        return $this->must_change_password ? 'sent' : 'active';
    }

    public function getFirstNameAttribute(): string
    {
        $name = trim((string) $this->name);

        return explode(' ', $name)[0] ?: $name;
    }

    public function company()
    {
        return $this->hasOne(Company::class);
    }

    public function courseEnrollments()
    {
        return $this->hasMany(CourseEnrollment::class);
    }

    public function getAvatarUrlAttribute(): ?string
    {
        return $this->avatar ? asset('storage/'.$this->avatar) : null;
    }

    public function getRoleLabelAttribute(): string
    {
        return $this->getRoleNames()->map(fn ($role) => ucfirst($role))->implode(', ') ?: '—';
    }

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'must_change_password' => 'boolean',
            'credentials_sent_at' => 'datetime',
        ];
    }
}
