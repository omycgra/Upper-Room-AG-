<?php
require_once __DIR__ . '/BaseController.php';
class EquipmentController extends BaseController {
    public function index() {
        if (Session::get('user_role') === 'dept_head') {
            Session::flash('error', 'Unauthorized access.');
            $base = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'])), '/');
            header("Location: $base/dashboard");
            exit;
        }
        $this->renderPlaceholder('Equipment Inventory');
    }
}
