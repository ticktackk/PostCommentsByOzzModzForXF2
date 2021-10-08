<?php

namespace ThemeHouse\PostComments\XF\Repository;

/**
 * Class Thread
 * @package ThemeHouse\PostComments\XF\Repository
 */
class Thread extends XFCP_Thread
{
    /**
     * @param $threadId
     * @throws \XF\Db\Exception
     */
    public function rebuildThreadPostPositions($threadId)
    {
        $db = $this->db();
        $db->query('SET @position := -1');
        $db->query("
            UPDATE xf_post AS post
            SET position = IF(thpostcomments_depth > 0, GREATEST(@position, 0),
                (@position := IF(message_state = 'visible', @position + 1, GREATEST(@position, 0))))
            WHERE thread_id = ?
            ORDER BY thpostcomments_lft
        ", $threadId);
    }
}
