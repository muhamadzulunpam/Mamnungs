<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ActivityLog extends Model
{
    const UPDATED_AT = null; // log tidak pernah diubah

    protected $fillable = [
        'user_id', 'user_name', 'action', 'description',
        'subject_type', 'subject_id', 'properties', 'ip_address', 'user_agent',
    ];

    protected $casts = ['properties' => 'array'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public static function record(
        string $action,
        string $description,
        ?Model $subject = null,
        array $properties = [],
    ): void {
        // Seeder dan perintah artisan tidak perlu dicatat
        if (app()->runningInConsole()) {
            return;
        }

        try {
            $user = auth()->user();

            static::create([
                'user_id' => $user?->id,
                'user_name' => $user?->name ?? 'Sistem',
                'action' => $action,
                'description' => mb_substr($description, 0, 500),
                'subject_type' => $subject ? $subject::class : null,
                'subject_id' => $subject?->getKey(),
                'properties' => $properties ?: null,
                'ip_address' => request()->ip(),
                'user_agent' => mb_substr((string) request()->userAgent(), 0, 255),
            ]);
        } catch (\Throwable $e) {
            report($e); // gagal mencatat tidak boleh merusak fitur utama
        }
    }
}