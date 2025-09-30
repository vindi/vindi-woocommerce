<?php

namespace VindiPaymentGateways;
use WP_Query;

class PostMeta
{
    /**
     * Check if exists a duplicate $meta on database
     * @param int $post_id
     * @param string $meta
     * @return int $post_id
     */
    public function check_vindi_item_id($post_id, $meta)
    {
        $product = wc_get_product($post_id);
    
        if (!$product) {
            return 0;
        }
    
        $vindi_id = $product->get_meta($meta, true);
    
        if (!$vindi_id) {
            return 0;
        }
    
        $args = [
            'post_type'  => 'product',
            'meta_key'   => $meta,
            'meta_value' => $vindi_id,
            'fields'     => 'ids',
            'posts_per_page' => -1,
        ];
    
        $query = new WP_Query($args);
    
        return count($query->posts);
    }
}
