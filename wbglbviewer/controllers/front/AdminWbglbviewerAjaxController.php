<?php
if (!defined('_PS_VERSION_')) {
    exit;
}

class AdminWbglbviewerAjaxController extends ModuleAdminController
{
    public $ajax = true;
    public $display_header = false;
    public $display_footer = false;

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
        if ((int)Tools::getValue('ajax') === 1) {
            $action = Tools::getValue('action');
            switch ($action) {
                case 'upload':
                    $this->ajaxProcessUpload();
                    break;
                case 'delete':
                    $this->ajaxProcessDelete();
                    break;
                default:
                    $this->ajaxDie(Tools::jsonEncode(['success' => false, 'error' => 'bad_action']));
            }
        } else {
            $this->ajaxDie(Tools::jsonEncode(['success' => false, 'error' => 'no_ajax']));
        }
    }

    protected function ajaxProcessUpload()
    {
        // We DO NOT call $this->checkToken() here.
        // The admin dispatcher already validated the ?token=... in the URL produced by getAdminLink().
        // Additionally, we require an authenticated employee:
        if (empty($this->context->employee) || (int)$this->context->employee->id <= 0) {
            $this->ajaxDie(Tools::jsonEncode(['success' => false, 'error' => 'not_authenticated']));
        }

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
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }

        $filename = 'product_' . $idProduct . '_' . time() . '.glb';
        $dest     = $dir . $filename;

        if (!move_uploaded_file($file['tmp_name'], $dest)) {
            $this->ajaxDie(Tools::jsonEncode(['success' => false, 'error' => 'move_failed']));
        }

        $relative = 'uploads/' . $filename;
        $this->module->setProductGlb($idProduct, $relative);

        $this->ajaxDie(Tools::jsonEncode(['success' => true, 'glb_path' => $relative]));
    }

    protected function ajaxProcessDelete()
    {
        if (empty($this->context->employee) || (int)$this->context->employee->id <= 0) {
            $this->ajaxDie(Tools::jsonEncode(['success' => false, 'error' => 'not_authenticated']));
        }

        $idProduct = (int)Tools::getValue('id_product');
        if (!$idProduct) {
            $this->ajaxDie(Tools::jsonEncode(['success' => false, 'error' => 'no_product']));
        }

        $ok = $this->module->deleteProductGlb($idProduct);
        $this->ajaxDie(Tools::jsonEncode(['success' => (bool)$ok]));
    }
}
