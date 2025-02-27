<?php
/*********************************************************************************
 * CarQuotes CRMEntity - Clonato da Quotes senza Item Details,
 * con tabelle vtiger_carquotes, vtiger_carquotesbillads, vtiger_carquotesshipads, vtiger_carquotescf
 *********************************************************************************/

class CarQuotes extends CRMEntity {

    var $log;
    var $db;

    // Nome tabella principale e indice
    var $table_name  = "vtiger_carquotes";
    var $table_index = "carquoteid";

    // Elenco tabelle CarQuotes (senza vtiger_inventoryproductrel)
    var $tab_name = array(
        'vtiger_crmentity',
        'vtiger_carquotes',
        'vtiger_carquotesbillads',
        'vtiger_carquotesshipads',
        'vtiger_carquotescf'
    );
    var $tab_name_index = array(
        'vtiger_crmentity'         => 'crmid',
        'vtiger_carquotes'         => 'carquoteid',
        'vtiger_carquotesbillads'  => 'carquotebilladdressid',
        'vtiger_carquotesshipads'  => 'carquoteshipaddressid',
        'vtiger_carquotescf'       => 'carquoteid'
    );

    // Tabella per i campi custom
    var $customFieldTable = array('vtiger_carquotescf', 'carquoteid');
    var $entity_table     = "vtiger_crmentity";
    var $billadr_table    = "vtiger_carquotesbillads";

    var $object_name  = "CarQuote";
    var $new_schema   = true;

    // Per getColumnFields('CarQuotes')
    var $column_fields = array();

    // Campi usati per sort
    var $sortby_fields = array('subject','crmid','smownerid','accountname','lastname');

    // Recupera campi da form post
    var $additional_column_fields = array(
        'assigned_user_name','smownerid','opportunity_id','case_id','contact_id','task_id',
        'note_id','meeting_id','call_id','email_id','parent_name','member_id'
    );

    // Definizione campi visibili in ListView
    var $list_fields = array(
        'CarQuote No'   => array('carquotes'=>'carquote_no'),
        'Subject'       => array('carquotes'=>'subject'),
        'Quote Stage'   => array('carquotes'=>'quotestage'),
        'Potential Name'=> array('carquotes'=>'potentialid'),
        'Account Name'  => array('carquotes'=>'accountid'),
        'Total'         => array('carquotes'=>'total'),
        'Assigned To'   => array('crmentity'=>'smownerid')
    );
    var $list_fields_name = array(
        'CarQuote No'   => 'carquote_no',
        'Subject'       => 'subject',
        'Quote Stage'   => 'quotestage',
        'Potential Name'=> 'potential_id',
        'Account Name'  => 'account_id',
        'Total'         => 'hdnGrandTotal',
        'Assigned To'   => 'assigned_user_id'
    );
    var $list_link_field = 'subject';

    // Definizione campi ricercabili
    var $search_fields = array(
        'CarQuote No'   => array('carquotes'=>'carquote_no'),
        'Subject'       => array('carquotes'=>'subject'),
        'Account Name'  => array('carquotes'=>'accountid'),
        'Quote Stage'   => array('carquotes'=>'quotestage')
    );
    var $search_fields_name = array(
        'CarQuote No'   => 'carquote_no',
        'Subject'       => 'subject',
        'Account Name'  => 'account_id',
        'Quote Stage'   => 'quotestage'
    );

    // Campi obbligatori (tolti 'quantity','listprice','productid')
    var $mandatory_fields = array('subject','createdtime','modifiedtime','assigned_user_id');

    // Per la ricerca alfabetica
    var $def_basicsearch_col = 'subject';

    // Disattiva item details
    var $isLineItemUpdate = false;

    /**
     * Costruttore
     */
    function __construct() {
        $this->log = Logger::getLogger('CarQuotes');
        $this->db  = PearDatabase::getInstance();
        // CarQuotes deve essere un modulo registrato (vtiger_tab) con name='CarQuotes'
        $this->column_fields = getColumnFields('CarQuotes');
    }
    // vecchia signature
    function Quotes() {
        self::__construct();
    }

