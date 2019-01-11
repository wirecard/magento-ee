<?php
/**
 * Shop System Plugins:
 * - Terms of Use can be found under:
 * https://github.com/wirecard/magento-ee/blob/master/_TERMS_OF_USE
 * - License can be found under:
 * https://github.com/wirecard/magento-ee/blob/master/LICENSE
 */

/**
 * Allows to load external javascript files by inserting a script tag.
 * Used to load the live support chat.
 *
 * @since 1.0.0
 */
class WirecardEE_PaymentGateway_Block_Head extends Mage_Adminhtml_Block_Page_Head
{
    /**
     * Classify HTML head item and queue it into "lines" array
     *
     * @param array        &$lines
     * @param string       $itemIf
     * @param string       $itemType
     * @param string|array $itemParams
     * @param string       $itemName
     * @param array        $itemThe
     *
     * @since 1.0.0
     */
    protected function _separateOtherHtmlHeadElements(&$lines, $itemIf, $itemType, $itemParams, $itemName, $itemThe)
    {
        parent::_separateOtherHtmlHeadElements($lines, $itemIf, $itemType, $itemParams, $itemName, $itemThe);

        $params = $itemParams ? $itemParams : '';
        if (is_array($itemParams)) {
            $params = [];
            foreach ($itemParams as $key => $val) {
                $params[] = $key . '="' . $val . '"';
            }
            $params = implode(' ', $params);
        }

        switch ($itemType) {
            case 'external_js':
                $lines[$itemIf]['other'][] = sprintf(
                    '<script type="text/javascript" src="%s" %s></script>',
                    $itemName,
                    $params
                );
                break;
        }
    }
}
