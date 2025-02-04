<?php declare(strict_types=1);
namespace Proto\Storage;

class UserLocationStorage extends Storage
{
	public function getLocations($userId)
	{
		if(!isset($userId))
		{
			return false;
		}

		$row = $this->select()
            ->where("user_id = ?")
            ->first([$userId]);

		return $row ?? false;
    }

    public function getUsers($clientId, $filter = null)
	{
		if(!isset($clientId))
		{
			return false;
		}

        $params = [$clientId];
        $select = $this->table('l')
            ->select('*')
            ->join(function($join)
            {
                $join->left('users', 'u')->on(['l.user_id', 'u.id']);
            })
            ->where('l.client_id = ?', "u.allow_access = '1'");

		if($filter)
        {
            $select
                ->where("(u.last LIKE ? OR u.first LIKE ?)");

            $search = '%' . $filter . '%';
            $params = [$clientId, $search, $search];
        }

        $rows = $this->fetch($select, $params);

		return isset($rows[0])? $rows[0] : false;
    }

    protected function exists($data): bool
	{
        $sql = $this->select('id')
            ->where("user_id = ?", "client_id = ?")
            ->limit(1);

		$rows = $this->fetch($sql, [$data->userId, $data->clientId]);
		return $this->checkExistCount($rows);
    }

    public function deleteMissingLocations($userId, $clientIds)
    {
        $locations = array_fill(0, count($clientIds), '?');
        $where = "'" . join("','", $locations) . "'";

        $sql = $this->table()
            ->delete()
            ->where("user_id = ?", "client_id NOT IN ($where)");

        return $this->db->query($sql, [$userId, ...$clientIds]);
    }

    public function deleteByUserId($userId)
    {
        $sql = $this->table()
            ->delete()
            ->where('user_id = ?');

        return $this->db->query($sql, [$userId]);
    }
}