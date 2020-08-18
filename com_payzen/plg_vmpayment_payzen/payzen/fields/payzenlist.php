<?php
/**
 * Copyright © Lyra Network.
 * This file is part of PayZen plugin for VirtueMart. See COPYING.md for license details.
 *
 * @author    Lyra Network (https://www.lyra.com/)
 * @copyright Lyra Network
 * @license   http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU General Public License (GPL v2)
*/

defined('JPATH_BASE') or die();

JFormHelper::loadFieldClass('filelist');

/**
 * Renders an item select element (with multiple choice possibility).
 */
class JFormFieldPayzenList extends JFormFieldList
{
    var $type = 'PayzenList';
    var $identifier = "payzen";

    function getOptions()
    {
        if (! class_exists('PayzenApi')) {
            $plugin_path = JPATH_ROOT . DS . 'plugins' . DS . 'vmpayment';
            if (version_compare(JVERSION, '1.6.0', 'ge')) {
                $plugin_path .= DS . $this->identifier;
            }

            require_once(rtrim(JPATH_ADMINISTRATOR, DS) . DS . 'components' . DS . 'com_payzen' . DS . 'classes' . DS . 'PayzenApi.php');
        }

        if ($this->fieldname == 'payment_cards') {
            $payzen_options = $this->_getAvailableCards();
        } else {
            foreach (PayzenApi::getSupportedLanguages() as $code => $lang) {
                $payzen_options[$code] = 'VMPAYMENT_' . strtoupper($this->identifier) . '_' . strtoupper($lang);
            }
        }

        // Construct an array of HTML OPTION statements.
        $options = array();
        foreach ($payzen_options as $key => $value) {
            $options[] = JHTML::_('select.option', $key, JText::_($value));
        }

        $options = array_merge(parent::getOptions(), $options);
        return $options;
    }

    function _getAvailableCards()
    {
        return PayzenApi::getSupportedCardTypes();
    }
}