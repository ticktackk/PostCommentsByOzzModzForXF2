<?php

namespace ThemeHouse\PostComments\Behavior;

use ThemeHouse\PostComments\XF\Entity\Post;
use XF\Mvc\Entity\Behavior;

/**
 * Class CommentTreeStructured
 * @package ThemeHouse\Comments\Behavior
 */
class CommentTreeStructured extends Behavior
{
    /**
     *
     */
    public function postSave()
    {
        if ($this->getOption('rebuildCache')) {
            $rebuild = (
                $this->entity->isChanged([
                    $this->config['parentField'],
                    $this->config['orderField']
                ])
                || ($this->config['rebuildExtraFields'] && $this->entity->isChanged($this->config['rebuildExtraFields']))
            );

            if ($rebuild) {
                $this->scheduleNestedSetRebuild();
            }
        }
    }

    /**
     *
     */
    protected function scheduleNestedSetRebuild()
    {
        /** @var \ThemeHouse\PostComments\XF\Entity\Post $entity */
        $entity = $this->entity;

        if (!empty($entity->Thread->FirstPost)) {
            $entityType = $entity->structure()->shortName;
            $config = [
                'parentField' => $this->config['parentField'],
                'orderField' => $this->config['orderField'],
                'rootField' => $this->config['rootField'],
                'keyColumn' => $this->config['keyColumn'],
                'rootId' => $entity->Thread->FirstPost->getValue($this->config['rootField']),
                'threadId' => $entity->thread_id
            ];

            \XF::runOnce('rebuildTree-' . $entityType, function () use ($entityType, $config) {
                $service = $this->app()->service($this->config['rebuildService'], $entityType, $config);
                /** @noinspection PhpUndefinedMethodInspection */
                $service->rebuildNestedSetInfo();
            });
        }
    }

    /**
     *
     * @throws \XF\PrintableException
     */
    public function postDelete()
    {
        if ($this->getOption('deleteChildAction') == 'delete') {
            $this->deleteChildren();
        } else {
            $parentId = $this->entity->getValue($this->config['parentField']);
            $this->moveChildrenTo($parentId);
        }

        if ($this->getOption('rebuildCache')) {
            $this->scheduleNestedSetRebuild();
        }
    }

    /**
     *
     * @throws \XF\PrintableException
     */
    protected function deleteChildren()
    {
        $finder = $this->entity->em()->getFinder($this->entity->structure()->shortName);
        $finder->where($this->config['parentField'], $this->entity->getEntityId());

        foreach ($finder->fetch() as $child) {
            /** @var Post $child */
            $child->getBehavior('ThemeHouse\PostComments:CommentTreeStructured')->setOption('rebuildCache', false);
            $child->delete(true, false);
        }
    }

    /**
     * @param $newParentId
     */
    protected function moveChildrenTo($newParentId)
    {
        $parentField = $this->config['parentField'];

        $this->entity->db()->update(
            $this->entity->structure()->table,
            [$parentField => $newParentId],
            "`{$parentField}` = ?",
            $this->entity->getEntityId()
        );
    }

    /**
     * @return array
     */
    protected function getDefaultConfig()
    {
        return [
            'parentField' => 'parent_id',
            'orderField' => 'display_order',
            'rootField' => 'root_id',
            'keyColumn' => $this->entity->structure()->primaryKey,
            'rebuildExtraFields' => [],
            'rebuildService' => 'ThemeHouse\PostComments:Post\RebuildNestedSet',
        ];
    }

    /**
     * @return array
     */
    protected function getDefaultOptions()
    {
        return [
            'rebuildCache' => true,
            'deleteChildAction' => 'move'
        ];
    }
}
