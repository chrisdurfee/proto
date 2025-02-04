<?php declare(strict_types=1);
namespace Proto\Storage;

class UserNotificationSettingStorage extends Storage
{
	public function getByUserId($userId)
	{
		if(!isset($userId))
		{
			return false;
		}

		$sql = "SELECT
                    n.name,
                    n.id AS notification_id,
                    (CASE WHEN ns.status = '1' OR ns.status IS NULL THEN '1' ELSE '0' END) AS status
                FROM
                    notification_types n
                    LEFT JOIN {$this->tableName} ns ON ns.notification_id = n.id AND ns.user_id = ?;";

		$rows = $this->fetch($sql, [$userId]);
		return isset($rows[0])? $rows[0] : false;
    }

    /**
	 * This will add or update the model to the table.
	 *
	 * @return bool
	 */
	public function setup(): bool
	{
		$sql = "INSERT INTO {$this->tableName}
                    (notification_id, user_id, status)
                VALUES
                    (?, ?, ?)
                ON DUPLICATE KEY UPDATE
                    status = VALUES(status)";

        $data = $this->getData();
        return $this->db->query($sql, [$data->notificationId, $data->userId, $data->status]);
	}
}