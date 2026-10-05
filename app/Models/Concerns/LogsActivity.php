<?php

namespace App\Models\Concerns;

use App\Models\ActivityLog;

trait LogsActivity
{
    public static function bootLogsActivity(): void
    {
        static::created(function ($m) {
            ActivityLog::record('created', "Menambah {$m->activityLabel()} \"{$m->activityName()}\"", $m);
        });

        static::updated(function ($m) {
            $changes = collect($m->getChanges())
                ->except(['updated_at', 'created_at', 'deleted_at', 'remember_token']);

            if ($changes->isEmpty()) {
                return; // misalnya hanya remember_token yang berubah saat login
            }

            $old = [];
            $new = [];
            foreach ($changes as $field => $value) {
                if ($field === 'password') {
                    $new[$field] = '(diubah)'; // password tidak pernah dicatat
                    continue;
                }
                $old[$field] = $m->getRawOriginal($field);
                $new[$field] = $value;
            }

            ActivityLog::record(
                'updated',
                "Mengubah {$m->activityLabel()} \"{$m->activityName()}\"",
                $m,
                ['old' => $old, 'new' => $new],
            );
        });

        static::deleted(function ($m) {
            ActivityLog::record('deleted', "Menghapus {$m->activityLabel()} \"{$m->activityName()}\"", $m);
        });
    }

    public function activityLabel(): string
    {
        return property_exists($this, 'activityLabel') ? $this->activityLabel : class_basename($this);
    }

    public function activityName(): string
    {
        return (string) ($this->name ?? $this->getKey());
    }
}