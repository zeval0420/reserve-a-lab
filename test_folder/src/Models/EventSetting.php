<?php

require_once __DIR__ . '/Model.php';

class EventSetting extends Model
{
    protected static string $table = 'event_settings';

    protected static array $fillable = [
        'event_id', 'default_points', 'default_time_seconds', 'ranking_order',
        'tie_break_mode', 'auto_show_ranking_after_score', 'timer_warning_seconds',
        'display_theme', 'extra_settings',
    ];

    public static function forEvent(int $eventId): ?array
    {
        $rows = self::where(['event_id' => $eventId]);

        return $rows[0] ?? null;
    }

    /**
     * Presentation toggles (show category / question number / timer on the
     * public display) are simple booleans with no relational meaning of
     * their own, so they live in the `extra_settings` JSON column rather
     * than as dedicated typed columns -- exactly the case that column was
     * designed for (see migration 002). This helper applies defaults so
     * every caller sees a complete set of keys even for events created
     * before a given toggle existed.
     *
     * @return array{show_category: bool, show_question_number: bool, show_timer: bool}
     */
    public static function presentationToggles(array $eventSetting): array
    {
        $defaults = [
            'show_category'         => true,
            'show_question_number'  => true,
            'show_timer'            => true,
        ];

        $stored = [];
        if (!empty($eventSetting['extra_settings'])) {
            $decoded = json_decode((string) $eventSetting['extra_settings'], true);
            if (is_array($decoded)) {
                $stored = $decoded;
            }
        }

        $merged = array_merge($defaults, array_intersect_key($stored, $defaults));

        foreach ($merged as $key => $value) {
            $merged[$key] = (bool) $value;
        }

        return $merged;
    }

    /**
     * Every event needs exactly one settings row. Admin "create event" flow
     * should call this immediately after Event::create() so the rest of the
     * app can always assume settings exist rather than null-checking
     * everywhere.
     */
    public static function createDefaultsFor(int $eventId): array
    {
        return self::create(['event_id' => $eventId]);
    }

    public static function create(array $attributes): array
    {
        if (array_key_exists('extra_settings', $attributes) && is_array($attributes['extra_settings'])) {
            $attributes['extra_settings'] = json_encode($attributes['extra_settings']);
        }

        return parent::create($attributes);
    }

    public static function update(int $id, array $attributes): array
    {
        if (array_key_exists('extra_settings', $attributes) && is_array($attributes['extra_settings'])) {
            $attributes['extra_settings'] = json_encode($attributes['extra_settings']);
        }

        return parent::update($id, $attributes);
    }
}
