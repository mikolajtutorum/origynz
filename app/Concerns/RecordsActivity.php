<?php

namespace App\Concerns;

use Illuminate\Support\Str;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

trait RecordsActivity
{
    use LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('audit')
            ->logFillable()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->dontLogIfAttributesChangedOnly(['updated_at'])
            ->logExcept($this->activityLogExcept())
            ->setDescriptionForEvent(fn (string $eventName): string => $this->activityDescription($eventName));
    }

    /**
     * @return list<string>
     */
    protected function activityLogExcept(): array
    {
        return [
            'created_at',
            'updated_at',
        ];
    }

    protected function activityDescription(string $eventName): string
    {
        return strtolower($eventName.' '.Str::headline(class_basename(static::class)));
    }
}
