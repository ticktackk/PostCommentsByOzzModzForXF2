<?php

namespace ThemeHouse\PostComments\Job;

use XF\Job\AbstractRebuildJob;

/**
 * Class RebuildNesting
 * @package ThemeHouse\PostComments\Job
 */
class RebuildNesting extends AbstractRebuildJob
{
    /**
     * @var array
     */
    protected $config = [];

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
        $thread = $this->app->em()->find('XF:Thread', $id, ['FirstPost']);
        if (!$thread) {
            return;
        }

        $this->getConfig();

        if (!empty($thread->FirstPost)) {
            $entityType = 'XF:Post';
            $config = [
                'parentField' => $this->config['parentField'],
                'orderField' => $this->config['orderField'],
                'rootField' => $this->config['rootField'],
                'keyColumn' => $this->config['keyColumn'],
                'rootId' => $thread->FirstPost->getValue($this->config['rootField']),
                'threadId' => $thread->thread_id
            ];

            $service = \XF::app()->service($this->config['rebuildService'], $entityType, $config);
            /** @noinspection PhpUndefinedMethodInspection */
            $service->rebuildNestedSetInfo();
        }
    }

    /**
     *
     */
    protected function getConfig()
    {
        $this->config = $this->getDefaultConfig();
    }

    /**
     * @return array
     */
    protected function getDefaultConfig()
    {
        return [
            'parentField' => 'thpostcomments_parent_post_id',
            'orderField' => 'position',
            'rootField' => 'thpostcomments_root_post_id',
            'keyColumn' => 'post_id',
            'rebuildExtraFields' => [],
            'rebuildService' => 'ThemeHouse\PostComments:Post\RebuildNestedSet',
        ];
    }

    /**
     * @return \XF\Phrase
     */
    protected function getStatusType()
    {
        return \XF::phrase('threads');
    }
}
