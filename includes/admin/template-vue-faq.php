<div class="wrap" id="wedocs-app">

    <h1><?php _e( 'FAQ', 'wedocs' ); ?></h1>

    <!-- <pre>@{{ $data | json }}</pre> -->

    <span class="spinner is-active" style="float: none;"></span>

    <ul class="docs not-loaded" v-sortable>
        <li class="single-doc">
            <h3>
	            <?php __( 'FAQ', 'wedocs' ) ?>
            </h3>

            <div class="inside">
                <ul class="sections" v-sortable-faq data-type="category">
                    <li v-for="category in categories" data-id="{{ category.category.id }}">
                        <span class="section-title" v-on:click="toggleCollapse">
                            <a target="_blank" href="{{ categoryEditUrl }}{{category.category.id }}">{{ category.category.title }} <span v-if="category.child.length > 0" class="count">{{ category.child.length }}</span></a>

                            <span class="actions wedocs-row-actions">
                                <span class="wedocs-btn-reorder" title="<?php esc_attr_e( 'Re-order this section', 'wedocs' ); ?>"><span class="dashicons dashicons-menu"></span></span>
                                <a target="_blank" href="{{ viewurl }}{{section.post.id }}" title="<?php esc_attr_e( 'Preview the section', 'wedocs' ); ?>"><span class="dashicons dashicons-external"></span></a>
                                <span class="wedocs-btn-remove" v-on:click="removeCategory(category, categories)" title="<?php esc_attr_e( 'Delete this FAQ category', 'wedocs' ); ?>"><span class="dashicons dashicons-trash"></span></span>
                                <span class="add-article" v-on:click="addFaqArticle(category,$event)" title="<?php esc_attr_e( 'Add a new FAQ article', 'wedocs' ); ?>"><span class="dashicons dashicons-plus-alt"></span></span>
                            </span>
                        </span>

                        <ul class="articles collapsed" v-if="category.child" v-sortable-faq data-type="category_doc">
                            <li class="article" v-for="article in category.child" data-id="{{ article.post.id }}">
                                <a target="_blank" href="{{ editurl }}{{ article.post.id }}">{{ article.post.title }}<span v-if="article.post.status != 'publish'" class="doc-status">{{ article.post.status }}</span></a>

                                <span class="actions wedocs-row-actions">
                                    <span class="wedocs-btn-reorder"><span class="dashicons dashicons-menu"></span></span>
	                                <span class="wedocs-btn-faq"  v-on:click="toggleFaq(article,$event)" title="{{ article.post.is_faq ? '<?php esc_attr_e( 'Remove from FAQ', 'wedocs' ) ?>' : '<?php esc_attr_e( 'Add to FAQ', 'wedocs' ); ?>' }}"><span class="dashicons dashicons-star-{{ article.post.is_faq ? 'filled' : 'empty' }}"></span></span>
                                    <a target="_blank" href="{{ viewurl }}{{article.post.id }}" title="<?php esc_attr_e( 'Preview the article', 'wedocs' ); ?>"><span class="dashicons dashicons-external"></span></a>
                                    <span class="wedocs-btn-remove" v-on:click="removeFaqArticle(article)" title="<?php esc_attr_e( 'Delete this article', 'wedocs' ); ?>"><span class="dashicons dashicons-trash"></span></span>
	                                <span class="wedocs-btn-visibility"  v-on:click="toggleVisibility(article,$event)" title="{{ article.post.status == 'publish' ? '<?php esc_attr_e( 'Hide from site', 'wedocs' ) ?>' : '<?php esc_attr_e( 'Make visible on site', 'wedocs' ); ?>' }}"><span class="dashicons dashicons-{{ article.post.status == 'publish' ? 'hidden' : 'visibility' }}"></span></span>

                                </span>
                            </li>
                        </ul>
                    </li>
                </ul>
            </div>

            <div class="add-section">
                <a class="button button-primary" href="#" v-on:click.prevent="addCategory()"><?php _e( 'Add Category', 'wedocs' ); ?></a>
            </div>
        </li>
    </ul>
</div>
