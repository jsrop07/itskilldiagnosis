<?php
namespace Admin\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;
use Zend\Session\Container;

class QuestionController extends AbstractActionController
{
	public function indexAction() { print_r("Question Index"); exit; }
	
	public function listAction() {
		$this->layout("layout/list");
		print_r("Question List");
		$vm = new ViewModel();
		return $vm;
	}

	/** ログインを処理
	 * @return string die("fail" or "success")
	 */
	public function loginAction() {
		$post = $this->params()->fromPost();
	
		if (isset($post["id"])) {
			$adminTb = $this->getServiceLocator()->get("AdminTable");

			$result = $adminTb->readById($post["id"]);
			// ログインを失敗した時
			if (empty($result) || $result["password"] != $post["password"]) {
				die("fail");
			}
			
			// ユーザ情報をセッションに保存
			$session = new Container("user");
			foreach ($result as $key => $data) {
				$session->offsetSet($key, $data);
			}
			die("success");
		}
	}
}