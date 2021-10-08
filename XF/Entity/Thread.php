<?php

namespace ThemeHouse\PostComments\XF\Entity;

use XF\Mvc\Entity\Structure;

/**
 * Class Thread
 * @package ThemeHouse\PostComments\XF\Entity
 *
 * @property integer thpostcomments_root_reply_count
 */
class Thread extends XFCP_Thread
{
    /**
     * @param Structure $structure
     * @return Structure
     */
    public static function getStructure(Structure $structure)
    {
        $structure = parent::getStructure($structure);

        $structure->columns['thpostcomments_root_reply_count'] = [
            'type' => self::UINT,
            'default' => 0
        ];

        return $structure;
    }

    /**
     * @return int|mixed
     */
    public function rebuildReplyCount()
    {
        $this->rebuildThPostCommentsReplyCount();

        return parent::rebuildReplyCount();
    }

    /**
     * @return mixed|null
     */
    public function rebuildThPostCommentsReplyCount()
    {
        $visiblePosts = $this->db()->fetchOne("
            SELECT COUNT(*)
            FROM xf_post
            WHERE thread_id = ?
                AND message_state = 'visible'
                AND thpostcomments_depth = 0
		", $this->thread_id);
        $this->thpostcomments_root_reply_count = max(0, $visiblePosts - 1);

        return $this->thpostcomments_root_reply_count;
    }

    /**
     * @return bool
     */
    public function rebuildThPostCommentsCounters()
    {
        $this->rebuildThPostCommentsReplyCount();

        return true;
    }

    public function rebuildLastPostInfo()
    {
        $lastPost = $this->db()->fetchRow("
			SELECT post_id, post_date, user_id, username
			FROM xf_post
			WHERE thread_id = ?
				AND message_state = 'visible'
				AND thpostcomments_depth = 0
			ORDER BY post_date DESC
			LIMIT 1
		", $this->thread_id);
        if (!$lastPost) {
            return false;
        }

        $this->last_post_id = $lastPost['post_id'];
        $this->last_post_date = $lastPost['post_date'];
        $this->last_post_user_id = $lastPost['user_id'];
        $this->last_post_username = $lastPost['username'] ?: '-';

        return true;
    }

    /**
     * @param \XF\Entity\Post $post
     */
    public function postAdded(\XF\Entity\Post $post)
    {
        /** @var \ThemeHouse\PostComments\XF\Entity\Post $post */
        if ($this->first_post_id && $post->thpostcomments_depth == 0) {
            $this->thpostcomments_root_reply_count++;
        }

        if ($post->thpostcomments_depth == 0) {
            parent::postAdded($post);
        } else {
            $this->reply_count++;
        }

        unset($this->_getterCache['post_ids']);
    }

    /**
     * @param \XF\Entity\Post $post
     */
    public function postRemoved(\XF\Entity\Post $post)
    {
        /** @var \ThemeHouse\PostComments\XF\Entity\Post $post */
        if ($post->thpostcomments_depth == 0) {
            $this->thpostcomments_root_reply_count--;
        }

        parent::postRemoved($post);
    }
}