    /**
     * Disabilita la logica item detail
     */
    function save_module() {
        parent::save_module($module);

        // Salvataggio indirizzi di fatturazione
        $this->insertIntoEntityTable('vtiger_carquotesbillads', $module);
    
        // Salvataggio indirizzi di spedizione
        $this->insertIntoEntityTable('vtiger_carquotesshipads', $module);    }

    /**
     * Evita insert su vtiger_inventoryproductrel
     */
    function insertIntoEntityTable($table_name, $module, $fileid = ''){
        if($table_name == 'vtiger_inventoryproductrel'){
            return;
        }
        parent::insertIntoEntityTable($table_name, $module, $fileid);
    }

    /**
     * Esempio: ottenere SalesOrder correlati
     * Devi avere carquoteid in vtiger_salesorder
     */
    function get_salesorder($id) {
        global $log,$singlepane_view;
        $log->debug("Entering get_salesorder($id) method ...");
        require_once('modules/SalesOrder/SalesOrder.php');

        $focus = new SalesOrder();
        $button = '';

        if($singlepane_view == 'true')
            $returnset = '&return_module=CarQuotes&return_action=DetailView&return_id='.$id;
        else
            $returnset = '&return_module=CarQuotes&return_action=CallRelatedList&return_id='.$id;

        $userNameSql = getSqlForNameInDisplayFormat(
            array('first_name'=>'vtiger_users.first_name','last_name'=>'vtiger_users.last_name'),
            'Users'
        );

        // NB: vtiger_salesorder deve avere colonna carquoteid
        // al posto di quoteid
        $query = "SELECT vtiger_crmentity.*, vtiger_salesorder.*,
                         vtiger_carquotes.subject AS carquotename,
                         vtiger_account.accountname,
                         CASE WHEN (vtiger_users.user_name NOT LIKE '') 
                              THEN $userNameSql 
                              ELSE vtiger_groups.groupname END AS user_name
                  FROM vtiger_salesorder
                  INNER JOIN vtiger_crmentity ON vtiger_crmentity.crmid=vtiger_salesorder.salesorderid
                  LEFT JOIN vtiger_carquotes ON vtiger_carquotes.carquoteid=vtiger_salesorder.carquoteid
                  LEFT JOIN vtiger_account ON vtiger_account.accountid=vtiger_salesorder.accountid
                  LEFT JOIN vtiger_groups ON vtiger_groups.groupid=vtiger_crmentity.smownerid
                  LEFT JOIN vtiger_salesordercf ON vtiger_salesordercf.salesorderid = vtiger_salesorder.salesorderid
                  LEFT JOIN vtiger_invoice_recurring_info ON vtiger_invoice_recurring_info.salesorderid = vtiger_salesorder.salesorderid
                  LEFT JOIN vtiger_sobillads ON vtiger_sobillads.sobilladdressid = vtiger_salesorder.salesorderid
                  LEFT JOIN vtiger_soshipads ON vtiger_soshipads.soshipaddressid = vtiger_salesorder.salesorderid
                  LEFT JOIN vtiger_users ON vtiger_users.id=vtiger_crmentity.smownerid
                  WHERE vtiger_crmentity.deleted=0 AND vtiger_salesorder.carquoteid = ".$id;

        $log->debug("Exiting get_salesorder method ...");
        return GetRelatedList('CarQuotes','SalesOrder',$focus,$query,$button,$returnset);
    }

