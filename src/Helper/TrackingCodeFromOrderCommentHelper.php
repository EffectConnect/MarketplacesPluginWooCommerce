<?php

namespace EffectConnect\Marketplaces\Helper;

/**
 * Helper function for extracting tracking code from order comments (adapted from old plugin).
 */
class TrackingCodeFromOrderCommentHelper
{
    /**
     * @return string[]
     */
    public static function getDefaultSearchStrings(): array
    {
        return [
            'tracking code:[code]',
            'SendCloud shipment is: [code]',
            'Track & Trace ([code])',
        ];
    }

    /**
     * @return string
     */
    public static function getDefaultSearchString(): string
    {
        return implode(PHP_EOL, self::getDefaultSearchStrings());
    }

    /**
     * @param int $orderId
     * @param string $trackingCodeSearchString
     * @return string
     */
    public static function getOrderTrackingCode(int $orderId, string $trackingCodeSearchString): string
    {
        $trackingCode = '';

        // We can't use WC_Order->get_customer_order_notes() because we are interested in private notes, so we re-used the WC code in that function below
        remove_filter('comments_clauses', ['WC_Comments', 'exclude_order_comments']);
        $notes = get_comments(
            array(
                'post_id' => $orderId,
                'orderby' => 'comment_ID',
                'order'   => 'DESC',
                'approve' => 'approve',
                'type'    => 'order_note'
            )
        );
        add_filter('comments_clauses', ['WC_Comments', 'exclude_order_comments']);

        // Search each order comment
        foreach ($notes as $note) {
            // Search for multiple search strings (delimited by a newline)
            foreach (explode("\n", $trackingCodeSearchString) as $trackingSearchItem) {
                // Search for '[code]' string which should contain the tracking code
                $regex = '/' . preg_quote(str_replace("\r", "", $trackingSearchItem), '/') . '/';
                $regex = str_replace('\[code\]', '(.*)', $regex);
                if (preg_match($regex, htmlspecialchars_decode($note->comment_content), $matches)) {
                    $trackingCode = end($matches);
                    break 2;
                }
            }
        }

        return $trackingCode;
    }
}
