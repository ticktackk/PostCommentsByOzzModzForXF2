<?php

namespace ThemeHouse\PostComments\XF\Finder;

use XF\Entity\Thread;

/**
 * Class Post
 * @package ThemeHouse\PostComments\XF\Finder
 */
class Post extends XFCP_Post
{
    /**
     * @param Thread $thread
     * @param array $limits
     * @return $this
     */
    public function inThread(Thread $thread, array $limits = [])
    {
        $finder = parent::inThread($thread, $limits);

        $finder->order([
            ['position', 'ASC'],
            ['thpostcomments_lft', 'ASC']
        ]);

        return $finder;
    }
}
