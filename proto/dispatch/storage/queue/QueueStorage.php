<?php declare(strict_types=1);
namespace Proto\Dispatch\Storage\Queue;

use Proto\Storage\Storage;

/**
 * QueueStorage
 *
 * This will handle the queue storage.
 *
 * @package Proto\Dispatch\Storage\Queue
 */
class QueueStorage extends Storage
{
    /**
     * This will set the agent id to the next batch of items.
     *
     * @param string $agentId
     * @param int $count
     * @return bool
     */
    public function updateAgentId(string $agentId, int $count = 300): bool
    {
        $sql = $this->table()
            ->update('agent_id = ?')
            ->where('status = "pending"', 'agent_id = ""')
            ->orderBy('priority ASC')
            ->limit($count);

        return $this->execute($sql, [$agentId]);
    }

    /**
     * This will get the next batch of pending items.
     *
     * @param string $agentId
     * @param int $count
     * @return array
     */
    public function getPending(string $agentId, int $count = 300): array
    {
        $sql = $this->select()
            ->where('status = "pending"', 'agent_id = ?')
            ->orderBy('priority ASC')
            ->limit($count);

        return $this->fetch($sql, [$agentId]);
    }

    /**
     * This will update the batch item status.
     *
     * @param string $agentId
     * @param string $status
     * @return bool
     */
    public function updateStatusByAgentId(string $agentId, string $status): bool
    {
        $sql = $this->table()
            ->update('status = ?')
            ->where('status = "pending"', 'agent_id = ?');

        return $this->execute($sql, [$status, $agentId]);
    }

    /**
     * This will delete the batch.
     *
     * @param string $agentId
     * @param string $status
     * @return bool
     */
    public function deleteByAgentId(string $agentId): bool
    {
        $sql = $this->table()
            ->delete()
            ->where('status = "sending"', 'agent_id = ?');

        return $this->execute($sql, [$agentId]);
    }
}