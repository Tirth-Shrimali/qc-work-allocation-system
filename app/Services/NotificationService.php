<?php

namespace App\Services;

use App\Models\AppNotification;
use App\Models\User;

class NotificationService
{
    /**
     * Notify one or many users (by id).
     *
     * @param  int|array<int>  $userIds
     */
    public static function send(int|array $userIds, string $type, string $title, string $message, ?string $link = null): void
    {
        $ids = is_int($userIds)
            ? [$userIds]
            : array_values(array_unique(array_filter($userIds, fn ($id) => $id && $id > 0)));

        foreach ($ids as $id) {
            AppNotification::create([
                'user_id' => $id,
                'type' => $type,
                'title' => $title,
                'message' => $message,
                'link' => $link,
            ]);
        }
    }

    /**
     * Notify every user holding one of the given role codes.
     *
     * @param  array<string>  $roleCodes
     */
    public static function toRoles(array $roleCodes, string $type, string $title, string $message, ?string $link = null): void
    {
        $ids = User::whereHas('roles', fn ($q) => $q->whereIn('code', $roleCodes))
            ->pluck('id')
            ->all();

        self::send($ids, $type, $title, $message, $link);
    }
}
