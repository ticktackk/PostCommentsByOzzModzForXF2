<?php

namespace ThemeHouse\PostComments\Service\Post;

use ThemeHouse\PostComments\XF\Entity\Post;
use XF\Mvc\Entity\Entity;

/**
 * Class RebuildNestedSet
 * @package ThemeHouse\PostComments\Service\Post
 */
class RebuildNestedSet extends \XF\Service\RebuildNestedSet
{
    /**
     * @var int
     */
    protected $position = 0;

    /**
     * @return array
     */
    protected function getDefaultConfig()
    {
        $config = parent::getDefaultConfig();
        $config['threadId'] = 0;
        return $config;
    }

    /**
     * @return array
     */
    protected function getBasePassableData()
    {
        return [];
    }

    /**
     * @param $id
     * @param array $passDown
     * @param int $depth
     * @param int $counter
     */
    protected function _rebuildNestedSetInfo($id, array $passDown, $depth = -1, &$counter = 0)
    {
        /** @var \XF\Mvc\Entity\Entity $entity */
        $entity = $this->tree->getData($id);

        if ($entity) {
            $counter++;
        }
        $left = $counter;

        if ($entity) {
            $selfData = $this->getSelfData($passDown, $entity, $depth, $left);
            $childPassDown = $this->getChildPassableData($passDown, $entity, $depth, $left);
        } else {
            $selfData = [];
            $childPassDown = $passDown;
        }

        foreach ($this->tree->childIds($id) as $childId) {
            $this->_rebuildNestedSetInfo($childId, $childPassDown, $depth + 1, $counter);
        }

        if ($entity) {
            $counter++;
        }
        $right = $counter;

        if ($entity) {
            /** @var Post $entity */
            $updateData = $selfData + [
                    'thpostcomments_lft' => $left,
                    'thpostcomments_rgt' => $right,
                    'thpostcomments_depth' => $depth,
                    'position' => $entity->message_state === 'visible' ? $this->position : max(0, $this->position - 1),
                ];

            if (!$depth && $entity->message_state === 'visible') {
                $this->position++;
            }

            $entity->fastUpdate($updateData);
        }
    }

    /**
     * @param array $passData
     * @param Entity $entity
     * @param $depth
     * @param $left
     * @return array
     */
    protected function getSelfData(array $passData, Entity $entity, $depth, $left)
    {
        return parent::getSelfData($passData, $entity, $depth, $left);
    }

    /**
     * @param array $passData
     * @param Entity $entity
     * @param $depth
     * @param $left
     * @return array
     */
    protected function getChildPassableData(array $passData, Entity $entity, $depth, $left)
    {
        return $passData;
    }

    /**
     * @return \XF\Mvc\Entity\ArrayCollection
     */
    protected function getEntities()
    {
        return $this->finder($this->entityType)
            ->where('thread_id', $this->config['threadId'])
            ->order($this->config['orderField'])
            ->fetch();
    }
}
