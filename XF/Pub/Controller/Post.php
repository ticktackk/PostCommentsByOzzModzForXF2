<?php

namespace ThemeHouse\PostComments\XF\Pub\Controller;

use XF\Entity\Thread;
use XF\Mvc\ParameterBag;
use XF\Service\Thread\Replier;

/**
 * Class Post
 * @package ThemeHouse\PostComments\XF\Pub\Controller
 */
class Post extends XFCP_Post
{
    /**
     * @param ParameterBag $params
     * @return \XF\Mvc\Reply\View
     * @throws \XF\Mvc\Reply\Exception
     */
    public function actionComment(ParameterBag $params)
    {
        /** @noinspection PhpUndefinedFieldInspection */
        $post = $this->assertViewablePost($params->post_id, ['Thread']);
        $thread = $post->Thread;

        if (!$thread->canReply($error)) {
            return $this->noPermission($error);
        }

        /** @var \ThemeHouse\PostComments\XF\Entity\Post $post */
        if ($post->thpostcomments_depth >= $this->app->options()->thpostcomments_max_comment_depth) {
            return $this->noPermission(\XF::phrase('thpostcomments_max_nesting_depth_reached'));
        }

        if (!\XF::visitor()->hasNodePermission($thread->node_id, 'thpostcomments_comment')) {
            return $this->noPermission();
        }

        $forceAttachmentHash = null;

        if ($this->request->exists('requires_captcha')) {
            /** @noinspection PhpUndefinedMethodInspection */
            $defaultMessage = $this->plugin('XF:Editor')->fromInput('message');
            $forceAttachmentHash = $this->filter('attachment_hash', 'str');
        } else {
            /** @noinspection PhpUndefinedFieldInspection */
            $defaultMessage = $thread->draft_reply->message;
        }

        $viewParams = [
            'thread' => $thread,
            'post' => $post,
            'forum' => $thread->Forum,
            'attachmentData' => $this->getReplyAttachmentData($thread, $forceAttachmentHash),
            'defaultMessage' => $defaultMessage,
            'inlineComment' => $this->responseType() == 'json'
        ];

        return $this->view('ThemeHouse\PostComments:Post\Add', 'thpostcomments_post_comment', $viewParams);
    }

    /**
     * @param Thread $thread
     * @param null $forceAttachmentHash
     * @return array|null
     */
    protected function getReplyAttachmentData(Thread $thread, $forceAttachmentHash = null)
    {
        /** @var \XF\Entity\Forum $forum */
        $forum = $thread->Forum;

        if ($forum && $forum->canUploadAndManageAttachments()) {
            if ($forceAttachmentHash !== null) {
                $attachmentHash = $forceAttachmentHash;
            } else {
                /** @noinspection PhpUndefinedFieldInspection */
                $attachmentHash = $thread->draft_reply->attachment_hash;
            }

            /** @var \XF\Repository\Attachment $attachmentRepo */
            $attachmentRepo = $this->repository('XF:Attachment');
            return $attachmentRepo->getEditorData('post', $thread, $attachmentHash);
        } else {
            return null;
        }
    }

    /**
     * @param ParameterBag $params
     * @return \XF\Mvc\Reply\Error|\XF\Mvc\Reply\Redirect|\XF\Mvc\Reply\Reroute|\XF\Mvc\Reply\View
     * @throws \XF\Mvc\Reply\Exception
     */
    public function actionAddComment(ParameterBag $params)
    {
        $this->assertPostOnly();

        /** @noinspection PhpUndefinedFieldInspection */
        $post = $this->assertViewablePost($params->post_id, ['Thread']);
        $thread = $post->Thread;

        if (!$thread->canReply($error)) {
            return $this->noPermission($error);
        }

        /** @var \ThemeHouse\PostComments\XF\Entity\Post $post */
        if ($post->thpostcomments_depth >= $this->app->options()->thpostcomments_max_comment_depth) {
            return $this->noPermission(\XF::phrase('thpostcomments_max_nesting_depth_reached'));
        }

        if (!\XF::visitor()->hasNodePermission($post->Thread->node_id, 'thpostcomments_comment')) {
            return $this->noPermission();
        }

        $thread = $post->Thread;

        if ($this->filter('no_captcha', 'bool')) {
            $this->request->set('requires_captcha', true);
            return $this->rerouteController(__CLASS__, 'reply', $params);
        } else {
            if (!$this->captchaIsValid()) {
                return $this->error(\XF::phrase('did_not_complete_the_captcha_verification_properly'));
            }
        }

        /** @noinspection PhpUndefinedMethodInspection */
        $message = $this->plugin('XF:Editor')->fromInput('message');

        /** @var \ThemeHouse\PostComments\XF\Service\Thread\Replier $replier */
        $replier = $this->service('XF:Thread\Replier', $thread);
        $replier->setMessage($message);
        $replier->buildThPostCommentsCommentTree($post);

        if ($thread->Forum->canUploadAndManageAttachments()) {
            $replier->setAttachmentHash($this->filter('attachment_hash', 'str'));
        }

        $replier->checkForSpam();
        $errors = null;

        if (!$replier->validate($errors)) {
            return $this->error($errors);
        }

        $this->assertNotFlooding('post');
        $newPost = $replier->save();

        $this->finalizeThreadReply($replier);
        return $this->redirect($this->buildLink('posts', $newPost));
    }

    /**
     * @param Replier $replier
     */
    protected function finalizeThreadReply(Replier $replier)
    {
        $replier->sendNotifications();

        $thread = $replier->getThread();
        $post = $replier->getPost();
        $visitor = \XF::visitor();

        $setOptions = $this->filter('_xfSet', 'array-bool');
        if ($thread->canWatch()) {
            if (isset($setOptions['watch_thread'])) {
                $watch = $this->filter('watch_thread', 'bool');
                if ($watch) {
                    /** @var \XF\Repository\ThreadWatch $threadWatchRepo */
                    $threadWatchRepo = $this->repository('XF:ThreadWatch');

                    $state = $this->filter('watch_thread_email', 'bool') ? 'watch_email' : 'watch_no_email';
                    $threadWatchRepo->setWatchState($thread, $visitor, $state);
                }
            } else {
                // use user preferences
                /** @noinspection PhpUndefinedMethodInspection */
                $this->repository('XF:ThreadWatch')->autoWatchThread($thread, $visitor, false);
            }
        }

        if ($thread->canLockUnlock() && isset($setOptions['discussion_open'])) {
            $thread->discussion_open = $this->filter('discussion_open', 'bool');
        }
        if ($thread->canStickUnstick() && isset($setOptions['sticky'])) {
            $thread->sticky = $this->filter('sticky', 'bool');
        }

        $thread->saveIfChanged($null, false);

        if ($visitor->user_id) {
            $readDate = $thread->getVisitorReadDate();
            if ($readDate && $readDate >= $thread->getPreviousValue('last_post_date')) {
                $post = $replier->getPost();
                $this->getThreadRepo()->markThreadReadByVisitor($thread, $post->post_date);
            }

            $thread->draft_reply->delete();

            if ($post->message_state == 'moderated') {
                $this->session()->setHasContentPendingApproval();
            }
        }
    }
}
