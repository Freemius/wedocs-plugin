/* jshint devel:true */
/* global Vue */
/* global weDocs */
/* global wp */
/* global swal */
/* global ajaxurl */

Vue.directive('sortable', {
    bind: function() {
        var $el = jQuery(this.el);

        $el.sortable({
            handle: '.wedocs-btn-reorder',
            stop: function(event, ui) {
                var ids = [];

                jQuery( ui.item.closest('ul') ).children('li').each(function(index, el) {
                    ids.push( jQuery(el).data('id'));
                });

                wp.ajax.post({
                    action: 'wedocs_sortable_docs',
                    ids: ids,
                    _wpnonce: weDocs.nonce
                });
            },
            cursor: 'move'
        });
    }
});

Vue.directive('sortable-faq', {
    bind: function() {
        var $el = jQuery(this.el),
            args = {
                _wpnonce: weDocs.nonce
            },
            isCategory = ('category' === $el.data('type'));

        args.action = isCategory ?
            'wedocs_sortable_category' :
            'wedocs_sortable_faq';

        $el.sortable({
            handle: '.wedocs-btn-reorder',
            stop: function(event, ui) {
                var ids = [];

                jQuery( ui.item.closest('ul') ).children('li').each(function(index, el) {
                    ids.push( jQuery(el).data('id'));
                });

                args.ids = ids;

                if ( !isCategory ){
                    args.category_id = jQuery( ui.item ).closest('ul').parents( 'li').data('id');
                }

                wp.ajax.post(args);
            },
            cursor: 'move'
        });
    }
});

