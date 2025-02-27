{strip}
{* Copia di SummaryViewWidgets.tpl di Quotes, con sostituzione in CarQuotes *}
{foreach item=DETAIL_VIEW_WIDGET from=$DETAILVIEW_LINKS['DETAILVIEWWIDGET']}
    {if ($DETAIL_VIEW_WIDGET->getLabel() eq 'Documents')}
        {assign var=DOCUMENT_WIDGET_MODEL value=$DETAIL_VIEW_WIDGET}
    {elseif ($DETAIL_VIEW_WIDGET->getLabel() eq 'ModComments')}
        {assign var=COMMENTS_WIDGET_MODEL value=$DETAIL_VIEW_WIDGET}
    {/if}
{/foreach}

<div class="left-block col-lg-5">
    <div class="summaryView">
        <div class="summaryViewHeader">
            <h4>{vtranslate('LBL_KEY_FIELDS', $MODULE_NAME)}</h4>
        </div>
        <div class="summaryViewFields">
            {$MODULE_SUMMARY}
        </div>
    </div>

    {* Documenti *}
    {if $DOCUMENT_WIDGET_MODEL}
      ...
    {/if}
</div>

<div class="middle-block col-lg-7">
    {* Attività *}
    <div id="relatedActivities">
        {$RELATED_ACTIVITIES}
    </div>

    {* Commenti *}
    {if $COMMENTS_WIDGET_MODEL}
      ...
    {/if}
</div>
{/strip}
