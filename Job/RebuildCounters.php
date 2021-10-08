<?php

namespace ThemeHouse\PostComments\Job;

use ThemeHouse\PostComments\XF\Entity\Thread;
use XF\Job\AbstractRebuildJob;

/**
 * Class RebuildCounters
 * @package ThemeHouse\PostComments\Job
 */
class RebuildCounters extends AbstractRebuildJob
{
    /**
     * @param $start
     * @param $batch
     * @return array
     */
    protected function getNextIds($start, $batch)
    {
        $db = $this->app->db();

        return $db->fetchAllColumn($db->limit(
            "
                SELECT thread_id
                FROM xf_thread
                WHERE thread_id > ?
                ORDER BY thread_id
            ",
            $batch
        ), $start);
    }

    /**
     * @param $id
     */
    protected function rebuildById($id)
    {
        /** @var \XF\Entity\Thread $thread */
        $thread = $this->app->em()->find('XF:Thread', $id);
        if (!$thread) {
            return;
        }

        /** @var Thread $thread */
        if ($thread->rebuildThPostCommentsCounters()) {
            $thread->saveIfChanged();
        }
    }

    /**
     * @return \XF\Phrase
     */
    protected function getStatusType()
    {
        return \XF::phrase('threads');
    }
}
