<?php declare(strict_types=1);
namespace Proto\Storage;

class WebPushUserStorage extends Storage
{
	public function getByUser($userId, $limit = false)
	{
		if(!isset($userId))
		{
			return false;
		}

		$sql = $this->select()
            ->where("user_id = ?")
            ->limit($limit);

		$rows = $this->fetch($sql, [$userId]);
		return isset($rows[0])? $rows[0] : false;
    }

    /**
     * This will get a web push user by client id.
     *
     * @param mixed $clientId
     * @param string|null $type
     * @return array|null
     */
    public function getByClientId(mixed $clientId, ?string $type = null): ?array
	{
		if (!isset($clientId))
		{
			return [];
		}

        $select = $this->table('w')->select('*');

		if($type)
        {
            $select
                ->join(function($join)
                {
                    $join->left('user_locations', 'l')->on(['w.user_id', 'l.user_id']);
                    $join->left('users', 'u')->on(['l.user_id', 'u.id']);
                })
                ->where('l.client_id = ?', "u.allow_access = '1'", "u.is_guest != '1'", "(n.name = ? AND ns.status != '0' OR ns.status IS NULL)");

            $rows = $this->fetch($select, [$clientId, $type]);
        }
        else
        {
            $select
                ->join(function($join)
                {
                    $join->left('user_locations', 'l')->on(['w.user_id', 'l.user_id']);
                    $join->left('users', 'u')->on(['l.user_id', 'u.id']);
                    $join->left('user_notification_settings', 's')->on(['u.id', 's.user_id']);
                    $join->left('notification_types', 'n')->on(['s.notification_id', 'n.id']);
                })
                ->where('l.client_id = ?', "u.allow_access = '1'", "u.is_guest != '1'", "w.status = 'active'");

            $rows = $this->fetch($select, [$clientId]);
        }

		return $rows ?? [];
    }

    public function updateStatusByKey($key, $status)
    {
        $sql = $this->table()
            ->update("status = ?")
            ->where("auth_keys = ?");

        return $this->db->query($sql, [$status, $key]);
    }

    protected function exists($data): bool
	{
        $sql = $this->select('id')
            ->where('user_id = ?', 'endpoint = ?')
            ->limit(1);

		$rows = $this->fetch($sql, [$data->userId, $data->endpoint]);
		return $this->checkExistCount($rows);
	}
}