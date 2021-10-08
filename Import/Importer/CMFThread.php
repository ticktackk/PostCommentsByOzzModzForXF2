<?php

namespace ThemeHouse\PostComments\Import\Importer;

use XF\Import\Importer\AbstractImporter;
use XF\Import\StepState;

/**
 * Class CMFThread
 * @package ThemeHouse\PostComments\Import\Importer
 */
class CMFThread extends AbstractImporter
{
    /**
     * @return array
     */
    public static function getListInfo()
    {
        return [
            'target' => '[TH] Post Comments',
            'source' => 'CMF Threads'
        ];
    }

    /**
     * @param array $vars
     * @return bool
     */
    public function renderBaseConfigOptions(array $vars)
    {
        return false;
    }

    /**
     * @param array $baseConfig
     * @param array $errors
     * @return bool
     */
    public function validateBaseConfig(array &$baseConfig, array &$errors)
    {
        return true;
    }

    /**
     * @param array $vars
     * @return bool
     */
    public function renderStepConfigOptions(array $vars)
    {
        return false;
    }

    /**
     * @param array $steps
     * @param array $stepConfig
     * @param array $errors
     * @return bool
     */
    public function validateStepConfig(array $steps, array &$stepConfig, array &$errors)
    {
        return true;
    }

    /**
     * @return bool
     */
    public function canRetainIds()
    {
        return false;
    }

    /**
     * @return bool
     */
    public function resetDataForRetainIds()
    {
        return false;
    }

    /**
     * @return array
     */
    public function getSteps()
    {
        return [
            'posts' => ['title' => 'Posts']
        ];
    }

    /**
     * @return int
     */
    public function getStepEndPosts()
    {
        return $this->db()->fetchOne('SELECT MAX(post_id) FROM xf_post') ?: 0;
    }

    /**
     * @param StepState $state
     * @param array $stepConfig
     * @param $maxTime
     * @return $this|StepState
     * @throws \XF\PrintableException
     */
    public function stepPosts(StepState $state, array $stepConfig, $maxTime)
    {
        $limit = 1000;

        $posts = $this->db()->fetchAll(
            "
            SELECT *
            FROM xf_post
            WHERE post_id > ? AND post_id <= ?
            ORDER BY post_id
            LIMIT {$limit}
        ",
            [
                $state->startAfter,
                $state->end
            ]
        );

        foreach ($posts as $post) {
            /** @var \ThemeHouse\PostComments\XF\Entity\Post $postEntity */
            $postEntity = \XF::finder('XF:Post')
                ->where('post_id', '=', $post['post_id'])
                ->fetchOne();

            $nestingId = $post['m_path'];

            //  Exclude Top Level Posts
            if (strlen($nestingId) > 2) {
                $rootPostId = substr($nestingId, 0, 2);
                $parentPostId = substr($nestingId, 0, -2);

                if (!empty($parentPostId)) {
                    $parentPost = $this->db()->fetchRow('
                        SELECT *
                        FROM xf_post
                        WHERE thread_id = ?
                          AND m_path = ?
                    ', [
                        $post['thread_id'],
                        $parentPostId
                    ]);
                } else {
                    $parentPost = ['post_id' => 0];
                }

                if (!empty($rootPostId)) {
                    $rootPost = $this->db()->fetchRow('
                        SELECT *
                        FROM xf_post
                        WHERE thread_id = ?
                          AND m_path = ?
                    ', [
                        $post['thread_id'],
                        $rootPostId
                    ]);
                } else {
                    $rootPost = [
                        'post_id' => 0,
                        'position' => $post['position']
                    ];
                }
            } else {
                $parentPost = [
                    'post_id' => 0
                ];
                $rootPost = [
                    'post_id' => 0,
                    'position' => $post['position']
                ];
            }


            $postEntity->thpostcomments_root_post_id = empty($rootPost) ? 0 : $rootPost['post_id'];
            $postEntity->thpostcomments_parent_post_id = empty($parentPost) ? 0 : $parentPost['post_id'];
            $postEntity->position = $rootPost['position'];
            $postEntity->save();

            $state->imported++;
            $state->startAfter = $post['post_id'];
        }

        if ($state->startAfter === $state->end) {
            return $state->complete();
        }

        return $state;
    }

    /**
     * @param array $stepsRun
     * @return array
     */
    public function getFinalizeJobs(array $stepsRun)
    {
        return [
            'ThemeHouse\PostComments:RebuildNesting',
        ];
    }

    /**
     * @return array
     */
    protected function getBaseConfigDefault()
    {
        return [];
    }

    /**
     * @return array
     */
    protected function getStepConfigDefault()
    {
        return [];
    }

    /**
     * @return bool
     */
    protected function doInitializeSource()
    {
        return true;
    }
}
