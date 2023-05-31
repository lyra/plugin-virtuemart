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

jimport('joomla.form.formfield');

use Lyranetwork\Payzen\Sdk\Form\Api as PayzenApi;

/**
 * Renders a label element.
 */
class JFormFieldPayzenLabel extends JFormField
{
    var $type = 'PayzenLabel';

    function getInput()
    {
        $html = $this->value;

        if ($this->fieldname == 'documentation') {
            // Get documentation links.
            $languages = array(
                'fr' => 'Français',
                'en' => 'English',
                'es' => 'Español',
                'pt' => 'Português'
                // Complete when other languages are managed.
            );

            $docs = '';
            foreach (PayzenApi::getOnlineDocUri() as $lang => $docUri) {
                $docs .= '<a style="margin-left: 10px; text-decoration: none; text-transform: uppercase; color: red;" href="' . $docUri . 'virtuemart3/sitemap.html" target="_blank">' . $languages[$lang] . '</a>';
            }

            $html = $docs;
        } elseif($this->fieldname == 'contact_email') {
            $html = PayzenApi::formatSupportEmails('support@payzen.eu');
        }

        return '<label>' . $html . '</label>';
    }
}
