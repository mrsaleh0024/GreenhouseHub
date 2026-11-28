<?php

namespace App\Models;

use App\Models\Builders\ActivityBuilder;
use App\Models\Concerns\VisibleToUser;
use App\Support\ActivityChangeRedactor;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Relations\Relation;

class Activity extends Model
{
    use VisibleToUser;

    public $timestamps = false;

    protected $fillable = [
        'occurred_at',
        'actor_id',
        'actor_role',
        'event',
        'subject_type',
        'subject_id',
        'customer_id',
        'greenhouse_id',
        'description',
        'changes',
        'ip_address',
    ];

    /**
     * Raw change payloads and IP addresses are internal/PII. Keep them out
     * of default array/JSON serialization so future consumers (including AI
     * services) cannot leak them accidentally. Use redactedChanges() for a
     * safe representation.
     */
    protected $hidden = ['changes', 'ip_address'];

    protected function casts(): array
    {
        return ['occurred_at' => 'datetime', 'changes' => 'array'];
    }

    protected static function booted(): void
    {
        static::updating(fn () => throw new \LogicException('رویدادها تغییرناپذیرند.'));
        static::deleting(fn () => throw new \LogicException('رویدادها حذف‌پذیر نیستند.'));
    }

    public function newEloquentBuilder($query): ActivityBuilder
    {
        return new ActivityBuilder($query);
    }

    /**
     * Allow legitimate model inserts without exposing raw insert
     * operations through the public Eloquent activity builder.
     */
    protected function insertAndSetId(
        \Illuminate\Database\Eloquent\Builder $query,
        $attributes
    ) {
        $keyName = $this->getKeyName();

        $id = $query->getQuery()->insertGetId(
            $attributes,
            $keyName
        );

        $this->setAttribute($keyName, $id);
    }

    public function subject(): MorphTo
    {
        return $this->morphTo();
    }

    /** Resolve the subject table from the stored morph alias or class. */
    public function subjectTable(): string
    {
        $type = (string) $this->subject_type;
        $class = Relation::getMorphedModel($type);

        if ($class === null && class_exists($type) && is_subclass_of($type, Model::class)) {
            $class = $type;
        }

        return $class ? (new $class)->getTable() : $type;
    }

    /**
     * Return a safe view of the change payload, including for historical
     * records that were written before redaction was introduced.
     *
     * @return array<string, mixed>
     */
    public function redactedChanges(): array
    {
        return app(ActivityChangeRedactor::class)->redact($this->subjectTable(), $this->getAttribute('changes') ?? []);
    }
}
