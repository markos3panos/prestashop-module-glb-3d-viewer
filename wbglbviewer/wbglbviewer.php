<?php
if (!defined('_PS_VERSION_')) {
    exit;
}

use PrestaShop\PrestaShop\Core\Product\ProductExtraContent;

class Wbglbviewer extends Module
{
    public function __construct()
    {
        $this->name = 'wbglbviewer';
        $this->tab = 'front_office_features';
        $this->version = '1.0.0';
        $this->author = 'Markos3panos';
        $this->need_instance = 0;
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('GLB 3D Viewer per Product');
        $this->description = $this->l('Attach a .glb file to each product and show an interactive 3D viewer on the product page.');
        $this->ps_versions_compliancy = ['min' => '1.7.0.0', 'max' => _PS_VERSION_];
    }

    public function install()
    {
        return parent::install()
            && $this->installDb()
            && $this->registerHook('displayAdminProductsExtra')
            && $this->registerHook('actionProductSave')
            && $this->registerHook('displayHeader')
            && ($this->registerHook('displayProductExtraContent') || $this->registerHook('displayFooterProduct'));
    }

    public function uninstall()
    {
        return $this->uninstallDb() && parent::uninstall();
    }

    private function installDb()
    {
        $sql = '
            CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'wbglbviewer` (
                `id_product` INT UNSIGNED NOT NULL PRIMARY KEY,
                `glb_path`   VARCHAR(255) DEFAULT NULL,
                `active`     TINYINT(1) NOT NULL DEFAULT 1,
                `updated_at` DATETIME NULL
            ) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8mb4;
        ';
        return Db::getInstance()->execute($sql);
    }

    private function uninstallDb()
    {
        $sql = 'DROP TABLE IF EXISTS `' . _DB_PREFIX_ . 'wbglbviewer`';
        return Db::getInstance()->execute($sql);
    }

    /* ----------------------------- Admin: product tab ----------------------------- */

    public function hookDisplayAdminProductsExtra($params)
    {
        $idProduct = isset($params['id_product'])
            ? (int)$params['id_product']
            : (int)Tools::getValue('id_product');

        if (!$idProduct) {
            return '<div class="panel"><p class="text-warning">'
                . $this->l('Open an existing product to upload a .glb file.')
                . '</p></div>';
        }

        $glb = $this->getProductGlb($idProduct);

        // Use AdminModules endpoint (no Tab/controller required)
        $adminAjaxUrl = $this->context->link->getAdminLink('AdminModules', true)
            . '&configure=' . $this->name;

        $this->context->smarty->assign([
            'module_dir'  => $this->_path,
            'id_product'  => (int)$idProduct,
            'glb_path'    => $glb ?: '',
            'ajax_url'    => $adminAjaxUrl,
            'ajax_token'  => Tools::getAdminTokenLite('AdminModules'),
        ]);

        return $this->display(__FILE__, 'views/templates/admin/product_form.tpl');
    }

    public static function currentAdminActionUrl()
    {
        return $_SERVER['REQUEST_URI'];
    }

    /* ----------------------------- Fallback: non-AJAX save ----------------------------- */

    public function hookActionProductSave($params)
    {
        $idProduct = (int)(
            $params['id_product'] ??
            ($params['product']->id ?? Tools::getValue('id_product'))
        );
        if (!$idProduct) {
            return;
        }

        // Delete checkbox fallback
        $delete = (bool)Tools::getValue('wbglb_delete');
        if ($delete) {
            $this->deleteProductGlb($idProduct);
            return;
        }

        // Upload fallback
        if (isset($_FILES['wbglb_file']) && !empty($_FILES['wbglb_file']['tmp_name'])) {
            $file = $_FILES['wbglb_file'];
            $ext = Tools::strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            if ($ext !== 'glb') {
                $this->displayError($this->l('Only .glb files are allowed.'));
                return;
            }

            if (!is_dir($this->getUploadDir())) {
                @mkdir($this->getUploadDir(), 0755, true);
            }

            $filename = 'product_' . $idProduct . '_' . time() . '.glb';
            $dest = $this->getUploadDir() . $filename;

            if (move_uploaded_file($file['tmp_name'], $dest)) {
                $relative = 'uploads/' . $filename;
                $this->setProductGlb($idProduct, $relative);
            } else {
                $this->displayError($this->l('Upload failed. Check folder permissions.'));
            }
        }
    }

    protected function getUploadDir()
    {
        return _PS_MODULE_DIR_ . $this->name . '/uploads/';
    }

    /* ----------------------------- Front ----------------------------- */

    public function hookDisplayHeader()
    {
        $controller = Tools::getValue('controller');
        if ($controller !== 'product') {
            return;
        }

        $this->context->controller->registerJavascript(
            'wbglbviewer-loader',
            'modules/' . $this->name . '/views/js/wbglbviewer-loader.js',
            ['server' => 'local', 'position' => 'bottom', 'priority' => 150]
        );

        $this->context->controller->registerStylesheet(
            'wbglbviewer-style',
            'modules/' . $this->name . '/views/css/wbglbviewer.css',
            ['server' => 'local', 'priority' => 50]
        );
    }

    protected function resolveIdProductFromParams($params)
    {
        $pid = 0;

        if (isset($params['product'])) {
            $p = $params['product'];

            if (is_object($p)) {
                if (isset($p->id)) {
                    $pid = (int) $p->id;
                } elseif (method_exists($p, 'getId')) {
                    $pid = (int) $p->getId();
                } elseif (isset($p->id_product)) {
                    $pid = (int) $p->id_product;
                }
            } elseif (is_array($p)) {
                if (isset($p['id_product'])) {
                    $pid = (int) $p['id_product'];
                } elseif (isset($p['id'])) {
                    $pid = (int) $p['id'];
                }
            }
        }

        if (!$pid) {
            $pid = (int) Tools::getValue('id_product');
            if (!$pid) {
                $pid = (int) Tools::getValue('id');
            }
        }

        return $pid;
    }

    public function hookDisplayProductExtraContent($params)
    {
        $idProduct = $this->resolveIdProductFromParams($params);
        if (!$idProduct) {
            return [];
        }

        $rel = $this->getProductGlb($idProduct);
        if (!$rel) {
            return [];
        }

        $glbUrl = $this->toModuleUrl($rel);

        $this->context->smarty->assign([
            'glb_url' => $glbUrl,
        ]);

        $html = $this->display(__FILE__, 'views/templates/hook/product_viewer.tpl');

        if (class_exists(ProductExtraContent::class)) {
            $block = new ProductExtraContent();
            $block->setTitle($this->l('3D View'))
                ->setContent($html);
            return [$block];
        }

        return $html;
    }

    public function hookDisplayFooterProduct($params)
    {
        $idProduct = $this->resolveIdProductFromParams($params);
        if (!$idProduct) {
            return;
        }

        $rel = $this->getProductGlb($idProduct);
        if (!$rel) {
            return;
        }

        $this->context->smarty->assign(['glb_url' => $this->toModuleUrl($rel)]);
        return $this->display(__FILE__, 'views/templates/hook/product_viewer.tpl');
    }

    protected function getProductGlb($idProduct)
    {
        $sql = 'SELECT `glb_path` FROM `' . _DB_PREFIX_ . 'wbglbviewer` WHERE `id_product`=' . (int)$idProduct;
        return Db::getInstance()->getValue($sql);
    }

    protected function setProductGlb($idProduct, $relativePath)
    {
        $exists = $this->getProductGlb($idProduct);

        if ($exists === false || $exists === null || $exists === '') {
            $sql = 'INSERT INTO `' . _DB_PREFIX_ . 'wbglbviewer` (`id_product`,`glb_path`,`active`,`updated_at`)
                    VALUES (' . (int)$idProduct . ', "' . pSQL($relativePath) . '", 1, NOW())
                    ON DUPLICATE KEY UPDATE `glb_path`=VALUES(`glb_path`), `active`=1, `updated_at`=NOW()';
        } else {
            $sql = 'UPDATE `' . _DB_PREFIX_ . 'wbglbviewer`
                    SET `glb_path`="' . pSQL($relativePath) . '", `active`=1, `updated_at`=NOW()
                    WHERE `id_product`=' . (int)$idProduct . ' LIMIT 1';
        }
        return Db::getInstance()->execute($sql);
    }

    protected function deleteProductGlb($idProduct)
    {
        $rel = $this->getProductGlb($idProduct);
        if ($rel) {
            $absolute = _PS_MODULE_DIR_ . $this->name . '/' . ltrim($rel, '/');
            if (is_file($absolute)) {
                @unlink($absolute);
            }
        }
        $sql = 'DELETE FROM `' . _DB_PREFIX_ . 'wbglbviewer` WHERE `id_product`=' . (int)$idProduct . ' LIMIT 1';
        return Db::getInstance()->execute($sql);
    }

    protected function toModuleUrl($relative)
    {
        $base = rtrim(Tools::getShopDomainSsl(true, true), '/') . __PS_BASE_URI__;
        return rtrim($base, '/') . '/modules/' . $this->name . '/' . ltrim($relative, '/');
    }

    protected function assertAdminAjax()
    {
        $ctx = Context::getContext();
        if (empty($ctx->employee) || (int)$ctx->employee->id <= 0) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['success' => false, 'error' => 'not_authenticated']);
            exit;
        }
    }

    public function ajaxProcessUpload()
    {
        $this->assertAdminAjax();

        $idProduct = (int)Tools::getValue('id_product');
        if (!$idProduct) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['success' => false, 'error' => 'no_product']);
            exit;
        }

        if (!isset($_FILES['wbglb_file']) || empty($_FILES['wbglb_file']['tmp_name'])) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['success' => false, 'error' => 'no_file']);
            exit;
        }

        $file = $_FILES['wbglb_file'];
        $ext  = Tools::strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if ($ext !== 'glb') {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['success' => false, 'error' => 'invalid_ext']);
            exit;
        }

        $dir = $this->getUploadDir();
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }

        $filename = 'product_' . $idProduct . '_' . time() . '.glb';
        $dest     = $dir . $filename;

        if (!move_uploaded_file($file['tmp_name'], $dest)) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['success' => false, 'error' => 'move_failed']);
            exit;
        }

        $relative = 'uploads/' . $filename;
        $this->setProductGlb($idProduct, $relative);

        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['success' => true, 'glb_path' => $relative]);
        exit;
    }

    public function ajaxProcessDelete()
    {
        $this->assertAdminAjax();

        $idProduct = (int)Tools::getValue('id_product');
        if (!$idProduct) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['success' => false, 'error' => 'no_product']);
            exit;
        }

        $ok = $this->deleteProductGlb($idProduct);

        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['success' => (bool)$ok]);
        exit;
    }
}
