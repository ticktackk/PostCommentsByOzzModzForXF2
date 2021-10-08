<?php

namespace ThemeHouse\PostComments;

use XF\AddOn\AbstractSetup;
use XF\AddOn\StepRunnerInstallTrait;
use XF\AddOn\StepRunnerUninstallTrait;
use XF\AddOn\StepRunnerUpgradeTrait;
use XF\Db\Schema\Alter;

/**
 * Class Setup
 * @package ThemeHouse\PostComments
 */
class Setup extends AbstractSetup
{
    use StepRunnerInstallTrait;
    use StepRunnerUpgradeTrait;
    use StepRunnerUninstallTrait;

    /**
     *
     */
    public function installStep1()
    {
        $schemaManager = $this->schemaManager();

        $schemaManager->alterTable('xf_post', function (Alter $table) {
            $table->addColumn('thpostcomments_parent_post_id', 'int')->setDefault(0);
            $table->addColumn('thpostcomments_root_post_id', 'int')->setDefault(0);
            $table->addColumn('thpostcomments_lft', 'int')->setDefault(0);
            $table->addColumn('thpostcomments_rgt', 'int')->setDefault(0);
            $table->addColumn('thpostcomments_depth', 'smallint', 5)->setDefault(0);
            $table->addKey('thpostcomments_parent_post_id');
            $table->addKey('thpostcomments_root_post_id');
            $table->addKey('thpostcomments_lft');
        });
    }

    /**
     *
     */
    public function installStep2()
    {
        $this->applyGlobalPermission('forum', 'thpostcomments_comment', 'forum', 'like');
    }

    /**
     *
     */
    public function installStep3()
    {
        $schemaManager = $this->schemaManager();
        $schemaManager->alterTable('xf_thread', function (Alter $table) {
            $table->addColumn('thpostcomments_root_reply_count', 'int')->setDefault(0);
        });

        \XF::app()->jobManager()->enqueue('ThemeHouse\PostComments:RebuildCounters');
    }

    /**
     *
     */
    public function upgrade1000231Step1()
    {
        $schemaManager = $this->schemaManager();
        $schemaManager->alterTable('xf_post', function (Alter $table) {
            $table->renameColumn('parent_post_id', 'thpostcomments_parent_post_id');
            $table->renameColumn('root_post_id', 'thpostcomments_root_post_id');
            $table->renameColumn('lft', 'thpostcomments_lft');
            $table->renameColumn('rgt', 'thpostcomments_rgt');
            $table->renameColumn('depth', 'thpostcomments_depth');
        });

        \XF::app()->jobManager()->enqueue('ThemeHouse\PostComments:RebuildCounters');
    }

    /**
     *
     */
    public function upgrade1000231Step2()
    {
        $schemaManager = $this->schemaManager();
        $schemaManager->alterTable('xf_thread', function (Alter $table) {
            $table->addColumn('thpostcomments_root_reply_count', 'int')->setDefault(0);
        });

        \XF::app()->jobManager()->enqueue('ThemeHouse\PostComments:RebuildCounters');
    }

    /**
     *
     */
    public function uninstallStep1()
    {
        $schemaManager = $this->schemaManager();

        $schemaManager->alterTable('xf_post', function (Alter $table) {
            $table->dropColumns([
                'thpostcomments_parent_post_id',
                'thpostcomments_root_post_id',
                'thpostcomments_lft',
                'thpostcomments_rgt',
                'thpostcomments_depth',
            ]);
        });
    }

    /**
     *
     */
    public function uninstallStep2()
    {
        $schemaManager = $this->schemaManager();

        $schemaManager->alterTable('xf_thread', function (Alter $table) {
            $table->dropColumns([
                'thpostcomments_root_reply_count',
            ]);
        });
    }
}
