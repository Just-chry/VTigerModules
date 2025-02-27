{*<!--
/*********************************************************************************
** The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is: vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
*
 ********************************************************************************/
-->*}

{include file='DetailViewFullContents.tpl'|vtemplate_path:$MODULE_NAME RECORD_STRUCTURE=$RECORD_STRUCTURE MODULE_NAME=$MODULE_NAME}
{strip}
    {foreach from=$RECORD_STRUCTURE.blocks item=BLOCK}
        {if $BLOCK.label == 'LBL_CARQUOTE_INFORMATION' || $BLOCK.label == 'LBL_BILLING_AND_SHIPPING_ADDRESS'}
            <div class="detailViewBlock">
                <h4>{vtranslate($BLOCK.label, $MODULE_NAME)}</h4>
                <table class="table table-bordered">
                    {foreach from=$BLOCK.fields item=FIELD}
                        <tr>
                            <td class="fieldLabel">{vtranslate($FIELD.fieldlabel, $MODULE_NAME)}</td>
                            <td class="fieldValue">{$RECORD->get($FIELD.fieldname)}</td>
                        </tr>
                    {/foreach}
                </table>
            </div>
        {/if}
    {/foreach}
{/strip}