    /**
     * Esempio get_activities (appuntato su "Calendar" / "Task")
     * Se lo vuoi uguale a Quotes, ok. 
     */
    function get_activities($id, $cur_tab_id, $rel_tab_id, $actions=false){
        global $log, $singlepane_view, $currentModule, $current_user;
        $log->debug("Entering get_activities($id) method ...");

        $this_module   = $currentModule;
        $related_module= vtlib_getModuleNameById($rel_tab_id);
        require_once("modules/$related_module/Activity.php");

        $other = new Activity();
        vtlib_setup_modulevars($related_module, $other);

        if($singlepane_view == 'true') {
            $returnset = '&return_module='.$this_module.'&return_action=DetailView&return_id='.$id;
        } else {
            $returnset = '&return_module='.$this_module.'&return_action=CallRelatedList&return_id='.$id;
        }
        $button = '<input type="hidden" name="activity_mode">';

        if($actions) {
            if(is_string($actions)) $actions = explode(',', strtoupper($actions));
            if(in_array('ADD',$actions) && isPermitted($related_module,1,'') == 'yes'){
                if(getFieldVisibilityPermission('Calendar',$current_user->id,'parent_id','readwrite')=='0'){
                    $button .= "<input title='".getTranslatedString('LBL_NEW')." ".
                            getTranslatedString('LBL_TODO',$related_module)."' class='crmbutton small create'
                            onclick='this.form.action.value=\"EditView\";this.form.module.value=\"$related_module\";
                                    this.form.return_module.value=\"$this_module\";
                                    this.form.activity_mode.value=\"Task\";' 
                            type='submit' name='button'
                            value='".getTranslatedString('LBL_ADD_NEW')." ".
                            getTranslatedString('LBL_TODO',$related_module)."'/>&nbsp;";
                }
            }
        }

        $userNameSql = getSqlForNameInDisplayFormat(
            array('first_name'=>'vtiger_users.first_name','last_name'=>'vtiger_users.last_name'),'Users'
        );

        $query = "SELECT 
            CASE WHEN (vtiger_users.user_name NOT LIKE '') THEN $userNameSql 
                 ELSE vtiger_groups.groupname END AS user_name,
            vtiger_contactdetails.contactid, vtiger_contactdetails.lastname, vtiger_contactdetails.firstname,
            vtiger_activity.*, vtiger_seactivityrel.crmid AS parent_id,
            vtiger_crmentity.crmid, vtiger_crmentity.smownerid, vtiger_crmentity.modifiedtime,
            vtiger_recurringevents.recurringtype
            FROM vtiger_activity
            INNER JOIN vtiger_seactivityrel ON vtiger_seactivityrel.activityid=vtiger_activity.activityid
            INNER JOIN vtiger_crmentity ON vtiger_crmentity.crmid=vtiger_activity.activityid
            LEFT JOIN vtiger_cntactivityrel ON vtiger_cntactivityrel.activityid=vtiger_activity.activityid
            LEFT JOIN vtiger_contactdetails ON vtiger_contactdetails.contactid=vtiger_cntactivityrel.contactid
            LEFT JOIN vtiger_users ON vtiger_users.id=vtiger_crmentity.smownerid
            LEFT JOIN vtiger_recurringevents ON vtiger_recurringevents.activityid=vtiger_activity.activityid
            LEFT JOIN vtiger_groups ON vtiger_groups.groupid=vtiger_crmentity.smownerid
            WHERE vtiger_seactivityrel.crmid=".$id." 
              AND vtiger_crmentity.deleted=0
              AND activitytype='Task' 
              AND (vtiger_activity.status IS NOT NULL AND vtiger_activity.status != 'Completed')
              AND (vtiger_activity.status IS NOT NULL AND vtiger_activity.status != 'Deferred')";

        $return_value = GetRelatedList($this_module,$related_module,$other,$query,$button,$returnset);
        if($return_value == null) $return_value = array();
        $return_value['CUSTOM_BUTTON'] = $button;

        $log->debug("Exiting get_activities method ...");
        return $return_value;
    }

