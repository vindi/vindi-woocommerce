<?php

namespace VindiPaymentGateways;

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
        global $wpdb;
        $vindi_id = get_post_meta($post_id, $meta, true);

        if (!$vindi_id) {
            return 0;
        }
        
        $sql = "SELECT 
                  post_id as id 
                FROM {$wpdb->prefix}postmeta
                WHERE 
                  meta_key LIKE '$meta' AND
                  meta_value LIKE $vindi_id
                ";

        $result = $wpdb->get_results($sql);

        if (is_array($result) && !empty($result)) {
            return count($result);
        }

        return 0;
    }
}
