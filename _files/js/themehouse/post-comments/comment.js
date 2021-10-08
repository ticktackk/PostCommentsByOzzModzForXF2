!function ($, window, document, _undefined) {
    "use strict";

    XF.CommentClick = XF.Click.newHandler({
        eventNameSpace: 'XFCommentClick',

        options: {
            editorTarget: null,
            commentContainer: '.js-commentContainer',
            href: null
        },

        $editorTarget: null,
        $commentForm: null,

        href: null,
        loading: false,

        init: function () {
            var edTarget = this.options.editorTarget;

            if (!edTarget) {
                console.error('No comment editorTarget specified');
                return;
            }

            this.$editorTarget = XF.findRelativeIf(edTarget, this.$target);
            if (!this.$editorTarget.length) {
                console.error('No comment target found');
                return;
            }

            this.href = this.options.href || this.$target.attr('href');
            if (!this.href) {
                console.error('No comment URL specified.');
            }
        },

        click: function (e) {
            if (!this.$editorTarget || !this.href) {
                return;
            }

            e.preventDefault();

            if (this.$editorTarget.parent().hasClass('is-commenting')) {
                $('form.message-quickReply').remove();
            }

            if (this.loading) {
                return;
            }

            this.loading = true;

            var data = {};
            XF.ajax('GET', this.href, data, XF.proxy(this, 'handleAjax'), {skipDefaultSuccessError: true});
        },

        handleAjax: function (data) {
            var $editorTarget = this.$editorTarget,
                self = this;

            if (data.errors || data.exception) {
                this.loading = false;
                return;
            }

            XF.setupHtmlInsert(data.html, function ($html, container) {
                $html.hide().insertAfter($editorTarget);
                XF.activate($html);
                self.$commentForm = $html;

                $html.on('ajax-submit:response', XF.proxy(self, 'commentSubmit'));
                $html.find('.js-cancelButton').on('click', XF.proxy(self, 'cancelClick'));

                var $hidden = $html.find('input[type=hidden]').first();
                $hidden.after('<input type="hidden" name="_xfInlineComment" value="1" />');

                $editorTarget.parent().addClass('is-commenting');

                $html.xfFadeDown(XF.config.speed.normal, function () {
                    $html.trigger('comment:shown');

                    var $commentContainer = $html.find(self.options.commentContainer);
                    if ($commentContainer.length) {
                        $commentContainer.get(0).scrollIntoView(true);
                    }

                    self.loading = false;
                });

                $html.trigger('comment:show');
            });
        },

        commentSubmit: function (e, data) {
            if (data.errors || data.exception) {
                return;
            }

            e.preventDefault();

            if (data.message) {
                XF.flashMessage(data.message, 3000);
            }

            var $editorTarget = this.$editorTarget,
                self = this;

            XF.setupHtmlInsert(data.html, function ($html, container, onComplete) {
                var target = self.options.editorTarget;
                target = target.replace(/<|\|/g, '').replace(/#[a-zA-Z0-9_-]+\s*/, '');

                var $message = $html.find(target);

                $message.hide();
                $editorTarget.replaceWith($message);
                self.$editorTarget = $message;
                XF.activate($message);

                self.stopCommenting(false, function () {
                    $message.xfFadeDown();

                    self.$commentForm.trigger('quickcomment:commentcomplete', data);
                });
            });
        },

        cancelClick: function (e) {
            this.stopCommenting(true);
        },

        stopCommenting: function (showMessage, onComplete) {
            var $editorTarget = this.$editorTarget,
                $commentForm = this.$commentForm,
                self = this;

            var finish = function () {
                $editorTarget.parent().removeClass('is-commenting');

                if (showMessage) {
                    $editorTarget.xfFadeDown();
                }

                if (onComplete) {
                    onComplete();
                }

                $commentForm.remove();
                self.$commentForm = null;
            };

            if ($commentForm) {
                $commentForm.xfFadeUp(null, finish);
            } else {
                finish();
            }
        }
    });

    XF.Click.register('comment', 'XF.CommentClick');
}(jQuery, window, document);