new Vue({
    el: '#wedocs-app',
    data: {
        editurl: '',
        categoryEditUrl: '',
        viewurl: '',
        docs: [],
        categories: []
    },

    ready: function() {
        var self = this,
            dom = jQuery( self.$el );

        this.editurl = weDocs.editurl;
        this.categoryEditUrl = weDocs.categoryEditUrl;
        this.viewurl = weDocs.viewurl;

        dom.find('ul.docs').removeClass('not-loaded').addClass('loaded');

        var isFaq = (0 < window.location.search.indexOf('page=wedocs-faq')),
            action = isFaq ?
            'wedocs_admin_get_faq' :
            'wedocs_admin_get_docs';

        jQuery.get(ajaxurl, {
            action: action,
            _wpnonce: weDocs.nonce
        }, function(data) {
            dom.find('.spinner').remove();
            dom.find('.no-docs').removeClass('not-loaded');

            if (isFaq){
                self.categories = data.data;
            } else {
                self.docs = data.data;
            }
        });
    },

    methods: {

        onError: function(error) {
            alert(error);
        },

        addDoc: function() {

            var that = this;
            this.docs = this.docs || [];

            swal({
                title: weDocs.enter_doc_title,
                type: "input",
                showCancelButton: true,
                closeOnConfirm: true,
                animation: "slide-from-top",
                inputPlaceholder: weDocs.write_something
            }, function(inputValue){
                if (inputValue === false) {
                    return false;
                }

                wp.ajax.send( {
                    data: {
                        action: 'wedocs_create_doc',
                        title: inputValue,
                        parent: 0,
                        _wpnonce: weDocs.nonce
                    },
                    success: function(res) {
                        that.docs.unshift( res );
                    },
                    error: this.onError
                });

            });
        },

        removeDoc: function(doc, docs) {
            var self = this;

            swal({
                title: "Are you sure?",
                text: "Are you sure to delete the entire documentation? Sections and articles inside this doc will be deleted too!",
                type: "warning",
                showCancelButton: true,
                confirmButtonColor: "#DD6B55",
                confirmButtonText: "Yes, delete it!",
                closeOnConfirm: false
            }, function() {
                self.removePost(doc, docs);
            });
        },

        addSection: function(doc) {
            swal({
                title: weDocs.enter_section_title,
                type: "input",
                showCancelButton: true,
                closeOnConfirm: true,
                animation: "slide-from-top",
                inputPlaceholder: weDocs.write_something
            }, function(inputValue){
                if (inputValue === false) {
                    return false;
                }

                inputValue = inputValue.trim();

                if ( inputValue ) {
                    wp.ajax.send( {
                        data: {
                            action: 'wedocs_create_doc',
                            title: inputValue,
                            parent: doc.post.id,
                            order: doc.child.length,
                            _wpnonce: weDocs.nonce
                        },
                        success: function(res) {
                            doc.child.push( res );
                        },
                        error: this.onError
                    });
                }
            });
        },

        removeSection: function(section, sections) {
            var self = this;

            swal({
                title: "Are you sure?",
                text: "Are you sure to delete the entire section? Articles inside this section will be deleted too!",
                type: "warning",
                showCancelButton: true,
                confirmButtonColor: "#DD6B55",
                confirmButtonText: "Yes, delete it!",
                closeOnConfirm: false
            }, function() {
                self.removePost(section, sections);
            });
        },

        addArticle: function(section, event) {
            var parentEvent = event;

            swal({
                title: weDocs.enter_doc_title,
                type: "input",
                showCancelButton: true,
                closeOnConfirm: true,
                animation: "slide-from-top",
                inputPlaceholder: weDocs.write_something
            }, function(inputValue){
                if (inputValue === false) {
                    return false;
                }

                wp.ajax.send( {
                    data: {
                        action: 'wedocs_create_doc',
                        title: inputValue,
                        parent: section.post.id,
                        status: 'draft',
                        order: section.child.length,
                        _wpnonce: weDocs.nonce
                    },
                    success: function(res) {
                        section.child.push( res );

                        var articles = jQuery( parentEvent.target ).closest('.section-title').next();

                        if ( articles.hasClass('collapsed') ) {
                            articles.removeClass('collapsed');
                        }
                    },
                    error: function(error) {
                        alert( error );
                    }
                });
            });
        },

        removeArticle: function(article, articles) {
            var self = this;

            swal({
                title: "Are you sure?",
                text: "Are you sure to delete the article?",
                type: "warning",
                showCancelButton: true,
                confirmButtonColor: "#DD6B55",
                confirmButtonText: "Yes, delete it!",
                closeOnConfirm: false
            }, function(){
                self.removePost(article, articles);
            });
        },

        removePost: function(item, items, message) {
            message = message || 'This post has been deleted';

            wp.ajax.send( {
                data: {
                    action: 'wedocs_remove_doc',
                    id: item.post.id,
                    _wpnonce: weDocs.nonce
                },
                success: function() {
                    items.$remove(item);
                    swal( 'Deleted!', message, 'success' );
                },
                error: function(error) {
                    alert( error );
                }
            });
        },

        /**
         * Toggle article's FAQ tag.
         *
         * @author Vova Feldman
         *
         * @param {Object} article
         * @param {Event}  event
         */
        toggleFaq: function (article, event) {
            wp.ajax.send( {
                data: {
                    action: 'wedocs_toggle_doc_faq',
                    id: article.post.id,
                    _wpnonce: weDocs.nonce
                },
                success: function(res) {
                    debugger;
                    article.post.is_faq = res.is_faq;
                },
                error: function(error) {
                    alert( error );
                }
            });
        },

        addFaqArticle: function(category, event) {
            var parentEvent = event;
            var that = this;
            this.categories = this.categories || [];

            swal({
                title: weDocs.enter_faq_title,
                type: "input",
                showCancelButton: true,
                closeOnConfirm: true,
                animation: "slide-from-top",
                inputPlaceholder: weDocs.write_something
            }, function(inputValue){
                if (inputValue === false) {
                    return false;
                }

                wp.ajax.send( {
                    data: {
                        action: 'wedocs_create_faq_doc',
                        title: inputValue,
                        category_id: category.category.id,
                        category_order: category.child.length,
                        category_all_order: that.categories[0].child.length,
                        status: 'draft',
                        // Don't associate with any parent doc.
                        // Otherwise, will be added as a section.
                        parent: -1,
                        _wpnonce: weDocs.nonce
                    },
                    success: function(res) {
                        category.child.push( res );

                        if ('-1' != category.category.id){
                            // If added to specified category, push the FAQ also to the "All" questions.
                            that.categories[0].child.push( res );
                        }

                        var articles = jQuery( parentEvent.target ).closest('.section-title').next();

                        if ( articles.hasClass('collapsed') ) {
                            articles.removeClass('collapsed');
                        }
                    },
                    error: function(error) {
                        alert( error );
                    }
                });
            });
        },

        /**
         * Toggle article's FAQ tag.
         *
         * @author Vova Feldman
         *
         * @param {Object} article
         * @param {Event}  event
         */
        toggleVisibility: function (article, event) {
            var that = this;
            this.categories = this.categories || [];

            wp.ajax.send( {
                data: {
                    action: 'wedocs_toggle_doc_visibility',
                    id: article.post.id,
                    _wpnonce: weDocs.nonce
                },
                success: function(res) {
                    article.post.status = res.status;

                    // If we are on the FAQ page, update the post visibility
                    // in all categories.
                    for (var i = 0; i < that.categories.length; i++){
                        for (var j = 0; j < that.categories[i].child.length; j++){
                            if (article.post.id == that.categories[i].child[j].post.id){
                                that.categories[i].child[j].post.status = res.status;
                            }
                        }
                    }
                },
                error: function(error) {
                    alert( error );
                }
            });
        },

        addCategory: function() {
            var that = this;
            this.categories = this.categories || [];

            swal({
                title: weDocs.enter_category_title,
                type: "input",
                showCancelButton: true,
                closeOnConfirm: true,
                animation: "slide-from-top",
                inputPlaceholder: weDocs.write_something
            }, function(inputValue){
                if (inputValue === false) {
                    return false;
                }

                inputValue = inputValue.trim();

                if ( inputValue ) {
                    wp.ajax.send( {
                        data: {
                            action: 'wedocs_create_category',
                            title: inputValue,
                            order: that.categories.length,
                            _wpnonce: weDocs.nonce
                        },
                        success: function(res) {
                            that.categories.push( res );
                        },
                        error: this.onError
                    });
                }
            });
        },

        removeCategory: function(category, categories) {
            swal({
                title: "Are you sure?",
                text: "Heads up - it will NOT delete the actual posts. Are you sure to delete the category?",
                type: "warning",
                showCancelButton: true,
                confirmButtonColor: "#DD6B55",
                confirmButtonText: "Yes, delete it!",
                closeOnConfirm: false
            }, function(){
                wp.ajax.send( {
                    data: {
                        action: 'wedocs_remove_category',
                        id: category.category.id,
                        _wpnonce: weDocs.nonce
                    },
                    success: function() {
                        categories.$remove(category);
                        swal( 'Deleted!', 'Category was successfully deleted.', 'success' );
                    },
                    error: function(error) {
                        alert( error );
                    }
                });
            });
        },

        removeFaqArticle: function(article) {
            var that = this;
            this.categories = this.categories || [];

            swal({
                title: "Are you sure?",
                text: "Are you sure to delete the article?",
                type: "warning",
                showCancelButton: true,
                confirmButtonColor: "#DD6B55",
                confirmButtonText: "Yes, delete it!",
                closeOnConfirm: false
            }, function(){
                wp.ajax.send( {
                    data: {
                        action: 'wedocs_remove_doc',
                        id: article.post.id,
                        _wpnonce: weDocs.nonce
                    },
                    success: function() {
                        // If we are on the FAQ page, remove article from all categories.
                        for (var i = 0; i < that.categories.length; i++) {
                            for (var j = 0; j < that.categories[i].child.length; j++) {
                                if (article.post.id == that.categories[i].child[j].post.id) {
                                    that.categories[i].child.$remove(that.categories[i].child[j]);
                                    // Move on to the next category.
                                    break;
                                }
                            }
                        }

                        swal('Deleted!', 'Article was successfully deleted.', 'success');
                    },
                    error: function(error) {
                        alert( error );
                    }
                });
            });
        },

        toggleCollapse: function(event) {
            jQuery(event.target).siblings('ul.articles').toggleClass('collapsed');
        }
    }
});
