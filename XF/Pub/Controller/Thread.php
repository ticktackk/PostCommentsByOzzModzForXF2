<?php

namespace ThemeHouse\PostComments\XF\Pub\Controller;

use XF\Mvc\ParameterBag;
use XF\Mvc\Reply\View;

/**
 * Class Thread
 * @package ThemeHouse\PostComments\XF\Pub\Controller
 */
class Thread extends XFCP_Thread
{
    /**
     * @param ParameterBag $params
     * @return View
     */
    public function actionIndex(ParameterBag $params)
    {
        $view = parent::actionIndex($params);

        if ($view instanceof View && $view->getParam('posts')) {
            $thread = $view->getParam('thread');
            $view->setParam('total', $thread->thpostcomments_root_reply_count + 1);

            $posts = $view->getParam('posts');
            /** @var \ThemeHouse\PostComments\Repository\Post $postRepo */
            $postRepo = \XF::repository('ThemeHouse\PostComments:Post');
            $nestedPosts = $postRepo->createPostTree($posts);
            $view->setParam('nestedPosts', $nestedPosts);
        }

        return $view;
    }

    /**
     * @param ParameterBag $params
     * @return mixed
     */
    public function actionThreadVotes(ParameterBag $params)
    {
        /** @noinspection PhpUndefinedMethodInspection */
        $view = parent::actionThreadVotes($params);

        if ($view instanceof View && $view->getParam('posts')) {
            $thread = $view->getParam('thread');
            $view->setParam('total', $thread->thpostcomments_root_reply_count + 1);

            $posts = $view->getParam('posts');
            /** @var \ThemeHouse\PostComments\Repository\Post $postRepo */
            $postRepo = \XF::repository('ThemeHouse\PostComments:Post');
            $nestedPosts = $postRepo->createPostTree($posts);
            $view->setParam('nestedPosts', $nestedPosts);
        }

        return $view;
    }

    /**
     * @param \XF\Entity\Thread $thread
     * @param $lastDate
     * @return View
     */
    protected function getNewPostsReply(\XF\Entity\Thread $thread, $lastDate)
    {
        $view = parent::getNewPostsReply($thread, $lastDate);

        if ($view instanceof View && $view->getParam('posts')) {
            /** @var \XF\Mvc\Entity\ArrayCollection $posts */
            $posts = $view->getParam('posts');
            $view->setParam('posts', [$view->getParam('thread')->LastPost]);
            $posts = $posts->pop();
            $view->setParam('firstUnshownPost', $posts->last());
        }

        return $view;
    }
}
