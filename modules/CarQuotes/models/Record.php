<?php
class CarQuotes_Record_Model extends Inventory_Record_Model {

    public function getCreateInvoiceUrl() {
        $invoiceModuleModel = Vtiger_Module_Model::getInstance('Invoice');
        return "index.php?module=".$invoiceModuleModel->getName().
               "&view=".$invoiceModuleModel->getEditViewName().
               "&carquote_id=".$this->getId();
    }

    public function getCreateSalesOrderUrl() {
        $salesOrderModuleModel = Vtiger_Module_Model::getInstance('SalesOrder');
        return "index.php?module=".$salesOrderModuleModel->getName().
               "&view=".$salesOrderModuleModel->getEditViewName().
               "&carquote_id=".$this->getId();
    }

    public function getCreatePurchaseOrderUrl() {
        $purchaseOrderModuleModel = Vtiger_Module_Model::getInstance('PurchaseOrder');
        return "index.php?module=".$purchaseOrderModuleModel->getName().
               "&view=".$purchaseOrderModuleModel->getEditViewName().
               "&carquote_id=".$this->getId();
    }

    public function getPDF() {
        $recordId = $this->getId();
        $moduleName = $this->getModuleName();

        $controller = new Vtiger_CarQuotePDFController($moduleName);
        $controller->loadRecord($recordId);

        $fileName = $moduleName.'_'.getModuleSequenceNumber($moduleName, $recordId);
        $controller->Output($fileName.'.pdf','D');
    }
}