    /**
     * Esempio get_history
     * Se non ti interessa, puoi commentare.
     * Notare che chiama getHistory('Quotes',...) => da cambiare in getHistory('CarQuotes',...) 
     * se vuoi differenziare
     */
    function get_history($id){
        global $log;
        $log->debug("Entering get_history($id) method ...");

        $userNameSql = getSqlForNameInDisplayFormat(
            array('first_name'=>'vtiger_users.first_name','last_name'=>'vtiger_users.last_name'),'Users'
        );

        $query = "SELECT vtiger_activity.activityid, vtiger_activity.subject,
                         vtiger_activity.status, vtiger_activity.eventstatus, 
                         vtiger_activity.activitytype, vtiger_activity.date_start,
                         vtiger_activity.due_date, vtiger_activity.time_start,
                         vtiger_activity.time_end, vtiger_contactdetails.contactid,
                         vtiger_contactdetails.firstname, vtiger_contactdetails.lastname, 
                         vtiger_crmentity.modifiedtime, vtiger_crmentity.createdtime, 
                         vtiger_crmentity.description,
                         CASE WHEN (vtiger_users.user_name NOT LIKE '')
                              THEN $userNameSql
                              ELSE vtiger_groups.groupname END AS user_name
                  FROM vtiger_activity
                  INNER JOIN vtiger_seactivityrel ON vtiger_seactivityrel.activityid=vtiger_activity.activityid
                  INNER JOIN vtiger_crmentity ON vtiger_crmentity.crmid=vtiger_activity.activityid
                  LEFT JOIN vtiger_cntactivityrel ON vtiger_cntactivityrel.activityid=vtiger_activity.activityid
                  LEFT JOIN vtiger_contactdetails ON vtiger_contactdetails.contactid=vtiger_cntactivityrel.contactid
                  LEFT JOIN vtiger_groups ON vtiger_groups.groupid=vtiger_crmentity.smownerid
                  LEFT JOIN vtiger_users ON vtiger_users.id=vtiger_crmentity.smownerid
                  WHERE vtiger_activity.activitytype='Task'
                    AND (vtiger_activity.status IN ('Completed','Deferred'))
                    AND vtiger_seactivityrel.crmid=".$id."
                    AND vtiger_crmentity.deleted=0";

        // Se vuoi cambiare "Quotes" in "CarQuotes"
        $log->debug("Exiting get_history method ...");
        return getHistory('CarQuotes',$query,$id);
    }

    /**
     * Se non vuoi la “quote stage history”, commenta del tutto
     * Oppure crea vtiger_carquotestagehistory e adatta la query
     */
    /*
    function get_quotestagehistory($id){
        ...
    }
    */

    /**
     * Rende setRelationTables coerente con “vtiger_carquotes”
     * (se vuoi relazioni con SalesOrder, Documents, etc.)
     * IMPORTANTE: se non hai aggiunto “carquoteid” in vtiger_salesorder,
     * questa parte non funzionerà
     */
    function setRelationTables($secmodule){
        $rel_tables = array(
            "SalesOrder" => array(
                "vtiger_salesorder"=> array("carquoteid","salesorderid"),
                "vtiger_carquotes" => "carquoteid"
            ),
            "Calendar" => array(
                "vtiger_seactivityrel"=>array("crmid","activityid"),
                "vtiger_carquotes"=>"carquoteid"
            ),
            "Documents" => array(
                "vtiger_senotesrel"=>array("crmid","notesid"),
                "vtiger_carquotes"=>"carquoteid"
            ),
            "Accounts" => array(
                "vtiger_carquotes"=>array("carquoteid","accountid")
            ),
            "Contacts" => array(
                "vtiger_carquotes"=>array("carquoteid","contactid")
            ),
            "Potentials" => array(
                "vtiger_carquotes"=>array("carquoteid","potentialid")
            ),
        );
        return $rel_tables[$secmodule];
    }

    /**
     * Esempio di unlinkRelationship
     * Se usi vtiger_carquotes al posto di vtiger_quotes
     */
    function unlinkRelationship($id, $return_module, $return_id){
        global $log;
        if(empty($return_module) || empty($return_id)) return;

        if($return_module == 'Accounts'){
            $this->trash('CarQuotes',$id);
        } elseif($return_module == 'Potentials'){
            $relation_query = 'UPDATE vtiger_carquotes SET potentialid=? WHERE carquoteid=?';
            $this->db->pquery($relation_query,array(null,$id));
        } elseif($return_module == 'Contacts'){
            $relation_query = 'UPDATE vtiger_carquotes SET contactid=? WHERE carquoteid=?';
            $this->db->pquery($relation_query,array(null,$id));
        } elseif($return_module == 'Documents'){
            $sql = 'DELETE FROM vtiger_senotesrel WHERE crmid=? AND notesid=?';
            $this->db->pquery($sql,array($id,$return_id));
        } elseif($return_module == 'Leads'){
            $relation_query = 'UPDATE vtiger_carquotes SET contactid=? WHERE carquoteid=?';
            $this->db->pquery($relation_query,array(null,$id));
        } else {
            parent::unlinkRelationship($id, $return_module, $return_id);
        }
    }

