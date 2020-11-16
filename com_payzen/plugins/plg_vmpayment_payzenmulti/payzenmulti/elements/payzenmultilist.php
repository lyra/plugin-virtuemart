<?php
/**
 * Copyright © Lyra Network.
 * This file is part of PayZen plugin for VirtueMart. See COPYING.md for license details.
 *
 * @author    Lyra Network (https://www.lyra.com/)
 * @copyright Lyra Network
 * @license   http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU General Public License (GPL v2)
*/

// Check to ensure this file is within the rest of the framework.
defined('JPATH_BASE') or die();

/**
 * Renders an item select element (with multiple choice possibility).
 */
class JElementPayzenMultiList extends JElement
{
    /**
     * Element name.
     *
     * @access protected
     * @var string
     */
    var $_name = 'PayzenmultiList';

    function getOptions($node)
    {
        if (! class_exists('PayzenApi')) {
            require_once(rtrim(JPATH_ADMINISTRATOR, DS) . DS . 'components' . DS . 'com_payzen' . DS . 'classes' . DS . 'PayzenApi.php');
        }

        $name = $node->attributes('name');
        if ($name == 'payment_cards') {
            $payzenmultiOptions = $this->_getAvailableMultiCards();
        } elseif ($name == 'language' || $name == 'available_languages') {
            foreach (PayzenApi::getSupportedLanguages() as $code => $lang) {
                $payzenmultiOptions[$code] = 'VMPAYMENT_PAYZENMULTI_' . strtoupper($lang);
            }
        }

        // Construct an array of HTML OPTION statements.
        $options = array();
        foreach ($payzenmultiOptions as $key => $value) {
            $options[] = JHTML::_('select.option', $key, JText::_($value));
        }

        foreach ($node->children() as $option) {
            $val = $option->attributes('value');
            $text = $option->data();
            $options[] = JHtml::_('select.option', $val, JText::_($text));
        }

        return $options;
    }

    function _getAvailableMultiCards()
    {
        $multi_cards = array(
            'AMEX', 'CB', 'DINERS', 'DISCOVER', 'E-CARTEBLEUE', 'JCB', 'MASTERCARD', 'PRV_BDP', 'PRV_BDT',
            'PRV_OPT', 'PRV_SOC', 'VISA', 'VISA_ELECTRON'
        );

        $all_cards = PayzenApi::getSupportedCardTypes();
        $avail_cards = array();

        foreach ($all_cards as $key => $value) {
            if (in_array($key, $multi_cards)) {
                $avail_cards[$key] = $value;
            }
        }

        return $avail_cards;
    }

    function fetchElement($name, $value, &$node, $control_name)
    {
        // Base name of the HTML control.
        $ctrl = $control_name . '[' . $name . ']';

        // Construct an array of the HTML OPTION statements.
        $options = $this->getOptions($node);

        // Construct the various argument calls that are supported.
        $attribs = ' ';
        if ($v = $node->attributes('size')) {
            $attribs .= 'size="' . $v . '"';
        }

        if ($v = $node->attributes('style')) {
            $attribs .= 'style="' . $v . '"';
        }

        if ($v = $node->attributes('class')) {
            $attribs .= 'class="' . $v . '"';
        } else {
            $attribs .= 'class="inputbox"';
        }

        if ($m = $node->attributes('multiple')) {
            $attribs .= ' multiple="multiple"';
            $ctrl .= '[]';
        }

        // Render the HTML select list.
        return JHTML::_('select.genericlist', $options, $ctrl, $attribs, 'value', 'text', $value, $control_name . $name);
    }
}
