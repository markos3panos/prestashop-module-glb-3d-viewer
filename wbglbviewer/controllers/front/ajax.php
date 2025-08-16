<?php

class AdminWbglbviewerAjaxController extends ModuleAdminController
{
    public function __construct()
    {
        $this->bootstrap = true;
        $this->module = Module::getInstanceByName('wbglbviewer');
        parent::__construct();
    }

    public function initProcess()
    {
        parent::initProcess();

        // Only handle ajax-style calls
        if (Tools::getValue('ajax')) {
            $action = Tools::getValue('action');
            if ($action === 'upload') {
                $this->ajaxProcessUpload();
            } elseif ($action === 'delete') {
                $this->ajaxProcessDelete();
            } else {
                $this->ajaxDie(json_encode(['success' => false, 'error' => 'bad_action']));
            }
        }
    }

  protected function ajaxProcessUpload()
{
    $this->checkToken();

    $idProduct = (int)Tools::getValue('id_product');
    if (!$idProduct) {
        $this->ajaxDie(Tools::jsonEncode(['success' => false, 'error' => 'no_product']));
    }

    if (!isset($_FILES['wbglb_file']) || empty($_FILES['wbglb_file']['tmp_name'])) {
        $this->ajaxDie(Tools::jsonEncode(['success' => false, 'error' => 'no_file']));
    }

    $file = $_FILES['wbglb_file'];
    $ext  = Tools::strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if ($ext !== 'glb') {
        $this->ajaxDie(Tools::jsonEncode(['success' => false, 'error' => 'invalid_ext']));
    }

    $dir = _PS_MODULE_DIR_ . 'wbglbviewer/uploads/';
    if (!is_dir($dir)) { @mkdir($dir, 0755, true); }

    $filename = 'product_'.$idProduct.'_'.time().'.glb';
    $dest     = $dir.$filename;

    if (!move_uploaded_file($file['tmp_name'], $dest)) {
        $this->ajaxDie(Tools::jsonEncode(['success' => false, 'error' => 'move_failed']));
    }

    $relative = 'uploads/'.$filename;
    $this->module->setProductGlb($idProduct, $relative);

    $this->ajaxDie(Tools::jsonEncode(['success' => true, 'glb_path' => $relative]));
}

protected function ajaxProcessDelete()
{
    $this->checkToken();

    $idProduct = (int)Tools::getValue('id_product');
    if (!$idProduct) {
        $this->ajaxDie(Tools::jsonEncode(['success' => false, 'error' => 'no_product']));
    }

    $ok = $this->module->deleteProductGlb($idProduct);
    $this->ajaxDie(Tools::jsonEncode(['success' => (bool)$ok]));
}

}