    /**
     * Esempi standard di import/export (se non li usi, puoi lasciare o rimuovere)
     */
    function createRecords($obj){
        return createRecords($obj);
    }
    function importRecord($obj, $inventoryFieldData, $lineItemDetails){
        return importRecord($obj,$inventoryFieldData,$lineItemDetails);
    }
    function getImportStatusCount($obj){
        return getImportStatusCount($obj);
    }
    function undoLastImport($obj,$user){
        return undoLastImport($obj,$user);
    }

    /**
     * Esempio di create_export_query
     * Cambiato in vtiger_carquotes
     */
    function create_export_query($where){
        global $log,$current_user;
        $log->debug("Entering create_export_query($where) method ...");

        include("include/utils/ExportUtils.php");

        // Permessi e campi
        $sql = getPermittedFieldsQuery("CarQuotes","detail_view");
        $fields_list = getFieldsListFromQuery($sql);
        // Se non vuoi inventory field, rimuovi la getInventoryFieldsForExport
        // $fields_list .= getInventoryFieldsForExport($this->table_name);

        $userNameSql = getSqlForNameInDisplayFormat(
            array('first_name'=>'vtiger_users.first_name','last_name'=>'vtiger_users.last_name'), 'Users'
        );

        // Costruiamo la query su vtiger_carquotes
        $query = "SELECT $fields_list 
                  FROM ".$this->entity_table."
                  INNER JOIN vtiger_carquotes ON vtiger_carquotes.carquoteid = vtiger_crmentity.crmid
                  LEFT JOIN vtiger_carquotescf ON vtiger_carquotescf.carquoteid = vtiger_carquotes.carquoteid
                  LEFT JOIN vtiger_carquotesbillads ON vtiger_carquotesbillads.carquotebilladdressid = vtiger_carquotes.carquoteid
                  LEFT JOIN vtiger_carquotesshipads ON vtiger_carquotesshipads.carquoteshipaddressid = vtiger_carquotes.carquoteid
                  LEFT JOIN vtiger_contactdetails ON vtiger_contactdetails.contactid = vtiger_carquotes.contactid
                  LEFT JOIN vtiger_potential ON vtiger_potential.potentialid = vtiger_carquotes.potentialid
                  LEFT JOIN vtiger_account ON vtiger_account.accountid = vtiger_carquotes.accountid
                  LEFT JOIN vtiger_currency_info ON vtiger_currency_info.id = vtiger_carquotes.currency_id
                  LEFT JOIN vtiger_groups ON vtiger_groups.groupid = vtiger_crmentity.smownerid
                  LEFT JOIN vtiger_users ON vtiger_users.id = vtiger_crmentity.smownerid";

        // Access Control
        $query .= $this->getNonAdminAccessControlQuery('CarQuotes',$current_user);
        $where_auto = "vtiger_crmentity.deleted=0";

        if($where != ""){
            $query .= " WHERE ($where) AND ".$where_auto;
        } else {
            $query .= " WHERE ".$where_auto;
        }

        $log->debug("Exiting create_export_query method ...");
        return $query;
    }

    /**
     * Se import di campi mandatory
     */
    function getMandatoryImportableFields(){
        // Se non vuoi item detail, puoi restituire array vuoto
        // oppure usare la funzione standard di Inventory
        return array('subject'); 
        //oppure: return getInventoryImportableMandatoryFeilds($this->moduleName);
    }
}
?>