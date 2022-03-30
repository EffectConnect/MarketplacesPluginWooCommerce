<?php


namespace EffectConnect\Marketplaces\DB;


use WP_Query;
use wpdb;

class OrderRepository
{
    private static $instance;

    /**
     * @var wpdb
     */
    private $wpdb;

    /**
     * Where we insert extra info about an order.
     * @var string
     */
    private $postMetaTable;

    /**
     * Used to get MyParcel generated tracking codes for orders.
     * @var
     */
    private $wpCommentsTable;

    private $trackingCodeNames = [
      'Track & trace code',
        'tracking code'
    ];


    /**
     * Get singleton instance of ProductOptionsRepository.
     * @return OrderRepository
     */
    static function getInstance(): OrderRepository
    {
        if (!self::$instance) {
            self::$instance = new OrderRepository();
        }

        return self::$instance;
    }

    private function __construct() {
        global $wpdb;
        $this->wpdb = $wpdb;
        $this->postMetaTable = 'wp_postmeta';
        $this->wpCommentsTable = 'wp_comments';
    }

    /**
     * Checks the WP_PostMeta for existing PostMeta with the current ecOrderId. (Query borrowed from the old plugin for now).
     * @param $ecOrderId
     * @return bool
     */
    public function checkIfOrderExists($ecOrderId): bool
    {

        if (isset($_GET['force'])) {
            return false;
        }
        $existing = new WP_Query(
            array(
                'post_type' => 'shop_order',
                'post_status' => 'any',
                'meta_query' => array(
                    array(
                        'key' => 'effectconnect_order_number',
                        'value' => $ecOrderId
                    )
                )
            )
        );
        while ($existing->have_posts()) {
            $existing->the_post();
            $this->order = wc_get_order(get_the_ID());

            return $this->order !== false;
        }

        return false;
    }

    /**
     * @param $ecId
     * @return array|null
     */
    public function getPostIdForEcId($ecId): ?array
    {
        return $this->wpdb->get_results(
            $this->wpdb->prepare("SELECT post_id FROM $this->postMetaTable WHERE meta_value = %s",
                $ecId
            ));
    }

    /**
     * @param $orderId
     * @return false|mixed|string
     */
    public function getOrderTrackingCode($orderId) {
        $comments = $this->wpdb->get_results(
            $this->wpdb->prepare("SELECT comment_content FROM $this->wpCommentsTable WHERE comment_post_ID = %d",
                $orderId
            ));

        foreach($comments as $comment) {
            foreach($this->trackingCodeNames as $trackingCodePrefix) {
                if (strstr($comment->comment_content, $trackingCodePrefix)) {
                    $code = explode(':', $comment->comment_content)[1]; // Gets the value after ':'.
                    if (strlen($code) > 0) return $code;
                }
            }
        }

        return false;
    }


}