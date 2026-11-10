<?php
declare(strict_types=1);
namespace App\Services;

use PDO;

final class SystemUserDirectory
{
    public const SORTS = ['name' => 'Nome', 'registered_at' => 'Data de inclusão', 'last_seen_at' => 'Último acesso'];

    public static function selection(mixed $sort, mixed $direction): array
    {
        $sort = is_string($sort) && isset(self::SORTS[$sort]) ? $sort : 'registered_at';
        $direction = is_string($direction) && in_array($direction, ['asc', 'desc'], true) ? $direction : ($sort === 'name' ? 'asc' : 'desc');
        return [$sort, $direction];
    }

    public static function users(PDO $db, int $system, mixed $sort, mixed $direction): array
    {
        [$sort, $direction] = self::selection($sort, $direction);
        $expression = ['name' => 'lower(u.name)', 'registered_at' => 'coalesce(u.registered_at,u.created_at)', 'last_seen_at' => 'u.last_seen_at'][$sort];
        $sql = 'select u.*, s.name system_name from system_users u join saas_systems s on s.id=u.system_id';
        if ($system > 0) $sql .= ' where u.system_id=:system_id';
        // Accounts without a recorded login stay last in either direction.
        $sql .= ' order by case when ' . $expression . ' is null then 1 else 0 end, ' . $expression . ' ' . $direction . ', lower(u.name), u.id';
        $q = $db->prepare($sql);
        $q->execute($system > 0 ? ['system_id' => $system] : []);
        return $q->fetchAll(PDO::FETCH_ASSOC);
    }
}
