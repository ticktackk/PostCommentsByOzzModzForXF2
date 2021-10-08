<?php

namespace ThemeHouse\PostComments\Repository;

use XF\Mvc\Entity\Repository;
use XF\Tree;

/**
 * Class Post
 * @package ThemeHouse\PostComments\Repository
 */
class Post extends Repository
{
    /**
     * @param $posts
     * @param int $rootId
     * @return Tree
     */
    public function createPostTree($posts, $rootId = 0)
    {
        return new Tree($posts, 'thpostcomments_parent_post_id', $rootId);
    }
}
