<?php

namespace ThemeHouse\PostComments\XF\Service\Thread;

use XF\Entity\Post;

/**
 * Class Replier
 * @package ThemeHouse\PostComments\XF\Service\Thread
 *
 * @property \ThemeHouse\PostComments\XF\Entity\Post post
 */
class Replier extends XFCP_Replier
{
    /**
     * @param Post $parentPost
     */
    public function buildThPostCommentsCommentTree(Post $parentPost)
    {
        $this->post->thpostcomments_parent_post_id = $parentPost->post_id;
        /** @var \ThemeHouse\PostComments\XF\Entity\Post $parentPost */
        $this->post->thpostcomments_depth = $parentPost->thpostcomments_depth + 1;
        $this->post->hydrateRelation('ParentPost', $parentPost);
        $this->post->buildThPostCommentsRootPost();
    }
}
