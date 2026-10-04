<?php

namespace App\Support;

/**
 * Central work status engine. Every status transition in the system
 * must pass through WorkStatus::canTransition().
 */
class WorkStatus
{
    public const NEW = 'NEW';
    public const ALLOCATED = 'ALLOCATED';
    public const ACCEPTED = 'ACCEPTED';
    public const IN_PROGRESS = 'IN_PROGRESS';
    public const ON_HOLD = 'ON_HOLD';
    public const SUBMITTED = 'SUBMITTED';
    public const UNDER_REVIEW = 'UNDER_REVIEW';
    public const REWORK = 'REWORK';
    public const APPROVED = 'APPROVED';
    public const COMPLETED = 'COMPLETED';
    public const REJECTED = 'REJECTED';
    public const CANCELLED = 'CANCELLED';

    /**
     * Allowed transitions: from => [to, ...].
     */
    public const TRANSITIONS = [
        self::NEW => [self::ALLOCATED, self::CANCELLED],
        self::ALLOCATED => [self::ACCEPTED, self::CANCELLED],
        self::ACCEPTED => [self::IN_PROGRESS, self::ON_HOLD, self::CANCELLED],
        self::IN_PROGRESS => [self::SUBMITTED, self::ON_HOLD, self::CANCELLED],
        self::ON_HOLD => [self::IN_PROGRESS, self::CANCELLED],
        self::SUBMITTED => [self::UNDER_REVIEW, self::CANCELLED],
        self::UNDER_REVIEW => [self::APPROVED, self::REJECTED, self::REWORK],
        self::REWORK => [self::IN_PROGRESS, self::CANCELLED],
        self::APPROVED => [self::COMPLETED, self::CANCELLED],
        self::REJECTED => [self::CANCELLED],
        self::COMPLETED => [],
        self::CANCELLED => [],
    ];

    public const LABELS = [
        self::NEW => 'New',
        self::ALLOCATED => 'Allocated',
        self::ACCEPTED => 'Accepted',
        self::IN_PROGRESS => 'In Progress',
        self::ON_HOLD => 'On Hold',
        self::SUBMITTED => 'Submitted',
        self::UNDER_REVIEW => 'Under Review',
        self::REWORK => 'Rework',
        self::APPROVED => 'Approved',
        self::COMPLETED => 'Completed',
        self::REJECTED => 'Rejected',
        self::CANCELLED => 'Cancelled',
    ];

    public const BADGES = [
        self::NEW => 'info',
        self::ALLOCATED => 'primary',
        self::ACCEPTED => 'secondary',
        self::IN_PROGRESS => 'warning',
        self::ON_HOLD => 'secondary',
        self::SUBMITTED => 'info',
        self::UNDER_REVIEW => 'primary',
        self::REWORK => 'danger',
        self::APPROVED => 'success',
        self::COMPLETED => 'success',
        self::REJECTED => 'danger',
        self::CANCELLED => 'dark',
    ];

    /** Statuses considered "open" (still being worked on). */
    public const OPEN = [
        self::NEW, self::ALLOCATED, self::ACCEPTED, self::IN_PROGRESS,
        self::ON_HOLD, self::SUBMITTED, self::UNDER_REVIEW, self::REWORK, self::APPROVED,
    ];

    /** Statuses in the analyst's execution scope. */
    public const EXECUTION = [
        self::ALLOCATED, self::ACCEPTED, self::IN_PROGRESS, self::ON_HOLD, self::REWORK,
    ];

    public static function canTransition(string $from, string $to): bool
    {
        if ($from === $to) {
            return false;
        }

        return in_array($to, self::TRANSITIONS[$from] ?? [], true);
    }

    public static function label(string $status): string
    {
        return self::LABELS[$status] ?? ucfirst(str_replace('_', ' ', strtolower($status)));
    }

    public static function badge(string $status): string
    {
        return self::BADGES[$status] ?? 'secondary';
    }

    public static function all(): array
    {
        return array_keys(self::LABELS);
    }
}
