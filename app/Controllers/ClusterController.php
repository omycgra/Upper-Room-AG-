<?php
require_once __DIR__ . '/BaseController.php';
require_once __DIR__ . '/../Models/Cluster.php';

class ClusterController extends BaseController {
    public function index() {
        $this->ensureClusterSchema();
        if (Session::get('user_role') === 'dept_head') {
            Session::flash('error', 'Unauthorized access.');
            $base = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'])), '/');
            header("Location: $base/dashboard");
            exit;
        }
        $clusterModel = new Cluster();
        View::render('cluster.index', [
            'title' => 'Groups Management',
            'clusters' => $clusterModel->all()
        ]);
    }

    public function store() {
        $this->isAdmin();
        $this->ensureClusterSchema();
        $clusterModel = new Cluster();
        
        $data = [
            'name' => $_POST['name'],
            'location' => $_POST['location'] ?? null,
            'description' => $_POST['description'] ?? null
        ];

        try {
            $clusterModel->create($data);
            Session::flash('success', 'Group created successfully');
        } catch (Exception $e) {
            Session::flash('error', 'Error: ' . $e->getMessage());
        }

        $base = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'])), '/');
        header("Location: $base/cluster");
        exit;
    }

    public function update() {
        $this->isAdmin();
        $this->ensureClusterSchema();
        $id = $_POST['id'] ?? null;
        if (!$id) {
            Session::flash('error', 'ID missing');
            $base = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'])), '/');
            header("Location: $base/cluster");
            exit;
        }

        $clusterModel = new Cluster();
        $data = [
            'name' => $_POST['name'],
            'location' => $_POST['location'] ?? null,
            'description' => $_POST['description'] ?? null
        ];

        try {
            $clusterModel->update($id, $data);
            Session::flash('success', 'Group updated successfully');
        } catch (Exception $e) {
            Session::flash('error', 'Error: ' . $e->getMessage());
        }

        $base = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'])), '/');
        header("Location: $base/cluster");
        exit;
    }

    public function delete() {
        $this->isAdmin();
        $id = $_GET['id'] ?? null;
        if ($id) {
            $clusterModel = new Cluster();
            try {
                $clusterModel->delete($id);
                Session::flash('success', 'Group deleted successfully');
            } catch (Exception $e) {
                Session::flash('error', 'Error: ' . $e->getMessage());
            }
        }
        $base = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'])), '/');
        header("Location: $base/cluster");
        exit;
    }

    public function viewAjax() {
        $this->ensureClusterSchema();
        $id = $_GET['id'] ?? null;
        if (!$id) {
            echo json_encode(['error' => 'No ID provided']);
            exit;
        }

        $clusterModel = new Cluster();
        $cluster = $clusterModel->find($id);
        
        header('Content-Type: application/json');
        echo json_encode($cluster ?: ['error' => 'Group not found']);
        exit;
    }

    private function ensureClusterSchema() {
        $db = Database::getInstance();
        SchemaState::once('clusters_schema_v1', function () use ($db) {
            $columns = [
                'location' => "ALTER TABLE clusters ADD COLUMN location VARCHAR(255) NULL",
                'description' => "ALTER TABLE clusters ADD COLUMN description TEXT NULL",
            ];

            foreach ($columns as $columnName => $sql) {
                if (!$db->columnExists('clusters', $columnName)) {
                    $db->query($sql);
                }
            }
        });
    }
}
